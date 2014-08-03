<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => '\local_exam_authorization\authorization::user_loggedin',
        'priority'    => 999,
    ),

);
