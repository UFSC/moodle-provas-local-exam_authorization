#!/bin/bash
#set -x

# Configuration script for the others tests, please configure your Moodle URL and webservice token below.

moodle_provas_url='https://localhost/~juliao/moodle29provas'
moodle_webservices_token='a443f7870ef8cf5ef3e12f1150d8b287'

url="${moodle_provas_url}/webservice/rest/server.php"
param1="wstoken=$moodle_webservices_token"
param2="moodlewsrestformat=json"
