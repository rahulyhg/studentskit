<?php
/**
 *@property Subject $Subject
 *@property User $User
 *@property Profile $Profile
 *@property TeacherLesson $TeacherLesson
 *@property UserLesson $UserLesson
 */
class LessonsController extends AppController {
	public $name = 'Lessons';
	public $uses = array('Subject', 'User', 'Profile', 'TeacherLesson', 'UserLesson', 'FileSystem');
    public $components = array('Comments.Comments' => array('userModelClass' => 'User', 'actionNames'=>array('community', 'comments')));
	//public $helpers = array('Form', 'Html', 'Js', 'Time');
	public $helpers = array('Watchitoo', 'Html', 'Layout');

    public function __construct($request = null, $response = null) {
        parent::__construct($request, $response);

        App::import('Vendor', 'Watchitoo'.DS.'Watchitoo');
        $this->Watchitoo = new Watchitoo();
    }

	public function beforeFilter() {
		parent::beforeFilter();
		/*$this->Auth->allow(	'index');
		$this->Auth->deny('submitOrder');*/
	}

    /////////////////

    /**
     * Initializes the view type for comments widget
     *
     * @return string
     * @access public
     */
    public function callback_commentsInitType() {
        return 'tree'; // threaded, tree and flat supported
    }


    public function community($subjectId){
        //viewVars
        $this->Subject->recursive = -1;
        $this->set('subject', $this->Subject->find('first', array('conditions'=>array('subject_id'=>$subjectId))));

    }
    public function comments($id = null) {
        $subject = $this->Subject->find('first', array('conditions'=>array('subject_id'=>$id)));
        $this->layout = 'ajax';
        $this->set(compact('subject', 'id'));
    }

    /////////////////

    /**
     *
     * if $teacherLessonId is provided - check if there is an existing recording on watchitoo
     * @param $subjectId
     */
    /**
     * Give the teacher the option to manage his lesson
     *
     * @param $subjectId
     * @return array|bool
     */
    public function subject($subjectId) {
        //Make sure the lesson belongs to the user
        $this->Subject->recursive = -1;
        $subjectData = $this->Subject->findBySubjectId($subjectId);
        if($subjectData['Subject']['user_id']!=$this->Auth->user('user_id')) {

            if(!empty($this->request->params['requested'])) {
                return false;
            }

            $this->Session->setFlash(__('You cannot manage this subject'));
            $this->redirect('/');
        }

        $meetingSettings = $this->Watchitoo->getSubjectMeetingSettings($subjectId);
        if(!empty($this->request->params['requested'])) {
            return array('meeting_settings'=>$meetingSettings, 'name'=>$subjectData['Subject']['name']);
        }


        //$this->Auth->user('image_source')
        $this->set('meetingSettings', $meetingSettings);
        $this->set('lessonName', $subjectData['Subject']['name']);
        /*$this->set('lessonType', $subjectData['Subject']['lesson_type']);
        $this->set('isTeacher', true);*/

        $this->set('subjectId', $subjectId);
        $this->set('blank', true); //Draw only the flash

        $this->render('common'.DS.'lesson');
    }

