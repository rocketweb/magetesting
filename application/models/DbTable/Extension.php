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
        /*
SELECT  `all` . * ,  `ec`.`class` AS  `category_class` ,  `ec`.`logo` AS  `category_logo` 
FROM (
    SELECT  `e` . * ,  ` , 1 AS  `installed` 
    FROM  `store_extension` AS  `se` 
    LEFT JOIN  `extension` AS  `e` ON e.id = se.extension_id
    WHERE ( se.store_id =  '101' )

    UNION

    SELECT  `last_version` . * 
    FROM (
        SELECT  `extension` . * ,  , 0 AS  `installed` 
        FROM  `extension` 
        WHERE
            ( extension IS NOT NULL )
            AND (
                1411
                BETWEEN REPLACE( from_version,  '.',  '' ) 
                AND REPLACE( to_version,  '.',  '' )
            ) 
            AND ( edition =  'CE' )
            AND ( is_dev IN ( 0, 1 ) )
        ORDER BY  `name` ASC ,  `version` DESC
    ) AS  `last_version` 
    GROUP BY  `last_version`.`name`
) AS  `all` 
LEFT JOIN  `extension_category` AS  `ec` ON ec.id = all.category_id
GROUP BY  `all`.`name` 
ORDER BY  `installed` DESC ,  `price` DESC
         */
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
                 ->where('extension IS NOT NULL')
                 ->where(' ?
                     BETWEEN REPLACE(from_version,\'.\',\'\')
                     AND REPLACE(to_version,\'.\',\'\')',
                     (int)str_replace('.','',$store['version'])
                 )
                 ->order(array('name', 'version DESC'));

        // get also developer extensions for admins
        // get only CE extensions for non admin users
        if (Zend_Auth::getInstance()->getIdentity()->group == 'admin') {
            $select_allowed_for_store->where('edition = ?', 'CE');
            $select_allowed_for_store->where('is_dev IN (?)',array(0,1));
        } else {
            $select_allowed_for_store->where('is_dev  = ? ',0);
        }
        $select_last_version_ids = 
            $this->select()
                 ->from(array('last_version' => $select_allowed_for_store), array('last_version.*'))
                 ->setIntegrityCheck(false)
                 ->group('last_version.name');

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
                 ->group('all.name')
                 ->order(array('installed DESC', 'price DESC'));

        return $this->fetchAll($select_all_extensions_sorted);
    }

    public function fetchFullListOfExtensions()
    {
        $sub_select = 
            $this->select()
                 ->from($this->_name, array('name', 'edition', 'version' => new Zend_Db_Expr('max(version)')))
                 ->setIntegrityCheck(false)
                 ->where('extension IS NOT NULL')
                 ->group(array('name', 'edition'));
        $identity = Zend_Auth::getInstance()->getIdentity();
        if(!is_object($identity) || 'admin' != $identity->group) {
            $sub_select->where('edition = ?', 'CE');
        }
        $select = 
            $this->select()
                 ->from(array('e1' => $sub_select), '')
                 ->setIntegrityCheck(false)
                 ->joinInner(array('e2' => 'extension'), 'e2.name = e1.name AND e2.edition = e1.edition AND e2.version = e1.version')
                 ->joinLeft(array('ec' => 'extension_category'), 'ec.id = e2.category_id', array('ec.class as category_class','ec.logo as category_logo'))
                 ->order('price DESC');

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
