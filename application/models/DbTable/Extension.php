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
                ->where('REPLACE(from_version,\'.\',\'\') <= ?', (int)str_replace('.','',$store['version']))
                ->where('REPLACE(to_version,\'.\',\'\') >= ? OR REPLACE(to_version,\'.\',\'\') IS NULL', (int)str_replace('.','',$store['version']));
//                ->where(' ? BETWEEN REPLACE(from_version,\'.\',\'\') AND REPLACE(to_version,\'.\',\'\')',(int)str_replace('.','',$store['version']));
                
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
    
    public function fetchStoreExtensions($store, $filter, $order, $offset, $limit, $return_count = false) {
        $select_installed_for_store = 
            $this->select()
                 ->from(array('se' => 'store_extension'), array('e.*', 'se.braintree_transaction_id', 'se.braintree_transaction_confirmed', 'se.status', 'store_extension_id' => 'se.id', 'installed' => new Zend_Db_Expr('1')))
                 ->setIntegrityCheck(false)
                 ->joinLeft(array('e' => $this->_name), 'e.id = se.extension_id', '')
                 ->where('se.store_id = ?', $store['id']);

        $select_allowed_for_store = 
            $this->select()
                 ->from($this->_name, array('*', 'braintree_transaction_id' => new Zend_Db_Expr('NULL'), 'braintree_transaction_confirmed'  => new Zend_Db_Expr('NULL'), 'status'  => new Zend_Db_Expr('NULL'), 'store_extension_id' => new Zend_Db_Expr('NULL'), 'installed' => new Zend_Db_Expr('0')))
                 ->setIntegrityCheck(false)
                 ->where('extension > ""')
                 ->where('REPLACE(from_version,\'.\',\'\') <= ?', (int)str_replace('.','',$store['version']))
                 ->where('REPLACE(to_version,\'.\',\'\') >= ? OR REPLACE(to_version,\'.\',\'\') IS NULL', (int)str_replace('.','',$store['version']))
//                 ->where(' ?
//                     BETWEEN REPLACE(from_version,\'.\',\'\')
//                     AND REPLACE(to_version,\'.\',\'\')',
//                     (int)str_replace('.','',$store['version'])
//                 )
                 ->order(array('sort DESC', 'id DESC'));
        if(isset($filter['query'])) {
            $filter['query'] = str_replace(array('+', ',', '~', '<', '>', '(', ')', '"', '*', '%'), '', $filter['query']);
            $filter['query'] = str_replace('-', '\-', $filter['query']);
            $filter['query'] = '%' . $filter['query'] . '%';

            $select_installed_for_store->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
            $select_allowed_for_store->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
        }

        // get only visible extensions for non admin users
        if(isset($filter['restricted']) && $filter['restricted']) {
            $select_allowed_for_store->where('is_visible = ?', 1);
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
                 ->setIntegrityCheck(false);

        if(!$return_count) {
            $select_all_extensions_sorted
                ->joinLeft(
                    array('ec' => 'extension_category'),
                    'ec.id = all.category_id',
                    array('ec.class as category_class','ec.logo as category_logo')
                )
                ->group('all.extension_key')
                ->order(array('installed DESC', 'price DESC'));
        }


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

        if(!$return_count) {
            // order
            $orders = array('extension_key ASC', 'edition DESC');
            if(isset($order['column']) && in_array(strtolower($order['column']), array('date'))) {
                $direction = (isset($order['dir']) && in_array(strtolower($order['dir']), array('asc', 'desc')));
                array_unshift($orders, $order['column']. ' ' . $direction);
            } else {
                array_unshift($orders, 'price DESC');
            }
            $select_all_extensions_sorted->order($orders);
    
            $select_all_extensions_sorted->limit($limit, $offset);
        } else {
            $select_all_extensions_sorted->reset('columns');
            $select_all_extensions_sorted->columns(array('count' => 'count(*) - installed', 'installed'));
            $result = $this->fetchRow($select_all_extensions_sorted);
            if(!$result) {
                return 0;
            }
            return $result->count;
        }

        return $this->fetchAll($select_all_extensions_sorted);
    }

    public function fetchFullListOfExtensions($filter, $order, $offset, $limit, $return_count = false)
    {
        $cache_name = 'frontend_extension';
        $sub_select_inner = 
            $this->select()
                 ->from($this->_name, array('name', 'edition', 'version', 'extension_key'))
                 ->setIntegrityCheck(false)
                 ->order(array('sort DESC', 'id DESC'));

        if(isset($filter['restricted']) && $filter['restricted']) {
            $cache_name .= '_restricted_true';
            $sub_select_inner
                ->where('edition = ?', 'CE')
                ->where('extension > ""')
                ->where('is_visible = ?', 1);
        }

        // where
        if(isset($filter['price'])) {
            $cache_name .= '_price_'.$filter['price'];
            $sub_select_inner->where('price ' . (('premium' == $filter['price']) ? '>' : '=') .' ?', 0);
        }
        if(isset($filter['category'])) {
            $cache_name .= '_category_'.$filter['category'];
            $sub_select_inner->where('category_id = ?', $filter['category']);
        }
        if(isset($filter['edition'])) {
            $cache_name .= '_edition_'.$filter['edition'];
            $sub_select_inner->where('edition = ?', strtoupper($filter['edition']));
        }
        if(isset($filter['query'])) {
            $filter['query'] = str_replace(array('+', ',', '~', '<', '>', '(', ')', '"', '*', '%'), '', $filter['query']);
            $filter['query'] = str_replace('-', '\-', $filter['query']);
            $cache_name .= '_query_'.preg_replace('/[^a-z0-9]/i', '_', $filter['query']);
            $filter['query'] = '%' . $filter['query'] . '%';
            $sub_select_inner->where('name LIKE ? OR description LIKE ? OR extension_key LIKE ? OR author LIKE ?', $filter['query']);
        }
        $sub_select =
            $this->select()
                 ->setIntegrityCheck(false)
                 ->from($sub_select_inner, array('extension_key', 'edition', 'version')) 
                 ->group(array('extension_key', 'edition'));

        if(!$return_count) {
            $select = 
                $this->select()
                     ->from(array('e1' => $sub_select), '')
                     ->setIntegrityCheck(false)
                     ->joinInner(array('e2' => 'extension'), 'e2.extension_key = e1.extension_key AND e2.edition = e1.edition AND e2.version = e1.version')
                     ->joinLeft(array('ec' => 'extension_category'), 'ec.id = e2.category_id', array('ec.class as category_class','ec.logo as category_logo'));

            // order
            $orders = array('e2.extension_key ASC', 'e2.edition DESC');
            if(isset($order['column']) && in_array(strtolower($order['column']), array('id'))) {
                $direction = (isset($order['dir']) && in_array(strtolower($order['dir']), array('asc', 'desc'))) ? $order['dir'] : 'asc';
                array_unshift($orders, 'e2.'.$order['column']. ' ' . $direction);
            } else {
                array_unshift($orders, 'e2.price DESC');
            }
            foreach($orders as $order) {
                $cache_name .= '_' . str_replace(array(' ', '.'), '', $order);
            }
            $select->order($orders);
            $cache_name .= '_' . $limit . '_' . $offset;
            $select->limit($limit, $offset);
        } else {
            $select =
                $this
                    ->select()
                    ->setIntegrityCheck(false)
                    ->from(array('temp' => $sub_select), array('count' => 'count(*)'));
            $result = $this->fetchRow($select);
            if(!$result) {
                return 0;
            }
            return $result->count;
        }

        if(!isset($filter['restricted']) || $filter['restricted'] === false) {
            $result = $this->fetchAll($select);
        } else {
            $cache = $this->getDefaultMetadataCache();
            if(($result = $cache->load($cache_name)) === false) {
                $result = $this->fetchAll($select);
                $cache->save($result, $cache_name, array('extension', 'frontend'));
            }
        }
        return $result;
    }

    public function findInstalled($store, $price_type)
    {
        $select = $this->select()
        ->setIntegrityCheck(false)
                ->from($this->_name)
                ->join('store_extension', $this->_name.'.id = store_extension.extension_id')
                ->where('store_id = ?', $store);
        if('free' == $price_type) {
            $select->where('price == 0');
        } elseif('premium' == $price_type) {
            $select->where('price > 0');
        }

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

    public function fetchDuplicatedFilesCount($open, $encoded)
    {
        $main_select = 
            $this->select()
                 ->setIntegrityCheck(false);
        $open_select = $this->select()->setIntegrityCheck(false)->from($this->_name, new Zend_Db_Expr('count(*)'));
        $encoded_select = clone $open_select;;
        $open_select->where('extension = ?', $open);
        $encoded_select->where('extension_encoded = ?', $encoded);
        $main_select->from(NULL, array('open' => $open_select, 'encoded' => $encoded_select));
        return $this->fetchRow($main_select);
    }
}
