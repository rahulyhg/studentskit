<?php
App::uses('CakeEvent', 'Event');
class TeacherLesson extends AppModel {
	public $name 		= 'TeacherLesson';
	public $useTable 	= 'teacher_lessons';
	public $primaryKey 	= 'teacher_lesson_id';
    public $actsAs = array('LanguageFilter');
		public $validate = array(
		'name'=> array(
			'between' => array(
            	'required'	=> 'create',
				'allowEmpty'=> false,
				'rule'    	=> array('between', 2, 45),
				'message' 	=> 'Between %d to %d characters'
			)
		),
		'description'=> array(
			'minLength' 	=> array(
				'required'	=> 'create',
				'allowEmpty'=> false,
				'rule'    	=> array('minLength', 15),
				'message' 	=> 'Must be more then %d characters'
			)
		),
        'subject_id'=> array(
            'validate_subject_id' 	=> array(
                'allowEmpty'=> true,
                'rule'    	=> 'validateSubjectId',
                'message' 	=> 'You cannot use this subject'
            )
        ),
        'request_subject_id'=> array(
            'validate_request_subject_id' 	=> array(
                'allowEmpty'=> true,
                'rule'    	=> 'validateRequestSubjectId',
                'message' 	=> 'You cannot offer this subject'
            )
        ),
        //Datetime: Cannot be empty for lesson_type=live, it will be checked in beforeValidate below

		'duration_minutes'=> array(
			'range' 		=> array(
				'required'	=> 'create',
				'allowEmpty'=> false,
				'rule'    	=> array('range', 4, 241),
				'message' 	=> 'Lesson must be more then %d minutes and less then %d minutes'
			)
		),
		'1_on_1_price'=> array(
			'price' => array(
            	'required'	=> 'create',
				'allowEmpty'=> false,
				'rule'    	=> 'numeric',
				'message' 	=> 'Enter a valid price'
			),
			'price_range' => array(
				'required'	=> 'create',
				'allowEmpty'=> false,
				'rule'    	=> array('range', -1, 500),
				'message' 	=> 'Price must be more then %d and less then %d'
			)
		),
        'max_students'=> array(
                'range' 		=> array(
                    'required'	=> 'create',
                    'allowEmpty'=> true,
                    'rule'    	=> array('range', 0, 1025),
                    'message' 	=> 'Lesson must have more then %d or less then %d students'
                ),
                'max_students' 	=> array(
                    'required'	=> 'create',
                    'allowEmpty'=> true,
                    'rule'    	=> 'maxStudentsCheck',
                    'message' 	=> 'You must set group price'
                )
            ),
		'full_group_total_price'=> array(
			'price' => array(
				'allowEmpty'=> true,
				'rule'    	=> 'numeric',
				'message' 	=> 'Enter a valid group price'
			),
			'price_range' => array(
				'allowEmpty'=> true,
				'rule'    	=> array('range', -1, 2501),
				'message' 	=> 'Price must be more then %d and less then %d'
			),
			'full_group_total_price' 	=> array(
				//'required'	=> 'create',
				'allowEmpty'=> true,
				'rule'    	=> 'fullGroupTotalPriceCheck',
				'message' 	=> 'You must set group price'
			)
		),
	);
	
	public $belongsTo 	= array(
					'User' => array(
						'className'	=> 'User',
						'foreignKey'=>'teacher_user_id',
						'fields'	=>array('first_name', 'last_name', 'image')
					),
					'Subject' => array(
						'className'	=> 'Subject',
						'foreignKey'=>'subject_id',
						'fields'	=>array('avarage_rating', 'image', 'type' )
					)
				);

