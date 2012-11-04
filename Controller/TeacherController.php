<?php
class TeacherController extends AppController {
	public $name = 'Teacher';
	public $uses = array('Subject', 'User', 'Profile', 'TeacherLesson', 'UserLesson');
	public $components = array('Session', 'RequestHandler', 'Auth'=>array('loginAction'=>array('controller'=>'Accounts','action'=>'login')),/* 'Security'*/);
	//public $helpers = array('Form', 'Html', 'Js', 'Time');


    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(	'subject');
    }
	
	public function index() {
		$aboutToStartLessons = $this->TeacherLesson->getUpcomming($this->Auth->user('user_id'). null, 2, 1);

        //Get student latest forum messages
        app::import('Model', 'Forum.Post');
        $postObj = new Post();
        $postObj->setLanguages($this->Session->read('languages_of_records'));
        $latestUpdatedTopics = $postObj->getGroupedLatestUpdatedTopicsByUser($this->Auth->user('user_id'), 3);
					
		$this->Set('aboutToStartLessons', $aboutToStartLessons);
        $this->Set('latestUpdatedTopics', $latestUpdatedTopics);
	}

	public function subjects($limit=5, $page=1) {
		$subjects = $this->Subject->getOffersByTeacher($this->Auth->user('user_id'), true, null, $page, $limit);
		$this->Set('teacherImage', $this->Auth->user('image'));
		$this->Set('subjects', $subjects);
	}
	
	public function subject( $subjectId=null ) {
        if($subjectId) {
            if(!$this->verifyOwnership('subject', $subjectId)) {
                $this->Session->setFlash(__('Cannot view this subject'));
                $this->redirect($this->referer());
            }
        }



        //Not posted yet
        if (!empty($this->request->data)) {
            App::import('Model', 'Subject');
            $this->request->data['Subject']['user_id'] = $this->Auth->user('user_id');
            $this->request->data['Subject']['type'] = SUBJECT_TYPE_OFFER;
            $this->Subject->set($this->request->data);

            if($this->Subject->save($this->request->data)) {
                $this->Session->setFlash(__('Subject saved'));
                $this->redirect(array('action'=>'subjects'));
            }

        //Edit - load default data
        } else if($subjectId) {
            //Default subject data
            if (empty($this->request->data)) {
                $this->request->data = $this->Subject->findBySubjectId($subjectId);

            }
        //New "add" form, set default language
        } else {
            $this->request->data['Subject']['language'] = Configure::read('Config.language');
        }


        //Set additional subject info
        if($subjectId) {
            $this->set('subjectId', $subjectId);
            $this->set('fileSystem', $this->Subject->getFileSystem($subjectId));
            $this->set('tests', $this->Subject->getTests($subjectId));
        }





        //Get subejct categories
        App::Import('Model', 'SubjectCategory');
        $scObj = new SubjectCategory();
        $subjectCategories = $scObj->getAllCategoriesOptions();
        $this->set('subjectCategories', $subjectCategories);

		//Group pricing
		if(	isSet($this->data['Subject']['1_on_1_price']) && 
			isSet($this->data['Subject']['full_group_student_price']) && !empty($this->data['Subject']['full_group_student_price']) &&
			isSet($this->data['Subject']['max_students']) && $this->data['Subject']['max_students']>1) {
			/*$groupPrice = $this->Subject->calcStudentFullGroupPrice(	$this->data['Subject']['1_on_1_price'], $this->data['Subject']['full_group_total_price'],
															$this->data['Subject']['max_students'], $this->data['Subject']['max_students']);*/
            $groupPrice = $this->Subject->calcStudentPriceAfterDiscount(	$this->data['Subject']['1_on_1_price'],
															                $this->data['Subject']['max_students'], $this->data['Subject']['max_students'],
                                                                            $this->data['Subject']['full_group_student_price']);
			$this->set('groupPrice', $groupPrice);
		}

        App::uses('Languages', 'Utils.Lib');
        $lang = new Languages();
        $this->set('languages', $lang->lists('locale'));

	}
	
	public function disableSubject($subjectId) {
		if(!$this->verifyOwnership('subject', $subjectId)) {
			return $this->error(1, array('subject_id'=>$subjectId));
		}
		
		if(!$this->Subject->disable($subjectId)) {
			return $this->error(1, array('subject_id'=>$subjectId));
		}
		return $this->success(1, array('subject_id'=>$subjectId));
	}
	
	//"upcoming", "archive", "booking requests", "invitations sent" and "proposed lessons"
	public function lessons($limit=5, $page=1) {
		$upcommingLessons = $this->TeacherLesson->getUpcomming($this->Auth->user('user_id'), null, $limit, $page);
		$this->Set('upcommingLessons', $upcommingLessons);

        $archiveLessons = $this->TeacherLesson->getArchive($this->Auth->user('user_id'), null, $limit, $page);
        $this->Set('archiveLessons', $archiveLessons);


		//Get lessons that pending for teacher approval - booking requests
		$bookingRequests = $this->UserLesson->getWaitingForTeacherApproval($this->Auth->user('user_id'), null, $limit, $page);
		$this->Set('bookingRequests', $bookingRequests);

		//Get lessons invitations - invitations sent
		$lessonInvitations = $this->UserLesson->getTeacherInvitations($this->Auth->user('user_id'), null, $limit, $page);
		$this->Set('lessonInvitations', $lessonInvitations);
		
		/*//Get lesson requests - proposed lessons
		$pendingProposedLessons = $this->UserLesson->getPendingProposedTeacherLessons($this->Auth->user('user_id'), null, $limit, $page);
		$this->Set('pendingProposedLessons', $pendingProposedLessons);*/
	}

	public function lessonsUpcoming( $limit=6, $page=1, $subjectId=null ) {
		$nextLessons = $this->TeacherLesson->getUpcomming($this->Auth->user('user_id'), $subjectId, $limit, $page);
		return $this->success(1, array('upcoming_lessons'=>$nextLessons));
	}
	public function lessonBookingRequests($limit=6, $page=1) {
		$bookingRequests = $this->UserLesson->getWaitingForTeacherApproval($this->Auth->user('user_id'), $limit, $page);
		return $this->success(1, array('booking_requests'=>$bookingRequests));
	}
	public function lessonsArchive($limit=6, $page=1) {
		$archiveLessons = $this->TeacherLesson->getArchive($this->Auth->user('user_id'), null, $limit, $page);
		return $this->success(1, array('archive_lessons'=>$archiveLessons));
	}
	public function lessonsInvitations($limit=6, $page=1) {
		$lessonInvitations = $this->UserLesson->getTeacherInvitations($this->Auth->user('user_id'), null, $limit, $page);
		return $this->success(1, array('lesson_invitaions'=>$lessonInvitations));
	}
	/*public function lessonsProposed($limit=6, $page=1) {
		$pendingProposedLessons = $this->UserLesson->getPendingProposedLessons($this->Auth->user('user_id'), null, $limit, $page);
		return $this->success(1, array('proposed_lessons'=>$pendingProposedLessons));
	}*/


	public function cancelTeacherLesson( $teacherLessonId ) {
        $tlData = $this->TeacherLesson->findByTeacherLessonId($teacherLessonId);
        if(!$tlData || $tlData['TeacherLesson']['teacher_user_id']!=$this->Auth->user('user_id')) {
            return $this->error(1, array('results'=>array('teacher_lesson_id'=>$teacherLessonId, 'validation_errors'=>$this->TeacherLesson->validationErrors)));
        }

		if($this->TeacherLesson->cancel($teacherLessonId/*, 'teacher', $this->Auth->user('user_id')*/)) {
			return $this->success(1, array('results'=>array('teacher_lesson_id'=>$teacherLessonId)));
		}

        return $this->error(2, array('results'=>array('teacher_lesson_id'=>$teacherLessonId, 'validation_errors'=>$this->TeacherLesson->validationErrors)));
	}
	
	
	public function createTeacherLesson($subjectId) {
		if (!empty($this->request->data)) {
			if($this->TeacherLesson->add(array('type'=>'subject','id'=>$subjectId), $this->request->data['TeacherLesson']['datetime'], $this->request->data['TeacherLesson']['is_public'], array('teacher_user_id'=>$this->Auth->user('user_id')) )) {
				return $this->success(1, array('subject_id'=>$subjectId));
			}
			return $this->error(1, array('results'=>array('subject_id'=>$subjectId, 'validation_errors'=>$this->TeacherLesson->validationErrors)));
		}
		//Remove this after testing + view "create_teacher_lessons.ctp"
		//return $this->error(2, array('subject_id'=>$subjectId));
	}
	public function manageTeacherLesson( $teacherLessonId ) {
		$teacherLessonData = $this->TeacherLesson->findByTeacherLessonId($teacherLessonId);
		$students = $this->UserLesson->getStudentsForTeacherLesson($teacherLessonId);
		
		//TODO: add student amount of lessons, FS and Tests managment
		$this->set('teacherLesson', $teacherLessonData['TeacherLesson']);
		$this->set('allStudents',	 $students);
	}
	
	public function profile() {
        $this->User->unbindAll(array('hasMany'=>array('TeacherCertificate', 'TeacherAboutVideo')));
        $userData = $this->User->findByUserId($this->Auth->user('user_id'));

		if (empty($this->request->data)) {
			$this->request->data = $userData;
		} else {
			  $this->User->set($this->request->data);
			  $this->User->save();
		}

        $this->set('userData', $userData);
	}

    /**
     * Add a certification to the Teacher's profile
     * @return array
     */
    public function addCertificate() {
        if (!empty($this->request->data)) {
            $this->request->data['TeacherCertificate']['teacher_user_id'] = $this->Auth->user('user_id');
            if(!$this->User->TeacherCertificate->save($this->request->data)) {
                return $this->error(1, array('results'=>$this->User->TeacherCertificate->validationErrors));
            }

            return $this->success(1, array('results'=>array('teacher_certificate_id'=>$this->User->TeacherCertificate->id)));
        }
    }
    public function removeCertificate($teacherCertificateId) {
        //Find record
        $this->User->TeacherCertificate->recursive = -1;
        $cert = $this->User->TeacherCertificate->find('first', array('teacher_certificate_id'=>$teacherCertificateId));
        if(!$cert || $cert['TeacherCertificate']['teacher_user_id']!=$this->Auth->user('user_id')) {
            return $this->error(1, array('results'=>array('teacher_certificate_id'=>$teacherCertificateId)));
        }

        if(!$this->User->TeacherCertificate->delete($teacherCertificateId)) {
            return $this->error(2, array('results'=>array('teacher_certificate_id'=>$teacherCertificateId)));
        }

        return $this->success(1, array('results'=>array('teacher_certificate_id'=>$teacherCertificateId)));
    }
    /**
     * Add a certification to the Teacher's profile
     * @return array
     */
    public function addAboutVideo() {


        if (!empty($this->request->data)) {
            $this->request->data['TeacherAboutVideo']['teacher_user_id'] = $this->Auth->user('user_id');
            if(!$this->User->TeacherAboutVideo->save($this->request->data)) {
                return $this->error(1, array('results'=>$this->User->TeacherAboutVideo->validationErrors));
            }

            return $this->success(1, array('results'=>array('teacher_about_video_id'=>$this->User->TeacherAboutVideo->id)));
        } else {
            App::uses('Languages', 'Utils.Lib');
            $lang = new Languages();
            $this->set('languages', $lang->lists('locale'));
        }
    }
    public function removeAboutVideo($teacherCertificateId) {
        //Find record
        $this->User->TeacherAboutVideo->recursive = -1;
        $cert = $this->User->TeacherAboutVideo->find('first', array('teacher_about_video_id'=>$teacherCertificateId));
        if(!$cert || $cert['TeacherAboutVideo']['teacher_user_id']!=$this->Auth->user('user_id')) {
            return $this->error(1, array('results'=>array('teacher_about_video_id'=>$teacherCertificateId)));
        }

        $this->User->TeacherCertificate->recursive = -1;
        if(!$this->User->TeacherCertificate->delete($teacherCertificateId)) {
            return $this->error(2, array('results'=>array('teacher_about_video_id'=>$teacherCertificateId)));
        }

        return $this->success(1, array('results'=>array('teacher_about_video_id'=>$teacherCertificateId)));
    }

	public function awaitingReview() {
		$awaitingReviews = $this->UserLesson->waitingTeacherReview($this->Auth->user('user_id'));
		$this->set('awaitingReviews', $awaitingReviews);
		
		$userData = $this->User->findByUserId($this->Auth->user('user_id'));
		$this->set('teacherAvarageRating', $userData['User']['teacher_avarage_rating']);
	}
	public function setReview($userLessonId) {
		if (!empty($this->request->data)) {
			if($this->UserLesson->rate(	$userLessonId, $this->Auth->user('user_id'),
			  							$this->request->data['UserLesson']['rating_by_teacher'], 
			  							$this->request->data['UserLesson']['comment_by_teacher'])) {
				$this->redirect(array('action'=>'awaitingReview'));
			}
			 
		}
		
		$setReview = $this->UserLesson->getLessons(array('teacher_user_id'=>$this->Auth->user('user_id')), $userLessonId);
		$this->Set('setReview', $setReview);
	}
	
	public function myReviews() {
        //Ajax - Home.getTeacherRatingByStudents

		//Get students comments for that teacher
		$teacherReviews = $this->UserLesson->getTeacherReviews( $this->Auth->user('user_id'), 10 );
		$this->Set('teacherReviews', $teacherReviews);
	}

    public function getLiveLessonMeeting($teacherLessonId) {
        return 'wft-234';
    }
	
	private function verifyOwnership($entityType, $entityId) {
		$foundRecord = false;
		switch($entityType) {
			case 'subject':
				$foundRecord = $this->Subject->find('first', array('conditions'=>array('subject_id'=>$entityId, 'user_id'=>$this->Auth->user('user_id'))));
			break;
			case 'teacher_lesson':
				$foundRecord = $this->TeacherLesson->find('first', array('conditions'=>array('teacher_lesson_id'=>$entityId, 'teacher_user_id'=>$this->Auth->user('user_id'))));
			break;
			case 'user_lesson':
				$foundRecord = true; //TODO
			break;
		}
		
		return $foundRecord ? true : false;
		
	}
}