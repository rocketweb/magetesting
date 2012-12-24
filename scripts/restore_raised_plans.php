<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->where('plan_raised_to_date <= ?', date("Y-m-d H:i:s"))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 1');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        $log->log('Restored plan for user: ' . $row['id'], Zend_Log::INFO);
        $db->update(
            'user', // table
            array(
                'plan_id' => $row['plan_id_before_raising'],
                'plan_id_before_raising' => null,
                'plan_raised_to_date' => null
            ), // set
            array('id = ?' => $row['id']) // where
        );
    }
} else {
    //$log->log('No plans to restore', Zend_Log::INFO);
}