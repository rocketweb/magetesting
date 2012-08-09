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
    
public function findByFilters(array $filters){
        
         $allowed_keys = array(
             'name',
             'namespace_module',           
             'from_version',
             'to_version',
             'edition',
             'is_dev',
         );
         
        $select = $this->select()
            ->from($this->_name);
            
            foreach (array_keys($filters) as $key){
                if (!in_array($key,$allowed_keys)){
                    return null;
                }
            }
        
            foreach($filters as $key => $value){
	      $select->where($key.' = ?', $value);
            }
                
	return $this->fetchRow($select);
    }
}
