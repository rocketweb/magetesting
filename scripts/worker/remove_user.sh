#!/bin/bash
# there is just one argument and it is:
# - user login
args=("$@")
if [ $# -eq 1 ]; then

    if [ `egrep -i "^$username" /etc/passwd > /dev/null` ]; then
        sudo userdel -f ${args[0]}
    fi

    if [ -d "/home/${args[0]}" ]; then
        sudo rm -R /home/${args[0]}
    fi    
    
    echo 'ok'
else
    echo 'error'
fi