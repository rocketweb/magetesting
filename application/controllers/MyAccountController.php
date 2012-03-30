<?php

/**
 * User can see and edit his data used in payment
 * @author Grzegorz(golaod)
 * @package MyAccountController
 */
class MyAccountController extends Integration_Controller_Action
{
    /**
     * My account dashboard with payment and plan data
     * @method indexAction
     */
    public function indexAction()
    {
        $this->view->user = $this->auth->getIdentity();

        $payments = new Application_Model_Payment();
        $this->view->payments = $payments->fetchUserPayments($this->view->user->id);
    }

    /**
     * Allows user to edit his details information
     * @method editAccountAction
     * @param int $id - $_GET
     */
    public function editAccountAction()
    {
        $id = (int) $this->_getParam('id', 0);

        $redirect = array(
            'module'     => 'default',
            'controller' => 'index',
            'action'     => 'index'
        );
        $flashMessage = 'Hack attempt deteckted.';

        if ($id == $this->auth->getIdentity()->id){

            $user = new Application_Model_User();
            $user = $user->find($id);

            $form = new Application_Form_EditAccount();
            $form->populate($user->__toArray());

            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();
            
                if($form->isValid($formData)) {
                    $auth = $this->auth->getIdentity();
                    $user->setOptions($form->getValues());
                    $user->setId($auth->id);
                    $user->save();

                    // refresh data in zend auth
                    foreach($user->__toArray() as $key => $val) {
                        $auth->$key = $val;
                    }
                    
                    $flashMessage = 'You succeffully edited your data.';
                    $this->_helper->FlashMessenger($flashMessage);

                    $redirect['controller'] = 'my-account';
                    return $this->_helper->redirector->gotoRoute($redirect, 'default', true);
                }
            }
            $this->view->form = $form;

        } else {

            $this->_helper->flashMessenger($flashMessage);
            return $this->_helper->redirector->goToRoute($redirect, 'default', true);

        }
    }
}