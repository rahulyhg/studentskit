<section class="container">
    <div class="container-inner">
        <div class="row">
            <div class="cont-span12">
                <div class="cont-span3 c-box-mar cbox-space">
                    <div class="student-main-box radius3">
                        <a class="student-pic radius3" href="#" title="">
                            <?php echo $this->Html->image($this->Layout->image($user['image_source'], 200, 210)); ?>
                        </a>
                        <h5><?php echo $user['username']; ?></h5>
                    </div> <!-- /student-main-box -->

                    <ul class="right-menu">
                        <li class="bg-main"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Student','action'=>'index')); ?>"><?php echo __('User Management'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2 " rel="<?php echo Router::url(array('controller'=>'Student','action'=>'lessons')); ?>"><?php echo __('Lessons'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Student','action'=>'profile')); ?>"><?php echo __('Profile'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Student','action'=>'awaitingReview')); ?>"><?php echo __('Rate'); ?></a></li>

                        <li class="bg-main"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Teacher','action'=>'index')); ?>"><?php echo __('Teacher Management'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Teacher','action'=>'subjects')); ?>"><?php echo __('Subjects'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load1" rel="student-profile-tm-mysubjects.html"><?php echo __('My Subjects'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel=<?php echo Router::url(array('controller'=>'Teacher','action'=>'lessons')); ?>><?php echo __('Lessons'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Teacher','action'=>'profile')); ?>"><?php echo __('Profile'); ?></a></li>
                        <li class="bg-sub"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Teacher','action'=>'awaitingReview')); ?>"><?php echo __('Rate'); ?></a></li>

                        <li class="bg-main"><a href="#" class="load2" rel="<?php echo Router::url(array('controller'=>'Message','action'=>'index')); ?>"><?php echo __('Messages'); ?></a></li>

                        <li class="bg-main"><a href="#" class="load1" rel="student-profile-billinginfo.html"><?php echo __('Billing Info'); ?></a></li>
                        <li class="bg-main"><a href="#" class="load1" rel="student-profile-calender.html"><?php echo __('Calender'); ?></a></li>
                        <li class="bg-main"><a href="#" class="load1" rel="student-profile-tm-credits.html"><?php echo __('Credit'); ?></a></li>
                    </ul> <!-- /right-menu -->
                </div> <!-- /cont-span3 -->
                <div class="cont-span15 c-mar-message loadpage1" id="main-area">

                </div><!-- /loadpage -->
            </div> <!-- /cont-span12 -->
        </div> <!-- /row -->
    </div> <!-- /container-inner -->
</section>