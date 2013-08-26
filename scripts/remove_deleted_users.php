<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->where('status = ?', 'deleted');

$result = $db->fetchAll($sql);

$cli = new RocketWeb_Cli();
$fileKit = $cli->kit('file');
$apacheKit = $cli->kit('apache');
$serviceKit = $cli->kit('service');

if($result) {
    foreach($result as $row) {

        $user = new Application_Model_User();
        $user->find($row['id']);
        
        $storeModel = new Application_Model_Store();
        $stores = $storeModel->getAllForUser($row['id']);
        
        if ($stores->getTotalItemCount()){
            /* we have stores waiting to remove, discard this user */
            continue;
        }

        $username = $username;
        //--------------PAPERTRAIL PART START-------------
        if ($user->getHasPapertrailAccount()){
            $id = $config->papertrail->prefix . (string) $user->getId();

            $service = new RocketWeb_Service_Papertrail(
                $config->papertrail->username,
                $config->papertrail->password    
            );
           
            $removeUser = false;
            try { 
                $responseExists = $service->getAccountUsage($id);
                $removeUser = true;
            } catch(Zend_Service_Exception $e) {
                $log->log($e->getMessage(), Zend_Log::CRIT);
                //retry later if error
                $removeUser = false;
            }
            
            //account exist
            if ($removeUser){
                try{
                    $responseRemove = $service->removeUser($id);
                } catch(Zend_Service_Exception $e) {
                    //if remove failed in papertrail, retry later
                    continue;
                }
            }

            if(isset($responseRemove->status) && $responseRemove->status == 'ok') {
                //success
                $user->setPapertrailApiToken(null);
                $user->setHasPapertrailAccount(0);
                $user->save();
            }
        }
        //--------------PAPERTRAIL PART END-----------------


        //--------------SYSTEM/MYSQL PART START-------------
        $startcwd = getcwd();
        //remove ftp account
        $user->disableFtp();

        //rebuild phpmyadmin blacklist
        $user->disablePhpmyadmin();

        //remove system user
        if ($user->getHasSystemAccount()){
            $delete = $cli->kit('user')->delete($username);
            $output = $delete->call()->getLastOutput();
            if (empty($output)){
                echo 'sh user removal script failed';
            } elseif(isset($output[0]) && $output[0]=='error'){ 
                echo 'sh script was run without arguments';
            }

            // just in case remove_user.sh didn't work
            $cli->createQuery(
                'userdel -f ?',
                $username
            )->asSuperUser(true)->call();

            $homeDir = '/home/' . $username;
            if(file_exists($homeDir)){
                $fileKit->clear()->remove($homeDir)->call();
            }
        }


        //remove mysql user
        $DbManager = new Application_Model_DbTable_Privilege($db,$config);
        if ($DbManager->checkIfUserExists($user->getLogin())){
            try {
                $log->log('Dropping ' . $user->getLogin() . ' user.', Zend_Log::INFO);
                $DbManager->dropUser($user->getLogin());
            } catch(PDOException $e){
                $message = 'Could not remove mysql user';
                $log->log($message, Zend_Log::CRIT);
                flock($fp, LOCK_UN); // release the lock
                continue;
            }
        } else {
            $message = 'user does not exist, ignoring.';
            $log->log($message, Zend_Log::NOTICE);
        }
        //--------------SYSTEM/MYSQL PART END--------------

        //--------------RSYSLOG FILES PART START-----------
        $fileKit->remove($username)->append('_*', null, false) // 'file'_*
            ->asSuperUser(true)->call();
        //--------------RSYSLOG FILES PART END ------------
        
        //--------------SUEXEC PART START------------------
        $fileKit->clear()->remove('/home/www-data/'.$username)->call();
        //--------------SUEXEC PART END------------------
        
        
        //--------------VIRTUALHOST PART START-------------
        $serverModel = new Application_Model_Server();
        $serverModel->find($user->getServerId());

        $apacheKit->clear()->disableSite($user->getLogin().'.'.$serverModel->getDomain())->call();

        $vhostfile = '/etc/apache2/sites-available/'.$user->getLogin().'.'.$serverModel->getDomain();
        if(file_exists($vhostfile)){
            unlink($vhostfile);
        }

        $serviceKit->clear()->restart('apache2');
        //--------------VIRTUALHOST PART END-------------

        //--------------MAGETESTING PART START-------------
        try {
            $db->delete('user','id = '.$user->getId());
        } catch (PDOException $e){
            $log->log('Cannot remove user id: '.$user->getId().' (possibly existing stores)',Zend_Log::ERR,$e->getTraceAsString());
            continue;
        } catch (Exception $e){
            $log->log('Cannot remove user id: '.$user->getId().'',Zend_Log::ERR,$e->getTraceAsString());
            continue;
        }

        //--------------MAGETESTING PART END---------------
        
    }
}
