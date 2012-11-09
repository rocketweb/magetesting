<?php

class Application_Model_DbTable_ExtensionScreenshot extends Zend_Db_Table_Abstract
{

    protected $_name = 'extension_screenshot';

    public function findByExtensionId($id)
    {
        $select = 
            $this->select()
                 ->from($this->_name)
                 ->where('extension_id = ?', $id);
        return $this->fetchAll($select);
    }
}
