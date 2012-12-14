<?php
/**
 * Create system in Papertrail
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_System_Create extends Application_Model_Task_Papertrail implements Application_Model_Task_Interface {
    
    CONST NAME = 'distributors/systems';
    CONST METHOD = 'POST';
    
    public function setup(\Application_Model_Queue &$queueElement) {
        parent::setup($queueElement);
        
        $this->_url_suffix = self::NAME;
    }

    public function process() {
        $this->_updateStatus('creating-papertrail-system');
        
        $data = $this->_init($this->getUri(), self::METHOD)
                     ->_setParameterPost()
                     ->_getDataResponse();
        
        if(isset($data->message)) {
            //log the message with problem
            throw new Exception($data->message);
        }
        
        if(isset($data->id)) {
            //success
            $this->_instanceObject->setPapertrailSyslogHostname($data->syslog_hostname)
                                  ->setPapertrailSyslogPort($data->syslog_port)
                                  ->save();
        }
        
        $this->_updateStatus('ready');
    }
    
    /**
     * Set the parameters for POST method
     * 
     * @return Application_Model_Task_Papertrail_System_Create
     */
    protected function _setParameterPost() {
        $data = array(
            'id'         => (string)$this->_instanceObject->getDomain(),
            'name'       => $this->_instanceObject->getInstanceName(),
            'account_id' => (string)$this->_userObject->getId()
        );
        
        $this->_client->setParameterPost($data);
        
        return $this;
    }
    
}