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
            'downloadonly'      => 'for fetching external stores',
            'disabledownload' => 'for installing local stores',
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
        ->where('queue.status =?', 'processing');        

$query = $sql->query();
$queueElement = $query->fetch();

if ($queueElement) {
    //something is currently installed, abort
    $message = 'Another installation in progress, aborting';
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
        $mode = 'download';  
    }
} elseif(isset($opts->disabledownload)) {
    /* process everything but downloads */
    $fp = fopen(APPLICATION_PATH . '/../data/locks/worker_disabledownload.lock', "c");

    if (flock($fp, LOCK_EX | LOCK_NB)) {
        $mode = 'allbutdownload';
    }
} else {
    /* process all queue tasks */ 
    $fp = fopen(APPLICATION_PATH . '/../data/locks/worker_all.lock', "c");
    if (flock($fp, LOCK_EX | LOCK_NB)) {
        $mode = 'all';
    }
}

$queueModel = new Application_Model_Queue();

    $worker = new Application_Model_Worker($config,$db);
    while ($queueElement = $queueModel->getForServer($current_server_id,$mode)){
        $worker->work($queueElement);
    }
    
/* release the lock */
flock($fp, LOCK_UN);

/* close file handle */
fclose($fp); 
