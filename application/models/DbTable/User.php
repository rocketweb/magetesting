<?php

class Application_Model_DbTable_User extends Zend_Db_Table_Abstract
{

    protected $_name = 'user';

    public function findByEmail($email)
    {
        $select = $this->select()
                       ->where('email = ?', $email)
                       ->limit(1);
        return $this->fetchRow($select);
    }
}
