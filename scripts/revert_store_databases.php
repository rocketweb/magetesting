<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('store')
    ->where('do_hourly_db_revert = ?', '1')
        ->where('status = ?','ready');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {

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
		->setRetryCount(2)
		->save();        
    }
}
