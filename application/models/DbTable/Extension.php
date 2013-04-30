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
    
    public function fetchStoreExtensions($store, $filter, $order, $offset, $limit) {
        $select_installed_for_store = 
            $this->select()
                 ->from(array('se' => 'store_extension'), array('e.*', 'se.braintree_transaction_id', 'se.braintree_transaction_confirmed', 'se.status', 'installed' => new Zend_Db_Expr('1')))
                 ->setIntegrityCheck(false)
                 ->joinLeft(array('e' => $this->_name), 'e.id = se.extension_id', '')
                 ->where('se.store_id = ?', $store['id']);

        $select_allowed_for_store = 
            $this->select()
                 ->from($this->_name, array('*', 'braintree_transaction_id' => new Zend_Db_Expr('NULL'), 'braintree_transaction_confirmed'  => new Zend_Db_Expr('NULL'), 'status'  => new Zend_Db_Expr('NULL'), 'installed' => new Zend_Db_Expr('0')))
                 ->setIntegrityCheck(false)
                 ->where('extension > ""')
                 ->where(' ?
                     BETWEEN REPLACE(from_version,\'.\',\'\')
                     AND REPLACE(to_version,\'.\',\'\')',
                     (int)str_replace('.','',$store['version'])
                 )
                 ->order(array('sort DESC', 'id DESC'));
        if(isset($filter['query'])) {
            $filter['query'] = str_replace(array('+', ',', '~', '<', '>', '(', ')', '"', '*', '%'), '', $filter['query']);
            $filter['query'] = str_replace('-', '\-', $filter['query']);
            $filter['query'] = '%' . $filter['query'] . '%';

            $select_installed_for_store->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
            $select_allowed_for_store->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
        }

        // get also developer extensions for admins
        // get only CE extensions for non admin users
        if (Zend_Auth::getInstance()->getIdentity()->group == 'admin') {
            $select_allowed_for_store->where('is_dev IN (?)',array(0,1));
        } else {
            $select_allowed_for_store->where('edition = ?', 'CE')
                                     ->where('is_dev  = ? ',0);
        }
        $select_last_version_ids = 
            $this->select()
                 ->from(array('last_version' => $select_allowed_for_store), array('last_version.*'))
                 ->setIntegrityCheck(false)
                 ->group('last_version.extension_key');

        $select_all_extensions_sorted = 
            $this->select()
                 // add alias for union
                 ->from(array(
                     'all' => $this->select()
                                   ->setIntegrityCheck(false)
                                   ->union(array($select_installed_for_store, $select_last_version_ids))
                 ))
                 ->setIntegrityCheck(false)
                 ->joinLeft(
                     array('ec' => 'extension_category'),
                     'ec.id = all.category_id',
                     array('ec.class as category_class','ec.logo as category_logo')
                 )
                 ->group('all.extension_key')
                 ->order(array('installed DESC', 'price DESC'));


        // where
        if(isset($filter['price'])) {
            $select_all_extensions_sorted->where('price ' . (('premium' == $filter['price']) ? '>' : '=') .' ?', 0);
        }
        if(isset($filter['category'])) {
            $select_all_extensions_sorted->where('category_id = ?', $filter['category']);
        }
        if(isset($filter['install'])) {
            $select_all_extensions_sorted->having('installed = ?', ('installed' == strtolower($filter['install'])) ? 1 : 0);
        }

        // order
        if(isset($order['column']) && in_array(strtolower($order['column']), array('date'))) {
            $direction = (isset($order['dir']) && in_array(strtolower($order['dir']), array('asc', 'desc')));
            $select_all_extensions_sorted->order($order['column']. ' ' . $direction);
        } else {
            $select_all_extensions_sorted->order('price DESC');
        }

        $select_all_extensions_sorted->limit($limit, $offset);

        return $this->fetchAll($select_all_extensions_sorted);
    }

    public function fetchFullListOfExtensions($filter, $order, $offset, $limit)
    {
        $sub_select_inner = 
            $this->select()
                 ->from($this->_name, array('name', 'edition', 'version', 'extension_key'))
                 ->setIntegrityCheck(false)
                 ->order(array('sort DESC', 'id DESC'));
        $identity = Zend_Auth::getInstance()->getIdentity();
        if(!is_object($identity) || 'admin' != $identity->group) {
            $sub_select_inner->where('edition = ?', 'CE')
                             ->where('extension > ""');
        }
        // where
        if(isset($filter['price'])) {
            $sub_select_inner->where('price ' . (('premium' == $filter['price']) ? '>' : '=') .' ?', 0);
        }
        if(isset($filter['category'])) {
            $sub_select_inner->where('category_id = ?', $filter['category']);
        }
        if(isset($filter['edition'])) {
            $sub_select_inner->where('edition = ?', strtoupper($filter['edition']));
        }
        if(isset($filter['query'])) {
            $filter['query'] = str_replace(array('+', ',', '~', '<', '>', '(', ')', '"', '*', '%'), '', $filter['query']);
            $filter['query'] = str_replace('-', '\-', $filter['query']);
            $filter['query'] = '%' . $filter['query'] . '%';
            $sub_select_inner->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
        }
        $sub_select =
            $this->select()
                 ->setIntegrityCheck(false)
                 ->from($sub_select_inner, array('name', 'edition', 'version'))
                 ->group(array('extension_key', 'edition'));

        $select = 
            $this->select()
                 ->from(array('e1' => $sub_select), '')
                 ->setIntegrityCheck(false)
                 ->joinInner(array('e2' => 'extension'), 'e2.name = e1.name AND e2.edition = e1.edition AND e2.version = e1.version')
                 ->joinLeft(array('ec' => 'extension_category'), 'ec.id = e2.category_id', array('ec.class as category_class','ec.logo as category_logo'));

        // order
        if(isset($order['column']) && in_array(strtolower($order['column']), array('date'))) {
            $direction = (isset($order['dir']) && in_array(strtolower($order['dir']), array('asc', 'desc')));
            $select->order($order['column']. ' ' . $direction);
        } else {
            $select->order('price DESC');
        }

        $select->limit($limit, $offset);

        return $this->fetchAll($select);
    }

    public function findInstalled($store)
    {
        $select = $this->select()
        ->setIntegrityCheck(false)
                ->from($this->_name)
                ->join('store_extension', $this->_name.'.id = store_extension.extension_id')
                ->where('store_id = ?', $store);

        return $this->fetchAll($select);
    }
    
     public function findByFilters(array $filters){
        
         $allowed_keys = array(
             'name',
             'extension_key',
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

    public function findByExtensionKeyAndEdition($extension_key, $edition)
    {
        $select =
            $this->select()
                 ->setIntegrityCheck(false)
                 ->from($this->_name)
                 ->where('extension_key = ?', $extension_key)
                 ->where('edition = ?', $edition)
                 ->order(array('sort DESC', 'id DESC'));

        return $this->fetchAll($select);
    }
}
