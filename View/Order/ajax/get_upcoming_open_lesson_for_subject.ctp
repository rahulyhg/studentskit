<?php
if($response['response']['results']) {

    foreach($response['response']['results'] AS $upcomingAvailableLesson) {
        echo $this->element('Order'.DS.'upcoming_lesson_div', array('upcomingAvailableLesson'=>$upcomingAvailableLesson['TeacherLesson'], 'first'=>false));
    }
}
?>