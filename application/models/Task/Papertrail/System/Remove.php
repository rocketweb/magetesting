<?php
/**
 * Remove system in Papertrail
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_User_Remove extends Application_Model_Task_Papertrail implements Application_Model_Task_Interface {
    
    CONST NAME = 'distributors/systems';
    CONST METHOD = 'DELETE';
    
    public function setup(\Application_Model_Queue &$queueElement) {
        parent::setup($queueElement);
        
        $this->_url_suffix = self::NAME;
    }

    public function process() {
        $this->_updateStatus('removing-papertrail-system');
        
        $data = $this->_init($this->getUri((string)$this->_instanceObject->getDomain()), self::METHOD)
                     ->_getDataResponse();
        
        if(isset($data->message)) {
            //log the message with problem
            throw new Exception($data->message);
        }
        
        if(isset($data->status) && $data->status == 'ok') {
            //success
            $this->_instanceObject->setPapertrailSyslogHostname(null);
            $this->_instanceObject->setHasPapertrailSyslogPort(null);
            $this->_instanceObject->save();
        }

        $this->_updateStatus('ready');
    }

    
}