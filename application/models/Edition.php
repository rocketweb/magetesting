<?php

class Application_Model_Edition extends Application_Model_DbTable_Edition {

    public static function getAll() {

        $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('edition');
        return $sql->query()->fetchAll();  
    }

}

