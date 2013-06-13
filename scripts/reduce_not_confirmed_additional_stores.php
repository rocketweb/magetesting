<?php

include 'init.console.php';

$payment = new Application_Model_PaymentAdditionalStore();
$result = $payment->fetchStoresToReduce();

if($result) {
    foreach($result as $row) {
        $user = new Application_Model_User();
        $user->find($row->getId());
        if((int)$user->getAdditionalStores() !== (int)$user->getAdditionalStoresRemoved()) {
            $user->setAdditionalStoresRemoved((int)$user->getAdditionalStoresRemoved()+(int)$row->getStores());
            if((int)$user->getAdditionalStoresRemoved() > (int)$user->getAdditionalStores()) {
                $user->setAdditionalStoresRemoved($user->getAdditionalStores());
            }
            $user->save();
            $row->setDowngraded(1)->save();
        }
    }
    $log->log('All additional stores reduced.', Zend_Log::INFO);
} else {
    $log->log('There is no additional store to reduce.', Zend_Log::INFO);
}
