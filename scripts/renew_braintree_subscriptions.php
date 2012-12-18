<?php

include 'init.console.php';
require_once 'Braintree.php';

Braintree_Configuration::environment('sandbox');
Braintree_Configuration::merchantId('hwwzbybn8tvfrhjz');
Braintree_Configuration::publicKey('rpxf8q436zfmp78r');
Braintree_Configuration::privateKey('e87aea495ca0f8dfab7137f52b9adf26');

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('plan','user.plan_id = plan.id', array('plan.billing_period', 'plan.price'))
    ->where(new Zend_Db_Expr('plan_active_to <= CURRENT_TIMESTAMP'))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 1')
    ->where('billing_period != ?', '7 days')
    ->where('downgraded != 2');

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
                echo 'Renewed for user: ' . $row['id'].PHP_EOL;
                $db->update(
                    'user', // table
                    array(
                        'braintree_transaction_confirmed' => 1,
                        'braintree_transaction_id' => $braintree->transaction->id,
                        'plan_active_to' => date('Y-m-d', 
                            // increase saved plan_active_to by plan billing period
                            strtotime('+' . $row['billing_period'], strtotime($row['plan_active_to']))
                        )
                    ), // set
                    array('id = ?' => $row['id']) // where
                );
            } else {
                echo 'Problem for user: ' . $row['id'].PHP_EOL;
            }
        }
    }
    echo 'All subscriptions renewed.'.PHP_EOL;
} else {
    echo 'No subscriptions to renew'.PHP_EOL;
}