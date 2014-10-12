<?php
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__).'/../../../config.php');

$systemcontext = context_system::instance();

echo "=> Creating roles:\n";
if(!$DB->record_exists('role', array('shortname'=>'proctor'))) {
    $roleid = create_role('Responsáveis por aplicar provas', 'proctor', 'Responsáveis por aplicar provas');
    set_role_contextlevels($roleid, array(CONTEXT_COURSE));
    assign_capability('block/exam_actions:conduct_exam', CAP_ALLOW, $roleid, $systemcontext->id);
    echo "      - role 'proctor'\n";
}

if(!$DB->record_exists('role', array('shortname'=>'monitor'))) {
    $roleid = create_role('Monitoram provas', 'monitor', 'Monitoram provas');
    set_role_contextlevels($roleid, array(CONTEXT_COURSE));
    assign_capability('block/exam_actions:monitor_exam', CAP_ALLOW, $roleid, $systemcontext->id);
    echo "      - role 'monitor'\n";
}

echo "=> Removing permitions to 'editingteacher' assign, override and switch roles\n";
if($roleid = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'))) {
    $DB->delete_records('role_allow_assign',   array('roleid'=>$roleid));
    $DB->delete_records('role_allow_override', array('roleid'=>$roleid));
    $DB->delete_records('role_allow_switch',   array('roleid'=>$roleid));
}

