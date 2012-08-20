<?php

class Application_Model_DbTable_User extends Zend_Db_Table_Abstract
{

    protected $_name = 'user';

    public function findByEmail($email)
    {
        $select = $this->select()
                       ->where('email = ?', $email)
                       ->limit(1);
        return $this->fetchRow($select);
    }
    
    public function findByBraintreeSubscriptionId($subscription_id)
    {
        $select = $this->select()
                       ->where('braintree_subscription_id = ?', $subscription_id)
                       ->limit(1);
        return $this->fetchRow($select);
    }
}
