<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('store','user.id = store.user_id', 'domain')
    ->joinLeft('server','user.server_id = server.id', array('server_domain' => 'domain'))
    ->where('store.status = ?', 'ready')
    ->where('TIMESTAMPDIFF(SECOND, \''.date("Y-m-d H:i:s").'\', user.plan_active_to) > ?', 0)
    ->where('user.downgraded = ?', 1);

$apache = new RocketWeb_Cli_Kit_Apache();
$apache->asSuperUser();
$service = new RocketWeb_Cli_Kit_Service();
$service->asSuperUser();

$result = $db->fetchAll($sql);
if($result) {
    $restore_by_id = array();
    foreach($result as $store) {
        if(!isset($restore_by_id[$store['id']])) {
            $restore_by_id[$store['id']] = null;
        }

        /* enable user vhost */
        $apache->clear()->enableSite($store['login'].'.'.$store['server_domain'])->call();
    }
    
    if($restore_by_id) {
        $set = array(
                'group' => 'commercial-user',
                'downgraded' => 0
        );

        $user_ids = array_keys($restore_by_id);

        $where = array('id IN (?)' => $user_ids);
        $result = $db->update('user', $set, $where);
        //echo 'Update: '.$result.PHP_EOL;
        $log->log('Restored '.count($restore_by_id).' users', Zend_Log::INFO);

        foreach($user_ids as $user_id){
            //get users plan id
            $modelUser = new Application_Model_User();
            $modelUser->find($user_id);
            $this->log('Restored '.json_encode($modelUser->__toArray()), Zend_Log::INFO);
            
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

        $service->clear()->restart('apache2')->call();
    }
} else {
    //$log->log('There is no downgraded user to restore.', Zend_Log::INFO);
}