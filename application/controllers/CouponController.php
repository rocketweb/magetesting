<?php

class CouponController extends Integration_Controller_Action {

    public function init() {
        /* Initialize action controller here */
        $this->_helper->sslSwitch();
    }

    public function indexAction() {
        # display grid of all supported extensions
        /* index action is only alias for list extensions action */
        $this->listAction();
    }

    public function listAction() {
        $couponModel = new Application_Model_Coupon();
        $this->view->coupons = $couponModel->fetchList();
        $this->render('list');
    }

    public function addAction() {
        /* add and edit actions should have the same logic */
        $this->editAction('Add');
    }
    
    /**
     * Render coupon form
     * 
     * @param string $action (Add or Edit)
     * @return mixed
     */
    public function editAction($action = 'Edit')
    {
        $id = (int) $this->_getParam('id', 0);

        if(($cancel = (int)$this->_getParam('cancel', 0)) AND $cancel) {
            return $this->_helper->redirector->gotoRoute(array(
                'module'     => 'default',
                'controller' => 'coupon',
                'action'     => 'index',
            ), 'default', true);
        }

        $form_type = 'Application_Form_Coupon'.$action;
        $form = new $form_type();

        $couponModel = new Application_Model_Coupon();
        $coupon_data = $couponModel->__toArray();

        $planModel = new Application_Model_Plan();
        $plans = array();
        foreach($planModel->fetchAll() as $plan) {
            $plans[$plan->getId()] = $plan->getName();
        }
        $form->plan_id->addValidator(
            new Zend_Validate_InArray(array_keys($plans))
        );

        $form->plan_id->addMultiOptions(array('' => '') + $plans);

        if($id) {
            $couponModel = $couponModel->find($id);
            if($couponModel->getId()) {
                $coupon_data = $couponModel->__toArray();
                // empty date fix
                if('0000-00-00 00:00:00' != $coupon_data['used_date'] && $coupon_data['used_date']) {
                    $coupon_data['used_date'] = date('Y-m-d', strtotime($coupon_data['used_date']));
                } else {
                    $coupon_data['used_date'] = '';
                }
                $coupon_data['active_to'] = date('Y-m-d', strtotime($coupon_data['active_to']));

                $userModel = new Application_Model_User();
                $users = array();
                foreach($userModel->fetchAll() as $user) {
                    $users[$user->getId()] = $user->getLogin();
                }
                $form->user_id->addValidator(
                        new Zend_Validate_InArray(array_keys($users))
                );

                $form->user_id->addMultiOptions(array('' => '') + $users);

                // disable form for used coupon
                if($coupon_data['used_date']) {
                    $form->disableFields();
                }
            } else {
                $this->_helper->FlashMessenger('Coupon with given id does not exist.');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'coupon',
                        'action'     => 'index',
                ), 'default', true);
            }
        }

        if($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            if($formData['code'] != $couponModel->getCode()) {
                $form->addUniqueCodeValidator();
            }
            if($form->isValid($formData)) {
                $couponModel->setOptions($formData)->save();

                $this->_helper->FlashMessenger('Extension has been added properly.');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'coupon',
                        'action'     => 'index',
                ), 'default', true);
            } else {
                $coupon_data = $formData;
            }
        }

        $form->populate($coupon_data);

        $this->view->action = $this->_request->getActionName();
        $this->view->form = $form;
    
    }

    public function deleteAction()
    {
        // array with redirect to grid page
        $redirect = array(
                'module'      => 'default',
                'controller'  => 'coupon',
                'action'      => 'index'
        );

        // init form object
        $form = new Application_Form_CouponDelete();

        // shorten request
        $request = $this->getRequest();

        // if request is without proper id param
        // redirect to grid with information message 
        if(((int)$request->getParam('id', 0)) == 0) {
            // set message
            $this->_helper->FlashMessenger(
                array(
                    'type' => 'error',
                    'message' => 'You cannot delete coupon with specified id.'
                )
            );
            // redirect to grid
            return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
            );
        }

        if($request->isPost()) {
            // has post data and sent data is valid
            if($form->isValid($request->getParams())) {
                // someone agreed deletion 
                if($request->getParam('submit') == 'Yes') {
                    $flash_message = array(
                        'type' => 'success',
                        'message' => 'You have deleted coupon successfully.'
                    );
                    $coupon = new Application_Model_Coupon();
                    // set news id to the one passed by get param
                    try {
                        $coupon->delete($request->getParam('id'));
                    } catch(Exception $e) {
                        $this->getLog()->log('Admin - coupon delete', Zend_Log::ERR, $e->getMessage());
                        $flash_message = array(
                            'type' => 'error',
                            'from_scratch' => 1,
                            'message' => 'We couldn\'t delete coupon.<span class="hidden">'.$e->getMessage().'</span>'
                        );
                    }
                    // set message
                    $this->_helper->FlashMessenger(
                        $flash_message
                    );
                } else {
                    // deletion cancelled
                    // set message
                    $this->_helper->FlashMessenger(
                        array(
                            'type' => 'notice',
                            'message' => 'Coupon deletion cancelled.'
                        )
                    );
                }
                // redirect to grid if request is withou ajax
                return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
                );
            }
        }

        $this->view->form = $form;
    }
}