    /**
     * Live lesson page
     *
     * If lesson overdue - kick users away
     * in process - enter authorized users only
     * about to start - check if users need to do something in order to enter, if not - show counter. when get to 0 - refresh the page (client).
     */
    public function index($teacherLessonId) {

        $liveRequestStatus = $this->UserLesson->getLiveLessonStatus($teacherLessonId, $this->Auth->user('user_id'));

        if(!$liveRequestStatus) {
            $this->Session->setFlash(__('Invalid request'));
            $this->redirect('/');
        }

        //Check if overdue
        if($liveRequestStatus['overdue']) {
            $this->Session->setFlash(__('The lesson you\'re trying to enter is overdue'));
            $this->redirect(array('controller'=>'Home', 'action'=>'teacherSubject', $liveRequestStatus['subject_id']));

        } else { //if($liveRequestStatus['in_process'] || $liveRequestStatus['about_to_start']) {

            if($liveRequestStatus['in_process']) {
                $enterLesson = false;

                if($liveRequestStatus['is_teacher']) {
                    $enterLesson = true; //Lesson in process + it's the teacher

                } else if($liveRequestStatus['approved']) {
                    $enterLesson = true; //Lesson in process + user is authorized
                }

                if(!$enterLesson) {
                    $this->Session->setFlash(__('You cannot participant in this lesson'));
                    $this->redirect(array('controller'=>'Home', 'action'=>'teacherSubject', $liveRequestStatus['subject_id']));
                }


            } else if( $liveRequestStatus['about_to_start'] ) {
                $enterLesson = false;

                if($liveRequestStatus['approved']) {
                    //Show countdown
                    $enterLesson = true;

                } else if($liveRequestStatus['is_teacher']) {
                    $enterLesson = true;

                } else if($liveRequestStatus['pending_teacher_approval']) {
                    $this->Session->setFlash(__('Please wait for the teacher\'s approval first.'));
                    $this->redirect(array('controller'=>'Home', 'action'=>'teacherLesson', $liveRequestStatus['teacher_lesson_id']));

                } else if($liveRequestStatus['pending_user_approval']) {
                    $this->Session->setFlash(__('Please approve the lesson first'));
                    $this->redirect(array('controller'=>'Student', 'action'=>'lessons', 'tab'=>'invitations', $liveRequestStatus['user_lesson_id']));

                }

                if(!$enterLesson) {
                    $this->Session->setFlash(__('Please order the lesson first'));
                    $this->redirect(array('controller'=>'Home', 'action'=>'teacherLesson', $liveRequestStatus['teacher_lesson_id']));
                }
            }


            $this->_setMeetingData($liveRequestStatus, $enterLesson);

            $this->render('common'.DS.'lesson');
        }
    }



    /**
     * Video lesson page
     *
     * If its a free video - show it
     * If its a paid video
     *      if the user paid for it - show video
     *      else show "payment" button and 10 sec preview
     */

    public function video($subjectId) {

        $canWatchData = $this->UserLesson->getVideoLessonStatus($subjectId, $this->Auth->user('user_id'), true);

        if(!$canWatchData) {
            $this->Session->setFlash(__('Invalid request'));
            $this->redirect('/');
        }

        if(!$canWatchData['approved'] && !$canWatchData['is_teacher']) {
            if($canWatchData['pending_teacher_approval']) {
                $this->Session->setFlash(__('You\'re order is pending for the teacher approval'));

            } else if($canWatchData['pending_user_approval']) {
                $this->Session->setFlash(__('An invitation is waiting for your approval, You must approve it or order the lesson first'));
            } else {
                $this->Session->setFlash(__('You must order the lesson first'));
            }
            $this->redirect(array('controller'=>'Home', 'action'=>'teacherSubject', $subjectId));
        }

        if(empty($canWatchData['datetime']) && empty($canWatchData['end_datetime'])) {
            //First watch - set start/end time
            $this->UserLesson->setVideoStartEndDatetime($canWatchData['user_lesson_id']);
        }

        $this->set('showAds', ((!empty($canWatchData['end_datetime']) &&
            $this->TeacherLesson->toServerTime($canWatchData['end_datetime'])<=$this->TeacherLesson->timeExpression( 'now', false )) ||
            !$canWatchData['payment_needed']) );

        $this->_setMeetingData($canWatchData);


        $this->render('common'.DS.'lesson');
    }

    private function _setMeetingData($data, $enterLesson=true) {
        if($enterLesson) {
            $this->set('meetingSettings', $this->Watchitoo->getMeetingSettings($data['teacher_lesson_id'], $this->Auth->user('user_id')));
        }

        if($data['is_teacher']) {
            $this->set('FS', array('entity_type'=>'subject', 'entity_id'=>$data['subject_id']));
        } else {
            $this->set('FS', array('entity_type'=>'user_lesson', 'entity_id'=>$data['user_lesson_id']));
        }

        $this->set('subjectId', $data['subject_id']);
        //$this->set('datetime', $liveRequestStatus['datetime']);
        $this->set('lessonName', $data['lesson_name']);
    }

