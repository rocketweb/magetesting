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
    ->where(new Zend_Db_Expr('plan_active_to >= CURRENT_TIMESTAMP'))
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 0');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        $transaction = Braintree_Transaction::find($row['braintree_transaction_id']);

        if(isset($transaction->status) AND $transaction->status == 'settled') {
            $log->log('Plan payment confirmed for user: ' . $row['id'], Zend_Log::INFO);
            $db->update(
                'user', // table
                array('braintree_transaction_confirmed' => 1), // set
                array('id = ?' => $row['id']) // where
            );
        } else {
            $log->log('Plan payment not confirmed for user: ' . $row['id'], Zend_Log::INFO);
        }
    }
    $log->log('All plan payments processed.', Zend_Log::INFO);
} else {
    $log->log('There is no plan payment to process.', Zend_Log::INFO);
}

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('store_extension')
    ->where(new Zend_Db_Expr('LENGTH(braintree_transaction_id) > 0'))
    ->where('braintree_transaction_confirmed = 0');

$result = $db->fetchAll($sql);

if($result) {
    foreach($result as $row) {
        $transaction = Braintree_Transaction::find($row['braintree_transaction_id']);
        if(isset($transaction->status) AND $transaction->status == 'settled') {
            $log->log('Payment confirmed for extension: ' . $row['extension_id']. ' store: '. $row['store_id'], Zend_Log::INFO);
            $db->update(
                    'store_extension', // table
                    array('braintree_transaction_confirmed' => 1), // set
                    array('extension_id = ?' => $row['extension_id'], 'store_id = ?' => $row['store_id']) // where
            );

            //get store
            $storeModel = new Application_Model_Store();
            $storeModel->find($row['store_id']);

            // raise plan for user with 7 days plan
            $user = new Application_Model_User();
            $user->find($storeModel->getUserId());

            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            if(stristr($plan->getBillingPeriod(), 'days')) {
                $user->setPlanActiveTo(date('Y-m-d', strtotime('+7 days', strtotime($user->getPlanActiveTo()))));
                $user->setPlanRaisedToDate(date('Y-m-d', strtotime('+7 days')));
                $user->setPlanIdBeforeRaising($user->getPlanId());
                $user->setPlanId(2);
                $user->save();
            }

            $extensionModel = new Application_Model_Extension();
            $extensionModel->find($row['extension_id']);
                       
            $queueModel = new Application_Model_Queue();
            $queueModel->setStoreId($storeModel->getId());
            $queueModel->setTask('ExtensionOpensource');
            $queueModel->setStatus('pending');
            $queueModel->setUserId($storeModel->getUserId());
            $queueModel->setServerId($storeModel->getServerId());
            $queueModel->setExtensionId($row['extension_id']);
            $queueModel->setParentId(0);
            $queueModel->save();
            $opensourceId = $queueModel->getId();
            unset($queueModel);
            
            $queueModel = new Application_Model_Queue();
            $queueModel->setStoreId($storeModel->getId());
            $queueModel->setTask('RevisionCommit');
            $queueModel->setTaskParams(
                                array(
                                    'commit_comment' => 'Decoding ' . $extensionModel->getName() . ' (' . $extensionModel->getVersion() . ')',
                                    'commit_type' => 'extension-decode'
                                )
                        );
            $queueModel->setStatus('pending');
            $queueModel->setUserId($storeModel->getUserId());
            $queueModel->setServerId($storeModel->getServerId());
            $queueModel->setExtensionId($row['extension_id']);
            $queueModel->setParentId($opensourceId);
            $queueModel->save();
            
        } else {
            $log->log('Payment not confirmed for extension: ' . $row['extension_id']. ' store: '. $row['store_id'], Zend_Log::INFO);
        }
    }
    $log->log('All extension payments processed.', Zend_Log::INFO);
} else {
    $log->log('There is no extension payment to process.', Zend_Log::INFO);
}

