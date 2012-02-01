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
        $this->view->queue = $queueModel->getAllForUser(
            $this->auth->getIdentity()->id
        );
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
                $user->save();

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

}
