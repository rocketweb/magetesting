<?php

include 'init.console.php';

$payment = new Application_Model_PaymentAdditionalStore();
$result = $payment->fetchStoresToReduce($config->magento->currentServerId);

if($result) {
    foreach($result as $row) {
        $user = new Application_Model_User();
        $user->find($row->getUserId());
        if((int)$user->getAdditionalStores() !== (int)$user->getAdditionalStoresRemoved()) {
            $stores = new Application_Model_Store();
            $stores = $stores->getAllForUser($user->getId())->getCurrentItemCount();
            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            $stores_to_remove = (int)$row->getStores();
            if($stores > ((int)$user->getAdditionalStores()-$stores_to_remove)+(int)$plan->getStores()) {
                // downgraded because of too many stores installed
                $user->setDowngraded(3);
                // remove as many additional stores as possible
                $not_removed = $stores_to_remove;
                while(
                    $stores <= ((int)$user->getAdditionalStores()-1)+(int)$plan->getStores()
                    && $not_removed
                ) {
                    $not_removed--;
                    $user->setAdditionalStores((int)$user->getAdditionalStores()-1);
                }
                $user->setAdditionalStoresRemoved((int)$user->getAdditionalStoresRemoved()+$not_removed);
            } else {
                $user->setAdditionalStores((int)$user->getAdditionalStores()-$stores_to_remove);
                if($user->getAdditionalStores() < 0) {
                    $user->setAdditionalStores(0);
                }
            }
            $user->save();
            $row->setDowngraded(1)->save();
        }
    }
    include APPLICATION_PATH . '/../scripts/force_user_to_remove_stores.php';
    $log->log('All additional stores reduced.', Zend_Log::INFO);
} else {
    #$log->log('There is no additional store to reduce.', Zend_Log::INFO);
}
