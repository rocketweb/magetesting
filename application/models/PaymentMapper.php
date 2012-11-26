<?php
/**
 * Mapper for the payment model
 * @author Grzegorz (golaod)
 * @package Application_Model_PaymentMapper
 */
class Application_Model_PaymentMapper {

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
            $this->setDbTable('Application_Model_DbTable_Payment');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Payment $payment)
    {
        $data = $payment->__toArray();

        if (null === ($id = $payment->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function find($id, Application_Model_Payment $payment)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return array();
        }
        $row = $result->current();
        $payment->setId($row->id)
                ->setPrice($row->price)
                ->setFirstName($row->first_name)
                ->setLastName($row->last_name)
                ->setStreet($row->street)
                ->setPostalCode($row->postal_code)
                ->setCity($row->city)
                ->setState($row->state)
                ->setCountry($row->country)
                ->setDate($row->date)
                ->setPlanName($row->plan_name)
                ->setUserId($row->user_id)
                ->setSubscrId($row->subscr_id);
        return $payment;
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Payment();
            $entry->setId($row->id)
                  ->setPrice($row->price)
                  ->setFirstName($row->first_name)
                  ->setLastName($row->last_name)
                  ->setStreet($row->street)
                  ->setPostalCode($row->postal_code)
                  ->setCity($row->city)
                  ->setState($row->state)
                  ->setCountry($row->country)
                  ->setDate($row->date)
                  ->setPlanName($row->plan_name)
                  ->setUserId($row->user_id)
                  ->setSubscrId($row->subscr_id);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function fetchPaymentsByUser($id)
    {
        if(0 >= (int)$id) {
            throw new Exception('Wrong payment id number('.$id.')');
        }

        $resultSet = $this->getDbTable()->fetchPaymentsByUser($id);

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Payment();
            $entry->setId($row->id)
                  ->setPrice($row->price)
                  ->setFirstName($row->first_name)
                  ->setLastName($row->last_name)
                  ->setStreet($row->street)
                  ->setPostalCode($row->postal_code)
                  ->setCity($row->city)
                  ->setState($row->state)
                  ->setCountry($row->country)
                  ->setDate($row->date)
                  ->setPlanName($row->plan_name)
                  ->setUserId($row->user_id)
                  ->setSubscrId($row->subscr_id);
            $entries[] = $entry;
        }
        return $entries;
    }
}