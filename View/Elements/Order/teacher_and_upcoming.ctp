<div class="cont-span18 cbox-space2">
    <div class="student-main-box2 radius3 space8">
        <div class="sec-main-box space2">
            <?php
            echo $this->Html->link($this->Html->image($this->Layout->image($teacherData['image_source'], 200, 210), array('alt' => 'Teacher Image'))
                /*.'<p class="student-pic-edit"><span class="student-pic-edit-txt">Edit Image</span></p>'*/,
                array('controller'=>'Home', 'action'=>'teacher', $teacherData['user_id']),
                array('escape'=>false, 'class'=>'student-pic c-mar radius3')
            );
            ?>

            <h5><?php
                echo $this->Html->link($teacherData['username'],
                    array('controller'=>'Home', 'action'=>'teacher', $teacherData['user_id'])
                );
                ?></h5>
        </div>
        <div class="icon-box-social c-mar bod2">
            <div class="social-icons pad2">
                <a class="fb" href="#"></a>
                <a class="twit" href="#"></a>
                <a class="g-one c-mr-none" href="#"></a>
            </div>
        </div>
        <div class="log-box c-pad">
            <?php
            echo $this->Html->image($this->Layout->rating($teacherData['teacher_avarage_rating'], false), array('alt' => 'Teacher rating'));
            ?>
            <p>(<?php echo $teacherData['teacher_avarage_rating'].'/'.$teacherData['teacher_raters_amount']; ?> Reviews)</p>
        </div>
    </div> <!-- /student-main-box -->

    <?php
    if($upcomingAvailableLessons) {
        ?>
        <div class="lesson-box pad8">
            <h3 class="radius1"><strong>Join existing lessons</strong></h3>
            <div class="box-subject2 radius3">
                <?php
                $upcomingAvailableLessonsCount = count($upcomingAvailableLessons);
                foreach($upcomingAvailableLessons AS $ual) {
                    $ual = $ual['TeacherLesson'];
                    ?>
                    <div class="main-student fullwidth<?php echo (--$upcomingAvailableLessonsCount ? ' bod2' : null )?>">
                        <div class="inner-spots-box">
                            <div class="pull-right btn-width">
                                <?php echo $this->Layout->priceTag($ual['1_on_1_price'], $ual['full_group_student_price'], 'space25 order-price'); ?>
                                <?php
                                echo $this->Html->link('Join', array('controller'=>'Order', 'action'=>'init', 'join', $ual['teacher_lesson_id']),
                                    array('class'=>'btn-color-gry move-right space35 centered space3'));
                                ?>

                            </div>
                            <div class="space36">
                                <p>Start : <?php echo $this->Time->niceShort($ual['datetime']); ?></p>
                                <p>Current student <?php echo $ual['num_of_students']; ?> of <?php echo $ual['max_students']; ?></p>
                                <?php echo $this->Html->link('Lesson page', array('controller'=>'Home', 'action'=>'teacherLesson', $ual['teacher_lesson_id']));  ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <!-- /main-student -->
            </div><!-- /box-subject2 -->
        </div><!-- /lesson-box -->


        <a class="more radius3 gradient2" data-toggle="modal" href="#myModal"><strong>Load More</strong><i class="iconSmall-more-arrow"></i></a>

        <div id="myModal" class="modal hide fade">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3>Join existing group lessons</h3>
            </div> <!-- /modal-header -->
            <div class="modal-body">
                <h5><a href="#">Why should i join existing lesson?</a><i class="iconSmall-info space20 space23"></i> </h5>
                <?php
                $i=0;
                foreach($upcomingAvailableLessons AS $ual) {
                    $ual = $ual['TeacherLesson'];
                    ?>
                    <div class="main-student fullwidth bod2<?php echo (!$i ? ' space6' : null) ?>">
                        <div class="inner-spots-box">
                            <div class="pull-right btn-width">
                                <?php echo $this->Layout->priceTag($ual['1_on_1_price'], $ual['full_group_student_price'], 'space25 order-price'); ?>
                                <?php
                                echo $this->Html->link('Join', array('controller'=>'Order', 'action'=>'init', 'join', $ual['teacher_lesson_id']),
                                    array('class'=>'btn-color-gry move-right space35 centered space3'));
                                ?>
                            </div>
                            <div class="space36">
                                <p class="pull-left fullwidth">Start <?php echo $this->Time->niceShort($ual['datetime']); ?></p>
                                <p class="pull-left fullwidth">Current student <?php echo $ual['num_of_students']; ?> of <?php echo $ual['max_students']; ?></p>
                                <?php echo $this->Html->link('Lesson page', array('controller'=>'Home', 'action'=>'teacherLesson', $ual['teacher_lesson_id']));  ?>
                            </div>

                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="fullwidth pull-left">
                    <div class="pagination popmargin">
                        <ul>
                            <li class="disabled"><a href="#">&lt; &lt; Prev</a></li>
                            <li class="active"><a href="#">1</a></li>
                            <li><a href="#">2</a></li>
                            <li><a href="#">3</a></li>
                            <li><a href="#">4</a></li>
                            <li><a href="#">Next &gt; &gt;</a></li>
                        </ul>
                    </div>
                </div>
            </div> <!-- /modal-body -->
        </div>
        <?php
    }
    ?>

</div> <!-- /cont-span18 -->