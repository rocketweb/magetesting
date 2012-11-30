<?php

class Application_Model_Task_Extension_Remove 
extends Application_Model_Task_Extension 
implements Application_Model_Task_Interface {

    protected $_extensionObject=  '';
    
    private $db;
    private $config;
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
        
        $extensionModel = new Application_Model_Extension();
        $extensionModel->find($queueElement-getExtensionId());
        $this->_extensionObject = $extensionModel;
    }
    
    public function process(Application_Model_Queue $queueElement = null) {

        //remove extension config from etc/modules
        $this->_removeFiles();
        
        //remove database entry entry 
        $this->_removeDbData();

        //clear instance cache
        $this->_clearInstanceCache();
    }

    /**
     * Remove extension files from instance folder
     * TODO: fetch extension xml and remove all files mentioned in it, 
     */
    protected function _removeFiles() {
        exec('sudo rm ' . $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/' . $this->_instanceObject->getDomain() . '/app/etc/modules/'.$this->_extensionObject->getNamespaceModules().'.xml');
    }
    
    
    /**
     * Removes data related to instance extension
     * TODO: remove user_extension if not payable?
     */
    protected function _removeDbData(){
        $this->db->delete('instance_extension', array(
            'extension_id=' . $this->queueObject->getExtensionId(),
            'instance_id=' . $this->_queueObject->getInstanceId()
        ));
        
    }

}
