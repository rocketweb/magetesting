#!/bin/bash
#script removes user from ftp userlist if it exist there
#TODO: decide whether combine it with ftp-user-add

#our FTP user list
FILE='/etc/vsftpd.chroot_list'
args=("$@")

#username
USERLOGIN=${args[0]}

#decides if we need to restart ftp server
RESTART=0


#and the code that handles it
if grep -q $USERLOGIN $FILE; then
    for line in `cat /etc/vsftpd.chroot_list`
    do
        if [ "$line" == "$USERLOGIN" ]; then
            RESTART=1
        else
            echo $line >> /etc/vsftpd.chroot_list_temp
        fi       
    done
    mv /etc/vsftpd.chroot_list_temp /etc/vsftpd.chroot_list
fi

#restart server if changes applied
if [ "$RESTART" == "1" ]; then
    /etc/init.d/vsftpd restart
fi