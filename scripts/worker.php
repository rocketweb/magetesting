<?php
include 'init.console.php';

$current_server_id = 1;
$select = new Zend_Db_Select($db);

try {
    $opts = new Zend_Console_Getopt(
        array(
            'downloadonly'      => 'for fetching external instances',
            'disabledownload' => 'for installing local instances',
            'help'       => 'this list',
        )
    );

    $opts->parse();

} catch (Zend_Console_Getopt_Exception $e) {
    exit($e->getMessage() ."\n\n". $e->getUsageMessage());
}

//check if any script is currently being installed
    $sql = $select
        ->from('queue')
        ->joinLeft('user', 'queue.user_id = user.id', array('email'))
        ->where('queue.status =?', 'installing');        

$query = $sql->query();
$queueElement = $query->fetch();

if ($queueElement) {
    //something is currently installed, abort
    $message = 'Another installation in progress, aborting';
    echo $message;
    $log->log($message, LOG_INFO);
    exit;
}

if(isset($opts->help)) {
    echo $opts->getUsageMessage();
    exit;
} elseif(isset($opts->downloadonly)) {
    /* process only downloads */
    $fp = fopen("worker_downloadonly.lock", "c");

    if (flock($fp, LOCK_EX | LOCK_NB)) { 
        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'download');
    }
} elseif(isset($opts->disabledownload)) {
    /* process everything but downloads */
    $fp = fopen("worker_disabledownload.lock", "c");

    if (flock($fp, LOCK_EX | LOCK_NB)) { 
               
        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'allbutdownload');

    }
} else {
    /* process all queue tasks */ 
    $fp = fopen("worker_all.lock", "c");
        if (flock($fp, LOCK_EX | LOCK_NB)) { 

        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'all');

    }
}

if(!empty($queueElements)){
    
    /*TODO: rewrite this to not pass arguments */
    $taskModel = new Application_Model_Task($config,$db);
    
    foreach ($queueElements as $queueElement){
        $taskModel->process($queueElement);
    }
    
} else {
    
    $message = 'Nothing in pending queue';
    echo $message.PHP_EOL;
    $log->log($message, LOG_INFO, ' ');
    
        
}
/* release the lock */
flock($fp, LOCK_UN);

/* close file handle */
fclose($fp); 
