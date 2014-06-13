<?php

class Application_Model_DbTable_Version extends Zend_Db_Table_Abstract
{

    protected $_name = 'version';
     
    public function getVersionsByEdition( $edition )
    {
        $select = $this->select()->where( 'edition = ? ', $edition )->order(array('version asc'));
        return $this->fetchAll( $select )->toArray();
    }
    
    /**
     * @param string $versionString e.g 1.7.0.2
     */
    
    public function findByVersionString($versionString, $edition = 'CE')
    {
        $select = $this->select()
                ->from(array('v' => $this->_name))
                ->where('version = ?', $versionString)
                ->where('edition = ?', $edition)
                ->limit(1);

        return $this->fetchAll($select);
    }

}