<?php
echo $this->element('panel/send_msg_popup', array('buttonSelector'=>'.msg-teacher'));
echo $this->fetch('popups');
?>
<!-- Containeer
================================================== -->
<Section class="container">
    <div class="container-inner">
        <div class="row">
            <div class="cont-span12">
                <div class="cont-span16 c-box-mar cbox-space pos">
                    <div class="butn-space">
                        <?php echo $this->fetch('lesson_box'); ?>
                    </div>
                    <div class="student-main-box2 radius3 pad1">
                        <div class="sec-main-box">

                            <?php echo $this->fetch('topic_image'); ?>

                            <h6><?php
                                echo $this->Html->link($subjectData['name'], array('controller'=>'Home', 'action'=>'teacherSubject', $subjectData['subject_id']))
                                ?></h6>
                            <p class="pad2"><?php echo $subjectData['description']; ?></p>
                        </div>
                        <div class="icon-box-social  bod2">
                            <div class="social-icons pad2">
                                <a href="#" class="fb"></a>
                                <a href="#" class="twit"></a>
                                <a href="#" class="g-one"></a>
                                <p class="maxstudntbar"><span class="maxstudent">
                                    <?php
                                    if($subjectData['max_students'] && $subjectData['lesson_type']==LESSON_TYPE_LIVE) {
                                        if(!empty($teacherLessonData)) {
                                            echo 'Max. Students: '.$teacherLessonData['num_of_students'].'/'.$subjectData['max_students'];
                                        } else {
                                            echo 'Max. Students: '.$subjectData['max_students'];
                                        }
                                    }
                                    ?>
                                </span><span class="duration">Duration: <?php echo $subjectData['duration_minutes']; ?> min</span></p>
                                <div class="pull-right price-margn"><?php echo $this->Layout->priceTag($subjectData['1_on_1_price'], $subjectData['full_group_student_price']); ?></div>
                            </div>
                        </div>
                        <div class="log-box">
                            <p class="log1 radius3 gradient2"><span class="fontsize4"><?php echo $subjectData['total_lessons'] ?></span><br/>Lessons</p>
                            <p class="log1 radius3 gradient2"><span class="fontsize4"><?php echo $subjectData['students_amount'] ?></span><br/>Students</p>
                            <p class="log1 radius3 gradient2"><span class="fontsize4"><?php echo $subjectData['raters_amount'] ?></span><br/>Reviews</p>

                            <a href="#" class="log2 btn-black radius3"><?php
                                echo $this->Layout->ratingNew($subjectData['avarage_rating'], false, 'space20 centered');
                            ?><br/>Rating</a>
                        </div>
                    </div> <!-- /student-main-box -->

                    <?php if($teacherOtherSubjects) {
                        echo $this->element('Home/other_subjects', array('teacherSubjects'=>$teacherOtherSubjects));
                    }

                    if(!empty($upcomingAvailableLessons)) {
                        echo $this->element('Home/upcoming_lessons', array('upcomingAvailableLessons'=>$upcomingAvailableLessons));
                    }
                    ?>
                </div>
                <div class="cont-span17 cbox-space">
                    <ul class="teacher-box3">
                        <li>
                            <div class="pic-butn-box pos fix-width">
                                <div class="student-main-box radius3 fix-height">
                                    <a title="" href="#" class="teacher-pic radius3"><?php echo $this->Html->image($this->Layout->image($teacherData['image_source'], 149, 182), array('alt' => 'Topic image')); ?></a>
                                    <p class="onliestatus">
                                        <a href="#" class="msg-teacher" data-to_user_id="<?php echo $teacherData['user_id']; ?>"<?php
                                            if($subjectData['lesson_type']==LESSON_TYPE_LIVE && !empty($teacherLessonData)) {
                                                //Join lesson
                                                echo ' data-entity_type="teacher_lesson" data-entity_id="'.$teacherLessonData['teacher_lesson_id'].'"';
                                            } else {
                                                //Order a lesson
                                                echo ' data-entity_type="subject" data-entity_id="'.$subjectData['subject_id'].'"';
                                            }
                                            ?>><i class="iconMedium-mail pull-left"></i></a>
                                        <i class="iconSmall-green-dot pull-left space23"></i>
                                        <span class="pull-left online">Online</span>
                                    </p>
                                    <div class="head-text3">
                                        <div class="pull-left tutorname-wrapeper">
                                            <?php
                                            echo $this->Html->link('<span class="pad5"><strong>'.$teacherData['username'].'</strong></span>',
                                                                    array('controller'=>'Home', 'action'=>'teacher', $subjectData['user_id']),
                                                                    array('escape'=>false, 'class'=>'tutroaname'));
                                            ?>

                                        </div>
                                        <!--<span class="fontsize1 pad6 pull-left">Expert Math Teacher</span>-->
                                        <p class="pull-left"><?php echo $teacherData['teacher_about']; ?></p>
                                    </div> <!-- /head-text3-->



                                    <div class="space22 clear-left pull-left">
                                        <?php
                                            echo $this->Layout->ratingNew($teacherData['teacher_avarage_rating'], false, 'space3 space22');
                                        ?>
                                    </div>
                                </div> <!-- /student-main-box -->
                            </div> <!-- /pic-butn-box -->
                        </li>
                    </ul>
                    <!--<a href="#" class="more radius3 gradient2 space8"><strong><?php echo __('Load More'); ?></strong><i class="iconSmall-more-arrow"></i></a>-->

                    <?php
                        if($subjectRatingByStudents) {
                            echo $this->element('Home/reviews_by_students', array('ratingByStudents'=>$subjectRatingByStudents, 'title'=>__('What student say about this subject?')));
                        }
                    ?>


                    <!--<div class="lesson-box space8">
                        <h3 class="radius1"><strong>See Other Teachers On This Subject</strong></h3>
                        <div class="box-subject radius2">
                            <?php /*if(!empty($otherTeacherForThisSubject)) { */?>
                            <a href="#" class="arrow-left arrws2"></a>
                            <ul class="subject-books subject-books1">
                            <?php
/*                                //$count = count($otherTeacherForThisSubject); $i=1;
                                foreach($otherTeacherForThisSubject AS $otfts) {
                                    //echo '<li',($i++==$count ? 'class="m-none3"' : null),'>';
                                    echo '<li>',$this->Html->image($this->Layout->image($otfts['Teacher']['image_source'], 63, 63), array('alt' => 'Topic image')),'</li>';
                                }

                            */?>
                            </ul>
                            <a href="#" class="arrow-right arrws2"></a>
                            <?php /*} */?>
                        </div>
                    </div>-->


                </div> <!-- /cont-span17 -->
            </div> <!-- /cont-span12 -->
        </div><!-- /row -->
    </div><!-- /container-inner -->
</Section>