    public function isFutureDatetime($datetime) {
        return strtotime($datetime['datetime'])>=time();
    }
    public function validateSubjectId($subjectID){
        $subjectID = $subjectID['subject_id'];

        //Load the requested subject
        $subjectData = $this->Subject->findBySubjectId($subjectID);
        if(!$subjectData) {
            $this->invalidate('subject_id', ___('Invalid request subject'));
        }
        $subjectData = $subjectData['Subject'];

        //Validate its a subject offer
        if($subjectData['type']!=SUBJECT_TYPE_OFFER) {
            $this->invalidate('request_subject_id', __('must be a offer subject'));
        }

        //The teacher must be the subject owner
        if(isSet($this->data['TeacherLesson']['teacher_user_id']) && !empty($this->data['TeacherLesson']['teacher_user_id'])) {
            if($this->data['TeacherLesson']['teacher_user_id']!=$subjectData['user_id']) {
                $this->invalidate('request_subject_id', __('The teacher must be the subject owner'));
            }
        }

        return true;
    }
    public function validateRequestSubjectId($requestSubjectID){
        $requestSubjectID = $requestSubjectID['request_subject_id'];

        //Load the requested subject
        $requestSubjectData = $this->Subject->findBySubjectId($requestSubjectID);
        if(!$requestSubjectData) {
            $this->invalidate('request_subject_id', __('Invalid request subject'));
        }
        $requestSubjectData = $requestSubjectData['Subject'];

        //Validate its a subject request
        if($requestSubjectData['type']!=SUBJECT_TYPE_REQUEST) {
            $this->invalidate('request_subject_id', __('must be a request subject'));
        }

        //Validate the the 2 subjects share the same type live/video
        if(isSet($this->data['TeacherLesson']['lesson_type']) && !empty($this->data['TeacherLesson']['lesson_type'])) {
            if($requestSubjectData['lesson_type']!=$this->data['TeacherLesson']['lesson_type']) {
                if($requestSubjectData['type']==LESSON_TYPE_LIVE) {
                    $this->invalidate('request_subject_id', __('Please chose a LIVE lesson as a suggestion') );
                } else if($requestSubjectData['type']==LESSON_TYPE_VIDEO) {
                    $this->invalidate('request_subject_id', __('Please chose a VIDEO lesson as a suggestion') );
                }
            }
        }

        //Check that the owner of $requestSubjectID is the main student
        if(isSet($this->data['TeacherLesson']['student_user_id']) && !empty($this->data['TeacherLesson']['student_user_id'])) {
            if($this->data['TeacherLesson']['student_user_id']!=$requestSubjectData['user_id']) {
                $this->invalidate('request_subject_id', __('The main student must be the owner of the requested subject'));
            }
        }

        return true;
    }

	/* Taken from Subject model - start */
	public function fullGroupTotalPriceCheck( $price ) {
		if(!isSet($this->data['TeacherLesson']['max_students'])) {
			$this->invalidate('max_students', ___('Please enter a valid max students'));
			return false;
		} else  {
			if(	isSet($this->data['TeacherLesson']['full_group_total_price']) && !empty($this->data['TeacherLesson']['full_group_total_price']) && 
				$this->data['TeacherLesson']['max_students'] && $this->data['TeacherLesson']['1_on_1_price']) {
				
				//Check if full_group_total_price is MORE then  max_students*1_on_1_price
				$maxAllowed = $this->data['TeacherLesson']['max_students']*$this->data['TeacherLesson']['1_on_1_price'];
				if($this->data['TeacherLesson']['full_group_total_price']>$maxAllowed) {
					$this->invalidate('max_students', sprintf(__('Group price error, max is %d (max students * 1 on 1 price)'), $maxAllowed));

                    //Check if total group price is LESS then 1 on 1 price (1 on 1 price is NOT 0)
                } else if($this->data['TeacherLesson']['full_group_total_price']<=$this->data['TeacherLesson']['1_on_1_price']) {
                    $this->invalidate('full_group_total_price', sprintf(__('Full group price must be more the 1 on 1 price (%d)'), $this->data['TeacherLesson']['1_on_1_price']));
                }
			}
		}
		return true;
	}
	public function maxStudentsCheck( $maxStudents ) {
		if($maxStudents['max_students']>1 && (!isSet($this->data['TeacherLesson']['full_group_total_price']) || !$this->data['TeacherLesson']['full_group_total_price'])) {
			$this->invalidate('full_group_total_price', __('Please enter a valid group price or set Max students to 1'));
			return false;
		}
		return true;
	}

