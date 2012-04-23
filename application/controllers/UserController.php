<?php

class UserController extends Integration_Controller_Action
{

    public function init()
    {
        $this->_modelUser = new Application_Model_User();
    }

    public function indexAction()
    {
        // action body
    }

    public function dashboardAction()
    {
        $queueModel = new Application_Model_Queue();

        $timeExecution = $this->getInvokeArg('bootstrap')
                              ->getResource('config')
                              ->magento
                              ->instanceTimeExecution;
        $queueCounter = $queueModel->getPendingItems($timeExecution);

        $page = (int) $this->_getParam('page', 0);
        $paginator = $queueModel->getAllForUser(
            $this->auth->getIdentity()->id
        );
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->queue = $paginator;
        $this->view->queueCounter = $queueCounter;
        $this->view->timeExecution = $timeExecution;
        $this->view->response = $this->getResponse();
    }

    public function resetPasswordAction()
    {
        $form = new Application_Form_UserResetPassword();

        if($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                $redirect = array(
                    'controller' => 'user',
                    'action'     => 'login'
                );
    
                $user = new Application_Model_User();
                $newPassword = $user->resetPassword(
                    $form->login->getValue(),
                    $form->email->getValue()
                );

                if($newPassword) {
                    // send activation email to the specified user
                    $mailData = $this->getInvokeArg('bootstrap')
                                     ->getResource('config')
                                     ->user
                                     ->resetPassword;
                    $mail = new Integration_Mail_UserResetPassword($mailData, $user);
                    $mail->send();
                    $message['type']    = 'success';
                    $message['message'] = 'We sent you new password.'; 
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
        if(!$user->getId() OR $user->getPassword() != $key) {
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
                            $user = new Application_Model_User();
                            $user->find($userData->id)
                                 ->setGroup('free-user')
                                 ->setDowngraded(2)
                                 ->save();
                            $this->_helper->FlashMessenger(array('type'=> 'error', 'message' => 'We downgraded your account to free user.'));
                            $userData->group = 'free-user';
                        }
                    }

                    unset($userData->password);
                    if ($userData->status == 'inactive') {
                        $this->_helper->FlashMessenger('Your account is inactive');
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'login',
                        ), 'default', true);
                    } else {

                        $auth->getStorage()->write(
                                $userData
                        );

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
	    ));
	
        
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();          

            if($form->isValid($formData)) {
                
                $modelCoupon = new Application_Model_Coupon();
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
                
                $user->setOptions($form->getValues());
                $user = $user->save();

                if ($coupon) {
                    $result = $modelCoupon->apply($modelCoupon->getId(), $user->getId());
                    if ($result) {
                        //cupon->apply changed user so we need to fetch it again
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
                $mail->send();

                $this->_helper->FlashMessenger('You have been registered successfully');
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
                        $flashMessage = 'Activation completed. You can now log in into your account.';
                        $redirect['action'] = 'login';
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

        $form = new Application_Form_UserEdit();
        $form->populate($user->__toArray());

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if($form->isValid($formData)) {
                $user->setOptions($form->getValues());
                $user->save();

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
        if ($id == $this->auth->getIdentity()->id){
            
            //should we allow people to remove their accounts?
        } else {
            if($this->auth->getIdentity()->group != 'admin'){
                //you have no right to be here,redirect
            }
        }
        
        // here account removal or deactivating is made        ?
    }

}
