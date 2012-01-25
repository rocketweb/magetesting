<?php

class Application_Model_Edition extends Application_Model_DbTable_Edition {

    public static function getAll() {

        $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('edition');
        return $sql->query()->fetchAll();  
    }
    
    //TODO: prepare mapper for this
    public static function getKeys(){
         $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('edition');
        $res = $sql->query()->fetchAll(); 
        
        foreach ($res as $r){
            $temp[] = $r['key'];
        }
        return $temp;
    }
    
    public static function getOptions(){
         $sql = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('edition');
        $res = $sql->query()->fetchAll(); 
        
        foreach ($res as $r){
            $temp[$r['key']] = $r['name'];
        }
        return $temp;
    }

}

