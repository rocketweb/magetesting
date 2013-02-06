<?php

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select->from('store', array('backend_password', 'custom_pass', 'id'));

$result = $db->fetchAll($sql);

if($result) {
    $key = $config->mcrypt->key;
    $vector = $config->mcrypt->vector;
            
    foreach($result as $row) {
        $row = (object)$row;
        
        if($row->backend_password) {
            $filter = new Zend_Filter_Encrypt(array('adapter' => 'Mcrypt', 'key' => $key));
            $filter->setVector($vector);
            
            $encrypt = base64_encode($filter->filter($row->backend_password));
            echo $encrypt;
            try {
                $db->update(
                    'store', // table
                    array('backend_password' => $encrypt), // set
                    array('id = ?' => $row->id) // where
                );
            } catch(Exception $e) {
                $log->log('Save encrypted backend password fail! (' . $row->backend_password . ')', Zend_Log::ALERT);
            }
            unset($encrypt);
        }
        
        if($row->custom_pass) {
            $filter = new Zend_Filter_Encrypt(array('adapter' => 'Mcrypt', 'key' => $key));
            $filter->setVector($vector);
            
            $encrypt = base64_encode($filter->filter($row->custom_pass));
            echo $encrypt;
            
            try {
                $db->update(
                    'store', // table
                    array('custom_pass' => $encrypt), // set
                    array('id = ?' => $row->id) // where
                );
            } catch(Exception $e) {
                $log->log('Save encrypted custom password fail! (' . $row->custom_pass . ')', Zend_Log::ALERT);
            }
            unset($encrypt);
        }
        
    }

} 
