<?php
//This is for the topnav view, when user click on "load more"
echo $this->element('Topnav'.DS.'threads', array('threads'=>$response['response']['threads']));
?>