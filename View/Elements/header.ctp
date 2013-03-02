<?php
if(!isSet($navButtonSelection)) {
    $navButtonSelection = array(
        'home'=>true,
        'board'=>false,
        'account'=>false,
        'request'=>false,
        'support'=>false,
        //'howItWorks'=>false,
    );
}
?>
<header>

    <!-- Navbar
   ================================================== -->
    <section class="navbar">
        <div class="navbar-inner">
            <button type="button" class="btn btn-navbar space6" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <h1><?php echo $this->Html->link('Universito', '/', array('title'=>'Home', 'escape'=>false, 'plugin'=>false)); ?></h1>
            <div class="nav-collapse">
                <ul class="nav">
                    <li<?php echo $navButtonSelection['home']       ? ' class="active"' : null; ?>><?php echo $this->Html->link(__('Home'), '/', array('title'=>'Home', 'escape'=>false, 'plugin'=>false)); ?></li>
                    <li<?php echo $navButtonSelection['board']      ? ' class="active"' : null; ?>><?php echo $this->Html->link('<span>'.__('Community').'</span>', array('controller'=>'forum', 'action'=>'/', 'plugin'=>false), array('title'=>'Board', 'escape'=>false)); ?></li>
                    <li<?php echo $navButtonSelection['account']    ? ' class="active"' : null; ?>><?php echo $this->Html->link('<span>'.__('Account').'</span>', array('controller'=>'Organizer', 'action'=>'/', 'plugin'=>false), $this->Layout->requireLogin(array('title'=>'Account', 'escape'=>false)) ); ?></li>
                    <li<?php echo $navButtonSelection['request']    ? ' class="active"' : null; ?>><?php echo $this->Html->link('<span>'.__('Wish List').'<span>', array('controller'=>'Requests', 'action'=>'/', 'plugin'=>false), array('title'=>'Wish List', 'escape'=>false)); ?></li>
                    <li<?php echo $navButtonSelection['support']    ? ' class="active"' : null; ?>><?php echo $this->Html->link('<span>'.__('Support').'<span>', array('controller'=>'Support', 'action'=>'contact', 'plugin'=>false), array('title'=>'Support', 'escape'=>false)); ?></li>
                </ul>
            </div><!--/.nav-collapse -->
        </div>
    </section>
</header>