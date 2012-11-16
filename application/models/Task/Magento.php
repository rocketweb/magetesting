<?php

class Application_Model_Task_Magento 
extends Application_Model_Task {
    
    protected $_dbuser = '';
    protected $_dbpass = '';
    protected $_dbhost = '';
    protected $_dbname = '';
    protected $_systempass = '';

    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }   
    public function setup(Application_Model_Queue $queueElement){
        
        parent::setup($queueElement);
        
        $this->logger = $this->_getLogger();
        
        if(in_array($queueElement->getTask(),array('MagentoInstall','MagentoDownload'))){
                       
            $this->_dbhost = $this->config->resources->db->params->host; //fetch from zend config
            $this->_dbuser = $this->_userObject->getLogin(); //fetch from zend config
     
            $this->_dbpass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $this->_userObject->getLogin()), 0, 10); //fetch from zend config
            
            $this->_adminuser = $this->_userObject->getLogin();
            $this->_adminpass = $this->_generateAdminPass();          

            $this->_adminfname = $this->_userObject->getFirstname();
            $this->_adminlname = $this->_userObject->getLastname();
                        
            $this->_systempass = substr(sha1($this->config->magento->usersalt . $this->config->magento->userprefix . $this->_userObject->getLogin()), 10, 10);
            $this->_domain = $this->_instanceObject->getDomain();
        }
        
        $this->_dbname = $this->_userObject->getLogin() . '_' . $this->_instanceObject->getDomain();
        
        
    }
    
    /**
     * Sends email about successful install to instance owner
     * used by MagentoInstall and MagentoDownload Tasks
     */
    protected function _sendInstanceReadyEmail(){
        
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/_emails/');
    
        // assign values
        $html->assign('domain', $this->_instanceObject->getDomain());
        $html->assign('storeUrl', $this->config->magento->storeUrl);
        $html->assign('admin_login', $this->_adminuser);
        $html->assign('admin_password', $this->_adminpass);

        // render view
        $bodyText = $html->render('queue-item-ready.phtml');

        // create mail object
        $mail = new Zend_Mail('utf-8');
        // configure base stuff
        $mail->addTo($this->_userObject->getEmail());
        $mail->setSubject($this->config->cron->queueItemReady->subject);
        $mail->setFrom($this->config->cron->queueItemReady->from->email, $this->config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
        $mail->send();
        
    }
    
    protected function _createSymlink(){
        $domain = $this->_instanceObject->getDomain();
        exec('ln -s ' . $this->_instanceFolder . '/' . $domain . ' ' . INSTANCE_PATH . $domain);
    }
    
    /**
     * Creates system account for user during instance installation (in worker.php)
     * TODO: Same Method exists in MgentoInstall And MagentoDownload, 
     */
    protected function _createSystemAccount() {
        if ($this->_userObject->getHasSystemAccount() == 0) {
            $this->_updateStatus('installing-user');
            
            $this->db->update('user', array('system_account_name' => $this->config->magento->userprefix . $this->_dbuser), 'id=' . $this->_userObject->getId());

            /** WARNING!
             * in order for this to work, when you run this (worker.php) file,
             * you need to cd to this (scripts) folder first, like this:
              // * * * * * cd /var/www/magetesting/scripts/; php worker.php
             *
             */
            exec('sudo worker/create_user.sh ' . $this->config->magento->userprefix . $this->_dbuser . ' ' . $this->_systempass . ' ' . $this->config->magento->usersalt . ' ' . $this->config->magento->systemHomeFolder, $output);
            $message = var_export($output, true);
            $this->logger->log($message, LOG_DEBUG);
            unset($output);

            //TODO: Move this logic somewhere else, 
            // we're going tu use plan update for this
            //
            //TODO: check if $queueElement['plan_id'] has access 
            // to ftp account and send over credentials

            if ('free-user' != $this->_userObject->getGroup()) {
                /* send email with account details start */
                $modelUser = new Application_Model_User();
                $user_details = array(
                    'dbuser' => $this->_dbuser,
                    'dbpass' => $this->_dbpass,
                    'systempass' => $this->_systempass,
                    'email' => $this->_userObject->getEmail(),
                );
                $modelUser->sendFtpEmail($this->config, $user_details);
                $modelUser->sendPhpmyadminEmail($this->config, $user_details);
                /* send email with account details stop */
            }
        }
    }
    
}
        