<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('store')
    ->where('do_hourly_db_revert = ?', '1')
    ->where('status = ?','ready');

$result = $db->fetchAll($sql);


$stores_in_queue = array();

$qSelect = new Zend_Db_Select($db);
$qSql = $qSelect
    ->from('queue')
    ->where('task = ?','MagentoHourlyrevert');
$qResult = $db->fetchAll($qSql);

if($qResult){
    foreach($qResult as $qr){
        $stores_in_queue[] = $qr['store_id'];
    }
}

if($result) {
    foreach($result as $row) {
        if(in_array($row['id'],$stores_in_queue)){
            continue;
        }

        $userModel = new Application_Model_User();
        $userObject = $userModel->find($row['user_id']);

        $storeModel = new Application_Model_Store(); 
        $storeObject = $storeModel->find($row['id']);

		$queueModel = new Application_Model_Queue();
		$queueModel->setStoreId($row['id'])
		->setStatus('pending')
		->setUserId($row['user_id'])
		->setExtensionId(0)
		->setParentId(0)
		->setServerId($row['server_id'])
		->setTask('MagentoHourlyrevert')
		->setRetryCount(0)
		->save();        
    }
}
