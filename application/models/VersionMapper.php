<?php

class Application_Model_VersionMapper {

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
            $this->setDbTable('Application_Model_DbTable_Version');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Version $version)
    {
        $data = array(
            'id'       => $user->getId(),
            'edition'  => $user->getEdition(),
            'version'  => $user->getVersion()
        );

        if (null === ($id = $version->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

    }

    public function find($id, Application_Model_Version $version)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $version->setId($row->id)
                ->setEdition($row->edition)
                ->setVersion($row->version);
        return $version;
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
            $entry = new Application_Model_Version();
            $entry->setId($row->id)
                    ->setEdition($row->edition)
                    ->setVersion($row->version);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getAllForEdition( $edition )
    {
        return $this->getDbTable()->getVersionsByEdition( $edition );
    }

    //TODO: prepare mapper for this
    // duplicate getting ( one in getAllForEdition, second using ajax )
    // best solustion 'select * version left join edition', prepare 3 <select> and hide them

    public function getKeys() {

        $temp = array();
        foreach ($this->fetchAll() as $r) {
            $temp[] = $r->getId();
        }
        return $temp;

    }

}