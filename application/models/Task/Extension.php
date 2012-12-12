<?php

class Application_Model_Task_Extension 
extends Application_Model_Task {
    //private $db;
    //private $config;
    
    public function setup(Application_Model_Queue &$queueElement){
        
        parent::setup($queueElement);
    }
       
}
        