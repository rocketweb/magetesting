<?php
include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from(array('se' => 'store_extension'))
    ->where('se.added_date <= ?', date('Y-m-d', strtotime('-3 day', strtotime(date('Y-m-d')))) )
    ->where('se.braintree_transaction_id IS NULL')
    ->where('se.reminder_sent = 0')
    ->where('e.price > 0')
    ->joinLeft(array('st' => 'store'), 'st.id = se.store_id', array('st.user_id', 'st.domain'))
    ->joinLeft('server','server.id = se.store_id', array('server_domain' => 'domain'))
    ->joinLeft(array('u' => 'user'), 'u.id = st.user_id', array('u.email', 'u.firstname'))
    ->joinLeft(array('e' => 'extension'), 'e.id = se.extension_id', array('e.name'))
    ->order('st.user_id');

$result = $db->fetchAll($sql);


if($result) {
    $row = current($result);
    $userId = $row['user_id'];
    $mailData = array();
    $storeUrl = $config->magento->storeUrl;
    
    foreach($result as $row) {
        $row['url'] = $storeUrl;
        
        if($userId != $row['user_id']) {
            sendEmail($mailData, $config, $log);
            $mailData = array(); 
        } 
        
        $mailData[] = $row;
        $userId = $row['user_id'];
    }
    
    sendEmail($mailData, $config, $log);
    $log->log('All email with reminder buying extension sent.', Zend_Log::INFO);
} else {
    $log->log('There is no email with reminder buying extension to send.', Zend_Log::INFO);
}


function sendEmail(array $mailData, $appConfig, $log) {
    $mail = new Integration_Mail_ReminderBuyExtension();
    $mail->setup($appConfig, $mailData);

    try {
        $mail->send();

        foreach ($mailData as $record) {
            $extensionModel = new Application_Model_StoreExtension();
            $extension = $extensionModel->find($record['id']);
            $extension->setReminderSent(1);
            $extension->save();
            unset($extensionModel);
        }
        
        $log->log('Sent email with reminder buying extension - ' . $record['extension_id'], Zend_Log::INFO);
        
    } catch (Zend_Mail_Transport_Exception $e){
        $log->log('Reminder Buying Extension - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
    }
}