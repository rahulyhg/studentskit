<section class="container">
    <div class="container-inner">
<?php
    //$showAds
    echo $this->Html->scriptBlock($this->Watchitoo->initJS($meetingSettings['meeting_id'], $meetingSettings), array('inline'=>(isSet($blank)), 'safe'=>false));
?>

        <div class="row">
            <div class="lesson-box pull-left">
                <h3 class="radius1"><!--5:30 - --><strong><?php echo $lessonName; ?></strong></h3>
                <div class="lesson-box-content file-lesson no-padding-and-border">
                    <?php echo $this->Watchitoo->embedMeetingJS($meetingSettings['meeting_id'], $meetingSettings); ?>
                </div>
            </div>

<?php
    if(!isSet($blank) || !$blank) {
?>

            <div class="search-all2 file-linkinput-bar">
                <div class="black-line-approv wid-one"></div>
                <ul class="booking-nav">
                    <li class="active"><a href="#">File</a></li>
                    <li class="c-mar3"><a href="#">Test</a></li>
                </ul>
            </div>
            <div class="fullwidth pull-left space6">
                <h4>Test</h4>
                <p class="pull-left file-linkinput-bar">
                    <span class="pull-left">Test name goes here</span>
                    <a data-toggle="modal" href="#myModal" class="btn-blue text-color pull-right">Start <i class="iconSmall-add-arrow"></i></a>
                </p><div id="myModal" class="modal hide fade" style="display: none; ">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h3>Test name goes here</h3>
                </div> <!-- /modal-header -->
                <div class="modal-body">
                    <form class="sk-form">
                        <fieldset>
                            <ol class="testform">
                                <li><span class="pull-left">1</span><p class="test-questn">What was the first programming language?</p>
                                    <div class="pull-left">
                                        <ul class="space30">
                                            <li><input type="radio" name=""> <p class="test-option">Binary</p></li>
                                            <li><input type="radio" name=""> <p class="test-option">C</p></li>
                                            <li><input type="radio" name=""> <p class="test-option">Assembly</p></li>
                                        </ul>
                                    </div>
                                </li>
                                <li><span class="pull-left">2</span><p class="test-questn">When the first laptop was invented?</p>
                                    <div class="pull-left">
                                        <ul class="space30">
                                            <li><input type="radio" name=""> <p class="test-option">1950</p></li>
                                            <li><input type="radio" name=""> <p class="test-option">1955</p></li>
                                            <li><input type="radio" name=""> <p class="test-option">1976</p></li>
                                        </ul>
                                    </div>
                                </li>
                            </ol>
                            <div class="control-group">
                                <label class="control-label"></label>
                                <div class="control  control1 test-startbtn">
                                    <button class="btn-blue" type="button">Start <i class="iconSmall-add-arrow"></i></button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div> <!-- /modal-body -->
            </div>
<?php
    }
?>
        </div>
    </div>
</section>