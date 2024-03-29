<?php
/**
 * Mapper for the plan model
 * @author Grzegorz (golaod)
 * @package Application_Model_PlanMapper
 */
class Application_Model_PlanMapper {

    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * @return Application_Model_DbTable_Plan
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Plan');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Plan $plan)
    {
        $data = $plan->__toArray();

        if (!($id = (int)$plan->getId())) {
            unset($data['id']);
            $plan->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $plan;
    }

    public function find($id, Application_Model_Plan $plan)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $plan->setId($row->id)
              ->setName($row->name)
              ->setStores($row->stores)
              ->setPrice($row->price)
              ->setPriceDescription($row->price_description)
              ->setFtpAccess($row->ftp_access)
              ->setPhpmyadminAccess($row->phpmyadmin_access)
              ->setCanAddCustomStore($row->can_add_custom_store)
              ->setBillingPeriod($row->billing_period)
              ->setBillingDescription($row->billing_description)
              ->setIsHidden($row->is_hidden)
              ->setAutoRenew($row->auto_renew)
        	  ->setCanDoDbRevert($row->can_do_db_revert)
              ->setMaxStores($row->max_stores)
              ->setStorePrice($row->store_price);
        
        return $plan;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('id = ?' => $id));
    }

    public function fetchAll($fetch_hidden = false)
    {
        $where = null;
        if(!$fetch_hidden) {
            $where = $this->getDbTable()->select()->where('is_hidden = ?', 0);
        }
        $resultSet = $this->getDbTable()->fetchAll($where);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Plan();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setStores($row->stores)
                  ->setPrice($row->price)
                  ->setPriceDescription($row->price_description)
                  ->setFtpAccess($row->ftp_access)
                  ->setPhpmyadminAccess($row->phpmyadmin_access)
                  ->setCanAddCustomStore($row->can_add_custom_store)
                  ->setBillingPeriod($row->billing_period)
                  ->setBillingDescription($row->billing_description)
                  ->setIsHidden($row->is_hidden)
                  ->setAutoRenew($row->auto_renew)
            	  ->setCanDoDbRevert($row->can_do_db_revert)
                  ->setMaxStores($row->max_stores)
                  ->setStorePrice($row->store_price);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function getAllByPhpmyadminAccess($has){
        
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('phpmyadmin_access = ?', $has));
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Plan();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setStores($row->stores)
                  ->setPrice($row->price)
                  ->setPriceDescription($row->price_description)
                  ->setFtpAccess($row->ftp_access)
                  ->setPhpmyadminAccess($row->phpmyadmin_access)
                  ->setCanAddCustomStore($row->can_add_custom_store)
                  ->setBillingPeriod($row->billing_period)
                  ->setBillingDescription($row->billing_description)
                  ->setIsHidden($row->is_hidden)
                  ->setAutoRenew($row->auto_renew)
            	  ->setCanDoDbRevert($row->can_do_db_revert)
                  ->setMaxStores($row->max_stores)
                  ->setStorePrice($row->store_price);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function findByBraintreeId($braintree_id){
        
        $row = $this->getDbTable()->fetchRow($this->getDbTable()->select()->where('braintree_id = ?', $braintree_id),PDO::FETCH_OBJ);

        if (!$row){
            return null;
        }
        
        $entry = new Application_Model_Plan();
        $entry->setId($row->id)
              ->setName($row->name)
              ->setStores($row->stores)
              ->setPrice($row->price)
              ->setPriceDescription($row->price_description)
              ->setFtpAccess($row->ftp_access)
              ->setPhpmyadminAccess($row->phpmyadmin_access)
              ->setCanAddCustomStore($row->can_add_custom_store)
              ->setBillingPeriod($row->billing_period)
              ->setBillingDescription($row->billing_description)
              ->setIsHidden($row->is_hidden)
              ->setAutoRenew($row->auto_renew)
        	  ->setCanDoDbRevert($row->can_do_db_revert)
              ->setMaxStores($row->max_stores)
              ->setStorePrice($row->store_price);
        return $entry;

    }

    public function fetchList() {
        $adapter = new Zend_Paginator_Adapter_Array($this->getDbTable()->fetchList());
        return new Zend_Paginator($adapter);
    }

}