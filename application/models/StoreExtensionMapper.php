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

    /**
     * @return Application_Model_DbTable_StoreExtension
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_StoreExtension');
        }
        return $this->_dbTable;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('`id` = ?' => $id));
    }

    public function save(Application_Model_StoreExtension $storeExtension)
    {
        $data = $storeExtension->__toArray();
        
        if($storeExtension->getReminderSent()===null){
            unset($data['reminder_sent']);
        }
        
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
            ->setReminderSent($row->reminder_sent)
            ->setStatus($row->status);

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
                  ->setReminderSent($row->reminder_sent)
                  ->setStatus($row->status);

            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchStoreExtension($store_id, $extension_id, Application_Model_StoreExtension $storeExtension) {
        $result = $this->getDbTable()->fetchStoreExtension($store_id, $extension_id);
        if (0 == count($result)) {
            return $storeExtension;
        }
        $row = $result->current();
        
        $storeExtension->setId($row->id)
            ->setStoreId($row->store_id)
            ->setExtensionId($row->extension_id)
            ->setAddedDate($row->added_date)
            ->setBraintreeTransactionId($row->braintree_transaction_id)
            ->setBraintreeTransactionConfirmed($row->braintree_transaction_confirmed)
            ->setReminderSent($row->reminder_sent)
            ->setStatus($row->status);

        return $storeExtension;
    }

    public function markAsPaid($paid, $id) {
        $data = array();
        if((int)$id) {
            if($paid) {
                $data = array(
                    'braintree_transaction_id' => -1,
                    'braintree_transaction_confirmed' => 1
                );

                // add OpenSource task
                $store_extension = new Application_Model_StoreExtension();
                $store_extension->find($id);
                $store = new Application_Model_Store();
                $store->find($store_extension->getStoreId());
                $extensionModel = new Application_Model_Extension();
                $extensionModel->find($store_extension->getExtensionId());

                $queueModel = new Application_Model_Queue();
                if(!$queueModel->alreadyExists('ExtensionOpensource', $store->getId(), $extensionModel->getId(), $store->getServerId())) {
                    $queueModel->setStoreId($store->getId());
                    $queueModel->setTask('ExtensionOpensource');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($store->getUserId());
                    $queueModel->setServerId($store->getServerId());
                    $queueModel->setExtensionId($store_extension->getExtensionId());
                    $queueModel->setParentId(0);
                    $queueModel->save();
                    $opensourceId = $queueModel->getId();
                    unset($queueModel);
                    
                    $queueModel = new Application_Model_Queue();
                    $queueModel->setStoreId($store->getId());
                    $queueModel->setTask('RevisionCommit');
                    $queueModel->setTaskParams(
                            array(
                                'commit_comment' => $extensionModel->getName() . ' (Open Source)',
                                'commit_type' => 'extension-decode'
                            )
                    );
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($store->getUserId());
                    $queueModel->setServerId($store->getServerId());
                    $queueModel->setExtensionId($store_extension->getExtensionId());
                    $queueModel->setParentId($opensourceId);
                    $queueModel->save();

                    $store->setStatus('installing-extension')->save();
                } else {
                    return false;
                }
            } else {
                $data = array(
                    'braintree_transaction_id' => new Zend_Db_Expr('NULL'),
                    'braintree_transaction_confirmed' => new Zend_Db_Expr('NULL')
                );

                // add rollback revision task
                $store_extension = new Application_Model_StoreExtension();
                $store_extension->find($id);
                $store = new Application_Model_Store();
                $store->find($store_extension->getStoreId());
                $extensionModel = new Application_Model_Extension();
                $extensionModel->find($store_extension->getExtensionId());

                $queueModel = new Application_Model_Queue();
                $revisionModel = new Application_Model_Revision();
                $revisionModel->getLastForStore($store->getId());
                if(
                    $revisionModel->getExtensionId() == $store_extension->getExtensionId()
                    && 'extension-decode' == $revisionModel->getType()
                    && !$queueModel->alreadyExists('RevisionRollback', $store->getId(), $extensionModel->getId(), $store->getServerId())
                ) {
                    $queueModel->setStoreId($store->getId());
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($store->getUserId());
                    $queueModel->setExtensionId($extensionModel->getId());
                    $queueModel->setParentId(0);
                    $queueModel->setServerId($store->getServerId());
                    $queueModel->setTask('RevisionRollback');
                    $queueModel->setTaskParams(
                            array(
                                'rollback_files_to' => $revisionModel->getHash(),
                                'rollback_db_to' => $revisionModel->getDbBeforeRevision(),
                            )
                    
                    );
                    $queueModel->save();

                    $store->setStatus('rolling-back-revision')->save();
                } else {
                    return false;
                }
            }
            $this->getDbTable()->update($data, array('id = ?' => $id));
            return true;
        }
    }
}
