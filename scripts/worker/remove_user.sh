#!/bin/bash
# there is just one argument and it is:
# - user login
args=("$@")
if [ $# -eq 1 ]; then
    sudo userdel -f ${args[0]}
    sudo rm -R /home/${args[0]}
    echo 'ok'
else
    echo 'error'
fi