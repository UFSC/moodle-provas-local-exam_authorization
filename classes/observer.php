<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/exam_authorization/classes/IPTools.php');
require_once($CFG->dirroot.'/local/exam_authorization/classes/exam_authorization.php');
require_once($CFG->libdir.'/filelib.php');

class local_exam_authorization_observer {

    protected static $user;
    protected static $remote_courses;
    protected static $role;

    public static function user_loggedin(\core\event\user_loggedin $event) {
        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        self::$user = new StdClass();
        self::$user->id = $event->userid;
        self::$user->username = $event->other['username'];

        self::check_permissions();
        self::sync_enrols();

        return true;
    }

    protected static function check_permissions() {
        global $SESSION;

        if (isset($SESSION->exam_access_key)) {
            self::check_student_permissions();
        } else {
            if(!self::check_teacher_permissions(false)) {
                self::check_monitor_permissions();
            }
        }
        $SESSION->exam_remote_courses = self::$remote_courses;
        $SESSION->exam_role = self::$role;
    }

    protected static function check_student_permissions() {
        global $DB, $SESSION;

        \local\exam_authorization::check_ip_range_student();
        \local\exam_authorization::check_version_header();
        \local\exam_authorization::check_ip_header();
        \local\exam_authorization::check_network_header();

        if (! $access_key = $DB->get_record('exam_access_keys', array('access_key' => $SESSION->exam_access_key))) {
            \local\exam_authorization::print_error('access_key_unknown');
        }
        if ($access_key->timecreated + $access_key->timeout*60 < time()) {
            \local\exam_authorization::print_error('access_key_timeout');
        }
        \local\exam_authorization::check_client_host($access_key);

        $local_course = $DB->get_record('course', array('id'=>$access_key->courseid), 'id, shortname, visible');
        if($local_course && $local_course->visible) {
            list($identifier, $shortname) = explode('_', $local_course->shortname, 2);
            if($remote_course = \local\exam_authorization::get_remote_course($identifier, $shortname, 'student')) {
                $remote_course->local_course = $local_course;
                self::$remote_courses[$identifier] = array($remote_course);
            } else {
                \local\exam_authorization::print_error('no_student_permission');
            }
        } else {
            \local\exam_authorization::print_error('course_not_avaliable');
        }

        unset($SESSION->exam_access_key);
        self::$role = 'student';
    }

    protected static function check_teacher_permissions($print_error=true) {
        if(\local\exam_authorization::check_ip_range_teacher($print_error)) {
            self::$remote_courses = \local\exam_authorization::get_remote_courses('teacher');
            if(empty(self::$remote_courses)) {
                return \local\exam_authorization::print_error('no_teacher_permission', $print_error);
            } else {
                self::$role = 'teacher';
                return true;
            }
        } else {
            return false;
        }
    }

    protected static function check_monitor_permissions() {
        global $DB;

        self::$remote_courses = array();
        foreach(\local\exam_authorization::get_remote_courses('monitor') AS $identifier=>$courses) {
            foreach($courses AS $rc) {
                if($local_course = $DB->get_record('course', array('shortname'=>$rc->local_shortname), 'id, shortname, visible') && $local_course->visible) {
                    $rc->local_course = $local_course;
                    self::$remote_courses[$identifier][] = $rc;
                }
            }
        }

        if(empty(self::$remote_courses)) {
            \local\exam_authorization::print_error('no_access_permission');
        } else {
            self::$role = 'monitor';
        }
    }

    protected static function sync_enrols() {
        global $DB;

        // suspend all user enrolments
        $ues = $DB->get_records('user_enrolments', array('userid'=>self::$user->id, 'status'=>ENROL_USER_ACTIVE));
        foreach($ues AS $ue) {
            if($enrol = $DB->get_record('enrol', array('id'=>$ue->enrolid))) {
                if($plugin = enrol_get_plugin($enrol->enrol)) {
                    $plugin->update_user_enrol($enrol, self::$user->id, ENROL_USER_SUSPENDED);
                }
            }
        }

        $roleid = 0;
        if(self::$role == 'student') {
            if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>'student'))) {
                \local\exam_authorization::print_error('role_unknown', 'student');
            }
        } else if(self::$role == 'teacher') {
            if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'))) {
                \local\exam_authorization::print_error('role_unknown', 'editingteacher');
            }
        } else {
            return;
        }

        // activate only the necessary enrolments
        if (!enrol_is_enabled('manual')) {
            \local\exam_authorization::print_error('enrol_not_active');
        }
        if (!$enrol = enrol_get_plugin('manual')) {
            \local\exam_authorization::print_error('enrol_not_active');
        }

        foreach(self::$remote_courses AS $identifier=>$remote_courses) {
            foreach($remote_courses AS $c) {
                if(isset($c->local_course)) {
                    $course = $c->local_course;
                } else {
                    $course = $DB->get_record('course', array('shortname'=>$c->local_shortname), 'id, shortname, visible');
                }
                if($course) {
                    $c->local_course = $course;
                    $instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$course->id), 'id ASC');
                    if($instance = reset($instances)) {
                        if($instance->status == ENROL_INSTANCE_DISABLED) {
                            $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);
                        }
                    } else {
                        $id = $enrol->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id'=>$id));
                    }
                    $enrol->enrol_user($instance, self::$user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
                }
            }
        }
    }

}
