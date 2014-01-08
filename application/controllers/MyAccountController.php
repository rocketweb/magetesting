<?php

/**
 * User can see and edit his data used in payment
 * @author Grzegorz(golaod)
 * @package MyAccountController
 */
class MyAccountController extends Integration_Controller_Action
{
    public function init() {
        $this->_helper->sslSwitch();
        parent::init();
    }

    /**
     * My account dashboard with payment and plan data
     * @method indexAction
     */
    public function indexAction()
    {
        $modelPlan = new Application_Model_Plan();
        $payments = new Application_Model_Payment();

        $modelUser = new Application_Model_User();
        $user = $modelUser->find($this->auth->getIdentity()->id);

        if ($user->getPlanIdBeforeRaising()) {
            $planData = $modelPlan->find($user->getPlanIdBeforeRaising());
            $modelRaisedPlan = new Application_Model_Plan();
            $raisedPlanData = $modelRaisedPlan->find($user->getPlanId());
        } else {
            $planData = $modelPlan->find($user->getPlanId());
            $raisedPlanData = false;
        }

        $not_settled_stores = false;
        $additional_stores = (int)$user->getAdditionalStores()-(int)$user->getAdditionalStoresRemoved();
        if($additional_stores) {
            $additional_stores = new Application_Model_PaymentAdditionalStore();
            if(count($additional_stores->fetchWaitingForConfirmation())) {
                $not_settled_stores = true;
            }
        }

        $this->view->plan = $planData;
        $this->view->not_settled_stores = $not_settled_stores;
        $this->view->raisedPlan = $raisedPlanData;
        $this->view->payments = $payments->fetchUserPayments($user->getId());
        $this->view->user = $user;
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
            if(!$user->getState()) {
                $form->state->setValue('Select State');
            }

            if(!$user->getCountry()) {
                $form->country->setValue('United States');
            }
            $informPayPal = (int)$this->getRequest()->getParam('inform', 0);

            if ($this->_request->isPost()) {
                $formData = $this->_request->getPost();
                $state_options = $form->state->getMultiOptions();
                if('United States' != $formData['country']) {
                    $form->state->setMultiOptions(array($formData['state'] => $formData['state']));
                }
                if($form->isValid($formData)) {
                    $auth = $this->auth->getIdentity();
                    
                    $form->removeElement('login');
                    $form->removeElement('email');
                    
                    $pass = $form->getValue('password');
                    $passRe = $form->getValue('password_repeat');
                    
                    if(empty($pass) && empty($passRe)) {
                        $form->removeElement('password');
                        $form->removeElement('password_repeat');
                    }
                    
                    $user->setOptions($form->getValues());

                    $user->setId($auth->id);
                    $user->save((is_null($user->getPassword())) ? false : true);

                    // refresh data in zend auth
                    foreach($user->__toArray() as $key => $val) {
                        $auth->$key = $val;
                    }
                    
                    $flashMessage['message'] = 'You succeffully edited your details.';
                    $flashMessage['type'] = 'success';
                    $this->_helper->FlashMessenger($flashMessage);

                    $redirect['controller'] = 'my-account';

                    return $this->_helper->redirector->gotoRoute($redirect, 'default', true);
                } else {
                    if('United States' != $formData['country']) {
                        $form->state->setMultiOptions($state_options);
                    }
                    $form->login->setValue($user->getLogin());
                    $form->email->setValue($user->getEmail());
                }
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

        $this->view->renderBraintree = false;
        
        if($user->getId()) {
            $plan_model = new Application_Model_Plan();
            $this->view->plans = $plan_model->fetchAll();

            $versions = new Application_Model_Version();
            $this->view->versions = $versions->fetchAll();
            
            if ($user->getCity() && $user->getStreet() && $user->getCountry() && $user->getPostalCode() && $user->getState() && $user->getFirstname() && $user->getLastname()){
                $this->view->renderBraintree = true;
            }
            $this->view->user = $user;
        } else {
            return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'my-account',
                    'action' => 'index',
            ), 'default', true);
        }
    }
    
    public function couponAction() {
        $request = $this->getRequest();
        $couponForm = new Application_Form_CouponRegister();

        $user = new Application_Model_User();
        $user = $user->find($this->auth->getIdentity()->id);
        // users with active plan cannot use coupons
        if($user->hasPlanActive()) {
            return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'my-account'
            ), 'default', true);
        }

        if ($request->isPost()) {
            $formData = $request->getPost();

            if ($couponForm->isValid($request->getPost())) {

                $modelCoupon = new Application_Model_Coupon();
                $coupon = $modelCoupon->findByCode($couponForm->code->getValue());
                if ($coupon) {
                    
                    $applyResult = $modelCoupon->apply($modelCoupon->getId(), $user->getId());
                    $user->setBraintreeTransactionConfirmed(NULL);
                    $user->setBraintreeTransactionId(NULL);
                    $user->setAdditionalStores(0);
                    $user->setAdditionalStoresRemoved(0);
                    $user->save();

                    if ($applyResult === true) {
                    $flashMessage = 'Congratulations, you have successfully changed your plan!';
                    $this->_helper->flashMessenger($flashMessage);
                    return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'my-account',
                                'action' => 'index',
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

    public function cancelSubscriptionAction()
    {
        if($this->getRequest()->isPost()) {
            $user = new Application_Model_User();
            $user->find($this->auth->getIdentity()->id);
            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            if($user->hasPlanActive()
               && 1 == (int)$user->getBraintreeTransactionConfirmed()
               && strlen($user->getBraintreeTransactionId())
               && (int)$plan->getId()
               && 1 == (int)$plan->getAutoRenew()
            ) {
                $user->setBraintreeTransactionConfirmed(-1)->save();
                $this->_helper->flashMessenger(array('type'=>'success','message' => 'Your subscription has been successfully cancelled.'));
            }
        }

        return $this->_helper->redirector->gotoRoute(array(
            'module' => 'default',
            'controller' => 'my-account',
            'action' => 'index',
        ), 'default', true);
    }

    public function removeAdditionalStoresAction()
    {
        $this->view->user = new Application_Model_User();
        $this->view->user->find($this->auth->getIdentity()->id);
        $this->view->left_stores = (int)$this->view->user->getAdditionalStores()-(int)$this->view->user->getAdditionalStoresRemoved();

        if(!$this->view->left_stores) {
            $flashMessage = 'You cannot remove more stores.';
            $this->_helper->flashMessenger(array('type'=>'error','message' => $flashMessage));
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'my-account',
                'action' => 'coupon',
            ), 'default', true);
        }

        if($this->getRequest()->isPost()) {
            $stores = (int)$this->_getParam('additional-stores-quantity', 0);
            $flashMessage = array('type'=>'error','message' => 'You cannot delete more stores.');
            if($stores && (int)$this->view->user->getAdditionalStores() >= (int)$this->view->user->getAdditionalStoresRemoved()+$stores) {
                $this->view->user->setAdditionalStoresRemoved(
                    (int)$this->view->user->getAdditionalStoresRemoved()+$stores
                )->save();
                $flashMessage = array('type'=>'success','message' => 'You deleted '.$stores.' additional store from your plan.');
            }
            $this->_helper->flashMessenger($flashMessage);
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'my-account',
                'action' => 'index',
            ), 'default', true);
        }
    }
}
