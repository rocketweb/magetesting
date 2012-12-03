<?php

class Application_Model_DbTable_Revision extends Zend_Db_Table_Abstract
{
    protected $_name = 'revision';
    protected $_primary = 'id';

    public function getAllForInstance($instance_id)
    {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(array('r' => $this->_name), array('r.id', 'r.comment', 'r.filename', 'r.db_before_revision'))
                       ->where('r.instance_id = ?', $instance_id)
                       ->order('r.id DESC');
        return $this->fetchAll($select);
    }
    
    /**
     * Used only in revert
     * @param type $instance_id
     * @return type
     */
     
    public function getPreLastForInstance($instance_id)
    {
        $select = $this->select()
                       ->from(array('r' => $this->_name), array('r.id', 'r.comment', 'r.filename', 'r.db_before_revision', 'r.hash'))
                       ->where('r.instance_id = ?', $instance_id)
                       ->order('r.id DESC')
                       ->limit(1, 1);
        return $this->fetchRow($select);
    }
}