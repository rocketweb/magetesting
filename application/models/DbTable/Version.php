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
    
    public function getClosestVersion($versionString){
        
        $versionString = (int)$versionString;
        
        $select = $this->select()
                ->from(
                    array('v' => $this->_name), 
                        array(
                            'id' => 'id',
                            'version',
                            'distance' => new Zend_Db_Expr("ABS(REPLACE(version,'.','') - '".$versionString."')"),
                        )
                )
                ->order(array('distance asc'))
                ->limit(1);
        
        return $this->fetchRow( $select )->toArray();       
    }

}