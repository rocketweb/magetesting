<?php

class Application_Model_StoreConflictMapper{
    
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
     * @return Application_Model_DbTable_storeConflict
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_StoreConflict');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_StoreConflict $storeConflict)
    {
        $data = $storeConflict->__toArray();
        

        
        if (null === ($id = $storeConflict->getId())) {
            unset($data['id']);
            $storeConflict->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $storeConflict;
    }

    public function find($id, Application_Model_StoreConflict $storeConflict)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $storeConflict->setId($row->id)
            ->setStoreId($row->store_id)
            ->setModule($row->module)
            ->setClass($row->class)
            ->setRewrites($row->rewrites)
            ->setIgnore($row->ignore);
        return $storeConflict;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_storeConflict();
            $entry->setId($row->id)
                ->setStoreId($row->store_id)
                ->setModule($row->module)
                ->setClass($row->class)
                ->setRewrites($row->rewrites)
                ->setIgnore($row->ignore);

            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchUserStoreConflicts($user_id, $store_id)
    {
        $resultSet = $this->getDbTable()->fetchUserStores($user_id, $store_id);
        if (0 == sizeOf($resultSet)) {
            return array();
        }

        $entries   = array();
        foreach ($resultSet as $store) {
            $conflicts = $this->fetchStoreConflicts($store->id, 0);
            $ignoreConflicts = $this->fetchStoreConflicts($store->id, 1);
            $entries[$store->id] = array(
                'conflicts' => $conflicts,
                'ignore' => $ignoreConflicts,
                'count' => sizeOf($conflicts)
            );
        }
        return $entries;
    }

    public function fetchStoreConflicts($store_id, $ignore)
    {
        $resultSet = $this->getDbTable()->fetchStoreConflicts($store_id, $ignore);
        if (0 == sizeOf($resultSet)) {
            return array();
        }

        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_storeConflict();
            $entry->setId($row->id)
                ->setStoreId($row->store_id)
                ->setModule($row->module)
                ->setClass($row->class)
                ->setRewrites($row->rewrites)
                ->setIgnore($row->ignore);
            $entries[] = $entry->__toArray();
        }
        return $entries;

        /*
         * I'll wait for pagination if needed
        $select = $this->getDbTable()->fetchStoreConflicts($store_id);
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);

        $paginator = new Zend_Paginator($adapter);

        return $paginator;*/
    }
}
