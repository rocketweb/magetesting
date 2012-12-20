<?php

class Application_Model_DbTable_StoreExtension extends Zend_Db_Table_Abstract
{

    protected $_name = 'store_extension';

    public function fetchStoreExtension($store_id, $extension_id) {
        $select = $this->select()
                       ->from($this->_name)
                       ->setIntegrityCheck(false)
                       ->where('store_id = ?', $store_id)
                       ->where('extension_id = ?', $extension_id);
               
        return $this->fetchAll($select);
    }
}