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

        if (null === ($id = $edition->getId())) {
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
              ->setInstances($row->instances)
              ->setPrice($row->price);
        return $plan;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete($id);
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Plan();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setInstances($row->instances)
                  ->setPrice($row->price);
            $entries[] = $entry;
        }
        return $entries;
    }

}