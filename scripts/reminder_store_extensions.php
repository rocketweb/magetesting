<?php
include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from(array('se' => 'store_extension'))
    ->where('se.added_date >= ?', date('Y-m-d', strtotime('-3 day', strtotime(date('Y-m-d')))) )
    ->where('se.braintree_transaction_confirmed = 0')
    ->where('se.reminder_sent = 0')
    ->joinLeft(array('st' => 'store'), 'st.id = se.store_id', array('st.user_id', 'st.domain'))
    ->joinLeft(array('u' => 'user'), 'u.id = st.user_id', array('u.email', 'u.firstname'));

$result = $db->fetchAll($sql);


if($result) {
    foreach($result as $row) {
        $row['url'] = $config->magento->storeUrl;
        
        $mail = new Integration_Mail_ReminderBuyExtension($config->cron->buyStoreExtension, (object)$row);

        try {
            $mail->send();
            
            $extensionModel = new Application_Model_StoreExtension();
            $extension = $extensionModel->find($row['extension_id']);
            $extension->setReminderSent(1);
            $extension->save();
            
            $log->log('Sent email with reminder buying extension - ' . $row['extension_id'], Zend_Log::INFO);
            
        } catch (Zend_Mail_Transport_Exception $e){
            $log = $this->getInvokeArg('bootstrap')->getResource('log');
            $log->log('Reminder Buying Extension - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
        }
        
    }
//    $log->log('All email with reminder buying extension sent.', Zend_Log::INFO);
} else {
    $log->log('There is no email with reminder buying extension to send.', Zend_Log::INFO);
}