<?php

class Application_Model_ExtensionScreenshotMapper {

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
     * @return Application_Model_DbTable_ExtensionScreenshot
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_ExtensionScreenshot');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_ExtensionScreenshot $extension)
    {
        $data = $extension->__toArray();
        if (null === ($id = $extension->getId())) {
            unset($data['id']);
            $extension->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        return $extension;
    }

    public function find($id, Application_Model_ExtensionScreenshot $extension)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $extension->setId($row->id)
            ->setExtensionId($row->extension_id)
            ->setImage($row->image);
        return $extension;
    }

    public function fetchByExtensionId($id)
    {
        $screenshots = array();
        foreach($this->getDbTable()->findByExtensionId($id) as $row) {
            $entity = new Application_Model_ExtensionScreenshot();
            $screenshots[] = 
                $entity->setId($row->id)
                       ->setExtensionId($row->extension_id)
                       ->setImage($row->image);
        }
        return $screenshots;
    }

    public function delete($id)
    {
        $this->getDbTable()->delete(array('id = ? ' => $id));
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
}
