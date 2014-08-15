<?php

class Integration_Mail_UserRegisterActivation extends Integration_Mail
{
    protected $_template = '_emails/user-register-activation.phtml';
    
    protected $_userObject;
    protected $_config;
    protected $_ppid;

    public function __construct()
    {
        parent::__construct();
    }
    
    public function setup($appConfig, $data, $view = null){
        $this->_config = $appConfig;

        $this->_userObject = $data['user'];
                 
        $this->_setHeaders();
        $this->_setView($view);
        $this->_setBody();
    }
    
    protected function _setHeaders(){
        $from = $this->_config->user
                                 ->activationEmail->from;
        
        $this->mail->setFrom( $from->email, $from->desc );
        $this->mail->addTo($this->_userObject->getEmail());
        $this->mail->setSubject($this->_config->user
                                 ->activationEmail->subject);
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView($view = null){
        if ($view instanceof Zend_View_Abstract) {
            $this->view = $view;
        } else {
            $this->view = Zend_Layout::getMvcInstance()->getView();
        }

        $this->view->storeUrl = $this->_config->magento->storeUrl;

        $string_to_hash = $this->_userObject->getLogin().$this->_userObject->getEmail().$this->_userObject->getAddedDate();
        $activationUrl = '/user/activate/id/'.$this->_userObject->getId();
        $activationUrl .= '/hash/'.substr(sha1($string_to_hash),0,20);

        $this->view->activationLink = $this->view->storeUrl.$activationUrl;
    }

    protected function _setBody(){
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');
        $msg = $this->view->render($this->_template);
        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}