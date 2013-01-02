<?php

class Application_Model_StoreExtensionMapper{
    
    protected $_dbTable;

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

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_StoreExtension');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_StoreExtension $storeExtension)
    {
        $data = $storeExtension->__toArray();

        if (null === ($id = $storeExtension->getId())) {
            unset($data['id']);
            $data['added_date'] = date('Y-m-d H:i:s');
            $storeExtension->setAddedDate($data['added_date']);  
            $storeExtension->setId($this->getDbTable()->insert($data));
        } else {
            unset($data['added_date']);
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $storeExtension;
    }

    public function find($id, Application_Model_StoreExtension $storeExtension)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $storeExtension->setId($row->id)
            ->setStoreId($row->store_id)
            ->setExtensionId($row->extension_id)
            ->setAddedDate($row->added_date)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setReminderSent($row->reminder_sent);

        return $storeExtension;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_StoreExtension();
            $entry->setId($row->id)
                  ->setStoreId($row->store_id)
                  ->setExtensionId($row->extension_id)
                  ->setAddedDate($row->added_date)
                  ->setBraintreeTransactionId($row->braintree_transaction_id)
                  ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
                  ->setReminderSent($row->reminder_sent);

            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchStoreExtension($store_id, $extension_id, Application_Model_StoreExtension $storeExtension) {
        $result = $this->getDbTable()->fetchStoreExtension($store_id, $extension_id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();       
        
        $storeExtension->setId($row->id)
            ->setStoreId($row->store_id)
            ->setExtensionId($row->extension_id)
            ->setAddedDate($row->added_date)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setReminderSent($row->reminder_sent);

        return $storeExtension;
    }
}
