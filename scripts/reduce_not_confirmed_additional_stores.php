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
            $stores = new Application_Model_Store();
            $stores = count($stores->getAllForUser($user->getId()));
            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            if($stores > ((int)$user->getAdditionalStores()-(int)$user->getAdditionalStoresRemoved())+(int)$plan->getStores()) {
                $user->setDowngraded(3);
            }
            $user->save();
            $row->setDowngraded(1)->save();
        }
    }
    $log->log('All additional stores reduced.', Zend_Log::INFO);
} else {
    $log->log('There is no additional store to reduce.', Zend_Log::INFO);
}
