<?php

class Application_Model_Version {

    public static function getAll() {
        $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('version');
        return $sql->query()->fetchAll();
    }
    
    public static function getAllForEdition($keyname) {
        $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('version')
                ->where('edition = ?',$keyname);
        return $sql->query()->fetchAll();
    }

}

