<?php

class UserController extends Integration_Controller_Action
{

    public function init()
    {
        $this->_modelUser = new Application_Model_User();
        $this->_helper->sslSwitch();
    }

    public function indexAction()
    {
        // action body
    }

    public function dashboardAction()
    {
        //var_dump($this->auth->getIdentity());die;
        $storeModel = new Application_Model_Store();

        $timeExecution = $this->getInvokeArg('bootstrap')
                              ->getResource('config')
                              ->magento
                              ->storeTimeExecution;
        $storeCounter = $storeModel->getPendingItems($timeExecution);

        $page = (int) $this->_getParam('page', 0);
        $paginator = $storeModel->getAllForUser(
            $this->auth->getIdentity()->id
        );
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->user = $this->auth->getIdentity();
        $this->view->userGroup = $this->view->user->group;
        $this->view->queue = $paginator;
        $this->view->queueCounter = $storeCounter;
        $this->view->timeExecution = $timeExecution;
        $this->view->response = $this->getResponse();
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/user-dashboard.js'), 'text/javascript');
    }

    public function resetPasswordAction()
    {
        $form = new Application_Form_UserResetPassword();
        $supportEmail = $this->getInvokeArg('bootstrap')
                                     ->getResource('config')
                                    ->user
                                    ->activationEmail
                                    ->from
                                    ->email;
        if($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $redirect = array(
                    'controller' => 'user',
                    'action'     => 'login'
                );
    
                $user = new Application_Model_User();
                
                try {
                    $newPassword = $user->resetPassword(
                        $form->email->getValue()
                    );
                } catch (PDOException $e){
                    $message['type']    = 'error';
                    $message['message'] = 'There was a problem with setting new password, please contact us at.'.$supportEmail; 
                    $this->_helper->flashMessenger($message);
                        $redirect['action'] = 'reset-password';
                        return $this->_helper->redirector->goToRoute(
                                $redirect,
                                'default',
                                true
                    );
                }

                if($newPassword) {
                    // send activation email to the specified user
                    $mailData = $this->getInvokeArg('bootstrap')
                                     ->getResource('config')
                                     ->user
                                     ->resetPassword;
                    
                     
                    
                    try {
                    $mail = new Integration_Mail_UserResetPassword($mailData, $user);
                    $mail->send();
                    } catch (Exception $e){
                        $message['type']    = 'error';
                        $message['message'] = 'There was a problem with sending email to you, please contact us at.'.$supportEmail; 
                        $this->_helper->flashMessenger($message);
                        $redirect['action'] = 'reset-password';
                        return $this->_helper->redirector->goToRoute(
                                $redirect,
                                'default',
                                true
                        );
                    }
                    
                    $message['type']    = 'success';
                    $message['message'] = 'We sent you link with form to set your new password.';
                    
                } else {
                    $message['type']    = 'notice';
                    $message['message'] = 'Wrong credentials.';
                }
    
                $this->_helper->flashMessenger($message);
                $redirect['action'] = 'reset-password';
                return $this->_helper->redirector->goToRoute(
                        $redirect,
                        'default',
                        true
                );
                
            } 
        }

