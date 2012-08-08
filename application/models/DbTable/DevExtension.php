<?php

class Application_Model_DbTable_DevExtension extends Zend_Db_Table_Abstract
{

    protected $_name = 'dev_extension';

    /**
     * 
     * @param array $instance
     * @return array
     */
    public function findMatching($instance)
    {
        $select = $this->select()
                ->from($this->_name)
                ->where('edition = ?', $instance['edition'])
                ->where('from_version <= ?', $instance['version'])
                ->where('to_version >= ?', $instance['version']);
                
        return $this->fetchAll($select);
    }
    
}