$del_caps = array('student'=>array(
                          'moodle/blog:manageexternal',
                          'moodle/blog:search',
                          'moodle/blog:view',
                          'moodle/user:readuserblogs',
                          'moodle/user:readuserposts',
                          'enrol/self:unenrolself',
                          'gradereport/overview:view',
                          'gradereport/user:view',
                          'moodle/comment:post',
                          'moodle/comment:view',
                          'moodle/course:isincompletionreports',
                          'moodle/course:viewscales',
                          'moodle/portfolio:export',
                          'moodle/course:viewparticipants',
                          'moodle/rating:viewany',
                          'moodle/user:viewdetails',
                          'mod/assign:exportownsubmission',
                          'mod/chat:chat',
                          'mod/chat:readlog',
                          'mod/forum:allowforcesubscribe',
                          'mod/forum:createattachment',
                          'mod/forum:deleteownpost',
                          'mod/forum:exportownpost',
                          'mod/forum:replypost',
                          'mod/forum:startdiscussion',
                          'mod/forum:viewdiscussion',
                          'mod/forum:viewrating',
                          'mod/wiki:createpage',
                          'mod/wiki:editcomment',
                          'mod/wiki:editpage',
                          'mod/wiki:viewcomment',
                          'block/online_users:viewlist',
                          ),
                  'user'=>array(
                          'block/admin_bookmarks:myaddinstance',
                          'block/badges:myaddinstance',
                          'block/calendar_month:myaddinstance',
                          'block/calendar_upcoming:myaddinstance',
                          'block/comments:myaddinstance',
                          'block/community:myaddinstance',
                          'block/glossary_random:myaddinstance',
                          'block/mentees:myaddinstance',
                          'block/messages:myaddinstance',
                          'block/mnet_hosts:myaddinstance',
                          'block/myprofile:myaddinstance',
                          'block/navigation:myaddinstance',
                          'block/news_items:myaddinstance',
                          'block/online_users:myaddinstance',
                          'block/private_files:myaddinstance',
                          'block/rss_client:myaddinstance',
                          'block/settings:myaddinstance',
                          'block/tags:myaddinstance',
                          'moodle/blog:create',
                          'moodle/blog:manageexternal',
                          'moodle/blog:search',
                          'moodle/blog:view',
                          'moodle/course:request',
                          'moodle/portfolio:export',
                          'moodle/site:sendmessage',
                          'moodle/tag:create',
                          'moodle/tag:edit',
                          'moodle/tag:flag',
                          'moodle/user:changeownpassword',
                          'moodle/user:editownmessageprofile',
                          'moodle/user:editownprofile',
                          'moodle/user:manageownblocks',
                          'moodle/user:manageownfiles',
                          'moodle/webservice:createmobiletoken',
                          'moodle/badges:manageownbadges',
                          'moodle/badges:viewotherbadges',
                          'moodle/badges:earnbadge',
                          'moodle/badges:viewbadges',
                          'moodle/calendar:manageownentries',
                          'moodle/comment:post',
                          'moodle/comment:view',
                          'moodle/rating:rate',
                          'moodle/rating:view',
                          'moodle/rating:viewall',
                          'moodle/rating:viewany',
                          'repository/dropbox:view',
                          'repository/equella:view',
                          'repository/alfresco:view',
                          'repository/flickr:view',
                          'repository/flickr_public:view',
                          'repository/googledocs:view',
                          'repository/merlot:view',
                          'repository/picasa:view',
                          'repository/s3:view',
                          'repository/skydrive:view',
                          'repository/url:view',
                          'repository/wikimedia:view',
                          'repository/youtube:view',
                          'block/online_users:viewlist',
                          ),
                  'editingteacher'=>array(
                          'moodle/blog:manageentries',
                          'moodle/blog:manageexternal',
                          'moodle/blog:search',
                          'moodle/blog:view',
                          'moodle/community:add',
                          'moodle/community:download',
                          'moodle/portfolio:export',
                          'moodle/site:doclinks',
                          'moodle/site:readallmessages',
                          'moodle/tag:editblocks',
                          'moodle/tag:manage',
                          'moodle/user:readuserblogs',
                          'moodle/user:readuserposts',
                          'enrol/cohort:config',
                          'enrol/guest:config',
                          'enrol/manual:enrol',
                          'enrol/manual:manage',
                          'enrol/manual:unenrol',
                          'enrol/meta:config',
                          'enrol/paypal:manage',
                          'enrol/self:config',
                          'enrol/self:manage',
                          'enrol/self:unenrol',
                          'gradeexport/ods:view',
                          'gradeexport/txt:view',
                          'gradeexport/xls:view',
                          'gradeexport/xml:view',
                          'gradeimport/csv:view',
                          'mod/assignment:addinstance',
                          'mod/chat:addinstance',
                          'mod/forum:addinstance',
                          'mod/wiki:addinstance',
                          'moodle/badges:awardbadge',
                          'moodle/badges:configurecriteria',
                          'moodle/badges:configuredetails',
                          'moodle/badges:configuremessages',
                          'moodle/badges:createbadge',
                          'moodle/badges:deletebadge',
                          'moodle/badges:viewawarded',
                          'moodle/calendar:managegroupentries',
                          'moodle/cohort:view',
                          'moodle/comment:delete',
                          'moodle/comment:post',
                          'moodle/comment:view',
                          'moodle/course:bulkmessaging',
                          'moodle/course:changecategory',
                          'moodle/course:changefullname',
                          'moodle/course:changeidnumber',
                          'moodle/course:changeshortname',
                          'moodle/course:changesummary',
                          'moodle/course:enrolconfig',
                          'moodle/course:markcomplete',
                          'moodle/course:useremail',
                          'moodle/grade:import',
                          'moodle/grade:lock',
                          'moodle/notes:manage',
                          'moodle/notes:view',
                          'moodle/role:assign',
                          'moodle/role:review',
                          'moodle/role:safeoverride',
                          'moodle/role:switchroles',
                          'mod/chat:chat',
                          'mod/chat:deletelog',
                          'mod/chat:readlog',
                          'mod/chat:exportparticipatedsession',
                          'mod/chat:exportsession',
                          'mod/forum:addnews',
                          'mod/forum:addquestion',
                          'mod/forum:allowforcesubscribe',
                          'mod/forum:createattachment',
                          'mod/forum:deleteanypost',
                          'mod/forum:deleteownpost',
                          'mod/forum:editanypost',
                          'mod/forum:exportdiscussion',
                          'mod/forum:exportownpost',
                          'mod/forum:exportpost',
                          'mod/forum:managesubscriptions',
                          'mod/forum:movediscussions',
                          'mod/forum:postwithoutthrottling',
                          'mod/forum:rate',
                          'mod/forum:replynews',
                          'mod/forum:replypost',
                          'mod/forum:splitdiscussions',
                          'mod/forum:startdiscussion',
                          'mod/forum:viewallratings',
                          'mod/forum:viewanyrating',
                          'mod/forum:viewdiscussion',
                          'mod/forum:viewhiddentimedposts',
                          'mod/forum:viewsubscribers',
                          'mod/forum:viewqandawithoutposting',
                          'mod/forum:viewrating',
                          'mod/wiki:createpage',
                          'mod/wiki:editcomment',
                          'mod/wiki:editpage',
                          'mod/wiki:managecomment',
                          'mod/wiki:managefiles',
                          'mod/wiki:managewiki',
                          'mod/wiki:overridelock',
                          'mod/wiki:viewcomment',
                          'mod/wiki:viewpage',
                          ),
            );
echo "=> Unassigning capabilities:\n";
foreach($del_caps AS $role=>$caps) {
    echo "      - from: {$role}\n";
    if($roleid = $DB->get_field('role', 'id', array('shortname'=>$role))) {
        foreach($caps AS $cap) {
            unassign_capability($cap, $roleid);
        }
    }
}

