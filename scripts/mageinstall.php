<?php

/*
 * IF YOU WILL NEED EXTRA OPTIONS, USE THIS
 * 
  try {
  $opts = new Zend_Console_Getopt(
  array(
  'help' => 'Displays help.',
  'hello' => 'try it !',
  'downgrade_expired_users' => 'disables users with expired active to date',
  'restore_downgraded_users' => 'restores downgraded users if we noticed their payments',
  'magentoinstall' => 'handles magento from install queue',
  'magentoremove' => 'handles magento from remove queue',
  )
  );

  $opts->parse();
  } catch (Zend_Console_Getopt_Exception $e) {
  exit($e->getMessage() . "\n\n" . $e->getUsageMessage());
 */

$fp = fopen("install_lock.txt", "c");

if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
    include 'init.console.php';

    $output = '';
    $select = new Zend_Db_Select($db);

//check if any script is currently being installed
    $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id', array('version'))
            ->joinLeft('user', 'queue.user_id = user.id', array('email'))
            ->where('queue.status =?', 'installing')
            ->where('queue.type = ? ','clean');

    $query = $sql->query();
    $queueElement = $query->fetch();

    if ($queueElement) {
        //something is currently installed, abort
        $message = 'Another installation in progress, aborting';
        echo $message;
        $log->log($message, LOG_INFO);
        flock($fp, LOCK_UN); // release the lock
        exit;
    }

    $select = new Zend_Db_Select($db);
    $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id', array('version', 'sample_data_version'))
            ->joinLeft('user', 'queue.user_id = user.id', array('email', 'login', 'group', 'firstname', 'lastname', 'has_system_account'))
            ->where('queue.status =?', 'pending')
            ->where('user.status =?', 'active')
            ->where('queue.type = ? ','clean');

    $query = $sql->query();
    $queueElements = $query->fetchAll();


    if (!$queueElements) {
        $message = 'Nothing in pending queue';
        echo $message;
        $log->log($message, LOG_INFO, ' ');
        flock($fp, LOCK_UN); // release the lock
        exit;
    }

    foreach ($queueElements as $queueElement) {

        $db->update('queue', array('status' => 'installing'), 'id=' . $queueElement['id']);

        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $queueElement['login'] . '_' . $queueElement['domain'] . '.log');
        $log = new Zend_Log($writer);

        $dbhost = $config->resources->db->params->host; //fetch from zend config
        $dbname = $queueElement['login'] . '_' . $queueElement['domain'];
        $dbuser = $queueElement['login']; //fetch from zend config
        $dbpass = substr(sha1($config->magento->usersalt . $config->magento->userprefix . $queueElement['login']), 0, 10); //fetch from zend config
        
        $filePrefix = array(
            'CE' => 'magento',
            'EE' => 'enterprise',
            'PE' => 'professional',
        );

        $instanceFolder = $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $dbuser . '/public_html';
        if ($queueElement['has_system_account'] == 0) {
            $db->update('queue', array('status' => 'installing-user'), 'id=' . $queueElement['id']);
            $db->update('user', array('system_account_name' => $config->magento->userprefix . $dbuser), 'id=' . $queueElement['user_id']);

            /** WARNING!
             * in order for this to work, when you run this (console.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php mageinstall.php
             *
             */
            $systempass = substr(sha1($config->magento->usersalt . $config->magento->userprefix . $queueElement['login']), 10, 10); //fetch from zend config
            exec('sudo ./create_user.sh ' . $config->magento->userprefix . $dbuser . ' ' . $systempass . ' ' . $config->magento->usersalt . ' ' . $config->magento->systemHomeFolder, $output);
            $message = var_export($output, true);
            $log->log($message, LOG_DEBUG);
            unset($output);

            if('free-user' != $queueElement['group']) {
                /* send email with account details start */
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
                // assign valeues
                $html->assign('ftphost', $config->magento->ftphost);
                $html->assign('ftpuser', $config->magento->userprefix . $dbuser);
                $html->assign('ftppass', $systempass);

                $html->assign('dbhost', $config->magento->dbhost);
                $html->assign('dbuser', $config->magento->userprefix . $dbuser);
                $html->assign('dbpass', $dbpass);

                $html->assign('storeUrl', $config->magento->storeUrl);
            
                // render view
                $bodyText = $html->render('system-account-created.phtml');

                // create mail object
                $mail = new Zend_Mail('utf-8');
                // configure base stuff
                $mail->addTo($queueElement['email']);
                $mail->setSubject($config->cron->systemAccountCreated->subject);
                $mail->setFrom($config->cron->systemAccountCreated->from->email, $config->cron->systemAccountCreated->from->desc);
                $mail->setBodyHtml($bodyText);
                $mail->send();
                /* send email with account details stop */
            }
        }
        $db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement['id']);
        $adminuser = $queueElement['login'];
        $adminpass = substr(
                        str_shuffle(
                                str_repeat('0123456789', 5)
                        )
                        , 0, 5) . substr(
                        str_shuffle(
                                str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 7)
                        )
                        , 0, 7);

        $adminfname = $queueElement['firstname'];
        $adminlname = $queueElement['lastname'];

        $magentoVersion = $queueElement['version'];
        $sampleDataVersion = $queueElement['sample_data_version'];
        $installSampleData = $queueElement['sample_data'];

        $domain = $queueElement['domain'];
        $startCwd = getcwd();

        $message = 'domain: ' . $domain;
        $log->log($message, LOG_DEBUG);

        $dbprefix = $domain . '_';

        $adminemail = $config->magento->adminEmail; //fetch from zend config
        $storeurl = $config->magento->storeUrl . '/instance/' . $domain; //fetch from zend config
        $message = 'store url: ' . $storeurl;
        $log->log($message, LOG_DEBUG);

        chdir($instanceFolder);

        if ($installSampleData && !file_exists(APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz')){
            $message = 'Couldn\'t find sample data file, will not install queue element';
            //echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $db->update('queue', array('error_message' => $message), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            continue; //jump to next queue element
        }
        
        if ($installSampleData) {
            echo "Now installing Magento with sample data...\n";
        } else {
            echo "Now installing Magento without sample data...\n";
        }

        echo "Preparing directory...\n";
        exec('sudo mkdir ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log($message, LOG_DEBUG);
        unset($output);

        if (!file_exists($instanceFolder . '/' . $domain) || !is_dir($instanceFolder . '/' . $domain)) {
            $message = 'Directory does not exist, aborting';
            echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $db->update('queue', array('error_message' => $message), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            //shouldn't continue be here?
        }

        exec('sudo chmod +x ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);

        chdir($domain);

        echo "Copying package to target directory...\n";
        
        if (!file_exists(APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/'.$filePrefix[$queueElement['edition']].'-' . $magentoVersion . '.tar.gz')){
            $message = 'Couldn\'t find package files, aborting';
            echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $db->update('queue', array('error_message' => $message), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            continue; //jump to next queue element
        }
        
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/'.$filePrefix[$queueElement['edition']].'-' . $magentoVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . '/', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/'.$filePrefix[$queueElement['edition']].'-' . $magentoVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . "/\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/keyset0.sql ' . $instanceFolder . '/' . $domain . '/');
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/keyset1.sql ' . $instanceFolder . '/' . $domain . '/');

        if ($installSampleData) {
            $db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement['id']);
            echo "Copying sample data package to target directory...\n";
            exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . '/', $output);
            $message = var_export($output, true);
            $log->log("\nsudo cp " . APPLICATION_PATH . '/../data/pkg/' . $queueElement['edition'] . '/magento-sample-data-' . $sampleDataVersion . '.tar.gz ' . $instanceFolder . '/' . $domain . "/\n" . $message, LOG_DEBUG);
            unset($output);
        }

        $db->update('queue', array('status' => 'installing-files'), 'id=' . $queueElement['id']);
        echo "Extracting data...\n";
        exec('sudo tar -zxvf '.$filePrefix[$queueElement['edition']].'-' . $magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $log->log("\nsudo tar -zxvf ".$filePrefix[$queueElement['edition']]."-" . $magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {
            $db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement['id']);
            echo "Extracting sample data...\n";
            exec('sudo tar -zxvf magento-sample-data-' . $sampleDataVersion . '.tar.gz', $output);
            $message = var_export($output, true);
            $log->log("\nsudo tar -zxvf magento-sample-data-" . $sampleDataVersion . ".tar.gz\n" . $message, LOG_DEBUG);
            unset($output);

            echo "Moving sample data files...\n";
            exec('sudo mv magento-sample-data-' . $sampleDataVersion . '/* .', $output);
            $message = var_export($output, true);
            $log->log("\nsudo mv mv magento-sample-data-" . $sampleDataVersion . "/* .\n" . $message, LOG_DEBUG);
            unset($output);
        }

        $db->update('queue', array('status' => 'installing-files'), 'id=' . $queueElement['id']);
        echo "Moving files...\n";
        exec('sudo cp -R magento/* .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp -R magento/* .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo cp magento/.htaccess .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo cp magento/.htaccess .\n" . $message, LOG_DEBUG);
        unset($output);

        exec('rm -R ' . $instanceFolder . '/' . $domain . '/magento');

        echo "Setting permissions...\n";
        exec('sudo chmod 777 var/.htaccess app/etc', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 var -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod 777 var var/.htaccess app/etc\n" . $message, LOG_DEBUG);
        unset($output);

        
        exec('sudo chmod 777 downloader', $output);
        $message = var_export($output, true);
        $log->log("\nsudo sudo chmod 777 downloader\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chmod 777 media -R', $output);
        $message = var_export($output, true);
        $log->log("\nsudo chmod -R 777 media\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {
            $db->update('queue', array('status' => 'installing-samples'), 'id=' . $queueElement['id']);
            echo "Inserting sample data\n";
            exec('sudo mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' < magento_sample_data_for_' . $sampleDataVersion . '.sql');
        }
        $db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement['id']);
        echo "Cleaning up files...\n";
        exec('sudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf downloader/pearlib/cache/* downloader/pearlib/download/*\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf magento/ '.$filePrefix[$queueElement['edition']].'-' . $magentoVersion . '.tar.gz', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf magento/ ".$filePrefix[$queueElement['edition']]."-" . $magentoVersion . ".tar.gz\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt', $output);
        $message = var_export($output, true);
        $log->log("\nsudo rm -rf index.php.sample .htaccess.sample php.ini.sample LICENSE.txt STATUS.txt\n" . $message, LOG_DEBUG);
        unset($output);

        if ($installSampleData) {
            exec('sudo rm -rf magento-sample-data-' . $sampleDataVersion . '/ magento-sample-data-' . $sampleDataVersion . '.tar.gz magento_sample_data_for_' . $sampleDataVersion . '.sql', $output);
            $message = var_export($output, true);
            $log->log("\nsudo rm -rf magento-sample-data-" . $sampleDataVersion . "/ magento-sample-data-" . $sampleDataVersion . ".tar.gz magento_sample_data_for_" . $sampleDataVersion . ".sql\n" . $message, LOG_DEBUG);
            unset($output);
        }
        $db->update('queue', array('status' => 'installing-magento'), 'id=' . $queueElement['id']);
        echo "Installing Magento...\n";
        exec('sudo mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' < keyset0.sql');
        exec('cd ' . $instanceFolder . '/' . $domain . ';sudo  /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $dbhost . '"' .
                ' --db_name "' . $config->magento->instanceprefix . $dbname . '"' .
                ' --db_user "' . $config->magento->userprefix . $dbuser . '"' .
                ' --db_pass "' . $dbpass . '"' .
                //' --db_prefix "' . $dbprefix . '"' .
                ' --url "' . $storeurl . '"' .
                ' --use_rewrites "yes"' .
                ' --use_secure "no"' .
                ' --secure_base_url ""' .
                ' --use_secure_admin "no"' .
                ' --admin_firstname "' . $adminfname . '"' .
                ' --admin_lastname "' . $adminlname . '"' .
                ' --admin_email "' . $adminemail . '"' .
                ' --admin_username "' . $adminuser . '"' .
                ' --admin_password "' . $adminpass . '"' .
                ' --skip_url_validation "yes"', $output);
        exec('sudo mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' < keyset1.sql');
        // update backend admin password
        $set = array('backend_password' => $adminpass);
        $where = array('domain = ?' => $domain);
        $log->log(PHP_EOL . 'Updating queue backend password: ' . $db->update('queue', $set, $where), Zend_Log::DEBUG);
        // end
        // create magento connect ftp config and remove settings for free user
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
            'ftp://',
            'ftp://'.$config->magento->userprefix.$dbuser.':'.$systempass.'@',
            $config->magento->ftphost
        );
        $connect_cfg = array(
                'php_ini' => '',
                'protocol' => 'http',
                'preferred_state' => 'stable',
                'use_custom_permissions_mode' => '0',
                'global_dir_mode' => 511,
                'global_file_mode' => 438,
                'root_channel_uri' => 'connect20.magentocommerce.com/community',
                'root_channel' => 'community',
                'sync_pear' => false,
                'downloader_path' => 'downloader',
                'magento_root' => $instanceFolder.'/'.$domain,
                'remote_config' => $ftp_user_host.'/public_html/'.$domain
        );
        $free_user = $queueElement['group'] == 'free-user' ? true : false;
        if($free_user AND !stristr($magentoVersion, '1.4')) {
            // index.php file
            $index_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/index.php');
            $new_index_file = str_replace(
                '<?php',
                '<?php'.PHP_EOL.'
if(stristr($_SERVER[\'REQUEST_URI\'], \'setting\')) {
    header(\'Location: http://\'.$_SERVER[\'SERVER_NAME\'].$_SERVER[\'PHP_SELF\']);
    exit;
}',
                $index_file
            );
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/index.php',
                $new_index_file
            );
            // header.phtml navigation file
            $nav_file = file_get_contents($instanceFolder.'/'.$domain.'/downloader/template/header.phtml');
            file_put_contents(
                $instanceFolder.'/'.$domain.'/downloader/template/header.phtml',
                    preg_replace('/<li.*setting.*li>/i', '', $nav_file)
            );
        }
        file_put_contents($instanceFolder.'/'.$domain.'/downloader/connect.cfg', $header.serialize($connect_cfg));
        // end
        $message = var_export($output, true);
        $log->log("\n" . 'cd ' . $instanceFolder . '/' . $domain . ';sudo /usr/bin/php -f install.php --' .
                ' --license_agreement_accepted "yes"' .
                ' --locale "en_US"' .
                ' --timezone "America/Los_Angeles"' .
                ' --default_currency "USD"' .
                ' --db_host "' . $dbhost . '"' .
                ' --db_name "' . $config->magento->instanceprefix . $dbname . '"' .
                ' --db_user "' . $config->magento->userprefix . $dbuser . '"' .
                ' --db_pass "' . $dbpass . '"' .
                //' --db_prefix "' . $dbprefix . '"' .
                ' --url "' . $storeurl . '"' .
                ' --use_rewrites "yes"' .
                ' --use_secure "no"' .
                ' --secure_base_url ""' .
                ' --use_secure_admin "no"' .
                ' --admin_firstname "' . $adminfname . '"' .
                ' --admin_lastname "' . $adminlname . '"' .
                ' --admin_email "' . $adminemail . '"' .
                ' --admin_username "' . $adminuser . '"' .
                ' --admin_password "' . $adminpass . '"' .
                ' --skip_url_validation "yes"' . "\n" . $message, LOG_DEBUG);
        unset($output);

        exec('sudo chown -R '.$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain, $output);
        $message = var_export($output, true);
        $log->log("\nsudo chown -R ".$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain."\n" . $message, LOG_DEBUG);
        unset($output);
        
        
        //applying patches for xml-rpc issue
        if ($magentoVersion > '1.3.2.3' AND $magentoVersion < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($magentoVersion == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($magentoVersion > '1.4.9.9' AND $magentoVersion < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $instanceFolder . '/' . $domain . '/lib/Zend/XmlRpc/Response.php');
        }
        
        
        echo "Finished installing Magento\n";

        //disable adminnotifications output
        exec('mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' -e \'INSERT INTO core_config_data (`scope`,`scope_id`,`path`,`value`) VALUES ("default",0,"advanced/modules_disable_output/Mage_AdminNotification",1) ON DUPLICATE KEY UPDATE `value` = 1\'');
        
//TODO: add mail info about ready installation
        
        exec('ln -s ' . $instanceFolder . '/' . $domain . ' ' . INSTANCE_PATH . $domain);

        $db->update('queue', array('status' => 'ready'), 'id=' . $queueElement['id']);

        chdir($startCwd);

        /* send email to instance owner start */
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
// assign valeues
        $html->assign('domain', $domain);
        $html->assign('storeUrl', $config->magento->storeUrl);
        $html->assign('admin_login', $adminuser);
        $html->assign('admin_password', $adminpass);
// render view
        $bodyText = $html->render('queue-item-ready.phtml');

// create mail object
        $mail = new Zend_Mail('utf-8');
// configure base stuff
        $mail->addTo($queueElement['email']);
        $mail->setSubject($config->cron->queueItemReady->subject);
        $mail->setFrom($config->cron->queueItemReady->from->email, $config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
        $mail->send();
        /* send email to instance owner stop */
    }
    flock($fp, LOCK_UN); // release the lock
    exit;
} else {
    //echo "Couldn't get the lock!";
}

fclose($fp);
