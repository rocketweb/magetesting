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
    ->where(new Zend_Db_Expr('plan_active_to >= CURRENT_TIMESTAMP'))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 0');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        $braintree = Braintree_Transaction::find($row['braintree_transaction_id']);
        if($braintree->success AND isset($braintree->transaction->status) AND $braintree->transaction->status == 'settled') {
            echo 'Confirmed for user: ' . $row['id'].PHP_EOL;
            $db->update(
                'user', // table
                array('braintree_transaction_confirmed' => 1), // set
                array('id = ?' => $row['id']) // where
            );
        } else {
            echo 'Not confirmed for user: ' . $row['id'].PHP_EOL;
        }
    }
    echo 'All subscriptions checked.'.PHP_EOL;
} else {
    echo 'No subscriptions to check'.PHP_EOL;
}

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('store_extension')
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 0');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        $braintree = Braintree_Transaction::find($row['braintree_transaction_id']);
        if($braintree->success AND isset($braintree->transaction->status) AND $braintree->transaction->status == 'settled') {
            echo 'Confirmed for extension: ' . $row['extension_id']. ' store: '. $row['store_id'].PHP_EOL;
            $db->update(
                    'store_extension', // table
                    array('braintree_transaction_confirmed' => 1), // set
                    array('extension_id = ?' => $row['extension_id'], 'store_id = ?' => $row['store_id']) // where
            );
        } else {
            echo 'Not confirmed for extension: ' . $row['extension_id']. ' store: '. $row['store_id'].PHP_EOL;
        }
    }
    echo 'All extensions checked.'.PHP_EOL;
} else {
    echo 'No extensions to check'.PHP_EOL;
}