$configs = array(
                 array('auth', implode(',', array('manual', 'nologin'))),
                 array('enrol_plugins_enabled', 'manual'),

                 array('defaulthomepage', 1),
                 array('navshowfrontpagemods', false),
                 array('navadduserpostslinks', false),
                 array('enablewebservices', true),
                 array('forcelogin', false),
                 array('forceloginforprofiles', true),
                 array('forceloginforprofileimage', true),
                 array('profilesforenrolledusersonly', true),
                 array('cronclionly', true),
                 array('disableuserimages', true),
                 array('navsortmycoursessort', 'shortname'),
                 array('showuseridentity', ''),

                 array('enablegravatar', false),
                 array('allowattachments', false),
                 array('enableoutcomes', false),
                 array('enablecourserequests', false),
                 array('usecomments', false),
                 array('usetags', false),
                 array('enablenotes', false),
                 array('enableportfolios', false),
                 array('messaging', false),
                 array('enablestats', false),
                 array('enablerssfeeds', false),
                 array('enableblogs', false),
                 array('enablecompletion', false),
                 array('enableavailability', false),
                 array('enableplagiarism', false),
                 array('enablebadges', false),
                 array('opentogoogle', false),
                 array('gradepublishing', false),
                 array('registerauth', false),
                 array('guestloginbutton', false),
                 array('authpreventaccountcreation', true),
                 array('allowuserblockhiding', false),
                 array('enabledevicedetection', false),
                 array('allowguestmymoodle', false),
                 array('navshowcategories', false),
                 array('autologinguests', false),

                 array('grade_displaytype', 1),
                 array('grade_decimalpoints', 1),

                 array('ackup_general_comments', false, 'backup'),
                 array('backup_general_blocks', false, 'backup'),
                 array('backup_general_filters', false, 'backup'),
                 array('backup_general_badges', false, 'backup'),
                 array('backup_auto_badges', false, 'backup'),
                 array('backup_general_users', true, 'backup'),
                 array('backup_general_anonymize', false, 'backup'),
                 array('backup_general_userscompletion', false, 'backup'),
                 array('backup_general_logs', false, 'backup'),
                 array('backup_general_histories', false, 'backup'),
                 array('visible', false, 'moodlecourse'),
                 array('format', 'topics', 'moodlecourse'),
                 array('numsections', 2, 'moodlecourse'),
                 array('hiddensections', 1, 'moodlecourse'),
                 array('showgrades', 0, 'moodlecourse'),
                 array('showreports', 0, 'moodlecourse'),
                 array('enablecompletion', 0, 'moodlecourse'),
                 array('enablecompletion', 0, 'moodlecourse'),

                 array('field_lock_firstname', 'locked', 'auth/manual'),
                 array('field_lock_lastname', 'locked', 'auth/manual'),
                 array('field_lock_email', 'locked', 'auth/manual'),
                 array('field_lock_idnumber', 'locked', 'auth/manual'),

                );

$modules = array('chat',
                 'feedback',
                 'imscp',
                 'lti',
                 'wiki',
                );

$blocks = array(
                'activity_modules',
                'admin_bookmarks',
                'badges',
                'blog_menu',
                'blog_recent',
                'blog_tags',
                'calendar_month',
                'calendar_upcoming',
                'comments',
                'community',
                'completionstatus',
                'course_overview',
                'course_summary',
                'feedback',
                'glossary_random',
                'mentees',
                'messages',
                'myprofile',
                'mnet_hosts',
                'news_items',
                'private_files',
                'quiz_results',
                'recent_activity',
                'rss_client',
                'search_forums',
                'section_links',
                'selfcompletion',
                'social_activities',
                'tag_flickr',
                'tag_youtube',
                'tags',
                );

echo "=> desabling message processors\n";
$DB->set_field('message_processors', 'enabled', '0');      // Disable output

echo "=> hidding some modules:\n";
foreach($modules AS $mod_name) {
    echo "      - {$mod_name}\n";
    if ($module = $DB->get_record("modules", array("name"=>$mod_name))) {
        $DB->set_field("modules", "visible", "0", array("id"=>$module->id));

        $sql = "UPDATE {course_modules}
                   SET visibleold=visible, visible=0
                 WHERE module=?";
        $DB->execute($sql, array($module->id));
    }
}

echo "=> hidding some blocks:\n";
foreach($blocks AS $blk_name) {
    echo "      - {$blk_name}\n";
    if ($block = $DB->get_record('block', array('name'=>$blk_name))) {
        $DB->set_field('block', 'visible', '0', array('id'=>$block->id));
    }
}

echo "=> changing global settings:\n";
foreach($configs AS $cfg) {
    if(count($cfg) == 2) {
        set_config($cfg[0], $cfg[1]);
    } else {
        set_config($cfg[0], $cfg[1], $cfg[2]);
    }
}

echo "=> end\n";
