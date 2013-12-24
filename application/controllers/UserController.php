<?php

class UserController extends Integration_Controller_Action
{

    public function init()
    {
        $this->_modelUser = new Application_Model_User();
        $this->_helper->sslSwitch();
    }

    public function dashboardAction()
    {
        $storeModel = new Application_Model_Store();

        $page = (int) $this->_getParam('page', 0);
        $paginator = $storeModel->getAllForUser(
            $this->auth->getIdentity()->id
        );
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);


        /* check expiry date for plan start */
        $planModel = new Application_Model_Plan();
        $planModel->find($this->auth->getIdentity()->plan_id);


        if ($this->auth->getIdentity()->group != 'admin'){

            $dateActive = new DateTime($this->auth->getIdentity()->plan_active_to);
            $dateToday = new DateTime();
            $interval = $dateToday->diff($dateActive);
            $diff = (int)$interval->format('%R%a');

            if($planModel->getAutoRenew() == 0 AND $diff <= 7) {
                $diff++;
                $s = $diff > 1 ? 's' : '';
                $message['type']    = 'notice';
                $message['message'] = 'You are using '.$planModel->getName().' plan and it will expire in '.$diff.' day'.$s;
                $this->view->messages = array($message);
            }

        }
        /* check expiry date for plan stop */

        $this->view->planModel = $planModel;
        $this->view->user = $this->auth->getIdentity();
        $this->view->userGroup = $this->view->user->group;
        $this->view->queue = $paginator;
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
                    $message['message'] = 'There was a problem with setting new password, please contact us at: '.$supportEmail; 
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
                    $appConfig = $this->getInvokeArg('bootstrap')
                                     ->getResource('config');
                    
