<?php

class Application_Model_Queue
{

    public static function add($data)
    {
        Zend_Db_Table::getDefaultAdapter()
                ->insert("queue", array(
                    "edition" => $data['edition'],
                    "status" => $data['status'],
                    "version_id" => $data['version_id'],
                    "user_id" => $data['user_id'],
                    "domain" => $data['domain'],
                        )
        );
    }
    
    public static function getAll(){
        $sql = Zend_Db_Table::getDefaultAdapter()
                ->select()->from('queue')->join('version', 'queue.version_id = version.id',array('version'));
        return $sql->query()->fetchAll();
    }

}

