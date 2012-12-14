<?php

class Application_Model_DbTable_Server extends Zend_Db_Table_Abstract
{

    protected $_name = 'server';

    public function fetchMostEmptyServer()
    {
        /*
         * SELECT IF(q.store_id IS NULL, 0, count(s.id) ) stores, s.id FROM server s 
         * LEFT JOIN queue q ON q.server_id = s.id WHERE q.status = 'ready' OR q.status IS NULL GROUP BY s.id ORDER BY stores ASC LIMIT 1
         */
        $select = $this->select()
                       ->from(array('s' => $this->_name), array('s.id'))
                       ->setIntegrityCheck(false)
                       ->joinLeft(array('i' => 'store'), 'i.server_id = s.id', array(new Zend_Db_Expr('IF(i.id IS NULL, 0, count(s.id) ) stores')))
                       ->where('i.status = ?', 'ready')
                       ->orWhere('i.status IS NULL')
                       ->group('s.id')
                       ->order('stores asc')
                       ->limit(1);
        return $this->fetchRow($select);
    }
}
