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
    ->joinLeft('plan','user.plan_id = plan.id', array('plan.billing_period', 'plan.price'))
    ->where(new Zend_Db_Expr('plan_active_to <= CURRENT_TIMESTAMP'))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 1')
    ->where('billing_period NOT LIKE ?', '%days');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        if($row['braintree_vault_id'] AND $row['price']) {
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
            } else {
                $log->log('Plan has not been renewed for user: ' . $row['id'], Zend_Log::INFO);
            }
        }
    }
    $log->log('All plan renewals has been processed.', Zend_Log::INFO);
} else {
    $log->log('There is no plan to renewal.', Zend_Log::INFO);
}