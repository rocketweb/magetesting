<?php

class PaymentController extends Integration_Controller_Action
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

                // used when user purchased extra stores to pass it to admin notification email
                $additional_stores = 0;

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
                } elseif($pay_for === 'extension') {
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
                } else { // if $pay_for == 'additional-stores'
                    if(preg_match('/^.*\-(\d+)$/i', $transaction_data->orderId, $match)) {
                        $additional_stores = (int)$match[1];
                        $user->setAdditionalStores((int)$user->getAdditionalStores()+$additional_stores);
                        $user->save();
                        $transaction_name = '+'.$additional_stores.' stores';
                        $transaction_type = 'additional-stores';
                    } else {
                        throw new Braintree_Controller_Exception('Number of additionals stores to add not found for user '.$user->getId());
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

                $adminNotificationData = array('user' => $user);
                $adminNotificationEmailType = '';
                if('extension' === $pay_for) {
                    // raise plan for user with 7 days plan
                    $plan = new Application_Model_Plan();
                    $plan->find($user->getPlanId());
                    if(stristr($plan->getBillingPeriod(), 'days')) {
                        $user->setPlanActiveTo(date('Y-m-d', strtotime('+7 days', strtotime($user->getPlanActiveTo()))));
                        $user->setPlanRaisedToDate(date('Y-m-d', strtotime('+7 days')));
                        $user->setPlanIdBeforeRaising($user->getPlanId());
                        $user->setPlanId(2);
                        $user->save();
                    }
                    
                    $extensionModel = new Application_Model_Extension();
                    $extensionModel->find($store_extension->getExtensionId());
                    
                    $queueModel = new Application_Model_Queue();
                    $queueModel->setStoreId($store->id);
                    $queueModel->setTask('ExtensionOpensource');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($store->user_id);
                    $queueModel->setServerId($store->server_id);
                    $queueModel->setExtensionId($store_extension->getExtensionId());
                    $queueModel->setParentId(0);
                    $queueModel->save();
                    $opensourceId = $queueModel->getId();
                    unset($queueModel);
                    
                    $queueModel = new Application_Model_Queue();
                    $queueModel->setStoreId($store->id);
                    $queueModel->setTask('RevisionCommit');
                    $queueModel->setTaskParams(
                            array(
                                    'commit_comment' => $extensionModel->getName() . ' (Open Source)',
                                    'commit_type' => 'extension-decode'
                            )
                    );
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($store->user_id);
                    $queueModel->setServerId($store->server_id);
                    $queueModel->setExtensionId($store_extension->getExtensionId());
                    $queueModel->setParentId($opensourceId);
                    $queueModel->save();

                    $storeModel = new Application_Model_Store();
                    $storeModel = $storeModel->find($store->id);
                    $storeModel->setStatus('installing-extension')->save();

                    $flash_message = 'You have successfully bought extension. It will be uploaded in open source.';
                    $redirect = array(
                            'controller' => 'queue',
                            'action' => 'extensions',
                            'store' => $domain
                    );
                    $adminNotificationEmailType = 'boughtExtension';
                    $adminNotificationData['extension'] = $extensionModel;
                } elseif('plan' === $pay_for) {
                    $flash_message = 'You have successfully paid for your plan.';
                    $redirect = array(
                            'controller' => 'my-account',
                            'action' => 'index'
                    );
                    $adminNotificationEmailType = 'boughtPlan';
                    $adminNotificationData['plan'] = $plan;
                } else {
                    $flash_message = 'You have successfully bought additional store(s).';
                    $redirect = array(
                        'controller' => 'user',
                        'action' => 'dashboard'
                    );
                    $adminNotificationEmailType = 'additionalStores';
                    $adminNotificationData['additional_stores'] = $additional_stores;
                }
                // send admin email
                if($adminNotificationEmailType) {
                    $adminNotification = new Integration_Mail_AdminNotification();
                    $adminNotification->setup($adminNotificationEmailType, $adminNotificationData);
                    try {
                        $adminNotification->send();
                    } catch(Exception $e) {
                        if($log = $this->getLog()) {
                            $log->log('Braintree - admin notification email', Zend_Log::DEBUG, $e->getMessage());
                        }
                    }
                }
            } else {
                // checks braintree errors and marks invalid fields in form, or throws Braintree_Controller_Exception
                $this->_handleResponseErrors($result);
                $this->_showPaymentForm();
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

    protected function _showPaymentForm() {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);

        $flash_message = NULL;
        $redirect = NULL;

        $pay_for = $this->_getParam('pay-for');
        $id = (int)$this->_getParam('id', 0);
        $additional_stores = (int)$this->_getParam('additional-stores-quantity', 0);
        $this->view->domain = $domain = $this->_getParam('domain'); 
        try {
            // check whether GET params are ok
            if(!in_array($pay_for, array('plan', 'extension', 'change-plan', 'additional-stores'))) {
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
            if(!$pay_for OR (!$id && $pay_for !== 'additional-stores')) {
                $redirect = array('controller' => 'my-account', 'action' => 'compare');
                throw new Braintree_Controller_Exception($flash_message['message']);
            }

            if($pay_for === 'plan') {
                // if user already has given plan, redirect him to compare page
                if($user->hasPlanActive() AND $id == $user->getPlanId()) {
                    $redirect = array('controller' => 'my-account', 'action' => 'compare');
                    $flash_message = array('type' => 'notice', 'message' => 'You already have this plan.');
                    throw new Braintree_Controller_Exception($flash_message['message']);
                }
            }

            if($pay_for === 'additional-stores') {
                $plan = new Application_Model_Plan();
                $plan->find($user->getPlanId());
                if(
                    $additional_stores <= 0
                    || (int)$user->getAdditionalStores() >= $plan->getMaxStores()
                    || (int)$user->getAdditionalStores()+$additional_stores > $plan->getMaxStores()
                ) {
                    $redirect = array('controller' => 'user', 'action' => 'dashboard');
                    $flash_message = array('type' => 'notice', 'message' => 'You cannot purchase more additional stores.');
                    throw new Braintree_Controller_Exception($flash_message['message']);
                }
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

                $extension = $user->hasStoreExtension($domain, $id);
                // check whether extension belongs to user
                if(!$extension) {
                    $flash_message = array();
                    $redirect = array(
                        'controller' => 'user',
                        'action' => 'dashboard',
                    );
                    throw new Braintree_Controller_Exception('Hack attempt or extension is not installed in given store.');
                }

                if(!empty($extension->braintree_transaction_id)) {
                    $flash_message = array(
                        'type' => 'error',
                        'message' => 'Extension was already bought for that store.'
                    );
                    $redirect = array(
                            'controller' => 'queue',
                            'action' => 'extensions',
                            'store' => $domain
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
            } elseif($pay_for === 'extension') {
                $model = new Application_Model_Extension();
            } else { // $pay_for === 'additional-stores'
                $data = $plan->__toArray();
                $data['additional_stores'] = $additional_stores;
                $payment_data = $this->_calculatePayment($user->getPlanActiveTo(), $plan->getBillingPeriod(), (float)$data['store_price']*$additional_stores);
                $data['price'] = $payment_data['price'];
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
                'amount' => $data['price'],
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
                $this->view->plan_change_calculation = $this->_calculatePlanChange($user, $model);
                $pay_for = 'plan';
                $this->view->new_plan_name = $data['name'];
            } else {
                $this->_helper->viewRenderer->setRender('form');
                // should we display inputs for billing address and credit card
                $this->view->show_billing_and_card = true;
                if($user->getBraintreeVaultId()) {
                    $transaction['customerId'] = $user->getBraintreeVaultId();
                    if($pay_for === 'extension') {
                        $order_type = '-ext-';
                    } elseif($pay_for === 'plan') {
                        $order_type = '-plan-';
                    } else {
                        $order_type = '-additional_store-'.$user->getId().'-'.time().'-';
                        $id = $additional_stores;
                    }
                    $transaction['orderId'] = $domain.$order_type.$id;
                    $transaction['options']['storeInVaultOnSuccess'] = false;
                    $transaction['options']['addBillingAddressToPaymentMethod'] = false;
                    $this->view->show_billing_and_card = false;
                }
                $url_segments = array(
                        'controller' => 'payment',
                        'action' => 'payment',
                        'pay-for' => $pay_for
                );
                if($pay_for !== 'additional-stores') {
                    $url_segments['id'] = $id;
                }
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
                    'payment/'.$pay_for.'.phtml',
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
                        case 81805:
                            $field_key = 'first_name';
                        break;
                        case 81806:
                            $field_key = 'last_name';
                        break;
                        case 81807:
                            $field_key = 'locality';
                        break;
                        case 81808:
                        case 81809:
                        case 81813:
                            $field_key = 'postal_code';
                        break;
                        case 81813:
                            $field_key = 'locality';
                        break;
                        case 81810:
                            $field_key = 'region';
                        break;
                        case 81811:
                        case 81812:
                            $field_key = 'street_address';
                        break;
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
                            $plan_change_calculation = $this->_calculatePlanChange($user, $plan);

                            // if new plan ends earlier than the old one, move payment day to today
                            $result = null;
                            $amount = $plan_change_calculation['amount'];
                            $new_plan_end = $plan_change_calculation['new_plan_end'];

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

                                    $adminNotification = new Integration_Mail_AdminNotification();
                                    $adminNotificationData = array('user' => $user, 'plan' => $plan, 'amount' => $amount);
                                    $adminNotification->setup('changedPlan', $adminNotificationData);
                                    $adminNotification->send();
                                } else {
                                    $this->_handleResponseErrors($result);
                                    $this->_setParam('pay-for', 'change-plan');
                                    $this->_showPaymentForm();
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
                            } catch(Exception $e) {
                                if($log = $this->getLog()) {
                                    $log->log('Braintree - admin notification email', Zend_Log::DEBUG, $e->getMessage());
                                }
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

    protected function _calculatePlanChange($user, $plan) {
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

        return array(
            'amount' => $amount,
            'billing_period' => $old_plan->getBillingPeriod(),
            'plan_name' => $old_plan->getName(),
            'used_days' => $subscription_used_days,
            'new_plan_end' => $new_plan_end
        );
    }
    
    /* Methods from payment manager */
    public function indexAction() {
        # display grid of all payments
        /* index action is only alias for list extensions action */
        $this->listAction();
    }

    public function listAction() {
        $planModel = new Application_Model_Payment();
        $paginator = $planModel->fetchList();
        $page = (int) $this->_getParam('page', 0);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->view->payments = $paginator;
        $this->render('list');
    }

    public function additionalStoresAction() {
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);
        if((int)$user->getPlanId()) {
            $plan = new Application_Model_Plan();
            $plan->find($user->getPlanId());
            $stores = new Application_Model_Store();
            $stores = $stores->getAllForUser($user->getId());
            $this->view->left_stores = (int)$plan->getStores()+(int)$plan->getMaxStores()-(int)$user->getAdditionalStores()-count($stores);
            $this->view->price = $plan->getStorePrice();
            if($this->view->left_stores > 0) {
                $this->render('additional-stores-quantity');
            }
        }
    }

    protected function _calculatePayment($plan_end, $plan_period, $price)
    {
        $data = array(
            'plan_end' => strtotime($plan_end),
        );
        $data['plan_start'] = strtotime('-'.$plan_period, $data['plan_end']);
        $data['plan_range'] = (($data['plan_end']-$data['plan_start'])/3600/24)+1;
        $data['plan_left_days'] = ceil(($data['plan_end']-strtotime(date('Y-m-d')))/3600/24)+1;
        $data['price'] = number_format($price*$data['plan_left_days']/$data['plan_range'], 2);
        return $data;
    }
}
