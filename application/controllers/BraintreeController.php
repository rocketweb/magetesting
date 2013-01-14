<?php

class BraintreeController extends Integration_Controller_Action
{
    protected $_general_error_message = 'We had a problem contacting payment gateway. Please try again or contact with <a href="mailto:support@magetesting.com">support@magetesting.com</a>.';

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

    public function paymentAction() {
        // handle braintree response
        if($_SERVER['QUERY_STRING']) {
            $this->_response();
        } else { // display payment form
            $this->_showPaymentForm();
        }
    }

    /**
     * Handles messages from braintree
     */
    protected function _response() {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);
        
        try {
            $flash_message = NULL;
            $redirect = NULL;
            $result = Braintree_TransparentRedirect::confirm($_SERVER['QUERY_STRING']);
            if($result->success AND $result->transaction->status == 'submitted_for_settlement') {
                $transaction_data = $result->transaction;
                unset($result); // free memory

                // Determine payment type and product id
                $pay_for = $this->_getParam('pay-for');
                $id = $this->_getParam('id');
                $domain = $this->_getParam('domain');
                // Check whether params are ok
                if(!in_array($pay_for, array('plan', 'extension'))) {
                    $pay_for = null;
                }
                if(!is_numeric($id) OR (int)$id < 1) {
                    $id = null;
                }

                // Handle payment response by type
                if($pay_for == 'plan') {
                    $plan = new Application_Model_Plan();
                    $plan = $plan->find($id);
                    // if plan with given id exist
                    if($plan->getId()) {
                        $user->setBraintreeTransactionId($transaction_data->id);
                        $user->setPlanId($id);
                        $user->setGroup('commercial-user');
                        $user->setPlanActiveTo(
                                date('Y-m-d', strtotime('+' . $plan->getBillingPeriod()))
                        );
                        $user->save();

                        // set transaction data for invoice record
                        $transaction_name = $plan->getName();
                        $transaction_type = 'subscription';
                    }
                } else { // if $pay_for == 'extension'
                    $store = new Application_Model_Store();
                    $store = $store->findByDomain($domain);
                    if(is_object($store) AND (int)$store->id AND $user->getId() == $store->user_id) {
                        $store_extension = new Application_Model_StoreExtension();
                        $store_extension = $store_extension->fetchStoreExtension($store->id, $id);
                        // add task only if bought extension exist in store and is commercial
                        if(
                            is_object($store_extension) AND
                            $store_extension->getExtensionId() == $id AND
                            !$store_extension->getBraintreeTransactionId() AND
                            !(int)$store_extension->getBraintreeTransactionConfirmed()
                        ) {
                            // set transaction confirmed
                            $store_extension->setBraintreeTransactionId($transaction_data->id);
                            $store_extension->setBraintreeTransactionConfirmed(0);
                            $store_extension->save();

                            $extension = new Application_Model_Extension();
                            $extension->find($id);

                            // set transaction data for invoice record
                            $transaction_name = $extension->getName();
                            $transaction_type = 'extension';
                        } else { // wrong extension id or it does not belong to given store
                            throw new Braintree_Controller_Exception('Wrong extension id('.$id.') or it does not belong to given store('.$domain.').');
                        }
                    } else { // wrong domain name or user does not have given store
                        throw new Braintree_Controller_Exception('Store('.$domain.') does not belong to user('.$user->getId().') or store does not exist.');
                    }
                }

                // add invoice to payment table
                $payment = new Application_Model_Payment();
                $payment_data = $user->__toArray();
                $billing = $transaction_data->billingDetails;
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
                $payment_data['price'] = $transaction_data->amount;
                $payment_data['date'] = date('Y-m-d H:i:s');
                $payment_data['braintree_transaction_id'] = $transaction_data->id;
                $payment_data['transaction_name'] = $transaction_name;
                $payment_data['transaction_type'] = $transaction_type;
                $payment->setOptions($payment_data);
                $payment->save();
                
                if(!$user->getBraintreeVaultId()) {
                    $user->setBraintreeVaultId($transaction_data->customerDetails->id);
                    $user->save();
                }

                if('extension' === $pay_for) {
                    $flash_message = 'You have successfully bought extension. It will be uploaded in open source version into your store just after transaction is confirmed.';
                    $redirect = array(
                            'controller' => 'queue',
                            'action' => 'extensions',
                            'store' => $domain
                    );
                } else {
                    $flash_message = 'You have successfully paid for your plan.';
                    $redirect = array(
                            'controller' => 'my-account',
                            'action' => 'index'
                    );
                }
            } else {
                // checks braintree errors and marks invalid fields in form, or throws Braintree_Controller_Exception
                $this->_handleResponseErrors($result);
                $this->_showPaymentForm(true);
            }
        } catch(Braintree_Controller_Exception $e) {
            if($log = $this->getLog()) {
                $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$e->getMessage();
                $log->log('Braintree - Response', Zend_Log::DEBUG, $error);
            }
            $message = array('from_scratch' => true, 'type' => 'error', 'message' => $this->_general_error_message);
            $redirect = array(
                'controller' => 'my-account',
                'action' => 'compare'
            );
        } catch(Braintree_Exception $e) {
            if($log = $this->getLog()) {
                $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$e->getMessage();
                $log->log('Braintree - Response', Zend_Log::ERR, $error);
            }
            $flash_message = array('type' => 'error', 'message' => 'Braintree service temporarily unavailable.');
            $redirect = array(
                'controller' => 'my-account',
            );
        }