    public function invite($idKey=null, $id=null) {
        if($idKey && $id) {
            $this->request->data[$idKey] = $id;
        }

        /*$this->request->data['teacher_lesson_id'] = 37;
        //$this->request->data['subject_id'] = 1;
        $this->request->data['emails'] = 'sivaneshokol@gmail.com';
        $this->request->data['message'] = 'My message';*/
        $this->Subject; //init const

        if (!empty($this->request->data)) {
            if((!isSet($this->request->data['teacher_lesson_id']) && !isSet($this->request->data['subject_id']) ) ||
                !isSet($this->request->data['emails']) || !isSet($this->request->data['message'])) {

                return $this->error(1);
            }
            $this->request->data['emails'] = explode(',', $this->request->data['emails']);

            //Sent with teacher_lesson_id
            if(isSet($this->request->data['teacher_lesson_id']) && !empty($this->request->data['teacher_lesson_id'])) {
                //Find teacher lesson
                $this->TeacherLesson->recursive = -1;
                $tlData = $this->TeacherLesson->find('first', array('conditions'=>array('teacher_lesson_id'=>$this->request->data['teacher_lesson_id'])));
                if(!$tlData) {
                    return $this->error(2);
                }
                $tlData = $tlData['TeacherLesson'];



                //Just in case we won't handle it later on
                unset($this->request->data['subject_id']);

                //If its the teacher, send invitations in the system
                if($tlData['lesson_type']==LESSON_TYPE_LIVE) {
                    //check if lesson is overdue/started
                    if(!$this->TeacherLesson->isFuture1HourDatetime($tlData['datetime'])) {
                        return $this->error(3);
                    }

                    //Email users
                    $this->emailUsers($this->request->data['emails'], $tlData['name'], $this->request->data['message'], 'TeacherLesson', $this->request->data['teacher_lesson_id']);

                    if($this->Auth->user('user_id')==$tlData['teacher_user_id']) {
                        $this->sendLiveLessonsJoinRequestsByTeacher($this->request->data['emails'], $this->request->data['teacher_lesson_id'], $this->request->data['message']);
                    }

                    return $this->success(1);
                } else {
                    $this->request->data['subject_id'] = $tlData['subject_id'];
                }

            }

            if(isSet($this->request->data['subject_id']) && !empty($this->request->data['subject_id'])) {
                $this->Subject->recursive = -1;
                $subjectData = $this->Subject->findBySubjectId($this->request->data['subject_id']);
                if(!$subjectData) {
                    return $this->error(4);
                }
                $subjectData = $subjectData['Subject'];

                $this->emailUsers($this->request->data['emails'], $subjectData['name'], $this->request->data['message'], 'Subject', $this->request->data['subject_id']);

                //OIts a video offer, and it's the teacher
                if($this->Auth->user('user_id')==$subjectData['user_id'] && $subjectData['type']==SUBJECT_TYPE_OFFER && $subjectData['lesson_type']==LESSON_TYPE_VIDEO) {
                    $this->sendVideoLessonsInvitationsByTeacher($this->request->data['emails'], $this->request->data['subject_id'], $this->request->data['message']);
                }

                return $this->success(2);
            }


        }
    }

    private function emailUsers($emails, $name, $message, $type, $id) {
        $message .= "\n\n".'In order to view the invitation, click here:';
        switch($type) {
            case 'TeacherLesson':
                $message .= Router::url(array('controller'=>'Home', 'action'=>'teacherLesson', $id), true); //Live lesson
                break;
            case 'Subject':
                $message .= Router::url(array('controller'=>'Home', 'action'=>'teacherSubject', $id), true); //Video lesson
                break;
        }
    }
    private function sendLiveLessonsJoinRequestsByTeacher($emails, $teacherLessonId, $offerMessage) {
        $this->TeacherLesson->recursive = -1;
        $tlData = $this->TeacherLesson->find('first', array('teacher_lesson_id'=>$teacherLessonId));

        $this->User->recursive = -1;
        $users = $this->User->find('all', array('conditions'=>array('email'=>$emails)));

        $emailAsKeys = array_flip($emails);
        foreach($users AS $user) {
            $user = $user['User'];
            unset($emailAsKeys[$user['email']]); //Remove user from emailing list

            $this->UserLesson->joinRequest($teacherLessonId, $user['user_id'], $tlData['TeacherLesson']['teacher_user_id'], null, array('offer_message'=>$offerMessage)); //Send invitation
        }
        return array_flip($emailAsKeys);
    }
    private function sendVideoLessonsInvitationsByTeacher($emails, $subjectId, $offerMessage) {
        $this->User->recursive = -1;
        $users = $this->User->find('all', array('conditions'=>array('email'=>$emails)));
        $emailAsKeys = array_flip($emails);
        foreach($users AS $user) {
            $user = $user['User'];
            unset($emailAsKeys[$user['email']]); //Remove user from emailing list
            $this->UserLesson->lessonRequest($subjectId, $user['user_id'], $this->UserLesson->toClientTime('now'), true, array('offer_message'=>$offerMessage)); //Send invitation
        }
        return array_flip($emailAsKeys);
    }
}
