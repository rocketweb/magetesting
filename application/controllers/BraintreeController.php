<?php


class BraintreeController extends Integration_Controller_Action
{
       
    public function init(){
        parent::init();
        require_once 'Braintree.php';
        Braintree_Configuration::environment('sandbox');
        Braintree_Configuration::merchantId('hwwzbybn8tvfrhjz');
        Braintree_Configuration::publicKey('rpxf8q436zfmp78r');
        Braintree_Configuration::privateKey('e87aea495ca0f8dfab7137f52b9adf26');
//        $this->_helper->sslSwitch();
//        parent::init();
//        require_once 'Braintree.php';
//        $config = $this->getInvokeArg('bootstrap')
//                       ->getResource('config');
//        
//        Braintree_Configuration::environment($config->braintree->environment);
//        Braintree_Configuration::merchantId($config->braintree->merchantId);
//        Braintree_Configuration::publicKey($config->braintree->publicKey);
//        Braintree_Configuration::privateKey($config->braintree->privateKey)
    }

    // @todo: I think we should concat form and response due to error reporting.
    // @todo: We should refactor the code to remove so many redirections,
    // @todo: maybe by throwing Exceptions with some message or special variables
    // @todo: we check whether extension exist in store in response but we should do that also for form etc
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
            // display chosen form
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

