<?php

class Application_Model_DbTable_Revision extends Zend_Db_Table_Abstract
{
    protected $_name = 'revision';
    protected $_primary = 'id';

    public function getAllForStore($store_id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('r' => $this->_name), array('r.id', 'r.comment', 'r.filename', 'r.db_before_revision', 'r.type'))
                       ->where('r.store_id = ?', $store_id)
                       ->order('r.id DESC');
        return $this->fetchAll($select);
    }
    
    /**
     * Currently not used
     * @param type $store_id
     * @return type
     */
     
    public function getPreLastForStore($store_id)
    {
        $select = $this->select()
                       ->from($this->_name)
                       ->where('store_id = ?', $store_id)
                       ->order('id DESC')
                       ->limit(1, 1);
        return $this->fetchRow($select);
    }
    
    /**
     * Used only in revert
     * @param type $store_id
     * @return type
     */
     
    public function getLastForStore($store_id)
    {
        $select = $this->select()
                       ->from($this->_name)
                       ->where('store_id = ?', $store_id)
                       ->order('id DESC')
                       ->limit(1);
        return $this->fetchRow($select);
    }
}