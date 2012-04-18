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

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") {
                    rrmdir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('queue')
    ->joinLeft('user', 'queue.user_id = user.id',array('email','login'))
    ->where('queue.status =?', 'closed');

$query = $sql->query();
$queueElement = $query->fetch();


if (!$queueElement){
    $message = 'Nothing in closed queue';
    echo $message;


    $log->log($message, LOG_INFO,' ');
    exit;
}


//drop database
$dbname = $queueElement['login'].'_'.$queueElement['domain'];

$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');
$log = new Zend_Log($writer);

$DbManager = new Application_Model_DbTable_Privilege($db,$config);

if ($DbManager->checkIfDatabaseExists($dbname)){
    try{
        $DbManager->dropDatabase($dbname);
    } catch(PDOException $e){
        $message = 'Could not remove database for instance';
        echo $message;
        $log->log($message, LOG_ERR);
        exit;
    }
} else {
    $message = 'database does not exist, ignoring...';
    echo $message;
    $log->log($message, LOG_ERR);
}

//remove folder recursively
$startCwd =  getcwd();
chdir(INSTANCE_PATH);

/* todo: replace rrmdir with exec() and system commad  */
rrmdir($queueElement['domain']);
chdir($startCwd);

$db->getConnection()->exec("use ".$config->resources->db->params->dbname);

$db->delete('queue','id='.$queueElement['id']);
unlink(APPLICATION_PATH . '/../data/logs/'.$queueElement['login'].'_'.$queueElement['domain'].'.log');