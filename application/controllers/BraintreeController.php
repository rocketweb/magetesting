<?php


class BraintreeController extends Integration_Controller_Action
{
       
    public function init(){
       parent::init();
       require_once 'Braintree.php';
       $config = $this->getInvokeArg('bootstrap')
                      ->getResource('config');
       
       Braintree_Configuration::environment($config->braintree->environment);
       Braintree_Configuration::merchantId($config->braintree->merchantId);
       Braintree_Configuration::publicKey($config->braintree->publicKey);
       Braintree_Configuration::privateKey($config->braintree->privateKey);

       $this->_helper->sslSwitch();
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
        if(!in_array($form, array('plan', 'extension', 'change-plan'))) {
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
            $this->view->domain = $domain = $this->_getParam('domain');
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
                    if(($form == 'plan' OR $form == 'change-plan') AND $user->hasPlanActive() AND !(int)$user->getBraintreeTransactionConfirmed()) {
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
                    // Do not allow users to change plan if they don't have active plan
                    if($form == 'change-plan' AND !$user->hasPlanActive()) {
                        $this->_helper->flashMessenger(
                            array(
                                'type' => 'notice', 
                                'message' => 'Subscribe any plan first.'
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
                if($form == 'plan' OR $form == 'change-plan') {
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
                if($form == 'change-plan') {
                    // change plan form has his own view
                    $this->_helper->viewRenderer->setRender('change-plan');
                    $this->view->id = $id;
                    $form = 'plan';
                } else {
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
                }
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

            $this->view->source = $this->_getParam('source',null);
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
                                $user->setGroup('commercial-user');
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
                                'message' =>  'You have successfully bought '.$pay_for.' extension. It will be uploaded in open source version into your store just after transaction is confirmed.'
                            )
                        );
                    }
                    if($pay_for == 'extension') {
                        return $this->_helper->redirector->gotoRoute(
                            array(
                                'controller' => 'queue',
                                'action' => 'extensions',
                                'store' => $domain
                            ), 'default', true
                        );
                    } else {
                        return $this->_helper->redirector->gotoRoute(
                            array(
                                'controller' => 'my-account',
                                'action' => 'compare'
                            ), 'default', true
                        );
                    }
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
                            $redirect = array(
                                'controller' => 'user',
                                'action'     => 'dashboard'
                            );
                        } else {
                            $old_plan = new Application_Model_Plan();
                            $old_plan = $old_plan->find($user->getPlanId());

                            $subscription_end = explode(' ', $user->getPlanActiveTo());
                            $subscription_end = strtotime($subscription_end[0]);//strtotime('15-12-2012');
                            $subscription_start = strtotime('-' . $old_plan->getBillingPeriod(), $subscription_end);
                            $today = strtotime(date('Y-m-d'));
                            $subscription_range_days = ($subscription_end-$subscription_start)/3600/24;
                            $subscription_used_days = (($today-$subscription_start)/3600/24)+1;
                            $subscription_not_used_days = $subscription_range_days-$subscription_used_days;
                            $refund = round($subscription_not_used_days/$subscription_range_days*(float)$old_plan->getPrice(), 2);
                            
                            $new_plan_start = $subscription_start;
                            $new_plan_end = strtotime('+' . $plan->getBillingPeriod(), $new_plan_start);
                            $new_plan_range_days = ($new_plan_end-$new_plan_start)/3600/24;

                            if($today >= $new_plan_end-(3600*24) AND $new_plan_range_days < $subscription_range_days) {
                                $new_plan_start = $today;
                                $new_plan_end = strtotime('+' . $plan->getBillingPeriod(), $new_plan_start);
                                $new_plan_range_days = ($new_plan_end-$new_plan_start)/3600/24;
                            }
                            $new_plan_used_days = (($today-$new_plan_start)/3600/24)+1;
                            $new_plan_not_used_days = $new_plan_range_days-$new_plan_used_days;
                            $extra_charge = round($new_plan_not_used_days/$new_plan_range_days*(float)$plan->getPrice(), 2);
                            // if new plan ends earlier than the old one, move payment day to today
                            $result = null;
                            $amount = (float)$extra_charge-$refund;
                            if($amount < 0) {
                                // lower the payment
                                $result = Braintree_Transaction::refund($user->getBraintreeTransactionId(), round(-1*$amount, 2));
                            } else {
                                // add extra payment with new transaction id
                                $result = Braintree_Transaction::sale(array(
                                    'amount' => round($amount, 2),
                                    'customerId' => $user->getBraintreeVaultId()
                                ));
                            }
                            $redirect = array(
                                    'controller' => 'my-account',
                                    'action' => 'index'
                            );

                            $payment = new Application_Model_Payment();
                            $payment->findByTransactionId($user->getBraintreeTransactionId());
                            if(is_object($result) AND $result->success AND $payment->getId()) {
                                if($amount > 0) {
                                    $payment->setId(NULL);
                                    $payment->setBraintreeTransactionId($result->transaction->id);
                                    $payment->setPrice(0);
                                    $user->setBraintreeTransactionId($result->transaction->id);
                                    $user->setBraintreeTransactionConfirmed(0);
                                }
                                $payment->setPrice((float)$payment->getPrice()+$amount);
                                $payment->save();

                                $user->setPlanId($id);
                                $user->setPlanActiveTo(date('Y-m-d H:i:s', $new_plan_end));
                                $user->save();
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
}
