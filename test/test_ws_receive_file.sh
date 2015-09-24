#!/bin/bash
#set -x

# Script with basic test for the webservice 'local_exam_authorization_receive_file'.
# Please configure the file 'webservice_config.sh' before use this.

source webservice_config.sh

# Generate a simple file for upload
file="test.bin"
dd if=/dev/urandom of=$file bs=500k count=3 >/dev/null 2>&1

webservice="local_exam_authorization_receive_file"
param3="wsfunction=$webservice"

data1="exam_client_livecd_version=3.2"
data2="exam_client_livecd_build=20150922"
data3="exam_client_ip=192.168.1.1"
data4="exam_client_network=192.168.1.0/24"
data5="exam_client_user_email=test@example.com"
data6="exam_client_user_description=Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."

curl -k -F "$param1" -F "$param2" -F "$param3" \
     -F "$data1" -F "$data2" -F "$data3" -F "$data4" -F "$data5" -F "$data6" -F "file=@$file" "$url"

rm -f $file
