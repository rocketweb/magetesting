<?php

class Application_Model_DbTable_Extension extends Zend_Db_Table_Abstract
{

    protected $_name = 'extension';

    /**
     * 
     * @param array $store
     * @return array
     */
    public function findMatching($store)
    {
	  //get already installed extensions
	  $installed = $this->findInstalled($store);
	  
	  $exclude = array();
	  foreach($installed as $ins){
	   $exclude[] = $ins->extension_id;
	  }
    
        $select = $this->select()
                ->from($this->_name)
                ->where('edition = ?', $store['edition'])
                ->where(' ? BETWEEN REPLACE(from_version,\'.\',\'\') AND REPLACE(to_version,\'.\',\'\')',(int)str_replace('.','',$store['version']));
                
                if (count($exclude)>0){
                    $select->where('id NOT IN (?) ',$exclude);
                }
                
                //get also developr extensions for admins
                if (Zend_Auth::getInstance()->getIdentity()->group == 'admin') {
                    $select->where('is_dev IN (?)',array(0,1));
                } else {
                    $select->where('is_dev  = ? ',0);
                }
                
//                var_dump($select->__toString());
               
        return $this->fetchAll($select);
    }
    
    public function fetchStoreExtensions($store) {
        $select = $this->select()
                        ->from(array('e' => $this->_name))
                        ->setIntegrityCheck(false)
                        ->where('e.edition = ?', $store['edition'])
                        ->where(' ? 
                                 BETWEEN REPLACE(e.from_version,\'.\',\'\')
                                 AND REPLACE(e.to_version,\'.\',\'\')',
                            (int)str_replace('.','',$store['version'])
                        );
        $select->joinLeft(
            array('se' => 'store_extension'),
            new Zend_Db_Expr('e.id = se.extension_id AND ( se.store_id =  '.$this->getDefaultAdapter()->quote($store->id).' OR se.store_id IS NULL )'),
            array('se.store_id', 'se.braintree_transaction_id')
        );
        $select->joinLeft(
            array('q' => 'queue'),
            'q.store_id = se.store_id AND q.extension_id = e.id',
            'q.id as q_id'
        );
        $select->joinLeft(
            array('ec' => 'extension_category'),
            'ec.id = e.category_id',
            'ec.class as category_class'
        );
        $select->order(array('se.store_id DESC', 'q_id ASC', 'price DESC'));
        $select->group(new Zend_Db_Expr('e.id DESC'));
        //get also developr extensions for admins
        if (Zend_Auth::getInstance()->getIdentity()->group == 'admin') {
            $select->where('e.is_dev IN (?)',array(0,1));
        } else {
            $select->where('e.is_dev  = ? ',0);
        }

        return $this->fetchAll($select);
    }

    public function findInstalled($store)
    {
        $select = $this->select()
        ->setIntegrityCheck(false)
                ->from($this->_name)
                ->join('store_extension', $this->_name.'.id = store_extension.extension_id')
                ->where('store_id = ?', $store['id'])
                ;
               
               //var_dump($select->__toString());
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
