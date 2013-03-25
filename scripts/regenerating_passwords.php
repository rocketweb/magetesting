<?php

include 'init.console.php';

if($argc < 2 || !isset($argv[1])) {
    echo 'You must enter new key as parameter' . PHP_EOL;
    exit();
}



$select = new Zend_Db_Select($db);
$sql = $select->from('store', array('backend_password', 'custom_pass', 'id'));

$result = $db->fetchAll($sql);

if($result) {
    $key = $config->mcrypt->key;
    $vector = $config->mcrypt->vector;
    
    if(isset($argv[2])) {
        $vectorNew = $argv[2];
    } else {
        $vectorNew = $vector;
    }
            
    foreach($result as $row) {
        $row = (object)$row;
        
        if($row->backend_password) {
            $filter = new Zend_Filter_Decrypt(array('adapter' => 'Mcrypt', 'key' => $key));
            $filter->setVector($vector);
            
            $decrypt = $filter->filter(base64_decode($row->backend_password));
            
            $filter1 = new Zend_Filter_Encrypt(array('adapter' => 'Mcrypt', 'key' => $argv[1]));
            $filter1->setVector($vectorNew);
            
            $encrypt = base64_encode($filter1->filter($decrypt));

            try {
                $db->update(
                    'store', // table
                    array('backend_password' => $encrypt), // set
                    array('id = ?' => $row->id) // where
                );
            } catch(Exception $e) {
                $log->log('Save encrypted backend password fail! (' . $row->id . ')', Zend_Log::ALERT);
            }
            unset($decrypt);
        }
        
        if($row->custom_pass) {
            $filter = new Zend_Filter_Decrypt(array('adapter' => 'Mcrypt', 'key' => $key));
            $filter->setVector($vector);
            
            $decrypt = $filter->filter(base64_decode($row->custom_pass));
            
            $filter1 = new Zend_Filter_Encrypt(array('adapter' => 'Mcrypt', 'key' => $argv[1]));
            $filter1->setVector($vectorNew);
            
            $encrypt = base64_encode($filter1->filter($decrypt));
            
            try {
                $db->update(
                    'store', // table
                    array('custom_pass' => $encrypt), // set
                    array('id = ?' => $row->id) // where
                );
            } catch(Exception $e) {
                $log->log('Save encrypted custom password fail! (' . $row->id . ')', Zend_Log::ALERT);
            }
            unset($decrypt);
        }
        
    }

} 