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

    public function fetchExtensionOrders($extensionOwnerId)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->_name, array($this->_name.'.date', $this->_name.'.id as payment_id', $this->_name.'.price'))
            ->joinLeft(
                'store_extension',
                $this->_name.'.braintree_transaction_id = store_extension.braintree_transaction_id',
                array('extension_id', 'braintree_transaction_id', 'braintree_transaction_confirmed')
            )
            ->joinLeft('extension','extension.id = store_extension.extension_id',array('name','edition','version'))
            ->order($this->_name.'.date DESC')
            ->where('extension.extension_owner_id = ?',$extensionOwnerId);
        return $select->query()->fetchAll();
    }
}
