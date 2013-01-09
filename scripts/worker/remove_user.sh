#!/bin/bash
# there is just one argument and it is:
# - user login

args=("$@")
if [ $# -eq 1 ]; then
    #remove user, its group and home dir   
    sudo userdel -f ${args[0]}
    echo 'ok'
else
    echo 'error'
fi