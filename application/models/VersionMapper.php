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
            'id'       => $version->getId(),
            'edition'  => $version->getEdition(),
            'version'  => $version->getVersion(),
            'sample_data_version'  => $version->getVersion()
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
        $resultSet = $this->getDbTable()->fetchAll(null, array('edition ASC', 'sorting_order DESC'));
        $entries   = array();

        $identity = Zend_Auth::getInstance()->getIdentity();
        $authGroup = is_object($identity) ? $identity->group : '';
        $config = Zend_Registry::get('config');
        $enterpriseAllowed = $config->magento->enterpriseEnabled != null && $config->magento->enterpriseEnabled == 1;

        foreach ($resultSet as $row) {
            if ($authGroup != 'admin' && $row->edition == 'EE' && !$enterpriseAllowed) {
                continue;
            }

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
    
    public function findByVersionString($versionString, $edition, Application_Model_Version $version)
    {
        $result = $this->getDbTable()->findByVersionString($versionString, $edition);
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

}