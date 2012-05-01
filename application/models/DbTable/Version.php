<?php

class Application_Model_DbTable_Version extends Zend_Db_Table_Abstract
{

    protected $_name = 'version';
     
    public function getVersionsByEdition( $edition )
    {
        $select = $this->select()->where( 'edition = ? ', $edition )->order(array('version asc'));
        return $this->fetchAll( $select )->toArray();
    }

}