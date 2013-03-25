<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('store')
    ->where('autorevert = ?', '1')
	->where('status = ?','ready');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
    	
    	$userModel = new Application_Model_User();
    	$userObject = $userModel->find($row['user_id']);
    	
    	$storeModel = new Application_Model_Store(); 
    	$storeObject = $storeModel->find($row['id']);
    	

    	$select = new Zend_Db_Select($db);
    	$sql = $select
    	->from('revision')
    	->where('store_id = ?', $row['id'])
    	->order(array('id desc'))
    	->limit(1);
    	
    	$revision = $db->fetchRow($sql);
    	
    	//drop database
    	$privilegeModel = new Application_Model_DbTable_Privilege($db, $config);
    	$privilegeModel->dropDatabase($userModel->getLogin().'_'.$storeModel->getDomain());

    	//create database again
    	$privilegeModel->createDatabase($userModel->getLogin().'_'.$storeModel->getDomain());    	

    	//insert db dump
    	exec('tar xfzO '.magento.systemHomeFolder.'/'.$config->magento->userprefix.$userObject->getLogin().'/public_html/'.$storeModel->getDomain().'/var/db/'.$revision['db_before_revision'].' | mysql -u'.$config->resources->db->params->username.' -p'.$config->resources->db->params->password.' '.$config->magento->storeprefix.$userObject->getLogin().'_'.$storeObject->getDomain().'');
        
    }
}
