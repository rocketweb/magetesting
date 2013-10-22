<?php

class Application_Model_PaymentAdditionalStoreMapper {

    protected $_dbTable;

    protected $_error = '';
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * @return Application_Model_DbTable_PaymentAdditionalStore
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_PaymentAdditionalStore');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_PaymentAdditionalStore $payment)
    {
        $data = $payment->__toArray();

        if (null === ($id = $payment->getId())) {
            $payment->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $payment;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('id = ?' => $id));
    }

    public function find($id, Application_Model_PaymentAdditionalStore $payment)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        
        $row = $result->current();
        $payment
            ->setId($row->id)
            ->setUserId($row->user_id)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setPurchasedDate($row->purchased_date)
            ->setStores($row->stores)
            ->setDowngraded($row->downgraded);
        return $payment;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_PaymentAdditionalStore();
            $entry
                ->setId($row->id)
                ->setUserId($row->user_id)
                ->setBraintreeTransactionId($row->braintree_transaction_id)
                ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
                ->setPurchasedDate($row->purchased_date)
                ->setStores($row->stores)
                ->setDowngraded($row->downgraded);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchWaitingForConfirmation()
    {
        $resultSet = $this->getDbTable()->fetchWaitingForConfirmation();
        
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_PaymentAdditionalStore();
            $entry
            ->setId($row->id)
            ->setUserId($row->user_id)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setPurchasedDate($row->purchased_date)
            ->setStores($row->stores)
            ->setDowngraded($row->downgraded);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchStoresToReduce($serverId)
    {
        $resultSet = $this->getDbTable()->fetchStoresToReduce($serverId);
    
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_PaymentAdditionalStore();
            $entry
            ->setId($row->id)
            ->setUserId($row->user_id)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setPurchasedDate($row->purchased_date)
            ->setStores($row->stores)
            ->setDowngraded($row->downgraded);
            $entries[] = $entry;
        }
        return $entries;
    }
}