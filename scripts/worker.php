<?php
include 'init.console.php';

$current_server_id = 1;
$select = new Zend_Db_Select($db);

$lockDir = APPLICATION_PATH . '/../data/locks/';
if (!file_exists($lockDir) || !is_dir($lockDir)){
    mkdir($lockDir);    
}

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
    $fp = fopen(APPLICATION_PATH . '/../data/locks/worker_downloadonly.lock', "c");

    if (flock($fp, LOCK_EX | LOCK_NB)) { 
        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'download');
    }
} elseif(isset($opts->disabledownload)) {
    /* process everything but downloads */
    $fp = fopen(APPLICATION_PATH . '/../data/locks/worker_disabledownload.lock', "c");

    if (flock($fp, LOCK_EX | LOCK_NB)) { 
               
        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'allbutdownload');

    }
} else {
    /* process all queue tasks */ 
    $fp = fopen(APPLICATION_PATH . '/../data/locks/worker_all.lock', "c");
        if (flock($fp, LOCK_EX | LOCK_NB)) { 

        $queueModel = new Application_Model_Queue();
        $queueElements  = $queueModel->getForServer($current_server_id,'all');

    }
}

if(!empty($queueElements)){
      
    $worker = new Application_Model_Worker($config,$db);
    
    foreach ($queueElements as $queueElement){
        $worker->work($queueElement);
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
