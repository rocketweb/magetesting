<?php
include 'init.console.php';

$current_server_id = 1;

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

if(isset($opts->help)) {
    echo $opts->getUsageMessage();
    exit;
} elseif(isset($opts->downloadonly)) {
    /* process only downloads */
    $fp = fopen($lockDir.'worker_downloadonly.lock', "c");
    $mode = 'download';  

} elseif(isset($opts->disabledownload)) {
    /* process everything but downloads */
    $fp = fopen($lockDir.'worker_disabledownload.lock', "c");
    $mode = 'allbutdownload';
    
} else {
    /* process all queue tasks */ 
    $fp = fopen($lockDir.'worker_all.lock', "c");
    $mode = 'all';
}

/* Lock file and get tasks */
if (flock($fp, LOCK_EX | LOCK_NB)) {
    $queueModel = new Application_Model_Queue();

    $worker = new Application_Model_Worker($config,$db);
    while ($queueElement = $queueModel->getForServer($current_server_id,$mode)){
        $worker->work($queueElement);
    }
}
    
/* release the lock */
flock($fp, LOCK_UN);

/* close file handle */
fclose($fp);
