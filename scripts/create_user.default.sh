#!/bin/bash
# Shell script to create:
# - system user
# - ftp account(not yet implemented)
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
      sudo groupadd -f ${args[0]}
      sudo useradd -p `mkpasswd ${args[1]}` -m ${args[0]} -g ${args[0]}
      sudo usermod -G ${args[0]} www-data
      sudo mkdir ${args[3]}/${args[0]}
      sudo mkdir ${args[3]}/${args[0]}/public_html
      sudo chmod 774 -R ${args[3]}/${args[0]}
      
      #others need to have execute in order for symlinks to work
      sudo chmod o+x ${args[3]}/${args[0]}
      sudo chmod o+x ${args[3]}/${args[0]}/public_html
      
      sudo chown -R ${args[0]} ${args[3]}/${args[0]}
      sudo chgrp -R ${args[0]} ${args[3]}/${args[0]}
    sqlQuery="UPDATE user SET has_system_account = 1 WHERE system_account_name = '"${args[0]}"';"
    mysql --user=$MyUSER -h $MyHOST --password=$MyPASS $MyDBNAME -e "$sqlQuery"
else
    echo 'wrong number of arguments'
fi