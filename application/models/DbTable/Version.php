<?php

class Application_Model_DbTable_Version extends Zend_Db_Table_Abstract
{

    protected $_name = 'version';
     
    public function getVersionsByEdition( $edition )
    {
        $select = $this->select()->where( 'edition = ? ', $edition )->order(array('sorting_order ASC'));
        return $this->fetchAll( $select )->toArray();
    }

}