    public function beforeValidate($options=array()) {
        parent::beforeValidate($options);

        App::import('Model', 'Subject');
        Subject::calcFullGroupStudentPriceIfNeeded($this->data['UserLesson'], ($this->id || !empty($this->data['Subject'][$this->primaryKey])) );
        Subject::extraValidation($this);


        if(isSet($this->data['TeacherLesson']['subject_id']) || !empty($this->data['TeacherLesson']['subject_id'])) {
            $subjectData = $this->Subject->findBySubjectId($this->data['TeacherLesson']['subject_id']);
            if(!$subjectData) {
                return false;
            }
            $subjectData = $subjectData['Subject'];


            if($subjectData['lesson_type']==LESSON_TYPE_LIVE) {
                //Make sure that datetime is not blank for live lessons and that its a future datetime + 1 hour from now
                $this->validator()->add('datetime', 'datetime', array(
                    'required'	=> 'create',
                    'allowEmpty'=> false,
                    'rule'    	=> array('datetime', 'ymd'),
                    'message' 	=> 'Invalid datetime format'
                ))->add('datetime', 'future_datetime', array(
                    'required'	=> 'create',
                    'allowEmpty'=> false,
                    'rule'    	=> 'isFutureDatetime',
                    'message' 	=> __('Please set a future datetime')
                ));

            } else if($subjectData['lesson_type']==LESSON_TYPE_VIDEO) {

                //Allow datetime to be blank, or be set to now || any future datetime
                $this->validator()->add('datetime', 'datetime', array(
                    'allowEmpty'=> true,
                    'rule'    	=> array('datetime', 'ymd'),
                    'message' 	=> __('Invalid datetime format')
                ));
            }
        }
    }




	/* Taken from Subject model - end */
	public function add( $source, $datetime=null, $isPublic=null, $extra=array() ) {
        //TODO: check if there is no lesson at that time
        $teacherLessonData = array();
        if($source['type']=='subject') {
            App::import('Model', 'Subject');
            $subjectObj = new Subject();

            //Find the subject
            $subjectObj->recursive = -1;
            $subjectData = $subjectObj->findBySubjectId($source['id']);
            if(!$subjectData || $subjectData['Subject']['is_enable']==SUBJECT_IS_ENABLE_FALSE) {
                return false;
            }
            $subjectData = $subjectData['Subject'];

            //Teacher lesson must be for lesson type offer
            if($subjectData['type']!=SUBJECT_TYPE_OFFER) {
                return false;
            }

            //Preparer the teacher lesson generic data
            $teacherLessonData  = array(//request_subject_id
                'subject_id'				=> $source['id'],
                'teacher_user_id'			=> $subjectData['user_id'],
                'lesson_type'				=> $subjectData['lesson_type'],
                'language'				    => $subjectData['language'],
                'datetime'					=> $datetime, //Convert timestamp to datetime
                'subject_category_id'		=> $subjectData['subject_category_id'],
                'forum_id'		            => $subjectData['forum_id'],
                'name'						=> $subjectData['name'],
                'description'				=> $subjectData['description'],
                'is_public'					=> is_null($isPublic) ? $subjectData['is_public'] : $isPublic,
                'duration_minutes'			=> $subjectData['duration_minutes'],
                'max_students'				=> $subjectData['max_students'],
                '1_on_1_price'				=> $subjectData['1_on_1_price'],
                'full_group_student_price'	=> $subjectData['full_group_student_price'],
                'full_group_total_price'	=> $subjectData['full_group_total_price'],
            );

            //Set the end of the lesson, video lesson end date is first-watching-time+2 days
            if($subjectData['lesson_type']==LESSON_TYPE_LIVE && $datetime) {
                $teacherLessonData['end_datetime'] = $this->timeExpression($datetime.' + '.$subjectData['duration_minutes'].' minutes' ,false);
                    //$this->Subject->datetimeToStr($datetime, $subjectData['duration_minutes']);
            }

            //The teacher must be the subject owner
            unset($extra['teacher_user_id']);
            $teacherLessonData = am($teacherLessonData, $extra);

            if(!isSet($teacherLessonData['student_user_id'])) {
                return false;
            }

        } else if($source['type']=='user_lesson') {
            App::import('Model', 'UserLesson');
            $ulObj = new UserLesson();
            $ulObj->recursive = -1;
            $ulData = $ulObj->findByUserLessonId($source['id']);
            if(!$ulData) {
                return false;
            }
            $teacherLessonData = $ulData['UserLesson'];

            //Only the teacher that opened the subject can teach it
            unset($extra['teacher_user_id']);
            $teacherLessonData = am($teacherLessonData, $extra);

        } else {
            return false;
        }

		$event = new CakeEvent('Model.TeacherLesson.beforeAdd', $this, array('teacher_lesson'=>$teacherLessonData, 'source'=>$source) );
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return false;
		}
		$this->create();
		$this->set($teacherLessonData);
		if(!$this->save()) {
			return false;
		}

