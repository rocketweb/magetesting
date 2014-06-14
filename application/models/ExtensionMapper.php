<?php

class Application_Model_ExtensionMapper {

    /* var Application_Model_DbTable_Extension */
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
     * @return Application_Model_DbTable_Extension
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Extension');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Extension $extension)
    {
        $data = $extension->__toArray();
        if (null === ($id = $extension->getId())) {
            unset($data['id']);
            $extension->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

        $cache = $this->getDbTable()->getDefaultMetadataCache();
        $cache->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('extension', 'frontend')
        );
        return $extension;
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
        ->setCategoryId($row->category_id)
        ->setAuthor($row->author)
        ->setLogo($row->logo)
        ->setVersion($row->version)
        ->setExtension($row->extension)
        ->setExtensionEncoded($row->extension_encoded)
        ->setExtensionKey($row->extension_key)
        ->setFromVersion($row->from_version)
        ->setToVersion($row->to_version)
        ->setEdition($row->edition)
        ->setIsVisible($row->is_visible)
        ->setExtensionOwner($row->extension_owner_id)
        ->setPrice($row->price)
        ->setSort($row->sort)
        ->setExtensionDetail($row->extension_detail)
        ->setExtensionDocumentation($row->extension_documentation);
        return $extension;
    }

    public function findByExtensionFileName($extension_name, $encoded, Application_Model_Extension $extension)
    {
        $result = $this->getDbTable()->findByExtensionFileName($extension_name, $encoded);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extension->setId($row->id)
            ->setName($row->name)
            ->setDescription($row->description)
            ->setCategoryId($row->category_id)
            ->setAuthor($row->author)
            ->setLogo($row->logo)
            ->setVersion($row->version)
            ->setExtension($row->extension)
            ->setExtensionEncoded($row->extension_encoded)
            ->setExtensionKey($row->extension_key)
            ->setFromVersion($row->from_version)
            ->setToVersion($row->to_version)
            ->setEdition($row->edition)
            ->setIsVisible($row->is_visible)
            ->setExtensionOwner($row->extension_owner_id)
            ->setPrice($row->price)
            ->setSort($row->sort)
            ->setExtensionDetail($row->extension_detail)
            ->setExtensionDocumentation($row->extension_documentation);
        return $extension;
    }

    public function findByExtensionKeyAndEdition($extension_key, $edition = null)
    {
        $resultSet = $this->getDbTable()->findByExtensionKeyAndEdition($extension_key, $edition);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Extension();
            $entry->setId($row->id)
                  ->setName($row->name)
                  ->setDescription($row->description)
                  ->setCategoryId($row->category_id)
                  ->setAuthor($row->author)
                  ->setLogo($row->logo)
                  ->setVersion($row->version)
                  ->setExtension($row->extension)
                  ->setExtensionEncoded($row->extension_encoded)
                  ->setExtensionKey($row->extension_key)
                  ->setFromVersion($row->from_version)
                  ->setToVersion($row->to_version)
                  ->setEdition($row->edition)
                  ->setIsVisible($row->is_visible)
                  ->setPrice($row->price)
                  ->setSort($row->sort)
                  ->setExtensionDetail($row->extension_detail)
                  ->setExtensionDocumentation($row->extension_documentation);
            $entries[] = $entry;
        }

        return $entries;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('id = ?' => $id));
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
            ->setCategoryId($row->category_id)
            ->setAuthor($row->author)
            ->setLogo($row->logo)
            ->setVersion($row->version)
            ->setExtension($row->extension)
            ->setExtensionEncoded($row->extension_encoded)
            ->setExtensionKey($row->extension_key)
            ->setFromVersion($row->from_version)
            ->setToVersion($row->to_version)
            ->setEdition($row->edition)
            ->setIsVisible($row->is_visible)
            ->setPrice($row->price)
            ->setSort($row->sort)
            ->setExtensionDetail($row->extension_detail)
            ->setExtensionDocumentation($row->extension_documentation);
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

    public function getInstalledForStore($store, $price_type = '*'){
        //find extensions that match version and edition
        $installedExtensions = $this->getDbTable()->findInstalled($store, $price_type);

        return $installedExtensions;
        
    }
    
    public function fetchStoreExtensions($store_name, $filter, $order, $offset, $limit, $return_count = false) {
        //find store by name
        $storeModel = new Application_Model_Store();
        $store = $storeModel->findByDomain($store_name);

        return $this->getDbTable()->fetchStoreExtensions($store, $filter, $order, $offset, $limit, $return_count);
    }

    public function fetchFullListOfExtensions($filter, $order, $offset, $limit, $return_count = false) {
        return $this->getDbTable()->fetchFullListOfExtensions($filter, $order, $offset, $limit, $return_count);
    }

    public function findByFilters(array $filters, Application_Model_Extension $extension){
        $row = $this->getDbTable()->findByFilters($filters);
        if (empty($row)) {
            return;
        }
        $extension->setId($row->id)
                ->setName($row->name)
                ->setDescription($row->description)
                ->setCategoryId($row->category_id)
                ->setAuthor($row->author)
                ->setLogo($row->logo)
                ->setVersion($row->version)
                ->setExtension($row->extension)
                ->setExtensionEncoded($row->extension_encoded)
                ->setExtensionKey($row->extension_key)
                ->setFromVersion($row->from_version)
                ->setToVersion($row->to_version)
                ->setEdition($row->edition)
                ->setIsVisible($row->is_visible)
                ->setPrice($row->price)
                ->setSort($row->sort)
                ->setExtensionDetail($row->extension_detail)
                ->setExtensionDocumentation($row->extension_documentation);

        return $extension;
    }

    public function fetchDuplicatedFilesCount($open, $encoded) {
        $result = $this->getDbTable()->fetchDuplicatedFilesCount($open, $encoded);
        if(!$result) {
            $result = array('open' => 0, 'encoded' => 0);
        } else {
            $result = $result->toArray();
        }
        return $result;
    }
}
