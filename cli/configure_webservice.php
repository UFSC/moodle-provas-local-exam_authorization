<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
// Este projeto é financiado pela
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/)
// e é distribuído sob os termos da "GNU General Public License",
// como publicada pela "Free Software Foundation".

/**
 * Script de configuração.
 *
 * @package    local_exam_remote
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$rolename = 'wsprovas';
$username = 'wsprovas';
$email    = 'wsprovas@qqq.zzz.br';
$password = 'Vy%;' . rand(100000, 999999);

$webservice           = 'rest';
$service              = 'Moodle Exam';
$token_ip_restriction = ''; // preencha com um ip ou máscara de ip para restringir o acesso

$context = context_system::instance();

// ----------------------------------------------------------------------------------
// Cria usuário para webservice

if(!$user = $DB->get_record('user', array('username'=>$username))) {
    $user = new stdclass();
    $user->username  = $username;
    $user->firstname = 'Webservice';
    $user->lastname  = 'Provas';
    $user->email     = $email;
    $user->password  = $password;
    $user->auth      = 'manual';
    $user->confirmed = 1;
    $user->policyagreed = 1;
    $user->mnethostid   = $CFG->mnet_localhost_id;
    $user->id = user_create_user($user, false);
}

// ----------------------------------------------------------------------------------
// Cria papel para webservice

if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>$rolename))) {
    $roleid = create_role('Webservice Provas', $rolename, 'Webservice Provas');
    set_role_contextlevels($roleid, array(CONTEXT_SYSTEM));
}
assign_capability('webservice/rest:use', CAP_ALLOW, $roleid, $context->id);

// ----------------------------------------------------------------------------------
// Atribui papel ao usuário no contexto global

role_assign($roleid, $user->id, $context->id);

// ----------------------------------------------------------------------------------
// Habilita o uso de webservices

$configs = array(
                 array('enablewebservices', true),
                );

foreach($configs AS $cfg) {
    if(count($cfg) == 2) {
        set_config($cfg[0], $cfg[1]);
    } else {
        set_config($cfg[0], $cfg[1], $cfg[2]);
    }
}

// ----------------------------------------------------------------------------------
// Ativa protocolo

$active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
if(!in_array($webservice, $active_webservices)) {
    $active_webservices[] = $webservice;
    $active_webservices = array_unique($active_webservices);
    set_config('webserviceprotocols', implode(',', $active_webservices));
}

// ----------------------------------------------------------------------------------
// Adicionando usuário ao serviço

if(!$ext_service = $DB->get_record('external_services', array('name'=>$service), 'id, name')) {
    echo "*** NÃO LOCALIZADO SERVIÇO: {$service}\n";
    exit;
}

$webservicemanager = new webservice();
$users = $webservicemanager->get_ws_authorised_users($ext_service->id);
if(!isset($users[$user->id])) {
    $serviceuser = new stdClass();
    $serviceuser->externalserviceid = $ext_service->id;
    $serviceuser->userid = $user->id;
    $webservicemanager->add_ws_authorised_user($serviceuser);
}

// ----------------------------------------------------------------------------------
// Gerando token

if($token = $DB->get_record('external_tokens', array('userid'=>$user->id, 'externalserviceid'=>$ext_service->id))) {
    $msg_token = "Token = {$token->token}";
} else {
    $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $ext_service->id, $user->id, $context, 0, $token_ip_restriction);
    $newtoken = new stdclass();
    $newtoken->id = $DB->get_field('external_tokens', 'id', array('token'=>$token));
    $newtoken->creatorid = 2;
    $DB->update_record('external_tokens', $newtoken);
    $msg_token = "New Token = {$token}";
}

$description = $DB->get_field('course', 'fullname', array('id' => 1));
echo "\n";
echo 'URL:   ' . $CFG->wwwroot . "\n";
echo 'Descr: ' . $description;
echo "\n" . $msg_token . "\n\n";
