<?php

class Application_Model_DbTable_Server extends Zend_Db_Table_Abstract
{

    protected $_name = 'server';

    public function fetchMostEmptyServer()
    {
        /**
          SELECT s.id,s.name,count(u.id) as users 
            FROM server AS s 
            LEFT JOIN user u ON u.server_id = s.id 
            GROUP by s.id
            ORDER by users
         */
        $select = $this->select()
                       ->from(array('s' => $this->_name), array('s.id'))
                       ->setIntegrityCheck(false)
                       ->joinLeft(array('u' => 'user'), 'u.server_id = s.id', array(new Zend_Db_Expr('IF(u.id IS NULL, 0, count(u.id) ) users')))
                       ->group('s.id')
                       ->order('users asc')
                       ->limit(1);
               
        return $this->fetchRow($select);
    }
}
