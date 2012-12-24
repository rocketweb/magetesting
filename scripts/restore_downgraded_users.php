<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('store','user.id = store.user_id', 'domain')
    ->where('store.status = ?', 'ready')
    ->where('TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, user.plan_active_to) > ?', 0)
    ->where('user.downgraded = ?', 1);

$result = $db->fetchAll($sql);
if($result) {
    $restore_by_id = array();
    foreach($result as $store) {
        if(!isset($restore_by_id[$store['id']])) {
            $restore_by_id[$store['id']] = null;
        }
        if(!is_link(STORE_PATH.$store['domain'])) {
            $storeFolder = $config->magento->systemHomeFolder.'/'.$config->magento->userprefix.$store['login'].'/public_html';
            exec('ln -s '.$storeFolder.'/'.$store['domain'].' '.STORE_PATH.$store['domain']);
        }
    }
    if($restore_by_id) {
        $set = array(
                'group' => 'commercial-user',
                'downgraded' => 0
        );
        
        $user_ids = array_keys($restore_by_id);
        
        $where = array('id IN (?)' => $user_ids);
        //echo 'Update: '.$db->update('user', $set, $where).PHP_EOL;
        $log->log('Restored '.count($restore_by_id).' users', Zend_Log::INFO);
        
        foreach($user_ids as $user_id){
            //get users plan id
            $modelUser = new Application_Model_User();
            $modelUser->find($user_id);
            
            $modelPlan = new Application_Model_Plan();
            $modelPlan->find($modelUser->getPlanId());
            
            //apply ftp and phpmyadmin access
            if ($modelPlan->getFtpAccess()){
               $modelUser->enableFtp(); 
            }
            
            if ($modelPlan->getPhpmyadminAccess()){
                $modelUser->enablePhpmyadmin();
            }
        }       
    }
} else {
    $log->log('There is no downgraded user to restore.', Zend_Log::INFO);
}