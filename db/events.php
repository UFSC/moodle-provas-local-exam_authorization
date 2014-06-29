<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => 'local_exam_authorization_observer::user_loggedin',
        'includefile' => '/local/exam_authorization/classes/observer.php',
        'priority'    => 999,
    ),

);
