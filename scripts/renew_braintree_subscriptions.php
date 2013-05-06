<?php

include 'init.console.php';
require_once 'Braintree.php';

Braintree_Configuration::environment($config->braintree->environment);
Braintree_Configuration::merchantId($config->braintree->merchantId);
Braintree_Configuration::publicKey($config->braintree->publicKey);
Braintree_Configuration::privateKey($config->braintree->privateKey);

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('plan','user.plan_id = plan.id', array('plan.billing_period', 'plan.price', 'plan.name as plan_name'))
    ->where('plan_active_to <= ?', date("Y-m-d H:i:s"))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 1')
    ->where('auto_renew = 1');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        if($row['braintree_vault_id'] AND $row['price']) {
            try {
                $braintree = Braintree_Transaction::sale(array(
                    'amount' => $row['price'],
                    'customerId' => $row['braintree_vault_id'],
                    'options' => array(
                        'submitForSettlement' => true
                    )
                ));
    
                if($braintree->success AND isset($braintree->transaction->status) AND $braintree->transaction->status == 'submitted_for_settlement') {
                    $log->log('Plan renewed for user: ' . $row['id'], Zend_Log::INFO);
                    $db->update(
                        'user', // table
                        array(
                            'braintree_transaction_confirmed' => 0,
                            'braintree_transaction_id' => $braintree->transaction->id,
                            'plan_active_to' => date('Y-m-d', 
                                // increase saved plan_active_to by plan billing period
                                strtotime('+' . $row['billing_period'], strtotime($row['plan_active_to']))
                            )
                        ), // set
                        array('id = ?' => $row['id']) // where
                    );
                    $last_payment = new Application_Model_Payment();
                    // use credentials from previous invoice
                    $last_payment->findLastForUser($row['id']);
                    $last_payment->setId(NULL);
                    $last_payment->setDate(NULL);
                    $last_payment->setBraintreeTransactionId($braintree->transaction->id);
                    // fetch plan name and price
                    $plan = new Application_Model_Plan();
                    $plan->find($row['plan_id']);
                    $last_payment->setPrice($row['price']);
                    $last_payment->setTransactionName($row['plan_name']);
                    $last_payment->setTransactionType('subscription');
                    // save new payment
                    $last_payment->save();

                    $adminNotification = new Integration_Mail_AdminNotification();
                    $user = new Application_Model_User();
                    $user->find($row['id']);
                    $adminNotificationData = array('user' => $user, 'plan' => $plan);
                    $adminNotification->setup('renewedPlan', $adminNotificationData);
                    try {
                        $adminNotification->send();
                    } catch(Exception $e) {
                        if($log) {
                            $log->log('Braintree - admin notification email', Zend_Log::DEBUG, $e->getMessage());
                        }
                    }
                } else {
                    $log->log('Plan has not been renewed for user: ' . $row['id'], Zend_Log::INFO);
                }
            } catch(Braintree_Exception $e) {
                $log->log('Braintree service is unavailable - exiting...', Zend_Log::ALERT);
                exit;
            }
        }
    }
    $log->log('All plan renewals has been processed.', Zend_Log::INFO);
} else {
    $log->log('There is no plan to renewal.', Zend_Log::INFO);
}