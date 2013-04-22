<?php
/**
 * Class gets database data from payment table
 * @author Grzegorz (golaod)
 * @package Application_Model_DbTable_Payment
 */
class Application_Model_DbTable_Payment extends Zend_Db_Table_Abstract
{

    protected $_name = 'payment';

    public function fetchPaymentsByUser($id, $limit = false)
    {
        $select = $this->select()
                       ->where('user_id = ?', $id)
                       ->order('date DESC');
        if($limit) {
            $select->limit(1);
        }

        return $this->fetchAll($select);
    }

    public function fetchPaymentByTransactionId($id)
    {
        $select = $this->select()
                       ->where('braintree_transaction_id = ?', $id)
                       ->limit(1);
        return $this->fetchRow($select);
    }

    public function fetchList() {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from($this->_name, array($this->_name.'.*'))
                       ->joinLeft('user', 'user.id = '.$this->_name.'.user_id', array('login'))
                       ->order($this->_name.'.date DESC');
        return $select->query()->fetchAll();
    }
}
