<?php


class BraintreeController extends Integration_Controller_Action
{
       
    public function init(){
        /*parent::init();
        require_once 'Braintree.php';
        $config = $this->getInvokeArg('bootstrap')
                       ->getResource('config');
        
        Braintree_Configuration::environment($config->braintree->environment);
        Braintree_Configuration::merchantId($config->braintree->merchantId);
        Braintree_Configuration::publicKey($config->braintree->publicKey);
        Braintree_Configuration::privateKey($config->braintree->privateKey);*/
        parent::init();
        require_once 'Braintree.php';
        Braintree_Configuration::environment('sandbox');
        Braintree_Configuration::merchantId('hwwzbybn8tvfrhjz');
        Braintree_Configuration::publicKey('rpxf8q436zfmp78r');
        Braintree_Configuration::privateKey('e87aea495ca0f8dfab7137f52b9adf26');
    }

    // @todo: I think we should concat form and response due to error reporting
    // @todo: We should refactor the code to remove so many redirections
    // @todo: maybe by throwing Exceptions with some message or special variable
    public function formAction() {
        $message = '';
        $form = $this->_getParam('pay-for');
        $id = $this->_getParam('id');

        // check whether GET params are ok
        if(!in_array($form, array('plan', 'extension'))) {
            $form = null;
            $message = 'Wrong form type.';
        }
        if(!is_numeric($id) OR !(int)$id) {
            $id = null;
            $message = 'Wrong id.';
        }

        // redirect to dashboard if GET params are wrong
        if(!$form OR !$id) {
            if($message) {
                $this->_helper->flashMessenger(
                    array(
                        'type' => 'error', 
                        'message' => $message
                    )
                );
            }
            return $this->_helper->redirector->gotoRoute(
                array(
                    'controller' => 'user',
                    'action' => 'dashboard'
                ), 'default', true
            );
        } else {
            $domain = $this->_getParam('domain');
            // we need domain name when someone buys an extension
            if($form == 'extension' AND !$domain) {
                $this->_helper->flashMessenger(
                    array(
                        'type' => 'error', 
                        'message' => 'Wrong domain name.'
                    )
                );
                return $this->_helper->redirector->gotoRoute(
                    array(
                        'controller' => 'my-account',
                        'action' => 'compare'
                    ), 'default', true
                );
            }
            // display choosen form
            $address = array();
            if((int)$this->auth->getIdentity()->id) {
                $user = new Application_Model_User();
                $user->find($this->auth->getIdentity()->id);
                if((int)$user->getId()) {
                    $address = array(
                        'first_name'  => $user->getFirstname(),
                        'last_name'   => $user->getLastname(),
                        'street'      => $user->getStreet(),
                        'postal_code' => $user->getPostalCode(),
                        'city'        => $user->getCity(),
                        'state'       => $user->getState(),
                        'country'     => $user->getCountry()
                    );
                }
            }
            // user needs to have filled billing address
            if(isset($address['street']) AND $address['street']) {
                // fetch additional data for specific_content
                $model = null;
                if($form == 'plan') {
                    $model = new Application_Model_Plan();
                } else {
                    $model = new Application_Model_Extension();
                }
                if(is_object($model)) {
                    $row = $model->find($id);
                    if($row->getName()) {
                        $data = $row->__toArray();
                    }
                }

                $transaction = array(
                    'type' => 'sale',
                    'amount' => $row->getPrice(),
                    'options' => array(
                        'storeInVaultOnSuccess' => true,
                        'submitForSettlement' => true
                    )
                );
                // should we display inputs for billing address and credit card
                $this->view->show_billing_and_card = true;
                if($user->getBraintreeVaultId()) {
                    $transaction['customerId'] = 'card_id';
                    $transaction['options']['storeInVaultOnSuccess'] = false;
                    $this->view->show_billing_and_card = false;
                }
                $this->view->tr_data = Braintree_TransparentRedirect::transactionData(array(
                    'redirectUrl' => 'http://localhost'.
                        $this->view->url(array('controller' => 'braintree', 'action' => 'response', 'pay-for' => $form, 'id' => $id)),
                    'transaction' => $transaction
                ));

                $this->view->specific_content = $this->view->partial(
                    'braintree/'.$form.'.phtml',
                    $data
                );
                $this->view->address = $address;
            } else {
                return $this->_helper->redirector->gotoRoute(
                    array(
                        'controller' => 'my-account',
                        'action' => 'edit-account',
                        'inform' => 1
                    ), 'default', true
                );
            }
        }
    }

