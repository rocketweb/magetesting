#!/bin/bash
#script rebuilds 

#our phpmyadmin config
FILE='/etc/phpmyadmin/config.inc.php'
args=("$@")

#user permissions in format: 'deny user1 from all','deny from user2 from all'
USERPERMISSIONS=${args[0]}

configline="\$cfg['Servers'][\$i]['AllowDeny']['rules']"


#add our configuration file include
configline="include('/etc/phpmyadmin/magetesting-rules.php');"
CONFIGEXISTS="0"
for line in `cat $FILE`
do
    if [[ "$line" == *"$configline"* ]] 
    then
        CONFIGEXISTS="1"
    fi       
done

if [[ "$CONFIGEXISTS" == "0" ]]
then
    echo "include('/etc/phpmyadmin/magetesting-rules.php');" >> $FILE
fi

#create our rules
touch /etc/phpmyadmin/magetesting-rules.php

#rewrite config files with new ruleset
 echo "<?php" >> /etc/phpmyadmin/magetesting-rules.php_temp
 echo "\$cfg['Servers'][1]['AllowDeny']['order'] = 'deny,allow';" >> /etc/phpmyadmin/magetesting-rules.php_temp
 echo "\$cfg['Servers'][1]['AllowDeny']['rules'] = array($USERPERMISSIONS);" >> /etc/phpmyadmin/magetesting-rules.php_temp

mv /etc/phpmyadmin/magetesting-rules.php_temp /etc/phpmyadmin/magetesting-rules.php
