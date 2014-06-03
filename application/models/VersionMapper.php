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

    /**
     * @return Application_Model_DbTable_Version
     */
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
            'version'  => $user->getVersion(),
            'sample_data_version'  => $user->getVersion()
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
                ->setVersion($row->version)
                ->setSampleDataVersion($row->sample_data_version);
        return $version;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete($id);
    }

    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll(null, array('edition', 'sorting_order'));
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Version();
            $entry->setId($row->id)
                    ->setEdition($row->edition)
                    ->setVersion($row->version)
                    ->setSampleDataVersion($row->sample_data_version);
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

    public function getKeys($with_edition = false) {

        $temp = array();
        $authGroup = Zend_Auth::getInstance()->getIdentity()->group;
        foreach ($this->fetchAll() as $r) {
                $edition = $with_edition ? $r->getEdition() : '';
                $temp[] = $edition.$r->getId();
        }
        return $temp;

    }

}