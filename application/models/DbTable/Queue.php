<?php

class Application_Model_DbTable_Queue extends Zend_Db_Table_Abstract
{

    protected $_name = 'queue';
		
    public function getAllJoinedWithVersions()
    {
    		$select = $this->select()
    				->from($this->_name)
    				->setIntegrityCheck(false)
    				->join('version', 'queue.version_id = version.id',array('version'));
    		return $this->fetchAll( $select );
    }
		
    public function findAllByUser( $user_id )
    {
    		$select = $this->select()
    				->from($this->_name)
    				->setIntegrityCheck(false)
    				->join('version', 'queue.version_id = version.id',array('version'))
    				->where( 'user_id = ?', $user_id );
    		return $this->fetchAll( $select );
    }
    
}