		$event = new CakeEvent('Model.TeacherLesson.afterAdd', $this, array('teacher_lesson'=>$teacherLessonData, 'source'=>$source) );
		$this->getEventManager()->dispatch($event);
		
		
		return $this->id;
	}
	
	
	public function cancel( $teacherLessonsId, $canceledBy='teacher',$teacherUserId=null ) {
		//Find the TeacherLesson
		$this->recursive = -1;
		$teacherLessonsData = $this->findByTeacherLessonId($teacherLessonsId);
		if(!$teacherLessonsData) {
			return false;
		}
		$teacherLessonsData = $teacherLessonsData['TeacherLesson'];
		
		if(!is_null($teacherUserId)) {
			//Check if that's the right teacher
			if($teacherLessonsData['teacher_user_id']!=$teacherUserId) {
				return false;
			}
		}
				
		if($teacherLessonsData['is_deleted']) {
			//Already deleted
			return true;
		}
		
		
		//TODO: move to event handler
		//Get all user lessons that are about to cancel
		App::import('Model', 'Subject');
		App::import('Model', 'UserLesson');
		$userLessonObj = new UserLesson();
		$userLessonData = $userLessonObj->find('all', array('conditions'=>array('UserLesson.teacher_lesson_id'=>$teacherLessonsId, 
																				'UserLesson.stage'=>array( USER_LESSON_PENDING_TEACHER_APPROVAL, USER_LESSON_PENDING_STUDENT_APPROVAL, USER_LESSON_ACCEPTED))));
		
		$event = new CakeEvent('Model.TeacherLesson.beforeCancel', $this, array('teacher_lesson'=>$teacherLessonsData,'user_lesson'=>$userLessonData));
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return false;
		}
		
		
		//Update all users that are going to take place in that lesson
		App::import('Model', 'UserLesson');
		$userLessonObj = new UserLesson();
		$userLessonObj->updateAll(	array('UserLesson.stage'=>USER_LESSON_DENIED_BY_TEACHER),
									array('UserLesson.stage'=>array(USER_LESSON_PENDING_TEACHER_APPROVAL, USER_LESSON_PENDING_STUDENT_APPROVAL, USER_LESSON_ACCEPTED), 'UserLesson.teacher_lesson_id'=>$teacherLessonsId));
										 
		
		//Delete the teacher lesson
		$this->id = $teacherLessonsId;
		$this->set(array('is_deleted'=>1));
		$this->save();
		//$this->delete($teacherLessonsId);						 
										 
		
		$event = new CakeEvent('Model.TeacherLesson.afterCancel', $this, array('teacher_lesson'=>$teacherLessonsData,'user_lesson'=>$userLessonData));
		$this->getEventManager()->dispatch($event);
		
		
		return true;
	}
	
	public function getLiveLessonsByDate( $teacherUserId, $year, $month=null ) {
		$this->getDataSource();
        $this->Subject; //Init const LESSON_TYPE_LIVE

        if(!$year) {
            $year = date('Y');
            $month = date('m');
        } else if(!$month) {
            $month = date('m');
        }

		$startDate = $year.'-'.($month ? $month : 1).'-1 00:00:00';
		$endDate = $year.'-'.($month ? $month : 12).'-1 23:59:59';


        //Convert the client time to server time
        $startDate = $this->toServerTime($startDate);
        $endDate = $this->toServerTime($endDate);

		$conditions = array('teacher_user_id'=>$teacherUserId, $this->alias.'.lesson_type'=>LESSON_TYPE_LIVE,
                            'OR'=>array(
                                'datetime BETWEEN ? AND ?' => array($startDate, $this->timeExpression($endDate.' + 1 month')),
                                'end_datetime BETWEEN ? AND ?' => array($startDate, $this->timeExpression($endDate.' + 1 month'))

                            ),
							'is_deleted'=>0 );

		return $this->find('all', array('conditions'=>$conditions));
	}
	
	public function getArchive($teacherUserId, $subectId=null, $limit=null, $page=1) {
		$conditions = array( 'teacher_user_id'=>$teacherUserId );
		if($subectId) {
			$conditions['TeacherLesson.subject_id'] = $subectId;
		}
		
		$conditions['OR'] = array(
			array('end_datetime <'=>$this->timeExpression('now', false), 'end_datetime IS NOT NULL' ),
			'is_deleted'=>1
		);
		
//		pr($conditions);
		return $this->find('all', array(
			'conditions'=>$conditions,
			'page'=>$page,
			'limit'=>$limit
		));
	}
	
	public function getUpcomming($teacherUserId, $subectId=null, $limit=null, $page=1) {
		$this->Subject;
		$conditions = array( 'teacher_user_id'=>$teacherUserId, 'is_deleted'=>0,
                'OR'=>array(
                    array('end_datetime >'=>$this->timeExpression('now', false)),
                    array('end_datetime IS NULL')

                )
		);




		if($subectId) {
			$conditions['TeacherLesson.subject_id'] = $subectId;
		}
		
		return $this->find('all', array(
			'conditions'=>$conditions,
			'page'=>$page,
			'limit'=>$limit
		));
	}
	
	/*public function getPendingProposedLessons($teacherUserId, $subectId=null, $limit=null, $page=1) {
		$conditions = array( 'teacher_user_id'=>$teacherUserId, 'datetime >'=>$this->timeExpression('now', false), 'is_deleted'=>0, 'request_subject_id IS NOT NULL', 'num_of_students'=>0 );
		if($subectId) {
			$conditions['TeacherLesson.subject_id'] = $subectId;
		}
		
		return $this->find('all', array(
			'conditions'=>$conditions,
			'page'=>$page,
			'limit'=>$limit
		));
	} */
    public function getLiveLessonMeeting($teacherLessonId) {
        return 'wfg-213';
    }
    public function getVideoUrl($teacherLessonId) {
        return 'videoUrl';
    }
    public function getFileSystem($teacherLessonId) {
        App::import('Model', 'FileSystem');
        $fsObj = new FileSystem();
        return $fsObj->getFS('lesson', $teacherLessonId);
    }

    public function getTests($teacherLessonId) {
        //Get subject tests
        App::import('Model', 'StudentTest');
        $testObj = new StudentTest();
        return $testObj->getTests('lesson', $teacherLessonId);;
    }

    public static function getLessonTiming($datetime, $duration) {
        $return = array('overdue'=>false, 'about_to_start'=>false);
        //Check the time status of the lesson
        if(($datetime+$duration*MIN)<time()) { //duetime > now = overdue
            $return['overdue'] = true;
        } if($datetime<time()) { //start time < now = not yer started
            $return['about_to_start'] = true;
        } else {
            $return['in_process'] = true;
        }
    }
	
}
?>