        $this->view->form = $form;
    }

    public function setNewPasswordAction()
    {
        $redirect = array(
            'controller' => 'user',
            'action'     => 'login'
        );

        $key = $this->getRequest()->getParam('key', false);
        $id  = $this->getRequest()->getParam('id', 0);
        if(!$key OR !$id) {
            return $this->_helper->redirector->goToRoute(
                    $redirect,
                    'default',
                    true
            );
        }

        $user = new Application_Model_User();
        $user->find($id, true);
        if(!$user->getId() OR sha1($user->getAddedDate().$user->getPassword()) != $key) {
            return $this->_helper->redirector->goToRoute(
                    $redirect,
                    'default',
                    true
            );
        }

        $form = new Application_Form_UserSetNewPassword();

        if($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $user->setPassword(sha1($form->password->getValue()));
                $user->save(true);
                $this->_helper->flashMessenger(array(
                        'type'    => 'success',
                        'message' => 'You can now login with your new password.'
                ));

                return $this->_helper->redirector->goToRoute(
                        $redirect,
                        'default',
                        true
                );
            }
        }

        $this->view->form = $form;
    }

    public function loginAction()
    {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        $form    = new Application_Form_UserLogin();

        if ($this->getRequest()->isPost()) {
            $formData = $request->getPost();

            if ($form->isValid($request->getPost())) {

                $login = $form->login->getValue();
                $pass = $form->password->getValue();

                $auth = Zend_Auth::getInstance();

                $dbAdapter = Zend_Db_Table::getDefaultAdapter();
                $adapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
                $adapter->setTableName('user')
                ->setIdentityColumn('login')
                ->setCredentialColumn('password')
                ->setCredentialTreatment('SHA1(?)');
                $adapter->setIdentity($login)->setCredential($pass);

                $result = $adapter->authenticate();

                if($result->isValid()) {

                    $userData = $adapter->getResultRowObject();

                    unset($userData->password);
                    if ($userData->status == 'inactive') {
                        $this->_helper->FlashMessenger('Your account is inactive');
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'login',
                        ), 'default', true);
                    } else {
                        $user = new Application_Model_User();
                        $user->find($userData->id);

                        $auth->getStorage()->write(
                                $userData
                        );

                        // if user has subscription or waiting for confirmation
                        if(in_array($userData->group, array('awaiting-user', 'commercial-user'))) {
                            $timeAfterLastPayment = time()-strtotime($userData->plan_active_to);
                            // if between active to date and active to date+3days
                            // notify user about payment
                            if( $timeAfterLastPayment < 3*60*60*24 AND $timeAfterLastPayment > 0) {
                                $this->_helper->FlashMessenger(array('type'=> 'notice', 'message' => 'We have not received payment for your subscription yet.'));
                            } elseif($timeAfterLastPayment > 3*60*60*24) {
                                // if date is farther than 3 days
                                // inform that we downgraded user account
                                $user->setGroup('free-user')
                                ->setDowngraded(2)
                                ->save();
                                $this->_helper->FlashMessenger(array('type'=> 'error', 'message' => 'We downgraded your account to free user.'));
                                $userData->group = 'free-user';
                            }
                        }

                        $this->_helper->FlashMessenger('You have been logged in successfully');

                        return $this->_helper->redirector->gotoRoute(array(
                                'module'     => 'default',
                                'controller' => 'user',
                                'action'     => 'dashboard',
                        ), 'default', true);
                    }
                } else {
                    $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'You have entered wrong credentials. Please try again.'));

                    return $this->_helper->redirector->gotoRoute(array(
                            'module'     => 'default',
                            'controller' => 'user',
                            'action'     => 'login',
                    ), 'default', true);
                }
            } else {
                $form->populate($formData);
            }
        }

        $this->view->form = $form;
    }

    public function registerAction()
    {
        $user = new Application_Model_User();

        $form = new Application_Form_UserRegister();
        $form->populate($user->__toArray());

        $form->getElement('login')->addValidator('Db_NoRecordExists',false,
                array('table' => 'user', 'field' => 'login'));
        
        $useCoupons = $this->getInvokeArg('bootstrap')
                              ->getResource('config')
                              ->register
                              ->useCoupons;
                              

	    $form->addElement('text', 'coupon', array(
            'label'      => 'Coupon code',
            'required'   => ($useCoupons) ? true : false,
            'filters'    => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(3, 45)),
            ),
            'class'      => 'span4'
	    ));
	
        
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();          

            if($form->isValid($formData)) {
                
                $modelCoupon = new Application_Model_Coupon();
                if ($useCoupons) {
                    $coupon = $modelCoupon->findByCode($formData['coupon']);

                    if (!$coupon){
                        $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'No coupon found!'));
                        return $this->_helper->redirector->gotoRoute(array(
                                'module'     => 'default',
                                'controller' => 'user',
                                'action'     => 'register',
                        ), 'default', true);
                    } elseif ($modelCoupon->isUnused() === false ){
                        $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'Coupon has already been used!'));
                        return $this->_helper->redirector->gotoRoute(array(
                                'module'     => 'default',
                                'controller' => 'user',
                                'action'     => 'register',
                        ), 'default', true);
                    }
                }
                
                $serverModel = new Application_Model_Server();
                
                $user->setOptions($form->getValues());
                $user = $user->save();

                if ($useCoupons && $coupon) {
                    $result = $modelCoupon->apply($modelCoupon->getId(), $user->getId());
                    if ($result) {
                        //coupon->apply changed user so we need to fetch it again
                        $modelUser = new Application_Model_User();
                        $user = $modelUser->find($user->getId());
                        $user->setGroup('commercial-user');
                        $user = $user->save();
                    }
                }

                // send activation email to the specified user
                $mailData = $this->getInvokeArg('bootstrap')
                                 ->getResource('config')
                                 ->user
                                 ->activationEmail;
                $mail = new Integration_Mail_UserRegisterActivation($mailData, $user);
                try {
                    $mail->send();
                    $this->_helper->FlashMessenger('You have been registered successfully. Please check your mail box for instructions to activate account.');
                } catch (Zend_Mail_Transport_Exception $e){
                    $log = $this->getInvokeArg('bootstrap')->getResource('log');
                    $log->log('User Register - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
                    $this->_helper->FlashMessenger(
                        array(
                            'type' => 'error',
                            'message' => 'Account has been registered, but mail couldn\'t be sent.'
                        )
                    );
                }

                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'login',
                ), 'default', true);
            }
        }
        
        $this->view->useCoupons = $useCoupons;
        $this->view->form = $form;
    }

    /**
     * Activates user account
     * @method activateAction
     */
    public function activateAction()
    {
        $request = $this->getRequest();
        $flashMessage = 'Activation link is incorrect.';
        $redirect = array(
                'controller' => 'user',
                'action'     => 'register'
        );
        if($request->isGet()) {
            $id = $request->getParam('id');
            $hash = $request->getParam('hash');
            if($id AND $hash) {
                $user = new Application_Model_User();
                switch($user->activateUser($id, $hash)) {
                    case 0:
                        $user = new Application_Model_User();
                        $user->find($id);
                        
                        $auth = Zend_Auth::getInstance();
                        $auth->getStorage()->write((object)$user->__toArray());
                        
                        $flashMessage = 'Activation completed. You have been logged in successfully.';
                        $redirect['action'] = 'dashboard';
                        break;
                    case 1:
                        // already set in initial variables
                        break;
                    case 2:
                        $flashMessage = 'Your account has been previously activated.';
                        $redirect['action'] = 'login';
                        break;
                }
            }
        }

        $this->_helper->FlashMessenger($flashMessage);
        return $this->_helper->redirector->goToRoute(
                $redirect,
                'default',
                true
        );
    }

    public function logoutAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::destroy(true, false);
        
        $this->_helper->FlashMessenger('You have been logout successfully');
        return $this->_helper->redirector->gotoRoute(array(
                'module'     => 'default',
                'controller' => 'index',
                'action'     => 'index',
        ), 'default', true);
    }
    
    public function listAction() {
        $user = new Application_Model_User();

        $page = (int) $this->_getParam('page', 0);
        $paginator = $user->fetchList();


        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->users = $paginator;
    }

    public function editAction()
    {
        
        $id = (int) $this->_getParam('id', 0);
        
        if ($id == $this->auth->getIdentity()->id){
            //its ok to edit
        } else {
            if($this->auth->getIdentity()->group != 'admin'){
                //you have no right to be here,redirect
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
            }
        }
        
        $user = new Application_Model_User();
        $user = $user->find($id);
        $server_model = new Application_Model_Server();
        $servers = array();
        $servers[0] = '';
        foreach($server_model->fetchAll() as $row) {
            $servers[$row->getId()] = $row->getName();
        }

        $form = new Application_Form_UserEdit();
        $form->server_id->setMultiOptions($servers);
        $form->populate($user->__toArray());
        
        if(!$user->getState()) {
            $form->state->setValue('Select State');
        }
        
        if(!$user->getCountry()) {
            $form->country->setValue('United States');
        }
        
        if ($this->_request->isPost()) {
            $form->removeElement('login');
            
            if(!strlen($this->_request->getParam('street'))) {
                $form->removeElement('street');
            }
            
            if(!strlen($this->_request->getParam('postal_code'))) {
                $form->removeElement('postal_code');
            }
            
            if(!strlen($this->_request->getParam('state'))) {
                $form->removeElement('state');
            }
            
            if(!strlen($this->_request->getParam('city'))) {
                $form->removeElement('city');
            }
            
            if(!strlen($this->_request->getParam('country'))) {
                $form->removeElement('country');
            }
            
            $formData = $this->_request->getPost();
            if(strlen($this->_request->getParam('password'))) {
                $form->password->setRequired(true);
                $form->password_repeat->setRequired(true);
            } else {
                unset($formData['password'], $formData['password_repeat']);
            }
            
            if($form->isValid($formData)) {
                if($formData['server_id'] == '0') {
                    $formData['server_id'] = NULL;
                }
                if(strlen($formData['password'])) {
                    unset($formData['password_repeat']);
                    $formData['password'] = sha1($formData['password']);
                }
                $user->setOptions($formData);
                
                $user->save((is_null($user->getPassword())) ? false : true);

                $this->_helper->FlashMessenger('User data has been changed successfully');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'list',
                ), 'default', true);
            }
        }
        $this->view->form = $form;
        
    }
    
    public function removeAction(){
        $id = (int)$this->_getParam('id', 0);
        if ($id == $this->auth->getIdentity()->id){
            
            //should we allow people to remove their accounts?
        } else {
            if($this->auth->getIdentity()->group != 'admin'){
                //you have no right to be here,redirect
            }
        }
        
        //TODO: account removal
        // here account removal or deactivating is made        ?
    }
    
    public function papertrailAction()
    {
        $domain = $this->_getParam('domain', null);
        
//        $paper = new RocketWeb_Service_Papertrail('magetesting', 'd41d8cFd98f00b2v4e980b998ecf8427e');
//        $response = $paper->getSystemData('2');
//        Zend_Debug::dump($response);
//        
//        $response = $paper->getAccountUsage('MarcinRocketWeb');
//        Zend_Debug::dump($response);die;
        
        if(is_null($domain)) {
            return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
        }
        
        $storeModel = new Application_Model_Store();
        $store = $storeModel->findByDomain($domain);

        if((int)$store->user_id !== (int)$this->auth->getIdentity()->id) {
            //this is not YOUR store!
            return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
        }
        
        $config = $this->getInvokeArg('bootstrap')->getResource('config')->papertrail;
        $timestamp = time();
        
        /**
         * Token deserves the most explanation. It is a SHA-1 hash of a string 
         * containing these 4 values in order, separated by colons: 
         * - account_id (from account provisioning request; see "Create Account")
         * - user_id
         * - SSO salt (a shared secret provided by Papertrail)
         * - timestamp (is the current time in UTC (Unix time))
         */
        $token = sha1( 
            implode(':', array(
                $config->prefix . $store->user_id, 
                $config->prefix . $store->user_id, 
                $config->ssoSalt, 
                $timestamp
             )
        ));
        
        $form = new Application_Form_PapertrailSession();
        $form->populate(array(
            'user_id'     => $config->prefix . $store->user_id,
            'account_id'  => $config->prefix . $store->user_id,
            'system_id'   => $config->prefix . $domain,
            'token'       => $token,
            'distributor' => $config->distributorName,
            'timestamp'   => $timestamp,
            'email'       => $this->auth->getIdentity()->email
        ));

        
        $this->view->form = $form;
    }
    
}
