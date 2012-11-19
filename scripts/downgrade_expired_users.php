<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('instance','user.id = instance.user_id', 'domain')
    ->where('instance.status = ?', 'ready')
    ->where('TIMESTAMPDIFF(SECOND,user.plan_active_to, CURRENT_TIMESTAMP) > ?', 3*60*60*24)
    ->where('(user.group IN (?)', array('awaiting-user', 'commercial-user'))
    ->orwhere('user.downgraded = ?)', 2);

$result = $db->fetchAll($sql);
if($result) {
    $downgrade_by_id = array();
    foreach($result as $instance) {
        if(!isset($downgrade_by_id[$instance['id']])) {
            $downgrade_by_id[$instance['id']] = null;
        }
        if(is_link(INSTANCE_PATH.$instance['domain'])) {
            exec('sudo rm '.INSTANCE_PATH.$instance['domain']);
        }
    }
    if($downgrade_by_id) {
        $set = array(
                'group' => 'free-user',
                'downgraded' => 1
        );
        
        $user_ids = array_keys($downgrade_by_id);
        
        $where = array('id IN (?)' => $user_ids);
        echo 'Update: '.$db->update('user', $set, $where).PHP_EOL;
        
        foreach ($user_ids as $user_id){
            $modelUser = new Application_Model_User();
            $modelUser->find($user_id);
            
            $modelUser->disableFtp();
            $modelUser->disablePhpmyadmin();
        }
        
    }
    echo 'Downgraded '.count($downgrade_by_id).' users'.PHP_EOL;
} else {
    echo 'Nothing to downgrade'.PHP_EOL;
}