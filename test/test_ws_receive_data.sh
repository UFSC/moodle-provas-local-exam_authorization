#!/bin/bash
#set -x

# Script with basic test for the webservice 'local_exam_authorization_receive_data'.
# Please configure the file 'webservice_config.sh' before use this.

source webservice_config.sh

webservice="local_exam_authorization_receive_data"
param3="wsfunction=$webservice"

data1="exam_client_ip=192.168.1.1"
data2="exam_client_network=192.168.1.0/24"

curl -k -F "$param1" -F "$param2" -F "$param3" -F "$data1" -F "$data2" "$url"
