<?php

class Application_Model_DbTable_PaymentAdditionalStore extends Zend_Db_Table_Abstract
{

    protected $_name = 'payment_additional_store';

    public function fetchWaitingForConfirmation()
    {
        $select =
            $this
                ->select()
                ->setIntegrityCheck(false)
                ->from($this->_name, $this->_name.'.*')
                ->join('user', 'user.id = '.$this->_name.'.user_id', '')
                ->join('plan', 'user.plan_id = plan.id', '')
                ->where($this->_name.'.downgraded = ?', 0)
                ->where($this->_name.'.braintree_transaction_confirmed = ?', 0)
                ->where(new Zend_Db_Expr('date(CURRENT_TIMESTAMP) BETWEEN date(DATE_ADD(purchased_date , INTERVAL 1 DAY )) AND date(DATE_ADD(purchased_date , INTERVAL 2 DAY ))'))
                ->where(new Zend_Db_Expr('(SELECT TIMESTAMP(date) FROM payment WHERE transaction_type = \'subscription\' AND payment.user_id = user.id ) < CURRENT_TIMESTAMP'));
        return $this->fetchAll($select);
    }
}
