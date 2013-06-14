<?php

include 'init.console.php';

$payment = new Application_Model_PaymentAdditionalStore();
$result = $payment->fetchStoresToReduce();

if($result) {
    foreach($result as $row) {
        $user = new Application_Model_User();
        $user->find($row->getUserId());
        if((int)$user->getAdditionalStores() !== (int)$user->getAdditionalStoresRemoved()) {
            $user->setAdditionalStoresRemoved((int)$user->getAdditionalStoresRemoved()+(int)$row->getStores());
            if((int)$user->getAdditionalStoresRemoved() > (int)$user->getAdditionalStores()) {
                $user->setAdditionalStoresRemoved($user->getAdditionalStores());
            }
            $stores = new Application_Model_Store();
            $stores = $stores->getAllForUser($user->getId())->getCurrentItemCount();
            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            if($stores > ((int)$user->getAdditionalStores()-(int)$user->getAdditionalStoresRemoved())+(int)$plan->getStores()) {
                // downgraded because of too many stores installed
                $user->setDowngraded(3);
            } else {
                $user->setAdditionalStores((int)$user->getAdditionalStores()-(int)$row->getStores());
                if($user->getAdditionalStores() < 0) {
                    $user->setAdditionalStores(0);
                }
                $user->setAdditionalStoresRemoved((int)$user->getAdditionalStoresRemoved()-(int)$row->getStores());
                if($user->getAdditionalStoresRemoved() < 0) {
                    $user->setAdditionalStoresRemoved(0);
                }
            }
            $user->save();
            $row->setDowngraded(1)->save();
        }
    }
    include 'force_user_to_remove_stores.php';
    $log->log('All additional stores reduced.', Zend_Log::INFO);
} else {
    $log->log('There is no additional store to reduce.', Zend_Log::INFO);
}
