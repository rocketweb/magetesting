<?php
if(!defined('APPLICATION_PATH')) {
    include 'init.console.php';
}

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('store','user.id = store.user_id', 'domain')
    ->joinLeft('server','user.server_id = server.id', array('server_domain' => 'domain'))
    ->where('store.status = ?', 'ready')
    ->orwhere('user.downgraded = ?)', 3);

$result = $db->fetchAll($sql);
if($result) {
    $downgrade_by_id = array();
    foreach($result as $store) {
        if(!isset($downgrade_by_id[$store['id']])) {
            $downgrade_by_id[$store['id']] = null;
        }
               
        /* disable user vhost */
        exec('sudo a2dissite '.$store['login'].'.'.$store['server_domain']);
    }

    if($downgrade_by_id) {
        $set = array(
            'downgraded' => 4 // downgraded because of too many stores installed
        );

        $user_ids = array_keys($downgrade_by_id);

        $where = array('id IN (?)' => $user_ids);
        $db->update('user', $set, $where);

        foreach ($user_ids as $user_id){
            $modelUser = new Application_Model_User();
            $modelUser->find($user_id);
            
            $modelUser->disableFtp();
            $modelUser->disablePhpmyadmin();
        }
        exec('sudo /etc/init.d/apache2 reload');
    }
    $log->log('Downgraded '.count($downgrade_by_id).' users', Zend_Log::INFO);
} else {
    //$log->log('There is no user to downgrade.', Zend_Log::INFO);
}