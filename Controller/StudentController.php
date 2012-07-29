<?php
/**
 *@property Subject $Subject
 */
class StudentController extends AppController {
	public $name = 'Student';
	public $uses = array('Subject', 'User', 'Profile', 'TeacherLesson', 'UserLesson');
	public $components = array('Session', 'RequestHandler', 'Auth'=>array('loginAction'=>array('controller'=>'Accounts','action'=>'login')),/* 'Security'*/);
	//public $helpers = array('Form', 'Html', 'Js', 'Time');

	public function index() {
		//Get lessons that about to start
		$upcommingLessons= $this->UserLesson->getUpcomming($this->Auth->user('user_id'), 2, 1);
					
		//TODO: get board messages
		
		//TODO: get lesson suggestions
					
		$this->Set('upcommingLessons', $upcommingLessons);
	}
	
	public function lessons($limit=5, $page=1, $lang=null) {
		//Get lessons that about to start - upcomming
		$upcommingLessons = $this->UserLesson->getUpcomming($this->Auth->user('user_id'), $limit, $page);
		$this->Set('upcommingLessons', $upcommingLessons);

        //Get lessons that are over - archive
        $archiveLessons = $this->UserLesson->getArchive($this->Auth->user('user_id'), $limit, $page);
        $this->Set('archiveLessons', $archiveLessons);

		//Get lessons that pending for teacher approval - booking requests
		$bookingRequests = $this->UserLesson->getBooking($this->Auth->user('user_id'), $limit, $page);
		$this->Set('bookingRequests', $bookingRequests);
		
		//Get lessons invitations - invitations
		$lessonInvitations = $this->UserLesson->getInvitations($this->Auth->user('user_id'), $limit, $page);
		$this->Set('lessonInvitations', $lessonInvitations);
		
		//Get lesson requests - lesson offers
        $subjectRequests = $this->Subject->getSubjectRequestsForStudent($this->Auth->user('user_id'), $limit, $page);
		$this->Set('subjectRequests', $subjectRequests);
	}

	public function lessonsUpcoming($limit=5, $page=1) {
		$upcommingLessons = $this->UserLesson->getUpcomming($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('upcommingLessons'=>$upcommingLessons));
	}
	public function lessonsBooking($limit=5, $page=1) {
		$bookingLessons = $this->UserLesson->getBooking($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('bookingLessons'=>$bookingLessons));
	}
	public function lessonsArchive($limit=5, $page=1) {
		$archiveLessons = $this->UserLesson->getArchive($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('archiveLessons'=>$archiveLessons));
	}
	public function lessonsInvitations($limit=5, $page=1) {
		$lessonInvitations = $this->UserLesson->getInvitations($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('lessonInvitations'=>$lessonInvitations));
	}
	public function subjectRequests($limit=5, $page=1) {
		$subjectRequests = $this->Subject->getSubjectRequestsForStudent($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('subjectRequests'=>$subjectRequests));
	}
	
	public function cacnelUserLesson( $userLessonId ) {
		if(!$this->UserLesson->cancelRequest( $userLessonId, $this->Auth->user('user_id') )) {
			return $this->error(1, array('user_lesson_id'=>$userLessonId));
		}
		
		return $this->success(1, array('user_lesson_id'=>$userLessonId));
	}
	
	public function acceptUserLesson( $userLessonId ) {
		if(!$this->UserLesson->acceptRequest( $userLessonId, $this->Auth->user('user_id') )) {
			return $this->error(1, array('user_lesson_id'=>$userLessonId));
		}

		return $this->success(1, array('user_lesson_id'=>$userLessonId));
	}