                    // Do not allow user to change his plan before braintree settle last transaction
                    if($user->hasPlanActive() AND !(int)$user->getBraintreeTransactionConfirmed()) {
                        $this->_helper->flashMessenger(
                            array(
                                'type' => 'error', 
                                'message' => 'You can\'t change plan before your last transaction will not be settled.'
                            )
                        );
                        return $this->_helper->redirector->gotoRoute(
                            array(
                                'controller' => 'my-account',
                                'action' => 'index'
                            ), 'default', true
                        );
                    }
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
                        'addBillingAddressToPaymentMethod' => true,
                        'submitForSettlement' => true
                    )
                );
                // should we display inputs for billing address and credit card
                $this->view->show_billing_and_card = true;
                if($user->getBraintreeVaultId()) {
                    $transaction['customerId'] = $user->getBraintreeVaultId();
                    $transaction['options']['storeInVaultOnSuccess'] = false;
                    $transaction['options']['addBillingAddressToPaymentMethod'] = false;
                    $this->view->show_billing_and_card = false;
                }
                $url_segments = array('controller' => 'braintree', 'action' => 'response', 'pay-for' => $form, 'id' => $id);
                if($form == 'extension') {
                    $url_segments['domain'] = $domain;
                }
                $this->view->tr_data = Braintree_TransparentRedirect::transactionData(array(
                    'redirectUrl' => $this->view->serverUrl() . $this->view->url($url_segments)
                    ,
                    'transaction' => $transaction,
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

        if($_SERVER['QUERY_STRING']) {
            $result = Braintree_TransparentRedirect::confirm($_SERVER['QUERY_STRING']);
            if($result->success) {
                if($result->transaction->status == 'submitted_for_settlement') {
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
                            $plan = new Application_Model_Plan();
                            $plan = $plan->find($id);
                            if($plan->getId()) {
                                $user->setBraintreeTransactionId($result->transaction->id);
                                $user->setPlanId($id);
                                $user->setPlanActiveTo(
                                    date('Y-m-d', strtotime('+' . $plan->getBillingPeriod()))
                                );
                                $user->save();

                                $transaction_name = $plan->getName();
                                $transaction_type = 'subscription';
                            }
                        } else {
                            // pay for extension
                            $domain = $this->_getParam('domain');
                            $store = new Application_Model_Store();
                            $store = $store->findByDomain($domain); 
                            if(is_object($store) AND (int)$store->id) {
                                $store_extension = new Application_Model_StoreExtension();
                                $store_extension = $store_extension->fetchStoreExtension($store->id, $id);
                                // add task only if bought extension exist in store and is not opened
                                if(
                                    is_object($store_extension) AND
                                    $store_extension->getExtensionId() == $id AND
                                    !$store_extension->getBraintreeTransactionId() AND
                                    !(int)$store_extension->getBraintreeTransactionConfirmed()
                                ) {
                                    // set transaction confirmed
                                    $store_extension->setBraintreeTransactionId($result->transaction->id);
                                    $store_extension->setBraintreeTransactionConfirmed(0);
                                    $store_extension->save();

                                    // add task with extension installation
                                    $extensionQueueItem = new Application_Model_Queue();
                                    $extensionQueueItem->setStoreId($store->id);
                                    $extensionQueueItem->setStatus('pending');
                                    $extensionQueueItem->setUserId($user->getId());
                                    $extensionQueueItem->setExtensionId($id);
                                    $extensionQueueItem->setParentId(0);
                                    $extensionQueueItem->setServerId($store->server_id);
                                    $extensionQueueItem->setTask('ExtensionOpensource');
                                    $extensionQueueItem->save();

                                    $extension = new Application_Model_Extension();
                                    $extension->find($id);
                                    $transaction_name = $extension->getName();
                                    $transaction_type = 'extension';
                                }
                            } else {
                                if ($log = $this->getLog()) {
                                    $log->log('Braintree', Zend_Log::ERR, $this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - errors: '.'Domain('.$domain.')');
                                }
                            }
                        }

                        if(isset($transaction_type)) {
                            // add invoice to payment table
                            $payment = new Application_Model_Payment();
                            $payment_data = $user->__toArray();
                            $billing = $result->transaction->billingDetails;
                            if(!$billing->firstName) {
                                $last_payment = new Application_Model_Payment();
                                $last_payment->findLastForUser($user->getId());
                                // set payment data from last payment record
                                if(!$last_payment->getFirstName()) {
                                    $payment_data = $last_payment->__toArray();
                                    var_dump($payment_data);
                                    unset($payment_data['id']);
                                } else {
                                    // set payment data from user record
                                    $payment_data = array(
                                        'city' => $user->getCity(),
                                        'country' => $user->getCountry(),
                                        'state' => $user->getState(),
                                        'street' => $user->getStreet(),
                                        'postal_code' => $user->getPostalCode(),
                                        'first_name' => $user->getFirstname(),
                                        'last_name' => $user->getLastname(),
                                    );
                                }
                            } else {
                                // set payment data from braintree result
                                $payment_data = array(
                                    'city' => $billing->locality,
                                    'country' => $billing->countryName,
                                    'state' => $billing->region,
                                    'street' => $billing->streetAddress,
                                    'postal_code' => $billing->postalCode,
                                    'first_name' => $billing->firstName,
                                    'last_name' => $billing->lastName
                                );
                            }
                            $payment_data['user_id'] = $user->getId();
                            $payment_data['price'] = $result->transaction->amount;
                            $payment_data['date'] = date('Y-m-d H:i:s');
                            $payment_data['braintree_transaction_id'] = $result->transaction->id;
                            $payment_data['transaction_name'] = $transaction_name;
                            $payment_data['transaction_type'] = $transaction_type;
                            $payment->setOptions($payment_data);
                            $payment->save();
                        }

                        if(!$user->getBraintreeVaultId()) {
                            $user->setBraintreeVaultId($result->transaction->customerDetails->id);
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
                    $errors .= $this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - errors:'.PHP_EOL;
                    foreach($result->errors->deepAll() as $error) {
                        $errors .= $error->code . ": " . $error->message . "\n";
                    }
                    $errors = rtrim($errors);
                    $this->_response->setBody('<pre>', $errors);
                    $log->log('Braintree', Zend_Log::ERR, $errors);
                }
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
                // Do not allow user to change his plan before braintree settle last transaction
                if(!(int)$user->getBraintreeTransactionConfirmed()) {
                    $this->_helper->flashMessenger(
                            array(
                                    'type' => 'error',
                                    'message' => 'You can\'t change plan before your last transaction will not be settled.'
                            )
                    );
                    return $this->_helper->redirector->gotoRoute(
                            array(
                                    'controller' => 'my-account',
                                    'action' => 'index'
                            ), 'default', true
                    );
                }
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
                                // lower the payment
                            } else {
                                $result = Braintree_Transaction::sale(array(
                                    'amount' => $amount,
                                    'customerId' => $user->getBraintreeVaultId()
                                ));
                                // @todo: add extra payment with new transaction id
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
        // @todo: find users with plan active to time() and do Transaction::sale
        // strtotime('last day of next month')
    }

    // check whether transactions with status 'submitted for settlement' are already settled
    public function validateSettlementsAction() {
        // @todo: find users with active transaction but not settled
        /*
         * SELECT * FROM `user` WHERE plan_active_to >= CURRENT_TIME() AND (braintree_transaction_confirmed = 0 OR braintree_transaction_confirmed IS NULL)
         * SELECT * FROM `store_extension` WHERE LENGTH(braintree_transaction_id) > 0 AND (braintree_transaction_confirmed = 0 OR braintree_transaction_confirmed IS NULL)
         */
        // @todo: find extensions with transaction id and not settled
        // @todo: find out should we join store and extension table
    }
}
