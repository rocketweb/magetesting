<?php

class Application_Model_DbTable_StoreConflict extends Zend_Db_Table_Abstract
{

    protected $_name = 'store_conflict';
    protected $_storeName = 'store';

    public function fetchStoreConflicts($store_id, $ignore)
    {
        $select = $this->select()
            ->from($this->_name)
            ->setIntegrityCheck(false)
            ->where('`store_id` = ?', $store_id)
            ->where('`ignore` = ?', $ignore);

        return $this->fetchAll($select);
    }

    public function fetchUserStores($user_id, $store_id)
    {
        $select = $this->select()
            ->from($this->_storeName)
            ->setIntegrityCheck(false);
        if($store_id === false) {
            $select->where('`user_id` = ?', $user_id);
        }else{
            $select->where('`id` = ?', $store_id);
        }

        return $this->fetchAll($select);
    }
}