        if($redirect) {
            // disable view
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            if($flash_message) {
                $this->_helper->flashMessenger(
                        $flash_message
                );
            }
            return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
            );
        }
    }

    protected function _showPaymentForm($allow_without_post = false) {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);

        $flash_message = NULL;
        $redirect = NULL;

        $pay_for = $this->_getParam('pay-for');
        $id = (int)$this->_getParam('id', 0);
        $this->view->domain = $domain = $this->_getParam('domain');

        try {
            // check whether GET params are ok
            if(!in_array($pay_for, array('plan', 'extension', 'change-plan'))) {
                $pay_for = NULL;
                $flash_message = array('type' => 'error', 'message' => 'Wrong form type.');
            }
            if(!$id) {
                $id = NULL;
                $flash_message = array('type' => 'error', 'message' => 'Wrong id.');

                // show form with preselected plan if user does not have active plan
                (int)$user->getPreselectedPlanId();
                if(!$pay_for AND (int)$user->getPreselectedPlanId() AND !$user->hasPlanActive()) {
                    $pay_for = 'plan';
                    $id = $user->getPreselectedPlanId();
                    $flash_message = NULL;
                }
            }

            // if form type or entity id is wrong, throw exception to help in redirecting
            if(!$pay_for OR !$id) {
                $redirect = array('controller' => 'my-account', 'action' => 'compare');
                throw new Braintree_Controller_Exception($flash_message['message']);
            }

            /*
             * display chosen form
             */

            // address data
            $address = $user->__toArray();
            $address['first_name'] = $address['firstname'];
            $address['last_name'] = $address['lastname'];

            if($pay_for == 'extension') {
                // we need domain name when someone buys an extension
                if(!$domain) {
                    $flash_message = array(
                            'type' => 'error',
                            'message' => 'Wrong domain name.'
                    );
                    $redirect = array(
                            'controller' => 'user',
                            'action' => 'dashboard'
                    );
                    throw new Braintree_Controller_Exception($flash_message['message']);
                }

                $extension = $user->hasStoreExtension($id);
                // check whether extension belongs to user
                if(!$extension) {
                    $flash_message = array(
                        'type' => 'error',
                        'message' => 'It is not your extension.'
                    );
                    $redirect = array(
                        'controller' => 'my-account',
                        'action' => 'compare'
                    );
                    throw new Braintree_Controller_Exception($flash_message['message']);
                }
                // check whether given GET params are correct
                if(!$allow_without_post AND $this->_request->isGet() AND ($extension->reminder_sent == 0 || !is_null($extension->braintree_transaction_id))) {
                    $flash_message = array(
                        'type' => 'error',
                        'message' => 'Wrong parameters in url.'
                    );
                    $redirect = array(
                            'controller' => 'my-account',
                            'action' => 'compare'
                    );
                    throw new Braintree_Controller_Exception($flash_message['message']);
                }
            }

            // Do not allow user to change his plan before braintree settle last transaction
            if(($pay_for == 'plan' OR $pay_for == 'change-plan') AND $user->hasPlanActive() AND !(int)$user->getBraintreeTransactionConfirmed()) {
                $flash_message = array(
                        'type' => 'error',
                        'message' => 'You can\'t change plan before your last transaction will not be settled.'
                );
                $redirect = array(
                        'controller' => 'my-account',
                        'action' => 'index'
                );
                throw new Braintree_Controller_Exception($flash_message['message']);
            }

            // Do not allow users to change plan if they don't have active plan
            if($pay_for == 'change-plan' AND !$user->hasPlanActive()) {
                $flash_message = array(
                        'type' => 'notice',
                        'message' => 'Subscribe any plan first.'
                );
                $redirect = array(
                        'controller' => 'my-account',
                        'action' => 'index'
                );
                throw new Braintree_Controller_Exception($flash_message['message']);
            }

            // fetch additional data for specific_content
            $model = null;
            if($pay_for == 'plan' OR $pay_for == 'change-plan') {
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

            // create braintree gateway data
            $transaction = array(
                'type' => 'sale',
                'amount' => $row->getPrice(),
                'options' => array(
                    'storeInVaultOnSuccess' => true,
                    'addBillingAddressToPaymentMethod' => true,
                    'submitForSettlement' => true
                )
            );

            // prepare view data and choose renderer file
            if($pay_for == 'change-plan') {
                // change-plan form has his own view
                $this->_helper->viewRenderer->setRender('change-plan');
                $this->view->id = $id;
                $pay_for = 'plan';
            } else {
                $this->_helper->viewRenderer->setRender('form');
                // should we display inputs for billing address and credit card
                $this->view->show_billing_and_card = true;
                if($user->getBraintreeVaultId()) {
                    $transaction['customerId'] = $user->getBraintreeVaultId();
                    $transaction['orderId'] = $domain.'-ext-'.$id;
                    $transaction['options']['storeInVaultOnSuccess'] = false;
                    $transaction['options']['addBillingAddressToPaymentMethod'] = false;
                    $this->view->show_billing_and_card = false;
                }
                $url_segments = array(
                        'controller' => 'braintree',
                        'action' => 'payment',
                        'pay-for' => $pay_for,
                        'id' => $id
                );
                if($pay_for == 'extension') {
                    $url_segments['domain'] = $domain;
                }
                $this->view->tr_data = Braintree_TransparentRedirect::transactionData(array(
                        'redirectUrl' => $this->view->serverUrl() . $this->view->url($url_segments)
                        ,
                        'transaction' => $transaction,
                ));
            }
            $this->view->specific_content = $this->view->partial(
                    'braintree/'.$pay_for.'.phtml',
                    $data
            );
            $this->view->address = $address;
            $this->view->source = $this->_getParam('source',null);
        } catch(Braintree_Controller_Exception $e) {
            // we just don't need to do anything here
            // but of course we can use this place to log errors
        } catch(Braintree_Exception $e) {
            if($log = $this->getLog()) {
                $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$e->getMessage();
                $log->log('Braintree - Response', Zend_Log::ERR, $error);
            }

            $flash_message = array('type' => 'error', 'message' => 'Braintree service temporarily unavailable.');
            $redirect = array(
                    'controller' => 'user',
                    'action' => 'dashboard'
            );
        }

        if($redirect) {
            // disable view
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
        
            if($flash_message) {
                $this->_helper->flashMessenger(
                        $flash_message
                );
            }
            return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
            );
        }
    }

    protected function _handleResponseErrors($braintree_result) {
        $flash_message = NULL;
        $redirect = NULL;
        $this->view->errors = array();
        if(is_object($braintree_result)) {
            $braintree_errors = $braintree_result->errors->deepAll();
            if(!$braintree_errors) {
                $this->view->messages = array(array('type' =>'error', 'message' => 'Your payment has been declined. Please check your payment details.'));
                if($log = $this->getLog()) {
                    $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$braintree_result->message;
                    $log->log('Braintree - payment declined', Zend_Log::NOTICE, $error);
                }
            } else {
                foreach($braintree_result->errors->deepAll() as $error) {
                    $field_key = '';
                    switch($error->code) {
                        case 81709:
                        case 81710:
                            $field_key = 'exp_date';
                        break;
                        case 81714:
                        case 81703:
                        case 81716:
                        case 81715:
                        case 81703:
                            $field_key = 'credit_card';
                        break;
                        case 91803:
                            $field_key = 'country';
                        break;
                        case 81707:
                            $field_key = 'cvv';
                        break;
                        case 91510:
                            $flash_message = array('type' =>'notice', 'message' => $this->_general_error_message);
                            $redirect = array(
                                'controller' => 'my-account'
                            );
                            if($log = $this->getLog()) {
                                $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message: Wrong Vault ID';
                                $log->log('Braintree - Response errors', Zend_Log::ERR, $error);
                            }
                        break;
                    }
    
                    if($field_key) {
                        if(!isset($this->view->errors[$field_key])) {
                            $this->view->errors[$field_key] = array();
                        }
                        array_push($this->view->errors[$field_key], $error->message);
                    } else {
                        if($log = $this->getLog()) {
                            $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$error->message.'('.$error->code.')';
                            $log->log('Braintree - Response unknown errors', Zend_Log::ERR, $error);
                        }

                        if(!$flash_message) {
                            $flash_message =array('from_scratch' => true, 'type' => 'error', 'message' => $this->_general_error_message);
                        }
                        if(!$redirect) {
                            $redirect = array(
                                'controller' => 'my-account'
                            );
                        }
                        break;
                    }
                }
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

        // if we have some errors for payment fields
        // render flash message
        if(isset($this->view->errors) AND $this->view->errors) {
            $this->view->messages = array(array('type' => 'error', 'message' => 'Some fields are filled incorrectly, please fix them and submit form again.'));
        }
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
                            $subscription_end = strtotime($subscription_end[0]);
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

                            try {
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

                                $payment = new Application_Model_Payment();
                                $payment->findByTransactionId($user->getBraintreeTransactionId());
                                if(is_object($result) AND $result->success AND $payment->getId()) {
                                    $payment->setId(NULL);
                                    $payment->setPrice($amount);
                                    $payment->setDate(date('Y-m-d H:i:s'));
                                    $payment->setBraintreeTransactionId($result->transaction->id);
                                    $payment->setTransactionName($plan->getName());
                                    if($amount > 0) {
                                        $user->setBraintreeTransactionId($result->transaction->id);
                                        $user->setBraintreeTransactionConfirmed(0);
                                    }
                                    $payment->save();

                                    $user->setPlanId($id);
                                    $user->setPlanActiveTo(date('Y-m-d H:i:s', $new_plan_end));
                                    $user->save();
                                    $flash_message = 'Your plan has been successfully changed.';
                                    $redirect = array(
                                            'controller' => 'my-account',
                                            'action' => 'index'
                                    );
                                } else {
                                    $this->_handleResponseErrors($result);
                                    $this->_setParam('pay-for', 'change-plan');
                                    $this->_showPaymentForm(true);
                                }
                            } catch(Braintree_Exception $e) {
                                if($log = $this->getLog()) {
                                    $error = 'User: '.$this->auth->getIdentity()->login.' - '.$this->getRequest()->getRequestUri().' - message:'.$e->getMessage();
                                    $log->log('Braintree - Response', Zend_Log::ERR, $error);
                                }
                                $flash_message = array('from_scratch' => true, 'type' => 'error', 'message' => $this->_general_error_message);
                                $redirect = array(
                                    'controller' => 'my-account',
                                    'action' => 'compare'
                                );
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
