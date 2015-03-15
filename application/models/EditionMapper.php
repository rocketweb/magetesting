<?php

class Application_Model_EditionMapper {

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
     * @return Application_Model_DbTable_Edition
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Edition');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Edition $edition)
    {
        $data = array(
                'id' => $edition->getId(),
                'key'   => $edition->getKey(),
                'name'   => $edition->getName()
        );

        if (null === ($id = $edition->getId())) {
            unset($data['id']);
            $edition->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $edition;
    }

    public function find($id, Application_Model_Edition $edition)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $edition->setId($row->id)
        ->setKey($row->key)
        ->setName($row->name);
        return $edition;
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
            $entry = new Application_Model_Edition();
            $entry->setId($row->id)
            ->setKey($row->key)
            ->setName($row->name);
            $entries[] = $entry;
        }
        return $entries;
    }

    // TODO: prepare mapper for this
    // duplicate getting
    // best solustion 'select * from version left join edition', prepare 3 <select> and hide them

    public function getKeys() {

        $temp = array();
        foreach ($this->fetchAll() as $r) {
            $temp[] = $r->getId();
        }
        return $temp;

    }

    public function getOptions() {

        $temp = array();
        $authGroup = Zend_Auth::getInstance()->getIdentity()->group;
        $config = Zend_Registry::get('config');
        $enterpriseAllowed = $config->magento->enterpriseApproved != null && $config->magento->enterpriseApproved == 1;

        foreach ($this->fetchAll() as $r) {
            if ($authGroup != 'admin' && $r->getKey() == 'EE' && !$enterpriseAllowed) {
                continue;
            }
                $temp[$r->getKey()] = $r->getName();
        }
        return $temp;

    }

}