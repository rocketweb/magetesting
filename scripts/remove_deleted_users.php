<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->where('status = ?', 'deleted');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {

        $user = new Application_Model_User();
        $user->find($row['id']);
        //--------------PAPERTRAIL PART START-------------
        if ($user->getHasPapertrailAccount()){
            $id = $config->papertrail->prefix . (string) $user->getId();

            $service = new RocketWeb_Service_Papertrail(
                $config->papertrail->username,
                $config->papertrail->password    
            );
            try { 
                $response = $service->removeUser((string)$id);
            } catch(Zend_Service_Exception $e) {
                $log->log($e->getMessage(), Zend_Log::CRIT);
                //retry later
                //continue;
            }

            if(isset($response->status) && $response->status == 'ok') {
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
            $workerfolder = APPLICATION_PATH.'/../scripts/worker';
            chdir($workerfolder);
            $command = 'cd '.APPLICATION_PATH.'/../scripts/worker';
            exec($command,$output);
            unset($output);
            $command = 'sudo ./remove_user.sh ' . $config->magento->userprefix . $user->getLogin();
            exec($command, $output);
            if (empty($output)){
                echo 'sh user removal script failed';
            } elseif(isset($output[0]) && $output[0]=='error'){ 
                echo 'sh script was run without arguments';
            }

            unset($output);
            chdir($startcwd);
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


        //--------------MAGETESTING PART START-------------
        $db->delete('user','id = '.$user->getId());
        //--------------MAGETESTING PART END---------------
    }
}
