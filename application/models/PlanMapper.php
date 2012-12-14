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

        if (null === ($id = $plan->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

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
              ->setFtpAccess($row->ftp_access)
              ->setPhpmyadminAccess($row->phpmyadmin_access)
              ->setCanAddCustomStore($row->can_add_custom_store)
              ->setBillingPeriod($row->billing_period)
              ->setPaypalId($row->paypal_id)
              ->setBraintreeId($row->braintree_id)
              ->setIsHidden($row->is_hidden);
        
        return $plan;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete($id);
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
                  ->setFtpAccess($row->ftp_access)
                  ->setPhpmyadminAccess($row->phpmyadmin_access)
                    ->setCanAddCustomStore($row->can_add_custom_store)
                  ->setBillingPeriod($row->billing_period)
                  ->setPaypalId($row->paypal_id)
                  ->setBraintreeId($row->braintree_id)
                  ->setIsHidden($row->is_hidden);
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
                  ->setFtpAccess($row->ftp_access)
                  ->setPhpmyadminAccess($row->phpmyadmin_access)
                    ->setCanAddCustomStore($row->can_add_custom_store)
                  ->setBillingPeriod($row->billing_period)
                  ->setPaypalId($row->paypal_id)
                  ->setBraintreeId($row->braintree_id)
                  ->setIsHidden($row->is_hidden);
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
              ->setFtpAccess($row->ftp_access)
              ->setPhpmyadminAccess($row->phpmyadmin_access)
                ->setCanAddCustomStore($row->can_add_custom_store)
              ->setBillingPeriod($row->billing_period)
              ->setPaypalId($row->paypal_id)
              ->setBraintreeId($row->braintree_id)
              ->setIsHidden($row->is_hidden);
        return $entry;

    }

}