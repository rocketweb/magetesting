<?php

class Application_Model_StoreMapper {

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
            $this->setDbTable('Application_Model_DbTable_Store');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Store $store)
    {
        $data = array(
            'id'                         => $store->getId(),
            'edition'                    => $store->getEdition(),
            'status'                     => $store->getStatus(),
            'version_id'                 => $store->getVersionId(),
            'user_id'                    => $store->getUserId(),
            'server_id'                  => $store->getServerId(),
            'domain'                     => $store->getDomain(),
            'store_name'              => $store->getStoreName(),
            'description'                => $store->getDescription(),
            'sample_data'                => $store->getSampleData(),
            'backend_password'           => $store->getBackendPassword(),
            'custom_protocol'            => $store->getCustomProtocol(),
            'custom_host'                => $store->getCustomHost(),
            'custom_port'                => $store->getCustomPort(),
            'custom_remote_path'         => $store->getCustomRemotePath(),
            'custom_login'               => $store->getCustomLogin(),
            'custom_pass'                => $store->getCustomPass(),
            'custom_sql'                 => $store->getCustomSql(),
            'error_message'              => $store->getErrorMessage(),
            'revision_count'             => $store->getRevisionCount(),
            'type'                       => $store->getType(),
            'custom_file'                => $store->getCustomFile(),
            'papertrail_syslog_hostname' => $store->getPapertrailSyslogHostname(),
            'papertrail_syslog_port'     => $store->getPapertrailSyslogPort(),
                
        );

        if (null === ($id = $store->getId())) {
            unset($data['id']);
            $data['backend_password'] = '';
            return $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }      

    }

    public function find($id, Application_Model_Store $store)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $store->setId($row->id)
                ->setEdition($row->edition)
                ->setStatus($row->status)
                ->setVersionId($row->version_id)
                ->setUserId($row->user_id)
                ->setServerId($row->server_id)
                ->setDomain($row->domain)
                ->setStoreName($row->store_name)
                ->setDescription($row->description)
                ->setSampleData($row->sample_data)
                ->setBackendPassword($row->backend_password)
                ->setCustomProtocol($row->custom_protocol)
                ->setCustomHost($row->custom_host)
                ->setCustomPort($row->custom_port)
                ->setCustomRemotePath($row->custom_remote_path)
                ->setCustomLogin($row->custom_login)
                ->setCustomPass($row->custom_pass)
                ->setCustomSql($row->custom_sql)
                ->setErrorMessage($row->error_message)
                ->setRevisionCount($row->revision_count)
                ->setType($row->type)
                ->setCustomFile($row->custom_file)
                ->setPapertrailSyslogPort($row->papertrail_syslog_port)
                ->setPapertrailSyslogHostname($row->papertrail_syslog_hostname);
        return $store;
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
            $entry = new Application_Model_Store();
            $entry->setId($row->id)
                    ->setEdition($row->edition)
                    ->setStatus($row->status)
                    ->setVersionId($row->version_id)
                    ->setUserId($row->user_id)
                    ->setServerId($row->server_id)
                    ->setDomain($row->domain)
                    ->setStoreName($row->store_name)
                    ->setDescription($row->description)
                    ->setSampleData($row->sample_data)
                    ->setBackendPassword($row->backend_password)
                    ->setCustomProtocol($row->custom_protocol)
                    ->setCustomHost($row->custom_host)
                    ->setCustomPort($row->custom_port)
                    ->setCustomRemotePath($row->custom_remote_path)
                    ->setCustomLogin($row->custom_login)
                    ->setCustomPass($row->custom_pass)
                    ->setCustomSql($row->custom_sql)
                    ->setErrorMessage($row->error_message)
                    ->setRevisionCount($row->revision_count)
                    ->setType($row->type)
                    ->setCustomFile($row->custom_file)
                    ->setPapertrailSyslogPort($row->papertrail_syslog_port)
                    ->setPapertrailSyslogHostname($row->papertrail_syslog_hostname);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getAll()
    {
        return $this->getDbTable()->getAllJoinedWithVersions();
    }

    public function changeStatusToClose($store, $byAdmin)
    {
        if($store->getUserId() AND $store->getDomain()) {
            if($byAdmin) {
                
                $this->getDbTable()->update(
                        array('status' => 'closed'),
                        array('domain = ?' => $store->getDomain())
                );
            } else {
                $this->getDbTable()->changeStatusToClose(
                        $store->getUserId(),
                        $store->getDomain()
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

    public function countUserStores( $user_id )
    {
        $data = $this->getDbTable()
                     ->countUserStores( $user_id )
                     ->current();

        return (int)$data->stores;
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
    
    public function findByDomain($domain){
        return $this->getDbTable()
                    ->findByDomain($domain);
    }
    
    public function findPositionByName($store_name)
    {
        return $this->getDbTable()
                    ->findPositionByName($store_name);
        
    }
}