    public function responseAction() {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);

        $result = Braintree_TransparentRedirect::confirm($_SERVER['QUERY_STRING']);
        if($result->success) {
            if($result->transaction['status'] == 'submitted_for_settlement') {
                $pay_for = $this->_getParam('pay-for');
                $id = $this->_getParam('id');
                // check whether GET params are ok
                if(!in_array($pay_for, array('plan', 'extension'))) {
                    $pay_for = null;
                }
                if(!is_numeric($id) OR (int)$id < 1) {
                    $id = null;
                }
                if($pay_for AND $id) {
                    if($pay_for == 'plan') {
                        $errors = false;
                        if($user->getPlanId() != $id) {
                            /*
                             $a = strtotime('15-12-2012');
                            $b = strtotime('11-12-2012'); // 11-12-2012
                            $c = strtotime('-7 days', $a); // 09-12-2012
                            echo ($b-$c)/3600/24;
                            */
                            $plan = new Application_Model_Plan();
                            $plan = $plan->find($id);
                            $subscription_end = strtotime($user->getPlanActiveTo());//strtotime('15-12-2012');
                            $subscription_start = strtotime('-7 days', $subscription_end);
                            $today = strtotime(date('Y-m-d'));
                            $plan_range_days = ($subscription_end-$subscription_start)/3600/24;
                            $not_used_days = $plan_range_days-(($today-$subscription_start)/3600/24);
                            $refund = ($not_used_days*(float)$plan->getPrice())/($plan_range_days);
                            // number_format($refund, 2);
                            if($refund > 0) {
                                $refund_result = Braintree_Transaction::refund($user->getSubscrId(), number_format($refund, 2));
                                if(!$refund_result->success) {
                                    $errors = true;
                                }
                            }
                        }
                        if(!$errors) {
                            $user->setBraintreeTransactionId($result->transaction['id']);
                            $user->setBraintreeVaultId($result->transaction['customer']['id']);
                            $user->setPlanId($id);
                            $user->setPlanActiveTo(date('Y-m-d', strtotime('last day of next month')));
                            $user->save();
                        }
                    } else {
                        // pay for extension
                        $domain = $this->_getParam('domain');
                        $store = new Application_Model_Store();
                        $store = $store->findByDomain($domain);
                        if(is_object($store) AND (int)$store->getId()) {
                            // add task with extension installation
                            $extensionQueueItem = new Application_Model_Queue();
                            $extensionQueueItem->setStoreId($store->id);
                            $extensionQueueItem->setStatus('pending');
                            $extensionQueueItem->setUserId($store->user_id);
                            $extensionQueueItem->setExtensionId($id);
                            $extensionQueueItem->setParentId(0);
                            $extensionQueueItem->setServerId($store->server_id);
                            $extensionQueueItem->setTask('ExtensionOpensource');
                            $extensionQueueItem->save();
                        } else {
                            if ($log = $this->getLog()) {
                                $log->log('Braintree', Zend_Log::ERR, $this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - errors: '.'Domain('.$domain.')');
                            }
                        }
                    }
                    if(!$user->getBraintreeVaultId()) {
                        $user->setBraintreeVaultId($result->transaction['transaction']['creditCard']['token']);
                        $user->save();
                    }
                    $this->_helper->flashMessenger(
                        array(
                            'type' => 'success',
                            'message' => 'You have been succefully charged for '.$pay_for.'.'
                        )
                    );
                }
                return $this->_helper->redirector->gotoRoute(
                    array(
                        'controller' => 'my-account',
                        'action' => 'compare'
                    ), 'default', true
                );
            } else {
                // transaction had wrong status
                return $this->_helper->redirector->gotoRoute(
                    array(
                        'controller' => 'my-account',
                        'action' => 'compare'
                    ), 'default', true
                );
            }
        } else {
            if ($log = $this->getLog()) {
                $errors = '';
                $errors .= $this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - errors:';
                foreach($result->errors->deepAll() as $error) {
                    $errors .= ' "'.$error->attribute.'('.$error->code.') '.$error->message.'",';
                }
                $errors = rtrim($errors, ',');
                $log->log('Braintree', Zend_Log::ERR, $errors);
            }
        }
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function changePlanAction() {
        $redirect = false;
        $flash_message = false;
        if($this->getRequest()->isPost()) {
            $id = (int)$this->_getParam('id', 0);
            $plan = new Application_Model_Plan();
            $plan = $plan->find($id);
            $user = new Application_Model_User();
            $user = $user->find($this->auth->getIdentity()->id);
            if($user->hasPlanActive()) {
                if($id AND $plan->getId()) {
                    if($id != $user->getPlanId()) {
                        if(!$this->_getParam('confirm')) {
                            // show form
                            $this->view->id = $id;
                        } else {
                            // @todo: remember to split date - we dont need exact time
                            $subscription_end = strtotime($user->getPlanActiveTo());//strtotime('15-12-2012');
                            $subscription_start = strtotime('-7 days', $subscription_end);
                            $today = strtotime(date('Y-m-d'));
                            $plan_range_days = ($subscription_end-$subscription_start)/3600/24;
                            $not_used_days = $plan_range_days-(($today-$subscription_start)/3600/24);
                            $refund = number_format(($not_used_days*(float)$plan->getPrice())/($plan_range_days), 2);
                            $result = null;
                            if($amount < 0) {
                                $result = Braintree_Transaction::refund($user->getBraintreeTransactionId(), (float)$amount);
                            } else {
                                $result = Braintree_Transaction::sale(array(
                                    'amount' => $amount,
                                    'customerId' => $user->getBraintreeVaultId()
                                ));
                            }
                            $redirect = array(
                                    'controller' => 'my-account',
                                    'action' => 'index'
                            );
                            if(is_object($result) AND $result->success) {
                                $payment = new Application_Model_Payment();
                                $payment = $payment->fetchByBraintreeTransactionId($user->getBraintreeTransactionId());
                                $payment->setPrice((float)$payment->getPrice()+(float)$amount);
                                $payment->save();
                                $flash_message = 'Your plan has been successfully changed.';
                            } else {
                                $flash_message = 'We couldn\'t change your plan.';
                            }
                        }
                    } else {
                        $redirect = array(
                                'controller' => 'my-account',
                                'action' => 'compare'
                        );
                        $flash_message = array('type' => 'notice', 'message' => 'You already have this plan.');
                    }
                } else {
                    $redirect = array(
                        'controller' => 'my-account',
                        'action' => 'compare'
                    );
                    $flash_message = array('type' => 'error', 'message' => 'Wrong plan.');
                }
            } else {
                $redirect = array(
                    'controller' => 'my-account',
                    'action' => 'compare'
                );
                $flash_message = array('type' => 'notice', 'message' => 'Subscribe any plan first.');
            }
        } else {
            $redirect = array(
                    'controller' => 'my-account'
            );
        }
        
        if($redirect) {
            if($flash_message) {
                $this->_helper->FlashMessenger($flash_message);
            }
            return $this->_helper->redirector->gotoRoute(
                    array_merge(array('module' => 'default'), $redirect)
                    , 'default', true
            );
        }
    }

    // emulate subscription feature
    public function chargeSubscriptionsAction() {
        
    }

    // check whether transactions with status 'submitted for settlement' are already settled
    public function validateSettlementsAction() {
        
    }
}
