<?php

class Application_Model_DbTable_Extension extends Zend_Db_Table_Abstract
{

    protected $_name = 'extension';

    /**
     * 
     * @param array $instance
     * @return array
     */
    public function findMatching($instance)
    {
	  //get already installed extensions
	  $installed = $this->findInstalled($instance);
	  
	  $exclude = array();
	  foreach($installed as $ins){
	   $exclude[] = $ins->extension_id;
	  }
    
        $select = $this->select()
                ->from($this->_name)
                ->where('edition = ?', $instance['edition'])
                ->where(' ? BETWEEN REPLACE(from_version,\'.\',\'\') AND REPLACE(to_version,\'.\',\'\')',(int)str_replace('.','',$instance['version']));
                
                if (count($exclude)>0){
                $select->where('id NOT IN (?) ',implode(',',$exclude));
                }
                
//                var_dump($select->__toString());
               
        return $this->fetchAll($select);
    }
    
    public function findInstalled($instance)
    {
        $select = $this->select()
		->setIntegrityCheck(false)
                ->from($this->_name)
                ->join('extension_queue', $this->_name.'.id = extension_queue.extension_id')
                ->where('queue_id = ?', $instance['id'])
                ;
               
               //var_dump($select->__toString());
        return $this->fetchAll($select);
    }
}
