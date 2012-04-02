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
                    if ($userData->status == 'inactive') {
                        $this->_helper->FlashMessenger('Your account is inactive');
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'login',
                        ), 'default', true);
                    } else {

                        $auth->getStorage()->write(
                                $adapter->getResultRowObject(null, 'password')
                        );

                        $this->_helper->FlashMessenger('You have been logged in successfully');

                        return $this->_helper->redirector->gotoRoute(array(
                                'module'     => 'default',
                                'controller' => 'user',
                                'action'     => 'dashboard',
                        ), 'default', true);
                    }
                } else {
                    $this->_helper->FlashMessenger('You have entered wrong credentials. Please try again.');

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

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if($form->isValid($formData)) {
                $user->setOptions($form->getValues());
                $user->setGroup('standard-user');
                $user = $user->save();

                // send activation email to the specified user
                $this->_sendActivationEmail($user);

                $this->_helper->FlashMessenger('You have been registered successfully');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'login',
                ), 'default', true);
            }
        }
        $this->view->form = $form;
    }

    /**
     * Sends activation mail to the user specified in param.
     * @method _sendActivationEmail
     * @param Application_Model_User $user
     */
    protected function _sendActivationEmail($user)
    {
        $mailData = $this->getInvokeArg('bootstrap')
                              ->getResource('config')
                              ->user
                              ->activationEmail;
        $mail = new Zend_Mail();

        $mail->setFrom($mailData->from->mail, $mailData->from->desc);
        $activationUrl = $this->view->url(
            array(
                'controller' => 'user',
                'action'     => 'activate',
                'id'         => $user->getId(),
                'hash'       => sha1($user->getLogin().$user->getEmail().$user->getAddedDate())
            )
        );
        $mailMessage = str_replace(
                '{activation_url}', 
                $this->view->serverUrl().$activationUrl, 
                $mailData->message
        );
        $mail->setBodyHtml($mailMessage, 'UTF-8');
        $mail->setSubject($mailData->subject);
        $mail->addTo($user->getEmail(), $user->getFirstname().' '.$user->getLastname() );
        $mail->send();
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
                        $flashMessage = 'Activation complited. You can now log in into your account.';
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
