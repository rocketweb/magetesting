<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->where(new Zend_Db_Expr('plan_raised_to_date <= CURRENT_TIMESTAMP'))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 1');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        echo 'Restored plan for user: ' . $row['id'].PHP_EOL;
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
    echo 'All plans restored.'.PHP_EOL;
} else {
    echo 'No plans to restore'.PHP_EOL;
}