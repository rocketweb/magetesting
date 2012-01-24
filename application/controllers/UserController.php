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
                                    'module' => 'default',
                                    'controller' => 'index',
                                    'action' => 'index',
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

    public function logoutAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::destroy(true, false);
				
        return $this->_helper->redirector->gotoRoute(array(
            'module'     => 'default',
            'controller' => 'user',
            'action'     => 'login',
        ), 'default', true);
    }

}

