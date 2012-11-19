#!/bin/bash
#script adds user to allowed ftp users
#TODO: decide whether combine it with ftp-user-remove

#our FTP user list
FILE='/etc/vsftpd.chroot_list'
args=("$@")

#username
USERLOGIN=${args[0]}

#decides if we need to restart ftp server
RESTART=0

if grep -q $USERLOGIN $FILE; then
    echo "Found"
else
    echo $USERLOGIN  >> $FILE
    RESTART=1
fi

#Restart Server if changes applied
if [ "$RESTART" == "1" ]; then
    /etc/init.d/vsftpd restart
fi