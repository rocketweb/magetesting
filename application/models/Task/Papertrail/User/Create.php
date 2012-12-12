<?php
/**
 * Create user in Papertrail
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_User_Create 
extends Application_Model_Task_Papertrail 
implements Application_Model_Task_Interface {
    
    CONST NAME = 'distributors/accounts';
    CONST METHOD = 'POST';

    public function process() {
        $this->_updateStatus('creating-papertrail-user');
        
        $data = $this->_init($this->getUri(), self::METHOD)
                     ->_setParameterPost()
                     ->_getDataResponse();
        
        if(isset($data->message)) {
            //log the message with problem
            throw new Exception($data->message);
        }
        
        if(isset($data->api_token)) {
            //success
            $this->_userObject->setPapertrailApiToken($data->api_token)
                              ->setHasPapertrailAccount(1)
                              ->save();
        }
        
        $this->_updateStatus('ready');
    }
    
    /**
     * Set the parameters for POST method
     * 
     * @return \Application_Model_Task_Papertrail_User_Create
     */
    protected function _setParameterPost() {
        $data = array(
            'id'   => (string)$this->_userObject->getId(),
            'name' => $this->_userObject->getLogin(),
            'plan' => 'free',
            'user' => array(
                'id'    => $this->_userObject->getId(),
                'email' => $this->_userObject->getEmail()
            )
        );
        
        $this->_client->setParameterPost($data);
        
        return $this;
    }
    
}