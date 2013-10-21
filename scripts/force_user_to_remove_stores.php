<?php
if(!defined('APPLICATION_PATH')) {
    include 'init.console.php';
} elseif(Zend_Controller_Front::getInstance()->getParam('bootstrap')) {
    // fetch application db adapter
    $db = new Application_Model_DbTable_User();
    $db = $db->getAdapter();
    $log = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Log');
    $config = Zend_Registry::get('config');
}

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('store','user.id = store.user_id', 'domain')
    ->joinLeft('server','user.server_id = server.id', array('server_domain' => 'domain'))
    ->where('user.server_id = ?', $config->magento->currentServerId)
    ->where('store.status = ?', 'ready')
    ->where('user.downgraded = ?', Application_Model_User::DOWNGRADED_TOO_MANY_STORES_SYMLINKS_NOT_DELETED);

$apache = new RocketWeb_Cli_Kit_Apache();
$apache->asSuperUser();
$service = new RocketWeb_Cli_Kit_Service();
$service->asSuperUser();

$result = $db->fetchAll($sql);
if($result) {
    $downgrade_by_id = array();
    foreach($result as $store) {
        if(!isset($downgrade_by_id[$store['id']])) {
            $downgrade_by_id[$store['id']] = null;
        }
               
        /* disable user vhost */
        $apache->clear()->disableSite($store['login'].'.'.$store['server_domain'])->call();
    }

    if($downgrade_by_id) {
        $set = array(
            // downgraded because of too many stores installed
            'downgraded' => Application_Model_User::DOWNGRADED_TOO_MANY_STORES_SYMLINKS_DELETED
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
        $service->clear()->reload('apache2')->call();
    }
    $log->log('Downgraded '.count($downgrade_by_id).' users', Zend_Log::INFO);
} else {
    //$log->log('There is no user to downgrade.', Zend_Log::INFO);
}