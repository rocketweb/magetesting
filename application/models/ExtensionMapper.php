<?php

class Application_Model_ExtensionMapper {

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
            $this->setDbTable('Application_Model_DbTable_Extension');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Extension $extension)
    {
        $data = array(
            'id' => $extension->getId(),
            'name'   => $extension->getName(),
            'description'   => $extension->getDescription(),
            'file_name'   => $extension->getFileName(),
            'namespace_module'   => $extension->getNamespaceModule(),
            'from_version'   => $extension->getFromVersion(),
            'to_version'   => $extension->getToVersion(),
            'edition'   => $extension->getEdition(),
            'is_dev'   => $extension->getIsDev(),
        );

        if (null === ($id = $extension->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

    }

    public function find($id, Application_Model_Extension $extension)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extension->setId($row->id)
        ->setName($row->name)
        ->setDescription($row->description)
        ->setFileName($row->file_name)
        ->setNamespaceModule($row->namespace_module)
        ->setFromVersion($row->from_version)
        ->setToVersion($row->to_version)
        ->setEdition($row->edition)
        ->setIsDev($row->is_dev);
        return $extension;
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
            $entry = new Application_Model_Extension();
            $entry->setId($row->id)
            ->setName($row->name)
            ->setDescription($row->description)
            ->setFileName($row->file_name)
            ->setNamespaceModule($row->namespace_module)
            ->setFromVersion($row->from_version)
            ->setToVersion($row->to_version)
            ->setEdition($row->edition)
            ->setIsDev($row->is_dev);
            $entries[] = $entry;
        }
        return $entries;
    }

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

        foreach ($this->fetchAll() as $r) {
            if($r->getEdition() == 'CE' OR $authGroup == 'admin') {
                $temp[$r->getId()] = $r->getName();
            }
        }
        return $temp;

    }
    
    public function getAllForInstance($instance_name){
        
        //find instance by name 
        $instanceModel = new Application_Model_Queue();
        $instance = $instanceModel->findByName($instance_name);
        
        //find extensions that match version and edition
        $matchingExtensions = $this->getDbTable()->findMatching($instance);
        
        //return them 
        $returnedArray = array();
        foreach($matchingExtensions as $me){
	  $returnedArray[$me->id] = $me->name;
        }
        return $returnedArray;
        
    }
    
    public function getInstalledForInstance($instance_name){
        
        //find instance by name 
        $instanceModel = new Application_Model_Queue();
        $instance = $instanceModel->findByName($instance_name);
        
        //find extensions that match version and edition
        $installedExtensions = $this->getDbTable()->findInstalled($instance);
        
        //return them 
        $returnedArray = array();
        foreach($installedExtensions as $me){
	  $returnedArray[$me->id] = $me->name;
        }
        return $returnedArray;
        
    }
    
    
    public function findByFilters(array $filters, Application_Model_Extension $extension){    
        $row = $this->getDbTable()->findByFilters($filters);
        if (empty($row)) {
            return;
        }
        $extension->setId($row->id)
                ->setName($row->name)
                ->setDescription($row->description)
                ->setFileName($row->file_name)
                ->setNamespaceModule($row->namespace_module)
                ->setFromVersion($row->from_version)
                ->setToVersion($row->to_version)
                ->setEdition($row->edition)
                ->setIsDev($row->is_dev);
        ;
        return $extension;
    }

}
