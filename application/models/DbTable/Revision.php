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
                       ->joinLeft(array('e' => 'extension'), new Zend_Db_Expr('r.extension_id = e.id'), array('extension_name' => 'e.name', 'extension_id' => 'e.id'))
                       ->where('r.instance_id = ?', $instance_id)
                       ->order('r.id DESC');
        return $this->fetchAll($select);
    }
}