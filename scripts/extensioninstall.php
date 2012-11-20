<?php

$fp = fopen("extension_install_lock.txt", "c");

if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
    include 'init.console.php';
    //fetch custom instances start
      
    $select = new Zend_Db_Select($db);
    
    //do stuff
    $select->from('extension_queue')
            ->where('status = ?','pending');
    
    $extensions = $select->query()->fetchAll();
    
    foreach($extensions as $ext){
    
        $db->update('extension_queue', array('status' => 'installing'), 'id=' . $ext['id']);
        $db->update('queue', array('status' => 'installing-extension'), 'id=' . $ext['queue_id']);
        
        //get instance data
        $modelQueue = new Application_Model_Queue();
        $queueItem = $modelQueue->find($ext['queue_id']);
    
        //get extension data
        $modelExtension = new Application_Model_Extension();
        $extensionData = $modelExtension->find($ext['extension_id']);
        
        //get user data
        $modelUser = new Application_Model_User();
        $userData = $modelUser->find($ext['user_id']);

        //prepare a logger
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $userData->getLogin() . '_' . $queueItem->getDomain() . '.log');
        $log = new Zend_Log($writer);
        
        //untar extension to instance folder
        exec('tar -zxvf '.
            $config->extension->directoryPath.'/'.$queueItem->getEdition().'/'.$extensionData->getExtension().
            ' -C '.$config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userData->getLogin() . '/public_html/'.$queueItem->getDomain()
        ,$output);
        $log->log(var_export($output,true),LOG_DEBUG);
        unset($output);
        
        //clear instance cache
        exec('sudo rm -R '.$config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userData->getLogin() . '/public_html/'.$queueItem->getDomain().'/var/cache/*');
        
        //set extension as installed
        $db->update('extension_queue', array('status' => 'ready'), 'id=' . $ext['id']);
        $db->update('queue', array('status' => 'ready'), 'id=' . $ext['queue_id']);
    }
    //finish
    
    
    flock($fp, LOCK_UN); // release the lock
    exit;
} else {
    //echo "Couldn't get the lock!";
}

fclose($fp);