                    try {
                        $mail = new Integration_Mail_UserResetPassword();
                        $mail->setup($appConfig, array('user'=>$user));
                        $mail->send();
                    } catch (Exception $e){
                        $message['type']    = 'error';
                        $message['message'] = 'There was a problem with sending email to you, please contact us at: '.$supportEmail; 
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
                $user->setPassword($form->password->getValue());
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
                    if ($userData->status == 'inactive' || (int)strtotime((string)$userData->active_from) > time()) {
                        $this->_helper->FlashMessenger('Your account is inactive');
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'login',
                        ), 'default', true);
                    } elseif($userData->status == 'deleted') {
                        $this->_helper->FlashMessenger('Your account has been deleted');
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'login',
                        ), 'default', true);
                    } else {
                        $user = new Application_Model_User();
                        $user->find($userData->id);
                        // if user has subscription or waiting for confirmation
                        if(
                            in_array($userData->group, array('awaiting-user', 'commercial-user')) &&
                            is_numeric($user->getBraintreeTransactionConfirmed()) &&
                            0 === (int)$user->getBraintreeTransactionConfirmed() &&
                            (int)$user->getPlanId()
                        ) {
                            $plan = new Application_Model_Plan();
                            $plan->find($user->getPlanId());
                            $boughtDate = strtotime('-'.$plan->getBillingPeriod(), strtotime($user->getPlanActiveTo()));
                            $timeAfterLastPayment = time()-$boughtDate;
                            if($timeAfterLastPayment < 3*60*60*24 && $timeAfterLastPayment >= 60*60*24) {
                                $this->_helper->FlashMessenger(array('type'=> 'notice', 'message' => 'We have not received payment for your subscription yet.'));
                            } elseif($timeAfterLastPayment >= 3*60*60*24) {
                                // if date is farther than 3 days
                                // inform that we downgraded user account
                                $user->setGroup('free-user')
                                     ->setDowngraded(Application_Model_User::DOWNGRADED_EXPIRED_SYMLINKS_NOT_DELETED)
                                     ->save();
                                $this->_helper->FlashMessenger(array('type'=> 'error', 'message' => 'We downgraded your account to free user.'));
                                $userData->group = 'free-user';
                                $userData->downgraded = Application_Model_User::DOWNGRADED_EXPIRED_SYMLINKS_NOT_DELETED;
                            }
                        }

                        $auth->getStorage()->write(
                            $userData
                        );

                        $this->_helper->FlashMessenger('You have been logged in successfully');

                        $controller = 'user';
                        $action = 'dashboard';

                        // redirect back to page requested before login
                        $session = new Zend_Session_Namespace('after_login_redirect');
                        if(isset($session->url)) {
                            $url = $session->url;
                            // remove url from session
                            unset($session->url);
                            // redirect
                            return $this->_helper->redirector->goToUrl($url);
                        }

                        return $this->_helper->redirector->gotoRoute(array(
                                'module'     => 'default',
                                'controller' => $controller,
                                'action'     => $action,
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

        $regex = new Zend_Validate_Regex("/^[a-z0-9_-]+$/");
        $regex->setMessage('Allowed chars: lowercase a-z, digits, dash, underscore', 'regexNotMatch');
        
        $form->getElement('login')
                ->addValidator('Db_NoRecordExists',false,
                    array('table' => 'user', 'field' => 'login')
                )
                ->addValidator($regex);
        
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

	    // add to form preselected plan id if chosen
	    // check free trial dates if was requested
	    $this->view->preselected_plan_id = NULL;
	    $plan_id = $this->_getParam('preselected_plan_id', 0);
	    $modelCoupon = new Application_Model_Coupon();
	    if('free-trial' === $plan_id) {
	        $config = Zend_Registry::get('config');
	        $freeTrialsPerDay = $config->register->freeTrialCouponsPerDay;
	        $nextFreeTrialDate = $modelCoupon->getNextFreeTrialDate($freeTrialsPerDay);
	        $flashMessages = $this->view->messages;
	        if(!$flashMessages) {
	            $flashMessages = array();
	        }
	        if($nextFreeTrialDate != date('Y-m-d')) {
	            $flashMessages = array_merge($flashMessages, array(
	                array(
	                    'type' => 'notice',
	                    'message' => 'Sorry we reached the maximum number of free trial customers for today. If you complete this signup form we can add you to the queue and will send a confirmation email when your free trial is activated on ' . date('l F dS\.', strtotime($nextFreeTrialDate))
	                )
	            ));
	        }
	        $this->view->messages = $flashMessages;
	    } else {
	        $plan_id = (int)$plan_id;
	    }
	    if($plan_id) {
	        $this->view->preselected_plan_id = $plan_id;
	    }

        $formData = $this->_request->getPost();
        if(count($formData) > 1) {
            if(!isset($formData['coupon'])) {
                $formData['coupon'] = '';
            }
            $wrong_coupon = false;
            $coupon = $modelCoupon->findByCode($formData['coupon']);
            if ($useCoupons || $formData['coupon']) {
                if (!$coupon || $modelCoupon->isUnused() === false ){
                    $wrong_coupon = true;
                }
            }

            if($form->isValid($formData) && !$wrong_coupon) {
                $user->setOptions($form->getValues());
                $apply_coupon_from = 0;
                $adminNotificationData = array();
                if('free-trial' === $plan_id) {
                    $adminNotificationData['free_trial'] = true;
                    if($modelCoupon->createNewFreeTrial($nextFreeTrialDate)) {
                        $coupon = true;
                        $user->setActiveFrom($nextFreeTrialDate);
                        if(date('Y-m-d') != $nextFreeTrialDate) {
                            $user->setActiveFromReminded(0);
                        } else {
                            $user->setActiveFromReminded(1);
                        }
                        $apply_coupon_from = strtotime($nextFreeTrialDate . ' ' . date('H:i:s'));
                    } else {
                        $user->setActiveFromReminded(1);
                    }
                } else {
                    $user->setPreselectedPlanId($plan_id);
                    $user->setActiveFromReminded(1);
                }
                $user = $user->save();

                $adminNotification = new Integration_Mail_AdminNotification();
                $adminNotificationData['user'] = $user;
                $plan = new Application_Model_Plan();
                $plan->find($user->getPreselectedPlanId());
                if($plan->getName()) {
                    $adminNotificationData['preselected_plan'] = $plan->getName();
                }
                if ($useCoupons || $coupon) {
                    $result = $modelCoupon->apply($modelCoupon->getId(), $user->getId(), $apply_coupon_from);
                    if ($result) {
                        $adminNotificationData['used_coupon'] = $modelCoupon->getCode();
                        //coupon->apply changed user so we need to fetch it again
                        $modelUser = new Application_Model_User();
                        $user = $modelUser->find($user->getId());
                        $user->setGroup('commercial-user');
                        $user->setBraintreeTransactionConfirmed(NULL);
                        $user->setBraintreeTransactionId(NULL);
                        $user = $user->save();
                    }
                }

                $adminNotification->setup('userCreated', $adminNotificationData);
                // send activation email to the specified user
                $appConfig = $this->getInvokeArg('bootstrap')
                                 ->getResource('config');
                $mail = new Integration_Mail_UserRegisterActivation();
                $mail->setup($appConfig, array('user' => $user));
                try {
                    $adminNotification->send();
                    $successMessage = 'You have been registered successfully.';
                    if('free-trial' === $plan_id && $nextFreeTrialDate != date('Y-m-d')) {
                        $successMessage .= ' We will send you an email when your free trial account will be ready.';
                    } else {
                        $mail->send();
                        $successMessage .= ' Please check your mail box for instructions to activate account.';
                    }
                    $this->_helper->FlashMessenger($successMessage);
                } catch (Zend_Mail_Transport_Exception $e){
                    $log = $this->getInvokeArg('bootstrap')->getResource('log');
                    $log->log('User Register - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
                    exit;
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
            if($wrong_coupon) {
                if(!$coupon || ($modelCoupon->isUnused() === false )) {
                    $form->coupon->addError('Provided coupon code is either not valid or was used already.')->markAsError();
                }
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

    public function addAction()
    {
        $this->editAction('add');
        $this->render('edit');
    }
    public function editAction($type = 'edit')
    {
        $user = new Application_Model_User();
        $id = (int) $this->_getParam('id', 0);
        if ($id == $this->auth->getIdentity()->id){
            //its ok to edit
            $user = $user->find($id);
        } elseif('edit' == $type) {
            if($this->auth->getIdentity()->group != 'admin'){
                //you have no right to be here,redirect
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
            } else {
                $user = $user->find($id);
            }
        }

        $form = 'Application_Form_User'.ucfirst($type);
        $form = new $form();
        $form->populate($user->__toArray());
        
        if(!$user->getState()) {
            $form->state->setValue('Select State');
        }
        
        if(!$user->getCountry()) {
            $form->country->setValue('United States');
        }
        
        if ($this->_request->isPost()) {
            
            $formData = $this->_request->getPost();

            if($user->getEmail() == $formData['email']) {
                $form->removeElement('email');
            }
            if(strlen($this->_request->getParam('password')) || 'add' == $type) {
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
                }
                $user->setOptions($formData);
                $user->save((is_null($user->getPassword())) ? false : true);

                $planModel = new Application_Model_Plan();
                // set admin plan for users with group admin
                if('admin' === $user->getGroup()) {
                    $admin_plan = $planModel;
                    foreach($planModel->fetchAll(true) as $plan) {
                        if($plan->getIsHidden()) {
                            $admin_plan = $plan;
                            break;
                        }
                    }
                    if((int)$admin_plan->getId()) {
                        $user->setPlanId($admin_plan->getId())->save();
                    }
                }
                // remove admin plan for users other than admin
                if('admin' !== $user->getGroup()) {
                    if((int)$user->getPlanId()) {
                        $planModel = $planModel->find($user->getPlanId());
                        if((int)$planModel->getId() && (int)$planModel->getIsHidden()) {
                            $user->setPlanId(NULL)->save();
                        }
                    }
                }

                $this->_helper->FlashMessenger('User data has been saved successfully');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'list',
                ), 'default', true);
            } else {
                if('edit' == $type) {
                    $form->login->setValue($user->getLogin());
                }
            }
        }
        $this->view->form = $form;
        
    }
    
    public function removeAction(){
        $id = (int)$this->_getParam('id', 0);

        $user = new Application_Model_User();
        $user = $user->find($id);

        $redirect = array(
            'controller' => 'user',
            'action' => 'dashboard'
        );

        if($this->auth->getIdentity()->group == 'admin' AND (int)$user->getId() AND $this->getRequest()->isPost()){
            if((int)$this->_getParam('confirm', 0)) {
                $task = new Application_Model_Queue();
                $task->setUserId($user->getId())
                     ->setServerId($user->getServerId())
                     ->setStatus('pending')
                     ->setExtensionId(0);

                $store_model = new Application_Model_Store();
                foreach($store_model->getAllForUser($user->getId()) as $store) {
                    $task->setStoreId($store['id']);

                    $parentId = 0;
                    if (strlen($store['papertrail_syslog_hostname'])) {
                        $task->setTask('PapertrailSystemRemove')
                             ->setParentId(0)
                             ->setId(0)
                             ->save();
                        $parentId = $task->getId();
                    }

                    $task->setId(0)
                         ->setTask('MagentoRemove')
                         ->setParentId($parentId);
                    $task->save();
                }
                $user->setStatus('deleted');
                $user->save();
                $this->_helper->flashMessenger('User has been successfully removed.');
            }
            $redirect['action'] = 'list';
        }

        $this->_helper->redirector->gotoRoute(
                $redirect, 'default', true
        );
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
    
    public function detailAction()
    {
        $id = (int) $this->_getParam('id', 0);
        
        $user = new Application_Model_User();
        $user = $user->find($id);

        if(!$user->getLogin()) {
            $this->_helper->flashMessenger(array('type' => 'error', 'message' => 'User with specified id does not exist.'));
            return $this->_helper->redirector->gotoRoute(array(
                    'module'     => 'default',
                    'controller' => 'user',
                    'action'     => 'list',
            ), 'default', true);
        }

        $server = new Application_Model_Server();
        $server->find($user->getServerId());
        
        $plan = new Application_Model_Plan();
        $plan->find($user->getPlanId());
        
        $coupon = new Application_Model_Coupon();
        $coupon->findByUser($user->getId());
        
        $planModel = clone $plan;
        $plans = array();

        foreach($planModel->fetchAll() as $row) {
            $plans[$row->getId()] = $row->getName();
        }
        
        $payment = new Application_Model_Payment();
        $payments = $payment->fetchUserPayments($user->getId());

        $page = (int) $this->_getParam('page', 0);
        $storeModel = new Application_Model_Store();
        $paginator = $storeModel->getAllForUser($id);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $stores_view =
            $this->view->partial(
                'queue/index.phtml',
                array(
                    'user_details' => true,
                    'queue' => $paginator
                )
            );
        $this->view->assign(
            array(
                'user'     => $user,
                'server'   => $server,
                'plan'     => $plan,
                'coupon'   => $coupon,
                'plans'    => $plans,
                'payments' => $payments,
                'stores' => $stores_view
            )
        );

//        Zend_Debug::dump($user);
//        Zend_Debug::dump($payments);
    }
    
}
