<?php
/**
 *@property Subject $Subject
 */
class HomeController extends AppController {
	public $name = 'Home';
	public $uses = array('Subject', 'User', 'Profile', 'TeacherLesson', 'UserLesson');
	public $components = array('Utils.FormPreserver'=>array('directPost'=>true,'actions'=>array('submitOrder')), 'Session', 'RequestHandler', 'Auth'=>array('loginAction'=>array('controller'=>'Accounts','action'=>'login')),
        //'Security',
                               /* 'Watchitoo'*/);
	//public $helpers = array('Form', 'Html', 'Js', 'Time');



	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow(	'index', 'searchSubject', 'searchSubjectLoadMore', 'subjectSearchSuggestions', 'teacherSubject', 'teacherLesson', 'teacher', 'user', 'order',
							'getTeacherRatingByStudentsForSubject', 'getTeacherSubjects', 'getTeacherRatingByStudents', 'getOtherTeachersForSubject', 'getUserLessons', 'cleanSession',
							'getUpcomingOpenLesson', 'getUpcomingOpenLessonForSubject', 'latestBoardPosts', 'getStudentArchiveLessons', 'getStudentRatingByTeachers'
							/*,'test', 'testLocking', 'calcStudentPriceAfterDiscount', 'calcStudentPriceAfterDiscount', 'testGeneratePaymentRecivers',
                            'testUpdateRatingStage'*/, 'testWatchitoo', 'uploadTest');
		$this->Auth->deny('submitOrder');
	}

    public function testRoute() {
        $orderURL = array('controller'=>'Order', 'action'=>'init', 'negotiate', 1, '?'=>array('returnURL'=>urlencode(Router::url(null, true))));
        pr(Router::url($orderURL, true));
        pr($orderURL); die;
    }

    public function testEmail() {
        App::uses('CakeEmail', 'Network/Email');
        //CakeEmail::deliver('eldad87@gmail.com', 'Subject', 'Message', array('from' => 'support@universito.com'));

        $emailObj = new CakeEmail('gmail');
        $emailObj->viewVars(array('title' => 'Lesson about to start', 'header'=>'Hi ', 'body'=>'Body'));
        $result = $emailObj->to('eldad87@gmail.com')
            ->template('default')
            ->domain(Configure::read('public_domain'))
            ->emailFormat('both')
            ->from('support@universito.com')
            ->subject('Teacher notification')
            ->send();
    }

    public function cleanSession() {
        $this->Session->destroy();
    }
    private function out($str) {
        pr($str);
    }

    public function testWatchitoo() {
       App::import('Vendor', 'Watchitoo'.DS.'Watchitoo');
        $wObj = new Watchitoo();
        echo '<pre>';
        var_dump($wObj->getMeetingSettings(63));
        var_dump($wObj->getMeetingSettings(63, 6)); //student
        //var_dump($wObj->getMeetingSettings(63, 4)); //teacher
        echo '</pre>';
        //$saveUser = $wObj->saveUser(null, 'eldad88@gmail.com', 'password', 'eldad', 'yamin', 'eldad yamin');
    }

    public function uploadTest() {
        /*$rowId = 1;
        $modelName = 'Subject';
        if (!empty($this->request->data)) {

            $imageUploaded = false;
            App::import('Vendor', 'Uploader.Uploader');
            $upObj = new Uploader(array('uploadDir'=>'img'.DS.$modelName.DS.$rowId.DS));
            if($res = $upObj->upload('fileName', array('name'=>$rowId, 'overwrite'=>true))) {

                //Resize file to a standard resolution
               if($resizePath = $upObj->resize(array('width' => 200,   'height'=>200, 'aspect'=>true, 'mode'=>$upObj::MODE_HEIGHT, 'append'=>'_resize',   'overwrite'=>true))) {
                    //Use the resized file
                    $upObj->setDestination($rowId.'_resize', true);

                    //Create thumbnails
                    $upObj->crop(array('width' => 60,   'height'=>60,   'append'=>'_60x60',    'overwrite'=>true));
                    $upObj->crop(array('width' => 72,   'height'=>72,   'append'=>'_72x72',    'overwrite'=>true));
                    $upObj->crop(array('width' => 78,   'height'=>78,   'append'=>'_78x78',    'overwrite'=>true));
                    $upObj->crop(array('width' => 149,  'height'=>182,  'append'=>'_149x182',  'overwrite'=>true));
                    $upObj->crop(array('width' => 188,  'height'=>197,  'append'=>'_188x197',  'overwrite'=>true));

                    //Delete the tmp resized image
                    $upObj->delete($resizePath);

                    $imageUploaded = true;
                }
            }

            //TODO:
            if($imageUploaded) {

            }
        }*/

        if (!empty($this->request->data)) {
            App::import('Model', 'Image');
            $imgObj = new Image();
            $imgObj->set($this->request->data);
            $imgObj->save();

        }

        App::import('Model', 'Image');
        $imgObj = new Image();
        $imgObj->delete(20);
    }

    /*public function testUpdateRatingStage() {
        //Init const
        $this->Subject;
        $this->TeacherLesson;



        //Build find conditions
        $conditions = array(
            'end_datetime < NOW()',
            'payment_status'=>array(PAYMENT_STATUS_DONE, PAYMENT_STATUS_NO_NEED),
            'rating_status' =>RATING_STATUS_PENDING,

        );

        $this->TeacherLesson->recursive = -1;
        $conditions = $this->TeacherLesson->getUnlockedRecordsFindConditions($conditions);

        //Check if payment needed
        $this->out('Finding ended lessons...');
        $teacherLessons = $this->TeacherLesson->find('all', array('conditions'=>$conditions, 'limit'=>10));
        $i=1;
        while($teacherLessons) {
            foreach($teacherLessons AS $teacherLesson) {

                $this->out( $i++.'. Ended lesson: '.'('.$teacherLesson['TeacherLesson']['teacher_lesson_id'].') '.$teacherLesson['TeacherLesson']['name']);
                //Lock record
                if(!$this->TeacherLesson->lock($teacherLesson['TeacherLesson']['teacher_lesson_id'], 0)) {
                    $this->out('Cannot lock! continue');
                    continue;
                }


                $this->out('Processing status...');

                //Get all UserLesson with status accepted and payment_status as TeacherLesson.payment_status
                $this->UserLesson->recursive = -1;
                $userLessonsCount = $this->countUserLessonCandidates($teacherLesson['TeacherLesson']['teacher_lesson_id']);
                $this->out($userLessonsCount.' students found');

                $this->out('Updating subject');
                $this->updateTeacherSubject($teacherLesson['TeacherLesson']['subject_id'], $userLessonsCount);

                $this->out('Updating teacher lesson');
                $this->updateTeacherLesson($teacherLesson['TeacherLesson']['teacher_lesson_id'], $teacherLesson['TeacherLesson']['duration_minutes'], $userLessonsCount);

                if($userLessonsCount) {
                    $this->out('Updating user lesson');
                    $this->updateUserLesson($teacherLesson['TeacherLesson']['teacher_lesson_id'], $teacherLesson['TeacherLesson']['lesson_type'], $teacherLesson['TeacherLesson']['duration_minutes']);
                }

                //Release lock
                $this->TeacherLesson->unlock($teacherLesson['TeacherLesson']['teacher_lesson_id']);
            }
            //Find the next payment
            $teacherLessons = $this->TeacherLesson->find('all', array('conditions'=>$conditions, 'limit'=>10));
            $this->out('Finding ended lessons...');
        }
        $this->out('EXIT, no ended lessons');
    }



    //3. Update teacher teacher_total_teaching_minutes, teacher_students_amount, teacher_total_lessons
    private function updateTeacherLesson( $teacherLessonId, $lessonDurationMinutes, $studentsAmount ) {
        $totalLessons = 1;
        if(!$studentsAmount) { //if no students - no need to increase counters
            $totalLessons = 0;
            $lessonDurationMinutes = 0;
            $studentsAmount = 0;
        }

        $this->TeacherLesson->create(false);
        //$this->TeacherLesson->id = $teacherLessonId;
        $this->TeacherLesson->updateAll(array(
            $this->TeacherLesson->User->alias.'.teacher_total_teaching_minutes' =>$this->TeacherLesson->User->alias.'.teacher_total_teaching_minutes+'.$lessonDurationMinutes,
            $this->TeacherLesson->User->alias.'.teacher_students_amount'        =>$this->TeacherLesson->User->alias.'.teacher_students_amount+'.$studentsAmount,
            $this->TeacherLesson->User->alias.'.teacher_total_lessons'          =>$this->TeacherLesson->User->alias.'.teacher_total_lessons+'.$totalLessons,
            $this->TeacherLesson->alias.'.rating_status'                        =>RATING_STATUS_DONE,

            //$this->TeacherLesson->getDataSource()->expression('teacher_total_teaching_minutes'.   ' +'.$lessonDurationMinutes),
            //'teacher_students_amount'       =>$this->TeacherLesson->getDataSource()->expression('teacher_students_amount'.          ' +'.$studentsAmount),
            //'teacher_total_lessons'         =>$this->TeacherLesson->getDataSource()->expression('teacher_total_lessons'.            ' +'.$totalLessons),
            //'rating_status'                 =>RATING_STATUS_DONE
        ),array(
                $this->TeacherLesson->alias.'.teacher_lesson_id'=>$teacherLessonId
        ));

        return $this->TeacherLesson->save();
    }

    //2. Update subject students_amount, total_lessons
    private function updateTeacherSubject( $subjectId, $studentsAmount ) {
        if(!$studentsAmount) { //if no students - no need to increase counters
            return true;
        }

        $this->Subject->create(false);
        $this->Subject->id = $subjectId;
        $this->Subject->recursive = -1;
        $this->Subject->set(array(
            'students_amount'   =>$this->TeacherLesson->getDataSource()->expression('students_amount'.  ' +'. $studentsAmount),
            'total_lessons'     =>$this->TeacherLesson->getDataSource()->expression('total_lessons'.    ' +1'),
        ));
        return $this->Subject->save();
    }

    //4. Update student student_total_lessons, students_total_learning_minutes
    private function updateUserLesson($teacherLessonId, $lessonType, $lessonDurationMinutes) {
        $this->UserLesson->create(false);
        return $this->UserLesson->updateAll(array(
                $this->UserLesson->Student->alias.'.student_total_lessons'          =>$this->UserLesson->Student->alias.'.student_total_lessons+1',
                $this->UserLesson->Student->alias.'.students_total_learning_minutes'=>$this->UserLesson->Student->alias.'.students_total_learning_minutes +'.$lessonDurationMinutes,
                $this->UserLesson->alias.'.stage'                                   =>($lessonType==LESSON_TYPE_LIVE ? USER_LESSON_PENDING_RATING : USER_LESSON_PENDING_STUDENT_RATING) //teacher can't rate student on video lesson
            ), array(
                $this->UserLesson->alias.'.payment_status'    =>array(PAYMENT_STATUS_DONE, PAYMENT_STATUS_NO_NEED),
                $this->UserLesson->alias.'.stage'             =>USER_LESSON_ACCEPTED,
                $this->UserLesson->alias.'.teacher_lesson_id' =>$teacherLessonId)
        );
    }

    private function countUserLessonCandidates($teacherLessonId) {
        $this->UserLesson->recursive = -1;
        return $this->UserLesson->find('count', array('conditions'=>array('payment_status'  =>array(PAYMENT_STATUS_DONE, PAYMENT_STATUS_NO_NEED),
            'stage'             =>USER_LESSON_ACCEPTED,
            'teacher_lesson_id' =>$teacherLessonId),
        ));
    }*/


    /*public function calcStudentPriceAfterDiscount() {
        pr($this->Subject->calcStudentPriceAfterDiscount(100, 20, 20, 30));
    }*/
    /*public function testLocking() {
        $this->Subject;
        $this->TeacherLesson;
        echo



        $returnUrl = Router::url(array('controller'=>'Home'), true);

        $conditions = array(
            $this->TeacherLesson->alias.'.lesson_type'      =>LESSON_TYPE_LIVE,
            $this->TeacherLesson->alias.'.payment_status'   =>PAYMENT_STATUS_PENDING,
            $this->TeacherLesson->alias.'.datetime <'       => $this->TeacherLesson->timeExpression('now +1 hour', false));

        $this->TeacherLesson->recursive = -1;
        $conditions = $this->TeacherLesson->getUnlockedRecordsFindConditions($conditions);
        $paymentNeeded = $this->TeacherLesson->find('first', array('conditions'=>$conditions));

        while($paymentNeeded) {
            //Lock record
            if(!$this->TeacherLesson->lock($paymentNeeded['TeacherLesson']['teacher_lesson_id'])) {
                continue;
            }

            //Pay
            $this->TeacherLesson->pay($paymentNeeded['TeacherLesson']['teacher_lesson_id'], $returnUrl, $returnUrl);

            //Release lock
            $this->TeacherLesson->unlock($paymentNeeded['TeacherLesson']['teacher_lesson_id']);

            //Find the next payment
            $paymentNeeded = $this->TeacherLesson->find('first', array('conditions'=>$conditions));
        }
    }*/

    /*public function updateRatingStage() {
        $this->UserLesson->TeacherLesson; // init consts
        $this->UserLesson->recursive = -1;
        $this->UserLesson->updateAll(array('stage'=>USER_LESSON_PENDING_RATING), array(
            'UserLesson.end_datetime < NOW()',
            'UserLesson.stage'=>USER_LESSON_ACCEPTED,
            'OR'=>array(array('UserLesson.payment_status'=>PAYMENT_STATUS_DONE),
                        array('UserLesson.payment_status'=>PAYMENT_STATUS_NO_NEED))
        ));
    }*/


	public function index() {
        $this->setJSSetting('search_suggestions_url', $this->getCurrentParamsWithDifferentURL(array('controller'=>'Home','action'=>'subjectSearchSuggestions'), array('page', 'limit', 'term')));

		//Get about to start messages
        $this->Subject->setLanguages($this->Session->read('languages_of_records'));
		$newSubjects = $this->Subject->getNewest(false);

        app::import('Model', 'Forum.Topic');
        $topicObj = new Topic();
        $topicObj->setLanguages($this->Session->read('languages_of_records'));
        $latestTopics = $topicObj->getLatest(5);

		$this->set('newSubjects', $newSubjects);
		$this->set('latestTopics', $latestTopics);
		$this->set('latestTopicsCount', 5);
    }

    public function latestBoardPosts($limit, $page) {
        app::import('Model', 'Forum.Topic');
        $topicObj = new Topic();
        $topicObj->setLanguages($this->Session->read('languages_of_records'));
        $latestTopics = $topicObj->getLatest($limit, $page);

        //Those are used for HTML rendering even/odd colors
        $this->set('page', $page);
        $this->set('limit', $page);

        return $this->success(1, array('results'=>$latestTopics));
    }

    /*public function testCalcStudentFullGroupPrice() {
        $currentStudents = 7;
        pr($this->Subject->calcStudentFullGroupPrice(2, 20, 10, $currentStudents));
        pr($this->Subject->calcStudentFullGroupPrice(2, 20, 10, $currentStudents)*$currentStudents);
        die;
    }



    /*public function testForumMessages() {
        app::import('Model', 'Forum.Topic');
        $topicObj = new Topic();
        $topicObj->Post->setLanguages($this->Session->read('languages_of_records'));
        pr($topicObj->Post->getGroupedLatestByUser(4, 2));
        //die;
    }*/

    public function categories() {
        App::import('Model', 'SubjectCategory');
        $scObj = new SubjectCategory();

        //http://www.liveperson.com/site-map/
        $categories = array(
            array(
                'name'=>'Technology and Programming',
                //'locale'=>array('heb'=>array('name'=>'תכנולוגיה ותיכנות')),
                'children'=>array(
                    array(  'name'=>'Networking'//,
                            //'locale'=>array('heb'=>array('name'=>'רשתות')),
                            //'children'=>array(
                            //                array(  'name'=>'TCP',
                            //                        'locale'=>array('heb'=>array('name'=>'טיסיפי')))
                            //)
                    ),
                    array('name'=>'Databases'),
                    array('name'=>'IT'),
                    array('name'=>'Hardware'),
                    array('name'=>'Mobile development'),
                    array('name'=>'OS'),
                    array('name'=>'Programming'),
                    array('name'=>'Security'),
                    array('name'=>'Telecommunication'),
                    array('name'=>'Web development'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Education',
                'children'=>array(
                    array('name'=>'Chemistry'),
                    array('name'=>'Graduate education'),
                    array('name'=>'Homework assistance'),
                    array('name'=>'Mathematics'),
                    array('name'=>'Physics'),
                    array('name'=>'Biology'),
                    array('name'=>'Languages'),
                    array('name'=>'Test prep'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Art and Creativity',
                'children'=>array(
                    array('name'=>'Music'),
                    array('name'=>'Animation'),
                    array('name'=>'Video'),
                    array('name'=>'Graphic design'),
                    array('name'=>'Photography'),
                    array('name'=>'Writing'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Business',
                'children'=>array(
                    array('name'=>'Accounting and Tax'),
                    array('name'=>'Consulting'),
                    array('name'=>'Business Plan'),
                    array('name'=>'Finance'),
                    array('name'=>'Insurance'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Coaching',
                'children'=>array(
                    array('name'=>'Career'),
                    array('name'=>'Dating and Relationship'),
                    array('name'=>'Communication'),
                    array('name'=>'Motivation'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Health',
                'children'=>array(
                    array('name'=>'Diet'),
                    array('name'=>'Alternative'),
                    array('name'=>'Other'),
                )
            ),
            array( 'name'=>'Lifestyle',
                    'children'=>array(
                        array('name'=>'Fashion'),
                        array('name'=>'Automotive'),
                        array('name'=>'Sports and Fitness'),
                        array('name'=>'Pets'),
                        array('name'=>'Home design'),
                        array('name'=>'Travel'),
                    )),
            array(
                'name'=>'Spirituality',
                'children'=>array(
                    array('name'=>'Astrology'),
                    array('name'=>'Dream analysis'),
                    array('name'=>'Fortune telling'),
                    array('name'=>'Graphology'),
                    array('name'=>'Religion'),
                    array('name'=>'Tarot reading'),
                    array('name'=>'Other'),
                )
            ),
            array(
                'name'=>'Other',
                'children'=>array(
                    array('name'=>'Other'),
                )
            )
        );

        $scObj->addBulk($categories);
    }


   /* public function testAddCategory() {
        App::import('Model', 'SubjectCategory');
        $scObj = new SubjectCategory();


       /** Multi lang
        *******************************/
       /*$scObj->create();
        $scObj->set(array('name'=>'Spirituality', 'description'=>'about spirituality'));
        $scObj->save();
        $id = $scObj->id;


        $scObj->create();
        $scObj->locale = 'heb'; // we are going to save the german version
        $scObj->id = $id;
        $scObj->save(array('name'=>'רוחניות', 'description'=>'אודות רוחניות'));

        $scObj->locale = 'he_he';
        pr($scObj->find('all'));*/

        /** Plain test
         ******************************* /
        $scObj->create();
        $scObj->set(array('name'=>'Spirituality', 'description'=>'about spirituality'));
        $scObj->save();
        $id = $scObj->id;

            $scObj->create();
            $scObj->set(array('name'=>'Astrology', 'description'=>'Astrology', 'parent_subject_category_id'=>$id));
            $scObj->save();
            $id2 = $scObj->id;

        $scObj->create();
        $scObj->set(array('name'=>'Chinese Astrology', 'description'=>'Chinese Astrology', 'parent_subject_category_id'=>$id2));
        $scObj->save();

        $scObj->create();
        $scObj->set(array('name'=>'Vedic Astrology', 'description'=>'Vedic Astrology', 'parent_subject_category_id'=>$id2));
        $scObj->save();

    $scObj->create();
    $scObj->set(array('name'=>'Graphology', 'description'=>'Graphology', 'parent_subject_category_id'=>$id));
    $scObj->save();



$scObj->create();
$scObj->set(array('name'=>'Computers', 'description'=>'Computers'));
$scObj->save();
$id = $scObj->id;

    $scObj->create();
    $scObj->set(array('name'=>'Applications', 'description'=>'Applications', 'parent_subject_category_id'=>$id));
    $scObj->save();
    $id2 = $scObj->id;

        $scObj->create();
        $scObj->set(array('name'=>'CAD', 'description'=>'CAD', 'parent_subject_category_id'=>$id2));
        $scObj->save();

        $scObj->create();
        $scObj->set(array('name'=>'SAP', 'description'=>'SAP', 'parent_subject_category_id'=>$id2));
        $scObj->save();

    $scObj->create();
    $scObj->set(array('name'=>'Databases', 'description'=>'Databases', 'parent_subject_category_id'=>$id));
    $scObj->save();
    $id2 = $scObj->id;

        $scObj->create();
        $scObj->set(array('name'=>'MySQL', 'description'=>'MySQL', 'parent_subject_category_id'=>$id2));
        $scObj->save();

        $scObj->create();
        $scObj->set(array('name'=>'NoSQL', 'description'=>'NoSQL', 'parent_subject_category_id'=>$id2));
        $scObj->save();
    }*/

    /*public function test() {
        App::import('Model', 'Notification');
        $notificationObj = new Notification();

        $notificationObj->addNotification(4, array('message_enum'=>'teacher.subject.request.offer.sent', 'params'=>array('teacher_user_id'=>4, 'student_user_id'=>5 , 'name'=>'lesson name', 'datetime'=>'10/2/87')));
        $notificationObj->addNotification(4, array('message_enum'=>'teacher.subject.request.offer.sent', 'params'=>array('teacher_user_id'=>4, 'student_user_id'=>5 , 'name'=>'lesson name', 'datetime'=>'10/2/87')));
        $notificationObj->addNotification(4, array('message_enum'=>'teacher.subject.request.offer.sent', 'params'=>array('teacher_user_id'=>4, 'student_user_id'=>5 , 'name'=>'lesson name', 'datetime'=>'10/2/87')));
    }*/

    public function searchSubjectLoadMore() {
        $query = $this->_searchDefaultQueryParams();

        //Search
        $subjectType = (isSet($this->request->query['type']) ? $this->request->query['type'] : SUBJECT_TYPE_OFFER);
        $subjectsData = $this->Subject->search($query, $subjectType);
        if($subjectsData) {
            return $this->success(1, array('subjects'=>$subjectsData['subjects']));
        }
        return $this->success(1, array('subjects'=>array()));
    }

	public function searchSubject() {
        $this->setJSSetting('search_load_more_url', $this->getCurrentParamsWithDifferentURL(array('controller'=>'Home','action'=>'searchSubjectLoadMore'), array('page', 'limit')));
        $this->setJSSetting('search_suggestions_url', $this->getCurrentParamsWithDifferentURL(array('controller'=>'Home','action'=>'subjectSearchSuggestions'), array('page', 'limit', 'term')));


        $query = $this->_searchDefaultQueryParams();

        //Search
        $subjectType = (isSet($this->request->query['type']) ? $this->request->query['type'] : SUBJECT_TYPE_OFFER);
        $subjectsData = $this->Subject->search($query, $subjectType);

        App::Import('Model', 'SubjectCategory');
        $scObj = new SubjectCategory();

        //Generate sub categories from facet
        if(isSet($subjectsData['facet']['name']) && $subjectsData['facet']['name']=='categories') {
            $categoryIds = array(); //Hold all ids
            $categories = array(); //Hold final results

            //Generate array(subject_category_id, count) for each category
            foreach($subjectsData['facet']['results'] AS $path=>$count) {
                $category = explode(',', $path);
                $categoryId = end($category);
                $categoryIds[] = $categoryId;
                $categories[$categoryId] = array('subject_category_id'=>$categoryId, 'count'=>$count);
            }


            //Add category name
            //$scObj->locale = 'eng';
            $foundCategories = $scObj->find('all', array('conditions'=>array('subject_category_id'=>$categoryIds)));
            /*foreach($foundCategories AS $subjectCategoryId=>$name) {
                $categories[$subjectCategoryId]['name'] = $name;
            }*/

            //Bug fix in CakePHP
            foreach($foundCategories AS $data) {
                $categories[$data['SubjectCategory']['subject_category_id']]['name'] = $data['0']['SubjectCategory__i18n_name'];
            }
            $subjectsData['categories'] = $categories;
        }

        if($subjectsData) {
            //Add breadcrumbs
            $subjectsData['breadcrumbs'] = array();
            if(isSet($this->request->query['category_id'])) {
                $scData = $scObj->findBySubjectCategoryId($this->request->query['category_id']);
                $scName = $scData['0']['SubjectCategory__i18n_name']; //Bug fix in CakePHP
                $scData = $scData['SubjectCategory'];

                if(!is_null($scData['path']) && !empty($scData['path'])) {
                    //$subjectsData['breadcrumbs'] = $scObj->find('list', array('fields'=>array('subject_category_id', 'name'), 'conditions'=>array('subject_category_id'=>explode(',', $scData['path']))));

                    //Bug fix in CakePHP
                    $subjectsData['breadcrumbs'] = array();
                    $breadcrumbs = $scObj->find('all', array('conditions'=>array('subject_category_id'=>explode(',', $scData['path']))));
                    foreach($breadcrumbs AS $data) {
                        $subjectsData['breadcrumbs'][$data['SubjectCategory']['subject_category_id']] = $data['0']['SubjectCategory__i18n_name'];
                    }

                }
                $subjectsData['breadcrumbs'][$this->request->query['category_id']] = $scName;
            }
        }



		if(isSet($this->params['ext'])) {
			$data = array();
			foreach($subjectsData['subjects'] AS &$subj) {
				$data['subjects']['subject'][] = $subj['Subject'];
			}
            if(isSet($subjectsData['facet'])) {
                $data['facet'] = $subjectsData['facet'];
            }
			return $this->success(1, array('results'=>$data));
		} else {
			if (empty($this->request->params['requested'])) {
                $this->request->data = $this->request->query; //For search form
				$this->set('subjectsData', $subjectsData);
			} else {
				return $subjectsData;
			}
		}
	}

    //http://universito.com/Home/subjectSearchSuggestions.json?search_terms=for%20the%20d
    public function subjectSearchSuggestions() {


        $query = $this->_searchDefaultQueryParams();

        $subjectType = (isSet($this->request->query['type']) ? $this->request->query['type'] : SUBJECT_TYPE_OFFER);
        $results = $this->Subject->searchSuggestions($query, $subjectType);




        if($this->RequestHandler->isAjax()) {
            //Without it - a regular response will return - something while autocomplere is waiting for a list of words
            if(isSet($results['collations'])) {
                echo json_encode($results['collations']);
            }
            die;
        } else if (empty($this->request->params['requested'])) {
            return $this->success(1, array('results'=>$results));
        } else {
            return $results;
        }
    }

    private function _searchDefaultQueryParams() {
        if (!empty($this->request->params['requested'])) {
            $this->request->query = $this->params->named;
        }


        $this->Subject; //For loading the const
        $searchTerms = !empty($this->request->query['term'])        ? $this->request->query['term']                                 : '*';

        $categoryId     = (isSet($this->request->query['category_id'])          ? (int) $this->request->query['category_id']	    : 0);
        $limit          = (isSet($this->request->query['limit']) 			    ? (int) $this->request->query['limit']		        : 8);
        $page           = (isSet($this->request->query['page']) 			    ? (int) $this->request->query['page']		        : 1);
        $language       = (isSet($this->request->query['languages_of_records']) ? $this->request->query['languages_of_records'] 	:
                            ($this->Session->read('languagesOfRecords')         ? $this->Session->read('languagesOfRecords')        : null));

        //1_on_1_price handle
        $priceFrom      = (isSet($this->request->query['1_on_1_price_from'])  &&
            $this->request->query['1_on_1_price_from']          ? max(min( (int) $this->request->query['1_on_1_price_from'], 1024), 0)	 : 0);

        $priceTo        = (isSet($this->request->query['1_on_1_price_to'])    &&
            $this->request->query['1_on_1_price_to']            ? max(min( (int) $this->request->query['1_on_1_price_to'], 1024), 0)	 : 1024); //Bug: we can't use wildecard *, therefore, put the max price that int can hold - UL validation for 1_on_1_price



        $avarageRatingFrom      = (isSet($this->request->query['avarage_rating_from'])  &&
            $this->request->query['avarage_rating_from']          ?   max(min( (int) $this->request->query['avarage_rating_from'], 5), 0): 0);

        $avarageRatingTo        = (isSet($this->request->query['avarage_rating_to'])    &&
            $this->request->query['avarage_rating_to']            ? max(min( (int) $this->request->query['avarage_rating_to'], 5), 0)	 : 5);


        $lessonType = array();
        if(isSet($this->request->query['lesson_type_video']) && $this->request->query['lesson_type_video']) {
            $lessonType[]  = LESSON_TYPE_VIDEO;
        }
        if(isSet($this->request->query['lesson_type_live']) && $this->request->query['lesson_type_live']) {
            $lessonType[]  = LESSON_TYPE_LIVE;
        }

        $query = array(
            'search'=>$searchTerms,
            'fq'=>array('is_public'=>SUBJECT_IS_PUBLIC_TRUE),
            'page'=>$page,
            'limit'=>$limit
        );
        if(!is_null($categoryId)) {
            $query['fq']['category_id'] = $categoryId;
        }
        if($language && (is_array($language) || $language!='all')) {
            $query['fq']['language'] = '('.implode(' OR ',$language).')';
        }
        if($lessonType) {
            $query['fq']['lesson_type'] = '('.implode(' OR ',$lessonType).')';
        }


        //From-To-Price
        if( $priceFrom<=$priceTo && ($priceFrom!=0 || $priceTo!=1024)) {
            $query['fq']['1_on_1_price'] = '['.$priceFrom.' TO '.$priceTo.',USD]';
        }

        //From-To-avarage_rating
        if( $avarageRatingFrom<=$avarageRatingTo && ($avarageRatingFrom!=0 || $avarageRatingTo!=0)) {
            $query['fq']['avarage_rating'] = '['.$avarageRatingFrom.' TO '.$avarageRatingTo.']';
        }


        $this->set('subjectSearchLimit', $limit);
        return $query;
    }



	public function teacherSubject($subjectId) {

        $data = $this->loadSubjectCommonInfo($subjectId);
        $subjectData =& $data['subjectData'];
        $this->set('lessonType', $subjectData['lesson_type']);

        $orderURL = array('controller'=>'Order', 'action'=>'init', 'order', $subjectId);
        $settings = array(
            'order_text'        => ($subjectData['lesson_type']==LESSON_TYPE_LIVE ? __('Order a LIVE lesson') : __('Order a VIDEO lesson')),
            'play_link'         => false,
            'order_url'         => $orderURL,
            'order_button_text' => __('Order'),
            'popup'             => array(    'description'   =>__('You\'re latest order for this lesson is still pending for the teacher approval'),
                                             'button'        =>array(array('name'=>__('I want to order again'), 'url'=>$orderURL)))
        );



        if($subjectData['lesson_type']==LESSON_TYPE_LIVE) {
            $upcomingAvailableLessonsLimit = 2;
            $this->set('upcomingAvailableLessonsLimit', $upcomingAvailableLessonsLimit);
            $upcomingAvailableLessons = $this->TeacherLesson->getUpcomingOpenLessons(null, $subjectId, $upcomingAvailableLessonsLimit);
            $this->set('upcomingAvailableLessons', $upcomingAvailableLessons);
            $settings['popup'] = false;

        //Video lesson, show order button if no request already made
        //else show play button
        } else if($subjectData['lesson_type']=='video') {
            $canWatchVideo = $this->UserLesson->getVideoLessonStatus($subjectId, $this->Auth->user('user_id'), false);
            if(!$canWatchVideo) {
                $this->redirect('/');
            }


            if($canWatchVideo['approved'] || $canWatchVideo['is_teacher']) {
                $settings['popup'] = false;

            } else if($canWatchVideo['pending_teacher_approval']) {
                $settings['popup']['description'] = __('You\'re latest order for this lesson is still pending for the teacher approval');


            } else if($canWatchVideo['pending_user_approval']) {
                $settings['popup']['description'] = __('There is a pending invitation for this lesson');

            } else if($canWatchVideo['payment_needed']) {
                $settings['popup'] = false;
            } else  {
                $settings['popup'] = false;
            }
        }

        $this->set('settings', $settings);
        //$this->set('orderURL', array('controller'=>'Order', 'action'=>'init', 'order', $subjectId));
	}

    public function getUpcomingOpenLesson($teacherUserId, $limit=3, $page=1) {
        $upcomingAvailableLessons = $this->TeacherLesson->getUpcomingOpenLessons($teacherUserId, null, $limit, $page);
        return $this->success(1, array('results'=>$upcomingAvailableLessons));
    }
    public function getUpcomingOpenLessonForSubject($subjectId, $limit=3, $page=1) {
        $upcomingAvailableLessons = $this->TeacherLesson->getUpcomingOpenLessons(null, $subjectId, $limit, $page);
        return $this->success(1, array('results'=>$upcomingAvailableLessons));
    }

   /* public function canWatchVideo($subjectId) {
        $canWatchVideo = $this->UserLesson->getVideoLessonStatus($subjectId, $this->Auth->user('user_id'), true);
        if(!$canWatchVideo) {
            return $this->error(1);
        }

        if($canWatchVideo['payment_needed']) {
            return $this->success(1, array('url'=>Router::url(array('controller'=>'Home', 'action'=>'order', $subjectId), true)));
        } else if($canWatchVideo['pending_teacher_approval']) {
            return $this->success(2);
        } else if($canWatchVideo['pending_user_approval']) {
            //TODO: if payment approval is set, then auto approve it and show the video
            return $this->success(3, array('url'=>Router::url(array('controller'=>'Student', 'action'=>'lessons', 'tab'=>'invitations', $canWatchVideo['user_lesson_id']), true)));
        } else if($canWatchVideo['show_video']) {
            return $this->success(4, array('url'=>Router::url(array('controller'=>'Lessons', 'action'=>'video', $subjectId), true)));
        }

        return $this->error(2);
    }*/

    //Only for join to live lessons
    public function teacherLesson($teacherLessonId) {
        $this->TeacherLesson->recursive = -1;
        $teacherLessonData = $this->TeacherLesson->findByTeacherLessonId($teacherLessonId);
        if(!$teacherLessonData) {
            $this->Session->setFlash(__('Invalid lesson'));
            $this->redirect($this->referer(array('controller'=>'Home')));
        } else if ($teacherLessonData['TeacherLesson']['lesson_type']!='live') {
            //redirect to subject page
            $this->redirect($this->referer(array('controller'=>'Home', 'action'=>'teacherSubject', $teacherLessonData['TeacherLesson']['subject_id'])));
        }
        $teacherLessonData = $teacherLessonData['TeacherLesson'];

        $liveRequestStatus = $this->UserLesson->getLiveLessonStatus($teacherLessonId, $this->Auth->user('user_id'));
        if(!$liveRequestStatus){
            $this->Session->setFlash(__('Invalid lesson'));
            $this->redirect($this->referer(array('controller'=>'Home')));
        }

        /*$this->set('showPendingTeacherApproval', false);
        $this->set('showAcceptInvitationButton', false);
        $this->set('showGoToLessonButton', false);
        $this->set('showPayForLessonButton', false);
        $this->set('showJoinForFreeLessonButton', false);*/

        $orderURL = array('controller'=>'Order', 'action'=>'init', 'join', $teacherLessonId);
        $settings = array(  'order_text'=>sprintf(__('Join lesson at %s'), CakeTime::niceShort($teacherLessonData['datetime'])),
                            'play_link'=>false,
                            'order_url'=>$orderURL,
                            'order_button_text'=>__('Join'),
                            'popup'=>array( 'description'=>__('You\'re latest order is still pending for the teacher approval'),
                                            'button'=>array(array('name'=>__('I want to order again'), 'url'=>$orderURL)))
                        );

        if($liveRequestStatus['overdue']) {
            $this->Session->setFlash(__('Lesson is overdue'));
            $this->redirect(array('controller'=>'Home', 'action'=>'teacherSubject', $liveRequestStatus['subject_id']));

        } else if($liveRequestStatus['approved'] || $liveRequestStatus['is_teacher']) {
            //TODO: show "Go to lesson" button
            //$this->set('showGoToLessonButton', true);
            $settings['popup'] = false;

        } else if($liveRequestStatus['pending_teacher_approval']) {
            //TODO: Show message on the page "pending for teacher approval, please come back later"
            //$this->set('showPendingTeacherApproval', true);
            $settings['popup']['description'] = __('You\'re latest order is still pending for the teacher approval');

        } else if($liveRequestStatus['pending_user_approval']) {
            //TODO: Show message on the page "pending for your approval, click <a>here</a> to review it in your panel"
            //$this->set('showAcceptInvitationButton', true);
            $settings['popup']['description'] = __('There is a pending invitation for this lesson');
            //$this->redirect(array('controller'=>'Student', 'action'=>'lessons', 'tab'=>'invitations', $liveRequestStatus['user_lesson_id']));

        } else if($liveRequestStatus['payment_needed']) {
            //$this->set('showOrderLessonButton', true);
            $settings['popup'] = false;

        } else  {
            //$this->set('showOrderFreeLessonButton', true);
            $settings['popup'] = false;
        }

        if($teacherLessonData['lesson_type']==LESSON_TYPE_LIVE) {
            $upcomingAvailableLessonsLimit = 2;
            $this->set('upcomingAvailableLessonsLimit', $upcomingAvailableLessonsLimit);
            $upcomingAvailableLessons = $this->TeacherLesson->getUpcomingOpenLessons(null, $teacherLessonData['subject_id'], $upcomingAvailableLessonsLimit);
            $this->set('upcomingAvailableLessons', $upcomingAvailableLessons);
        }


        $this->set('settings', $settings);
        $this->set('teacherLessonData', $teacherLessonData);
        $this->set('orderURL', array('controller'=>'Order', 'action'=>'init', 'join', $teacherLessonData['teacher_lesson_id']));
        $this->loadSubjectCommonInfo($teacherLessonData['subject_id']);

        $this->render('teacher_subject');
    }

    private function loadSubjectCommonInfo($subjectId, $teacherOtherSubjectsLimit=6) {
        $subjectData = $this->Subject->findBySubjectId( $subjectId );
        if(!$subjectData || $subjectData['Subject']['is_enable']==SUBJECT_IS_ENABLE_FALSE) {

            if (!$this->RequestHandler->isAjax()) {
                $this->Session->setFlash(__('Cannot view this subject'));
                $this->redirect($this->referer());
            }
            return false;
        }
        $subjectData = $subjectData['Subject'];

        //JS view params
        $this->setJSSetting('subject_id', $subjectId);
        $this->setJSSetting('teacher_user_id', $subjectData['user_id']);

        //Get students comments for that subject
        $reviewsLimit = 2;
        $this->set('reviewsLimit', $reviewsLimit);
        $subjectRatingByStudents = $this->Subject->getRatingByStudents( $subjectId, $reviewsLimit );

        //Get teacher other subjects
        $this->Subject->setLanguages($this->Session->read('languages_of_records'));
        $teacherOtherSubjects = $this->Subject->getOffersByTeacher( $subjectData['user_id'], false, null, 1, $teacherOtherSubjectsLimit, null, $subjectId );

        //Get teacher data
        $this->User->recursive = -1;
        $teacherData = $this->User->findByUserId( $subjectData['user_id'] );
        if(!$teacherData) {
            if (!$this->RequestHandler->isAjax()) {
                $this->Session->setFlash(__('Internal error'));
                $this->redirect($this->referer());
            }
            return false;
        }

        /*if(!empty($subjectData['category_id'])) {
            if(!$lor = $this->Session->read('languagesOfRecords')) {
                $lor = array();
            }
            if(!in_array($subjectData['language'],$lor)) {
                $lor[] = $subjectData['language'];
            }
            //TODO QA
            //$query = $this->_searchDefaultQueryParams();;
            $query = array(
                'search'=>$subjectData['name'],
                'fq'=>array('is_public'=>SUBJECT_IS_PUBLIC_TRUE, 'category_id'=>$subjectData['category_id'], 'language'=>$lor),
                'page'=>1,
                'limit'=>6
            );
            $otherTeacherForThisSubject = $this->Subject->search($query, $subjectData['type']);
            if($otherTeacherForThisSubject && !empty($otherTeacherForThisSubject['subjects'])) {
                $this->set('otherTeacherForThisSubject', $otherTeacherForThisSubject['subjects']);
            }
        }*/


        $this->set('subjectData', 				$subjectData);
        $this->set('subjectRatingByStudents', 	$subjectRatingByStudents);
        $this->set('teacherOtherSubjects', 		$teacherOtherSubjects);
        $this->set('teacherOtherSubjectsLimit', $teacherOtherSubjectsLimit);
        $this->set('teacherData', 			    $teacherData['User']);
        $this->set('paymentNeeded',             $subjectData['1_on_1_price']>0);

        $return = array('subjectData'               =>$subjectData,
                        'subjectRatingByStudents'   =>$subjectRatingByStudents,
                        'teacherOtherSubjects'      =>$teacherOtherSubjects,
                        'teacherData'               =>$teacherData['User'],
                        'paymentNeeded'             =>$subjectData['1_on_1_price']>0);

        if(!empty($otherTeacherForThisSubject)) {
            $return['otherTeacherForThisSubject'] = $otherTeacherForThisSubject;
        }
        return $return;
    }

	public function getOtherTeachersForSubject($subjectId, $limit=6, $page=1) {
		$subjectData = $this->Subject->findBySubjectId( $subjectId );
		if(!$subjectData) {
			return $this->error(1);
		}
		if(!$subjectData['Subject']['catalog_id']) {
			return $this->success(1);
		}


        $query = array(
            'search'=>$subjectData['Subject']['name'],
            'fq'=>array('is_public'=>SUBJECT_IS_PUBLIC_TRUE, 'category_id'=>$subjectData['Subject']['category_id'], 'language'=>$subjectData['language']),
            'page'=>1,
            'limit'=>6
        );
        $otherTeacherForThisSubject = $this->Subject->search($query, $subjectData['Subject']['type']);
		//$otherTeacherForThisSubject = $this->Subject->getbyCatalog( $subjectData['Subject']['catalog_id'], $subjectId, $limit, $page );

		return $this->success(1, array('subjects'=>$otherTeacherForThisSubject));
	}
	public function getTeacherRatingByStudentsForSubject($subjectId, $limit=2, $page=1) {
		$subjectRatingByStudents = $this->Subject->getRatingByStudents( $subjectId, $limit, $page );
		return $this->success(1, array('rating'=>$subjectRatingByStudents));
	}
	
	public function teacher($teacherUserId) {
        $this->setJSSetting('teacher_user_id', $teacherUserId);

		//Get teacher data
        //$this->User->recursive = -1;
        $this->User->TeacherAboutVideo->setLanguages($this->Session->read('languages_of_records'));
        $this->User->unbindAll(array('hasMany'=>array('TeacherCertificate', 'TeacherAboutVideo')));
		$teacherData = $this->User->findByUserId( $teacherUserId );
		if(!$teacherData) {
			return false;
		}

		//Get teacher other subjects
        $this->Subject->setLanguages($this->Session->read('languages_of_records'));
        $teacherOtherSubjectsLimit = 6;
		$teacherSubjects = $this->Subject->getOffersByTeacher( $teacherUserId, false, null, 1, $teacherOtherSubjectsLimit );
        $this->set('teacherOtherSubjectsLimit', 	            $teacherOtherSubjectsLimit);


		//Get students comments for that teacher
        $this->UserLesson->setLanguages($this->Session->read('languages_of_records'));

        $reviewsLimit = 2;
        $this->set('reviewsLimit', $reviewsLimit);
		$teacherReviews = $this->UserLesson->getTeacherReviews( $teacherUserId, $reviewsLimit );

		//get forum latest posts
        $latestPosts = $this->getLastUserPosts($teacherUserId, 2, 1);

        //Get upcoming lessons
        $upcomingAvailableLessonsLimit = 2;
        $this->set('upcomingAvailableLessonsLimit', $upcomingAvailableLessonsLimit);
        $upcomingAvailableLessons = $this->TeacherLesson->getUpcomingOpenLessons($teacherUserId, null, $upcomingAvailableLessonsLimit);


		$this->set('teacherData', 	            $teacherData);
		$this->set('teacherSubjects', 	        $teacherSubjects);
		$this->set('teacherReviews', 	        $teacherReviews);
		$this->set('latestPosts', 	            $latestPosts);
		$this->set('upcomingAvailableLessons',  $upcomingAvailableLessons);
	}

    public function getLastUserPosts($userId, $limit, $page) {
        //get forum latest posts
        app::import('Model', 'Forum.Post');
        $postObj = new Post();
        $postObj->setLanguages($this->Session->read('languages_of_records'));

        $results = $postObj->getLatestByUser($userId, $limit, $page);
        if ($this->RequestHandler->isAjax()) {
            return $this->success(1, array('results'=>$results));
        }
        return $results;
    }
	public function getTeacherRatingByStudents($teacherUserId, $limit=2, $page=1) {
        if($this->Auth->user('user_id')!=$teacherUserId) {
            $this->UserLesson->setLanguages($this->Session->read('languages_of_records'));
        }
        $subjectRatingByStudents = $this->UserLesson->getTeacherReviews( $teacherUserId, $limit, $page );
		return $this->success(1, array('rating'=>$subjectRatingByStudents));
	}
	public function getTeacherSubjects($teacherUserId, $limit=6, $page=1, $subjectId=null) {
        $this->Subject->setLanguages($this->Session->read('languages_of_records'));
		$teacherOtherSubjects = $this->Subject->getOffersByTeacher( $teacherUserId, false, null, $page, $limit, null, $subjectId );
		return $this->success(1, array('results'=>$teacherOtherSubjects));
	}
	
	public function user($userId) {
        $this->User->recursive = -1;
        $userData = $this->User->findByUserId( $userId );
        if(!$userData) {
            return false;
        }

        $this->setJSSetting('student_user_id', $userId);

        //get forum latest posts
        $latestPosts = $this->getLastUserPosts($userId, 2, 1);

        //Get teachers comments for that user
        $this->UserLesson->setLanguages($this->Session->read('languages_of_records'));
        $reviewsByTeachersLimit = 2;
        $this->set('reviewsByTeachersLimit', $reviewsByTeachersLimit);
        $studentReviews = $this->UserLesson->getStudentReviews( $userId, $reviewsByTeachersLimit );

        //Get archived lessons
        $this->UserLesson->recursive = -1;
        $this->UserLesson->setLanguages($this->Session->read('languages_of_records'));
        $archiveLessonsLimit = 2;
        $archiveLessons = $this->UserLesson->getArchive($userId, $archiveLessonsLimit, 1);
        $this->set('archiveLessonsLimit', $archiveLessonsLimit);

        $this->set('userData',         $userData['User']);
        $this->set('latestPosts',      $latestPosts);
        $this->set('studentReviews',   $studentReviews);
        $this->Set('archiveLessons',   $archiveLessons);
    }

    public function getStudentArchiveLessons($studentUserId, $limit=2, $page=1) {
        $archiveLessons = $this->UserLesson->getArchive($studentUserId, $limit, $page);
        return $this->success(1, array('lessons'=>$archiveLessons));
    }
    public function getStudentRatingByTeachers($studentUserId, $limit=2, $page=1) {
        $studentReviews = $this->UserLesson->getStudentReviews( $studentUserId, $limit, $page );
        return $this->success(1, array('rating'=>$studentReviews));
    }

	public function subject() {
		//TODO: find related subjects by categories
		//TODO: find teachers by catalog
		//TODO: get board messages
	}

	/*public function	order($subjectId, $year=null, $month=null) {
        //TODO: video - there is no need to show calendar

		//Get subject data, students_amount, raters_amount, avarage_rating
		$subjectData = $this->Subject->findBySubjectId( $subjectId );
		if(!$subjectData || $subjectData['Subject']['is_enable']==SUBJECT_IS_ENABLE_FALSE) {
			$this->Session->setFlash(__('This subject is no longer available'));
			$this->redirect($this->referer());
		}
		$subjectData = $subjectData['Subject'];
		
		if($subjectData['type']!=SUBJECT_TYPE_OFFER) {
			$this->Session->setFlash(__('This lesson cannot be ordered'));
			$this->redirect($this->referer());
		}
		
		//Get teacher data
		$teacherData = $this->User->findByUserId( $subjectData['user_id'] );
		if(!$teacherData) {
			$this->Session->setFlash(__('Internal error'));
			$this->redirect($this->referer());
		}
		
		
		//get booking-auto-approve-settings
		App::import('Model', 'AutoApproveLessonRequest');
		$aalsObj = new AutoApproveLessonRequest();
		$aalr = $aalsObj->getSettings($subjectData['user_id']);
		

        //Only live lesson needs a calender and have group
        $isLiveLesson = false;
        if($subjectData['lesson_type']==LESSON_TYPE_LIVE) {
            $isLiveLesson = true;
            //Get student lessons for a given month
            $allLiveLessons = $this->User->getLiveLessonsByDate( $subjectData['user_id'], false, $year, $month);

            $groupLessons = array();
            foreach($allLiveLessons AS $lesson) {
                if($lesson['type']=='TeacherLesson' && isSet($lesson['max_students']) && $lesson['max_students']>1 &&  $lesson['max_students']>$lesson['num_of_students']) {
                    $groupLessons[] = $lesson;
                }
            }
            $this->set('groupLessons',	 		$groupLessons);
            $this->set('allLiveLessons',	 	$allLiveLessons);
        }
		
		$this->set('isLiveLesson', 		    $isLiveLesson);
		$this->set('subjectData', 			$subjectData);
		$this->set('teacherUserData',		$teacherData['User']);
		$this->set('aalr', 					$aalr);

	}*/
	/*public function getUserLessons($userId, $year, $month=null) {
		$allLessons = $this->User->getLiveLessonsByDate( $userId, false, $year, $month);
		return $this->success(1, array('lessons'=>$allLessons));
	}*/

    /*public function submitOrder($requestType, $subjectId) {
		App::import('Model', 'Subject');
		App::import('Model', 'UserLesson');

		
		//TODO: add more params, max_students, price, public
		if(strtolower($requestType)=='join') {
			//Join
			if(!$this->UserLesson->joinRequest( $subjectId, $this->Auth->user('user_id') )) {
				$this->Session->setFlash__(('Cannot join lesson'));
				$this->redirect($this->referer());
			}
		} else { //New

			//Create timestamp TODO: check user timezone
            $datetime = null;
            if(isSet($this->data['UserLesson']['datetime']) && !empty($this->data['UserLesson']['datetime'])) {
                $datetime = $this->data['UserLesson']['datetime'];
            } else if(isSet($this->data['datetime']) && !empty($this->data['datetime'])) {
                $datetime = $this->data['datetime'];
            }

            if($datetime) {
                $datetime = mktime(($datetime['meridian']=='pm' ? $datetime['hour']+12 : $datetime['hour']), $datetime['min'], 0, $datetime['month'], $datetime['day'], $datetime['year']);
                $datetime = $this->UserLesson->timeExpression($datetime, false);
            }

			if(!$this->UserLesson->lessonRequest($subjectId, $this->Auth->user('user_id'), $datetime)) {
                $this->Session->setFlash(__('Cannot order lesson'));
                $this->redirect($this->referer());
            }
		}
    }*/


    /*public function testListTimezones() {
        App::uses('Locale', 'Utility');
        pr(Locale::listTimezones());
    }*/

    /*public function testFind() {
        //pr($this->UserLesson->getAssociated()); die;
        Configure::write('Config.timezone', 'Asia/Tokyo');
        //Configure::write('Config.timezone', 'Asia/Jerusalem');
        //$allLiveLessons = $this->User->getLiveLessonsByDate( array(1,2,3,4,5), false, 2012, 9);
        //pr($allLiveLessons);
        $this->User->recursive = 2;
        pr($this->User->find('all'));

    }
    public function testSave() {
        Configure::write('Config.timezone', 'Asia/Tokyo');
        //$this->UserLesson->saveAll( array('name'=>'uname', 'Subject'=>array('name'=>'sname', 'user_id'=>4), 'TeacherLesson'=>'tname'),array('validate'=>false));
        $this->UserLesson->saveAll( array('name'=>'uname', 'description'=>'aa', 'language'=>'en',
            'Subject'=>array('name'=>'sname', 'description'=>1, 'user_id'=>4, 'language'=>'en'),
            'TeacherLesson'=>array(array('name'=>'tname1'), array('name'=>'tname2'))),array('validate'=>false));
    }*/

    public function testTime() {
        //$this->UserLesson->find();
        App::uses('CakeTime', 'Utility');

        $userTimezone = 'Asia/Tokyo'; //Asia/Jerusalem
        $serverTime = date('Y-m-d H:i:s');


        Configure::write('Config.timezone', 'Asia/Jerusalem');


        pr($serverTime);
        pr($this->UserLesson->toServerTime('now'));
        pr($this->UserLesson->toClientTime('now'));
        pr($this->UserLesson->timeExpression('now', false));

        die;
        $startDate = '2012-03-01 01:00:00';
        $serverStartDate = $this->UserLesson->toServerTime($startDate);

        pr($startDate);
        pr($serverStartDate );
        die;

        pr( date('Y-m-d H:i:s') );
        pr( CakeTime::format('Y-m-d H:i:s', CakeTime::fromString('now + 60 seconds')) );
        pr( CakeTime::format('Y-m-d H:i:s', CakeTime::fromString('now')) );
        pr( date('Y-m-d H:i:s', CakeTime::fromString(date('Y-m-d H:i:s').' + 1 day')) );
        pr( date('Y-m-d H:i:s', CakeTime::fromString('2012-02 + 1 day')) );
        pr( date('Y-m-d H:i:s', CakeTime::fromString('now')) );

        //From server to user
        /*$userTimeTS = CakeTime::fromString($serverTime, $userTimezone); //Timestamp



        //From user to server
        $serverTime2 = CakeTime::toServer(date('Y-m-d H:i:s', $userTimeTS) , $userTimezone);


        pr($serverTime);
        pr(date('Y-m-d H:i:s', $userTimeTS));
        pr($serverTime2);*/


        /*$userTime = '2012-08-16 15:40:38';
        $serverTime2 = CakeTime::toServer($userTime , $userTimezone);
        pr($userTime);
        pr($serverTime2);*/
        die;
    }

}
