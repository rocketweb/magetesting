#!/bin/bash
# Shell script to create:
# - system user
# - ssh access(should work as long as ssh uses PAM)
# All arguments need to be passed. And they are:
# - user login
# - user password
# - user salt (to generate passwords with)
# - system home directory (usually /home)

#Config section
MyUSER="mysql-username"     # USERNAME
MyPASS="mysql-pasword"       # PASSWORD
MyHOST="mysql-host"          # Hostname
MyDBNAME="mysql-db" #Database Name for magetesting app

#And the script itself, nothing to change here
args=("$@")
if [ $# -eq 4 ]; then

    #create group if not exists  
    if [ !`egrep -i "^${args[0]}" /etc/group > /dev/null` ]; then
        sudo groupadd -f ${args[0]}
    fi

    #create user if not exists
    if [ !`egrep -i "^$username" /etc/passwd > /dev/null` ]; then
        sudo useradd -p `mkpasswd ${args[1]}` -m ${args[0]} -g ${args[0]}
        sqlQuery="UPDATE user SET has_system_account = 1 WHERE system_account_name = '"${args[0]}"';"
        mysql --user=$MyUSER -h $MyHOST --password=$MyPASS $MyDBNAME -e "$sqlQuery"
    fi

    sudo usermod -G ${args[0]} www-data

    #create user home dir
    if [ ! -d "${args[3]}/${args[0]}" ]; then
        sudo mkdir ${args[3]}/${args[0]}
    fi

    #create users public_html dir
    if [ ! -d "${args[3]}/${args[0]}/public_html" ]; then
        sudo mkdir ${args[3]}/${args[0]}/public_html
    fi

    sudo chmod 774 -R ${args[3]}/${args[0]}

    #others need to have execute in order for symlinks to work
    sudo chmod a+x ${args[3]}/${args[0]}
    sudo chmod a+x ${args[3]}/${args[0]}/public_html

    sudo chown -R ${args[0]} ${args[3]}/${args[0]}
    sudo chgrp -R ${args[0]} ${args[3]}/${args[0]}
    
else
    echo 'wrong number of arguments'
fi