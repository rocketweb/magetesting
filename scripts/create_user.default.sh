#!/bin/bash
# Shell script to create:
# - system user
# - ftp account
# - ssh access
# All arguments need to be passed. And they are:
# - user login
# - user password
# - user salt (to generate passwords with)
# - system home directory (usually /home)w


#Config section
MyUSER="mysql-username"     # USERNAME
MyPASS="mysql-pasword"       # PASSWORD
MyHOST="mysql-host"          # Hostname
MYSQL="$(which mysql)"


#And the script itself, nothing to change here
args=("$@")
#echo arguments to the shell

if [ $# -eq 4 ]; then
    pass=$(perl -e 'print crypt(${args[1]}, ${args[2]})' ${args[1]})    
      sudo groupadd -f ${args[0]}
      sudo useradd -p $pass -m ${args[0]} -g ${args[0]}
      sudo usermod -G ${args[0]} www-data
      sudo mkdir ${args[3]}/${args[0]}
      sudo mkdir ${args[3]}/${args[0]}/public_html
      sudo chmod 774 -R ${args[3]}/${args[0]}
      sudo chown -R ${args[0]} ${args[3]}/${args[0]}
      sudo chgrp -R ${args[0]} ${args[3]}/${args[0]}
      
    sqlQuery="UPDATE user SET has_system_account = 1 WHERE system_account_name = '"${args[0]}"';"

    mysql --user=$MyUSER -h $MyHOST --password=$MyPASS magentointegration -e "$sqlQuery"
    
else
    echo 'wrong number of arguments'
fi