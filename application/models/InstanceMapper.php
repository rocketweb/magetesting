<?php

class Application_Model_InstanceMapper {

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
            $this->setDbTable('Application_Model_DbTable_Instance');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Instance $instance)
    {
        $data = array(
                'id' => $instance->getId(),
                'edition'          => $instance->getEdition(),
                'status'           => $instance->getStatus(),
                'version_id'       => $instance->getVersionId(),
                'user_id'          => $instance->getUserId(),
                'domain'           => $instance->getDomain(),
                'instance_name'    => $instance->getInstanceName(),
                'sample_data'      => $instance->getSampleData(),
                'backend_password' => '',
                'custom_protocol'  => $instance->getCustomProtocol(),
                'custom_host'      => $instance->getCustomHost(),
                'custom_remote_path' => $instance->getCustomRemotePath(),
                'custom_login'     =>  $instance->getCustomLogin(),
                'custom_pass'      => $instance->getCustomPass(),
                'custom_sql'       => $instance->getCustomSql(),
                'error_message'       => $instance->getErrorMessage(),
                'type'       => $instance->getType(),
        );

        if (null === ($id = $instance->getId())) {
            unset($data['id']);
            return $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }      

    }

    public function find($id, Application_Model_Instance $instance)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $instance->setId($row->id)
                ->setEdition($row->edition)
                ->setStatus($row->status)
                ->setVersionId($row->version_id)
                ->setUserId($row->user_id)
                ->setDomain($row->domain)
                ->setInstanceName($row->instance_name)
                ->setSampleData($row->sample_data)
                ->setBackendPassword($row->backend_password)
                ->setCustomProtocol($row->custom_protocol)
                ->setCustomHost($row->custom_host)
                ->setCustomRemotePath($row->custom_remote_path)
                ->setCustomLogin($row->custom_login)
                ->setCustomPass($row->custom_pass)
                ->setCustomSql($row->custom_sql)
                ->setErrorMessage($row->error_message)
                ->setType($row->type)
                ;
        return $instance;
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
            $entry = new Application_Model_Instance();
            $entry->setId($row->id)
                    ->setEdition($row->edition)
                    ->setStatus($row->status)
                    ->setVersionId($row->version_id)
                    ->setUserId($row->user_id)
                    ->setDomain($row->domain)
                    ->setInstanceName($row->instance_name)
                    ->setSampleData($row->sample_data)
                    ->setBackendPassword($row->backend_password)
		    ->setCustomProtocol($row->custom_protocol)
                    ->setCustomHost($row->custom_host)
                    ->setCustomRemotePath($row->custom_remote_path)
                    ->setCustomLogin($row->custom_login)
                    ->setCustomPass($row->custom_pass)
                    ->setCustomSql($row->custom_sql)
                    ->setErrorMessage($row->error_message)
                    ->setType($row->type)
                    ;
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getAll()
    {
        return $this->getDbTable()->getAllJoinedWithVersions();
    }

    public function changeStatusToClose($instance, $byAdmin)
    {
        if($instance->getUserId() AND $instance->getDomain()) {
            if($byAdmin) {
                
                $this->getDbTable()->update(
                        array('status' => 'closed'),
                        array('domain = ?' => $instance->getDomain())
                );
            } else {
                $this->getDbTable()->changeStatusToClose(
                        $instance->getUserId(),
                        $instance->getDomain()
                );
            }
        }
    }

    public function getAllForUser($user_id)
    {
        $select = $this->getDbTable()
                       ->findAllByUser($user_id);
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        
        return new Zend_Paginator($adapter);
    }

    public function countUserInstances( $user_id )
    {
        $data = $this->getDbTable()
                     ->countUserInstances( $user_id )
                     ->current();

        return (int)$data->instances;
    }
    
    public function getWholeQueue()
    {
        $select = $this->getDbTable()
                     ->getWholeQueueWithUsersName();
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        
        return new Zend_Paginator($adapter);
    }

    public function getPendingItems($timeExecution)
    {
        $keys = array();
        foreach($this->getDbTable()->getPendingItems() as $key => $row) {
            $keys[$row->id] = ++$key*$timeExecution;
        }
        return $keys;
    }
    
    public function findByName($instance_name)
    {
        return $this->getDbTable()
                    ->findByName($instance_name);
        
    }
    
    public function findPositionByName($instance_name)
    {
        return $this->getDbTable()
                    ->findPositionByName($instance_name);
        
    }
}