    public function reProposeRequest($userLessonId) {
        if (empty($this->request->data)) {
            $this->request->data = $this->UserLesson->findByUserLessonId($userLessonId);;
        } else {
            if($this->UserLesson->reProposeRequest($userLessonId, $this->Auth->user('user_id'), $this->request->data['UserLesson'])) {
                if(isSet($this->params['ext'])) {
                    return $this->success(1, array('user_lesson_id'=>$userLessonId));
                }

                //$this->Session->setFlash('Re-Propose sent');
                //$this->redirect($this->referer());
            } else if(isSet($this->params['ext'])) {
                return $this->error(1, array('validation_errors'=>$this->UserLesson->validationErrors));
            }
        }

        //Group pricing
        if(	isSet($this->data['UserLesson']['1_on_1_price']) &&
            isSet($this->data['UserLesson']['full_group_total_price']) && !empty($this->data['UserLesson']['full_group_total_price']) &&
            isSet($this->data['UserLesson']['max_students']) && $this->data['UserLesson']['max_students']>1) {
            $groupPrice = $this->Subject->calcGroupPrice(	$this->data['UserLesson']['1_on_1_price'], $this->data['UserLesson']['full_group_total_price'],
                $this->data['UserLesson']['max_students'], $this->data['UserLesson']['max_students']);
            $this->set('groupPrice', $groupPrice);
        }
    }
	
	public function profile() {
		if (empty($this->request->data)) {
			$this->request->data = $this->User->findByUserId($this->Auth->user('user_id'));
		} else {
			  $this->User->set($this->request->data);
			  $this->User->save();
		}
	}
	
	public function awaitingReview() {
		$awaitingReviews = $this->UserLesson->waitingStudentReview($this->Auth->user('user_id'));
		$this->set('awaitingReviews', $awaitingReviews);
		
		$userData = $this->User->findByUserId($this->Auth->user('user_id'));
		$this->set('studentAvarageRating', $userData['User']['student_avarage_rating']);
	}
	public function setReview($userLessonId) {
		if (!empty($this->request->data)) {
			if($this->UserLesson->rate(	$userLessonId, $this->Auth->user('user_id'), 
			  							$this->request->data['UserLesson']['rating_by_student'], 
			  							$this->request->data['UserLesson']['comment_by_student'])) {
				$this->redirect(array('action'=>'awaitingReview'));
			}
			 
		}
		$setReview = $this->UserLesson->getLessons(array('student_user_id'=>$this->Auth->user('user_id')), $userLessonId);
		$this->Set('setReview', $setReview);
	}
	
	public function myReviews() {
		//Get students comments for that teacher
		$studentReviews = $this->User->getStudentReviews( $this->Auth->user('user_id'), 10 );
		$this->Set('studentReviews', $studentReviews);
	}

    public function invite() {
        $this->request->data['teacher_lesson_id'] = 1;
        $this->request->data['emails'] = 'eldad87@gmail.com';
        $this->request->data['message'] = 'My message';

        if (!empty($this->request->data)) {
            if(!isSet($this->request->data['teacher_lesson_id']) || !isSet($this->request->data['emails']) || !isSet($this->request->data['message'])) {
                return $this->error(1);
            }

            //Find teacher lesson
            $this->TeacherLesson->recursive = -1;
            $tlData = $this->TeacherLesson->find('first', array('teacher_lesson_id'=>$this->request->data['teacher_lesson_id']));
            if(!$tlData) {
                return $this->error(2);
            }
            $tlData = $tlData['TeacherLesson'];
            $this->request->data['emails'] = explode(',', $this->request->data['emails']);


            //If sender is the teacher
            if($this->Auth->user('user_id')==$tlData['teacher_user_id']) {
                //Find if any of those emails in our system, if so - send an invitation

                $this->User->recursive = -1;
                $users = $this->User->find('all', array('conditions'=>array('email'=>$this->request->data['emails'])));

                $emailAsKeys = array_flip($this->request->data['emails']);
                foreach($users AS $user) {
                    $user = $user['User'];
                    unset($emailAsKeys[$user['email']]); //Remove user from emailing list
                    $this->UserLesson->joinRequest($this->request->data['teacher_lesson_id'], $user['user_id'], $this->Auth->user('user_id')); //Send invitation
                }
                $this->request->data['emails'] = array_flip($emailAsKeys);
            }

            //TODO: email users
            //Email users
            App::uses('CakeEmail', 'Network/Email');
            $email = new CakeEmail();

            foreach($this->request->data['emails'] AS $toEmail) {
                $email->from(array('doNotReplay@studentskit.com' => 'Studentskit'));
                $email->subject('Invitation for '.$tlData['name']);
                $email->to($toEmail);
                $email->send($this->request->data['message']);
            }
        }

    }
}