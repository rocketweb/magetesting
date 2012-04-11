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
        $id = (int)$this->auth->getIdentity()->id;

        $redirect = array(
            'module'     => 'default',
            'controller' => 'index',
            'action'     => 'index'
        );
        $flashMessage = array(
            'type'    => 'notice',
            'message' => 'Hack attempt detected.'
        );

        if($id > 0){

            $user = new Application_Model_User();
            $user = $user->find($id);

            $form = new Application_Form_EditAccount();
            $form->populate($user->__toArray());

            $informPayPal = (int)$this->getRequest()->getParam('inform', 0);

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
                    
                    $flashMessage['message'] = 'You succeffully edited your details.';
                    $flashMessage['type'] = 'success';
                    $this->_helper->FlashMessenger($flashMessage);

                    $redirect['controller'] = 'my-account';
                    if($informPayPal) {
                        $redirect['action'] = 'compare';
                    }
                    return $this->_helper->redirector->gotoRoute($redirect, 'default', true);
                }
            }

            if($informPayPal) {
                $this->view->messages[] = array('type' => 'notice', 'message' => 'You have to fill your details before any subscription.');                
            }

            $this->view->form = $form;

        } else {

            $this->_helper->flashMessenger($flashMessage);
            return $this->_helper->redirector->goToRoute($redirect, 'default', true);

        }
    }

    /**
     * Shows invoice by given id
     * Invoice id has to belong to logged user
     * @method invoiceAction
     * @param int $id - $_GET
     */
    public function invoiceAction()
    {
        $id = (int)$this->_getParam('id', 0);

        $redirect = array(
            'controller' => 'my-account',
            'action'     => 'index',
        );
        $flashMessage = 'Wrong invoice id.';
        if(0 >= $id) {
            $this->_helper->flashMessenger($flashMessage);
            return $this->_helper->redirector->goToRoute(
                    $redirect,
                    'default',
                    true
            );
        }

        $payment = new Application_Model_Payment();
        $payment->find($id);

        if($payment->getUserId() != $this->auth->getIdentity()->id) {
            $this->_helper->flashMessenger($flashMessage);
            return $this->_helper->redirector->goToRoute(
                        $redirect,
                        'default',
                        true
                    );
        }

        $this->view->payment = $payment;
    }
    
    public function compareAction()
    {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);

        $this->view->renderPayPal = false;
        if($user->getId()) {
            if($user->getCity() AND $user->getStreet()) {
                $this->view->renderPayPal = true;
                $this->view->user = $user;
            }
        }
    }
    
    public function couponAction() {
        $request = $this->getRequest();
        $couponForm = new Application_Form_CouponRegister();
        if ($request->isPost()) {
            $formData = $request->getPost();

            if ($couponForm->isValid($request->getPost())) {

                $modelCoupon = new Application_Model_Coupon();
                $coupon = $modelCoupon->findByCode($couponForm->code->getValue());
                if ($coupon) {
                    
                    $applyResult = $modelCoupon->apply($coupon->getId(), $this->auth->getIdentity()->id);
                    
                    if ($applyResult === true) {
                    $flashMessage = 'Congratulations, you have successfully changed your plan!';
                    $this->_helper->flashMessenger($flashMessage);
                    return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'my-account',
                                'action' => 'coupon',
                                    ), 'default', true);
                    } else {
                        $flashMessage = $modelCoupon->getError();
                        $this->_helper->flashMessenger(array('type'=>'error','message' => $flashMessage));
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'my-account',
                                'action' => 'coupon',
                                    ), 'default', true);
                    }
                } else {
                    $flashMessage = 'No such coupon';
                    $this->_helper->flashMessenger(array('type'=>'error','message' => $flashMessage));
                    return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'my-account',
                                'action' => 'coupon',
                                    ), 'default', true);
                }
            }
        }
        $this->view->couponform = $couponForm;
    }
}
