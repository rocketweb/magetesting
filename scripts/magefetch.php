<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$fp = fopen("fetch_lock.txt", "c");

$sqlFileLimit = '600000'; // In Bytes!

if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
    include 'init.console.php';
    //fetch custom instances start
      
    $select = new Zend_Db_Select($db);

    //check if any script is currently being installed
        $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id', array('version'))
            ->joinLeft('user', 'queue.user_id = user.id', array('email'))
            ->where('queue.status =?', 'installing')
            ->where('queue.type = ? ','custom');

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

        //get records from custom queue
        $select = new Zend_Db_Select($db);
    $sql = $select
            ->from('queue')
            ->joinLeft('version', 'queue.version_id = version.id', array('version', 'sample_data_version'))
            ->joinLeft('user', 'queue.user_id = user.id', array('email', 'login', 'group', 'firstname', 'lastname', 'has_system_account'))
            ->where('queue.status =?', 'pending')
            ->where('user.status =?', 'active')
            ->where('queue.type = ? ','custom');

    $query = $sql->query();
    $queueElements = $query->fetchAll();

    $filePrefix = array(
      'CE' => 'magento',
      'EE' => 'enterprise',
      'PE' => 'professional',
    );

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
        $instanceFolder = $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $dbuser . '/public_html';

	
        
        if ($queueElement['has_system_account'] == 0) {
            $db->update('user', array('system_account_name' => $config->magento->userprefix . $dbuser), 'id=' . $queueElement['user_id']);

            /** WARNING!
             * in order for this to work, when you run this (console.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php mageinstall.php
             *
             */
            exec('sudo ./create_user.sh ' . $config->magento->userprefix . $dbuser . ' ' . $dbpass . ' ' . $config->magento->usersalt . ' ' . $config->magento->systemHomeFolder, $output);
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
                $html->assign('ftppass', $dbpass);

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
        
        //fetch sql file or yield error when not found
        
        //make sure remote path containts slash
        if(substr($queueElement['custom_remote_path'],-1)!="/"){
            $queueElement['custom_remote_path'] .= '/';
        }
        
        if(substr($queueElement['custom_host'],-1)!="/"){
            $queueElement['custom_host'] .= '/';
        }
        
        //do a sample connection to wget to check if protocol credentials are ok
        exec("wget --spider ".$queueElement['custom_host']." ".
             "--passive-ftp ".
             "--user='".$queueElement['custom_login']."' ".
             "--password='".$queueElement['custom_pass']."' ".
             "".$queueElement['custom_host']." 2>&1 | grep 'Logged in!'",$output);
        
        if (!isset($output[0])){
            $log->log("Protocol credentials does not match\n", LOG_DEBUG);
        }
            
        //connect through wget
        exec("wget --spider ".$queueElement['custom_host'].$queueElement['custom_sql']." 2>&1 ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']." | grep 'SIZE'",$output);
           
        $message = var_export($output, true);
        $log->log("wget --spider ".$queueElement['custom_host'].$queueElement['custom_sql']." 2>&1 ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']." | grep 'SIZE'\n" . $message, LOG_DEBUG);
        
        foreach($output as $out){
        
        
        $log->log(substr($out,0,8), LOG_DEBUG);
        
	  if (substr($out,0,8) == '==> SIZE'){
	    $sqlSizeInfo = explode(' ... ',$out);
	  }
        }
        
        $log->log($sqlSizeInfo[1], LOG_DEBUG);
        
       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){
            $message = 'Couldn\'t find sql data file, will not install queue element';
            //echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            continue; //jump to next queue element
        }
        unset($output);
        
        if ($sqlSizeInfo[1] > $sqlFileLimit){
            $message = 'Sql file is too big, aborting';
            //echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            continue; //jump to next queue element
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
            $log->log($message, LOG_DEBUG);
        }
        
        exec('sudo chmod +x ' . $instanceFolder . '/' . $domain, $output);
        $message = var_export($output, true);
        $log->log('chmodding domain: ' . $message, LOG_DEBUG);
        unset($output);

        chdir($domain);

        echo "Copying package to target directory...\n";
        //do a sample connection, and check for index.php, if it works, start fetching
        exec("wget --spider ".$queueElement['custom_host'].$queueElement['custom_remote_path']."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']." | grep 'SIZE'",$output);
  
         $message = var_export($output, true);
            $log->log("wget --spider ".$queueElement['custom_host'].$queueElement['custom_remote_path']."app/Mage.php 2>&1 ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']." | grep 'SIZE'\n" . $message, LOG_DEBUG);
        
        
        $sqlSizeInfo = explode(' ... ',$output[0]);
        
       //limit is in bytes!
        if ($sqlSizeInfo[1] == 'done' || $sqlSizeInfo[1] == 0){
            $message = 'Couldn\'t find app/Mage.php file data, will not install queue element';
            //echo $message;
            $db->update('queue', array('status' => 'error'), 'id=' . $queueElement['id']);
            $log->log($message, LOG_DEBUG);
            continue; //jump to next queue element
        }
        unset($output);
        
        exec("wget ".
             "--passive-ftp ".
             "-nH ".
             "-Q300m ".
             "-m ".
             "-np ".
             "-R 'sql,tar,gz,zip,rar' ".
             "-X '.htaccess' " . 
             "-I '".$queueElement['custom_remote_path']."app,".$queueElement['custom_remote_path']."downloader,".$queueElement['custom_remote_path']."errors,".$queueElement['custom_remote_path']."includes,".$queueElement['custom_remote_path']."js,".$queueElement['custom_remote_path']."lib,".$queueElement['custom_remote_path']."pkginfo,".$queueElement['custom_remote_path']."shell,".$queueElement['custom_remote_path']."skin' " .
             "--user='".$queueElement['custom_login']."' ".
             "--password='".$queueElement['custom_pass']."' ".
             "".$queueElement['custom_host'].$queueElement['custom_remote_path'].""
             ,$output);
             
              $message = var_export($output, true);
        $log->log("wget ".
             "--passive-ftp ".
             "-nH ".
             "-Q300m ".
             "-m ".
             "-np ".
             "-R 'sql,tar,gz,zip,rar' ".
             "-X '.htaccess' " . 
             "-I '".$queueElement['custom_remote_path']."app,".$queueElement['custom_remote_path']."downloader,".$queueElement['custom_remote_path']."errors,".$queueElement['custom_remote_path']."includes,".$queueElement['custom_remote_path']."js,".$queueElement['custom_remote_path']."lib,".$queueElement['custom_remote_path']."pkginfo,".$queueElement['custom_remote_path']."shell,".$queueElement['custom_remote_path']."skin' " .
             "--user='".$queueElement['custom_login']."' ".
             "--password='".$queueElement['custom_pass']."' ".
             "".$queueElement['custom_host'].$queueElement['custom_remote_path']."\n" . $message, LOG_DEBUG);
        unset($output);
        
        exec("wget  ".$queueElement['custom_host'].$queueElement['custom_sql']." ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']." ",$output);
  
         $message = var_export($output, true);
            $log->log("wget ".$queueElement['custom_host'].$queueElement['custom_sql']." ".
            "--passive-ftp ".
            "--user='".$queueElement['custom_login']."' ".
            "--password='".$queueElement['custom_pass']."' ".
            "".$queueElement['custom_host'].$queueElement['custom_remote_path']."\n" . $message, LOG_DEBUG);
        
	unset($output);
        
        $path_parts = pathinfo($queueElement['custom_sql']);
        //let's load sql to mysql database
        exec('sudo mysql -u' . $config->magento->userprefix . $dbuser . ' -p' . $dbpass . ' ' . $config->magento->instanceprefix . $dbname . ' < '.$path_parts['basename'].'');


     
        echo "Moving downloaded sources to main folder...\n";
        exec('sudo mv '.$queueElement['custom_remote_path'].'* .', $output);
        $message = var_export($output, true);
        $log->log("\nsudo mv ".$queueElement['custom_remote_path']."* .\n" . $message, LOG_DEBUG);
        unset($output);
        
        
	//now lets configure our local xml file
        $connectionString = '<connection>
                    <host><![CDATA[localhost]]></host>
                    <username><![CDATA['.$config->magento->userprefix . $dbuser.']]></username>
                    <password><![CDATA['.$dbpass.']]></password>
                    <dbname><![CDATA['.$config->magento->instanceprefix . $dbname.']]></dbname>
                    <active>1</active>
                </connection>';
               
        $localXml = file_get_contents($instanceFolder . '/' . $domain.'/app/etc/local.xml');
        $localXml = preg_replace("#<connection>(.*?)</connection>#is",$connectionString,$localXml);
        file_put_contents($instanceFolder .'/'. $domain.'/app/etc/local.xml',$localXml);
        unset($localXml);
        
        //remove main fetched folder
        $parts = explode('/',$queueElement['custom_remote_path']);
        exec('sudo rm '.$parts.' -R', $output);
        unset($parts);
        
        echo "Setting permissions...\n";
        exec('sudo mkdir var');
        exec('sudo mkdir downloader');
        exec('sudo mkdir media');
        
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
        
        // update backend admin password
        $set = array('backend_password' => $adminpass);
        $where = array('domain = ?' => $domain);
        $log->log(PHP_EOL . 'Updating queue backend password: ' . $db->update('queue', $set, $where), Zend_Log::DEBUG);
        // end
        // create magento connect ftp config and remove settings for free user
        $header = '::ConnectConfig::v::1.0::';
        $ftp_user_host = str_replace(
            'ftp://',
            'ftp://'.$config->magento->userprefix.$dbuser.':'.$dbpass.'@',
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
        
        //copy new htaccess over
        exec('sudo cp ' . APPLICATION_PATH . '/../data/pkg/Custom/.htaccess ' . $instanceFolder . '/' . $domain . '/.htaccess');
               
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
        
        exec('sudo chown -R '.$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain, $output);
        $message = var_export($output, true);
        $log->log("\nsudo chown -R ".$config->magento->userprefix.$dbuser.':'.$config->magento->userprefix.$dbuser.' '.$instanceFolder.'/'.$domain."\n" . $message, LOG_DEBUG);
        unset($output);
        
        
        echo "Finished installing Magento\n";


//TODO: add mail info about ready installation

        exec('ln -s ' . $instanceFolder . '/' . $domain . ' '.INSTANCE_PATH . $domain);
	$log->log(PHP_EOL . 'ln -s ' . $instanceFolder . '/' . $domain . ' '. INSTANCE_PATH . $domain, Zend_Log::DEBUG);
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
        
        //set database config
    
        //set file config
    
    //fetch custom instances fetch
    flock($fp, LOCK_UN); // release the lock
    exit;
} else {
    //echo "Couldn't get the lock!";
}

fclose($fp);