<?php echo $this->element('Panel'.DS.'menu');  ?>

<h3>Set Feedback</h3>
<?php 
	echo 'Lesson Name: ',$setReview['UserLesson']['name'],'<br />';
    echo 'Language: ',$setReview['UserLesson']['language'],'<br />'; //Ask user to set review in the same language as the lesson
	echo 'Date: ',$setReview['UserLesson']['datetime'],'<br />';
	echo 'teacher Name: ',$setReview['Student']['first_name'],' ',$setReview['Student']['last_name'],'<br />';
	echo $this->Html->link('Back', array('action'=>'awaitingReview'));
	echo '<br />';
	

    
echo $this->Form->create('UserLesson');
	echo $this->Form->hidden('user_lesson_id', array('value'=>$setReview['UserLesson']['user_lesson_id']));
	echo $this->Form->input('rating_by_teacher', array(
		'options' => array(1, 2, 3, 4, 5),
		'empty' => '(choose one)'
	));
	echo $this->Form->input('comment_by_teacher');
	echo $this->Form->submit('Save');
echo $this->Form->end();
?>