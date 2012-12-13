<?php
/**
 * Remove user in Papertrail
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_User_Remove extends Application_Model_Task_Papertrail implements Application_Model_Task_Interface {
    
    CONST NAME = 'distributors/accounts';
    CONST METHOD = 'DELETE';
    
    public function setup(\Application_Model_Queue &$queueElement) {
        parent::setup($queueElement);
        
        $this->_url_suffix = self::NAME;
    }

    public function process() {
        $this->_updateStatus('removing-papertrail-user');
        
        $data = $this->_init($this->getUri((string)$this->_userObject->getId()), self::METHOD)
                     ->_getDataResponse();
        
        if(isset($data->message)) {
            //log the message with problem
            throw new Exception($data->message);
        }
        
        if(isset($data->status) && $data->status == 'ok') {
            //success
            $this->_userObject->setPapertrailApiToken(null);
            $this->_userObject->setHasPapertrailAccount(0);
            $this->_userObject->save();
        }

        $this->_updateStatus('ready');
    }

    
}