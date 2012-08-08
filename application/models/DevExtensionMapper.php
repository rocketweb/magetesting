<?php

class Application_Model_DevExtensionMapper {

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
            $this->setDbTable('Application_Model_DbTable_DevExtension');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_DevExtension $extension)
    {
        $data = array(
            'id' => $extension->getId(),
            'name' => $extension->getName(),
            'repo_type' => $extension->getRepoType(),
            'repo_url' => $extension->getRepoUrl(),
            'repo_user' => $extension->getRepoUser(),
            'repo_password' => $extension->getRepoPassword(),
            'edition' => $extension->getEdition(),
            'from_version' => $extension->getFromVersion(),
            'to_version' => $extension->getToVersion(),
            'extension_config_file' => $extension->getExtensionConfigFile(),
        );

        if (null === ($id = $extension->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

    }

    public function find($id, Application_Model_DevExtension $extension)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extension->setId($row->id)
        ->setName($row->name)
        ->setRepoType($row->repo_type)
        ->setRepoUrl($row->repo_url)
                ->setRepoUser($row->repo_user)
                ->setRepoPassword($row->repo_password)
                ->setEdition($row->edition)
                ->setFromVersion($row->from_version)
                ->setToVersion($row->to_version)
                ->setExtensionConfigFile($row->extension_config_file)
        ;
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
                ->setRepoType($row->repo_type)
                ->setRepoUrl($row->repo_url)
                ->setRepoUser($row->repo_user)
                ->setRepoPassword($row->repo_password)
                ->setEdition($row->edition)
                ->setFromVersion($row->from_version)
                ->setToVersion($row->to_version)
                ->setExtensionConfigFile($row->extension_config_file)
                ;
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

    /* change this method to fetch all extensions that match selected instance */ 
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

}