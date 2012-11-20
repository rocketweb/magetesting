<?php


class BraintreeController extends Integration_Controller_Action
{
       
    public function init(){
        require_once 'Braintree.php';
        $config = $this->getInvokeArg('bootstrap')
                        ->getResource('config');
        
        Braintree_Configuration::environment($config->braintree->environment);
        Braintree_Configuration::merchantId($config->braintree->merchantId);
        Braintree_Configuration::publicKey($config->braintree->publicKey);
        Braintree_Configuration::privateKey($config->braintree->privateKey);
    }
    
    public function paymentAction(){
        
        $this->init();

        $plan = $this->getRequest()->getParam('plan', null);
        
        $modelPlan = new Application_Model_Plan();
        $modelPlan->findByBraintreeId($plan);
        
        if ($modelPlan != null) {

            /* what is amount used for ? */
            $amount = $modelPlan->getPrice();
            
        } else {
            return $this->_helper->redirector->gotoRoute(
                array(
                    'controller' => 'braintree', 
                    'action' => 'subscribe', 
                    'plan' => $plan
                ), 'default', false
            );
        }

        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);

        if ($user->getBraintreeVaultId() > 0) {
            return $this->_helper->redirector->gotoRoute(
                            array(
                        'controller' => 'braintree',
                        'action' => 'subscribe',
                        'plan' => $plan
                            ), 'default', false
            );
        } else {

            /* this is for storing customer in valut */
            $trData = Braintree_TransparentRedirect::createCustomerData(
                            array(
                                'redirectUrl' => $this->view->serverUrl() . $this->view->url(
                                        array(
                                            'controller' => 'braintree',
                                            'action' => 'result',
                                            'plan' => $plan
                                        )
                                )
                            )
            );
        }

        $this->view->trData = $trData;
        $this->view->user = $user;
        
    }
    

    public function resultAction(){
        
        $this->init();
        
        /*this is for creating customer*/
        $queryString = $_SERVER['QUERY_STRING'];
        $plan = $this->getRequest()->getParam('plan',null); 
        $result = Braintree_TransparentRedirect::confirm($queryString);
        if ($result->success) {
        
	    //update local database 
            $user = new Application_Model_User();
            $user->find($this->auth->getIdentity()->id);
            $user->setBraintreeVaultId($result->customer->id);
            $user->save();
        
            //$message = "Customer Created with email: ".$result->customer->email;
            return $this->_helper->redirector->gotoRoute(
                array('controller'=>'braintree','action'=>'subscribe','plan'=>$plan), 'default', false
            );
        }
        else {
            $message = $result->errors->deepAll();
        }

        $this->view->message = $message;

    }
       
    public function subscribeAction(){
        
        $this->init();
        
        $plan = $this->getRequest()->getParam('plan',null); 
                var_dump($plan);
        $modelPlan = new Application_Model_Plan();
        $modelPlan = $modelPlan->findByBraintreeId($plan);
               
        if ($plan == null || !$modelPlan){
             $this->_helper->flashMessenger(
                array(
                    'type' => 'error', 
                    'message' => 'Sorry, we do not support specified plan'
                )
            );    
            
            return $this->_helper->redirector->goToRoute(
                array(
                    'controller'=>'my-account',
                    'action'=>'compare'
                ), 
                'default', 
                true
            );
        }
        
        $planId = $plan; /* braintree plan id */
        $internalPlanId  = $modelPlan->getId();
        
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);
        if ($user->getBraintreeVaultId()>0){
            $customer = Braintree_Customer::find($user->getBraintreeVaultId());
            $paymentMethodToken = $customer->creditCards[0]->token;
            $result = Braintree_Subscription::create(array(
                'paymentMethodToken' => $paymentMethodToken,
                'planId' => $planId
            ));

            if ($result->success) {
                $user->setPlanId($internalPlanId);
                $user->setBraintreeSubscriptionId($result->subscription->id);
                
                $modelPlan = new Application_Model_Plan();
                $modelPlan->find($internalPlanId);
                $strtotime = strtotime('+'.$modelPlan->getBillingPeriod());
                $planActiveTo = date('Y-m-d H:i:s',$strtotime);
                $user->setPlanActiveTo( $planActiveTo );
                $user->save();
                
                $this->_helper->flashMessenger(
                        array(
                            'type' => 'success', 
                            'message' => 'You have been successfully subscribed to Magetesting'
                        )
                    );        
            }
            else {
                $message = $result->errors->deepAll();
                foreach($message as $m){
		  $this->_helper->flashMessenger(
		    array(
			'type' => 'error', 
			'message' => 'Subscription status: '. $m->messsage
		    )
		  ); 
                }
            }
        } else {
            $this->_helper->flashMessenger(
                array(
                    'type' => 'error', 
                    'message' => 'The customer cannot be found in our subscription payments vault'
                )
            );    
        }
        
        return $this->_helper->redirector->goToRoute(
                array(
                    'controller'=>'my-account',
                    'action'=>'compare'
                ), 
                'default', 
                true
            );
    }
    
    public function unsubscribeAction(){
            $user = new Application_Model_User();
            $user->find($this->auth->getIdentity()->id);
        
            try {
                $result = Braintree_Subscription::cancel($user->getBraintreeSubscriptionId());
                
                if ($result->success) {
                    $user->setPlanId(0);
                    $user->setBraintreeSubscriptionId(NULL);
                    $user->save();
                    $this->_helper->flashMessenger(
                        array(
                            'type' => 'success', 
                            'message' => 'You have been successfully unsubscribed from Magetesting'
                        )
                    );                
                } else {
                    $message = $result->errors->deepAll();
                    foreach ($message as $m){
                        $this->_helper->flashMessenger(
                            array(
                                'type' => 'error', 
                                'message' => "Subscription status ".$m->message
                            )
                        );
                    }
                }
            
            } catch (Exception $e){
                //TODO: check if this works beyond sandbox, in sandbox it throws 404
                $this->_helper->flashMessenger(
                    array(
                        'type' => 'error', 
                        'message' => "There was a problem while cancelling the subscription, please contact us with your details"
                    )
                );
            }
            
            return $this->_helper->redirector->goToRoute(
                array(
                    'controller'=>'my-account',
                    'action'=>'compare'
                ), 
                'default', 
                true
            );
    }
    
    public function webhookAction(){
        
        $log = $this->getInvokeArg('bootstrap')->getResource('log');
        
        
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        
        $verifyResult = Braintree_WebhookNotification::verify($this->getRequest()->getParam('bt_challenge'));
        
        
        //Use to test
        /*$sampleNotification = Braintree_WebhookTesting::sampleNotification(
            Braintree_WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE,
            '78r4yb'
        );

        $webhookNotification = Braintree_WebhookNotification::parse(
            $sampleNotification['signature'],
            $sampleNotification['payload']
        );*/
        
        $webhookNotification = Braintree_WebhookNotification::parse(
            $this->getRequest()->getParam('bt_signature_param'), 
            $this->getRequest()->getParam('bt_payload_param')
        );
        
        //not sure why but in sandbox mode, this is no go, works with sample notification though
        $log->log('Braintree - Notify', Zend_Log::DEBUG, json_encode($webhookNotification));
        
        $userModel = new Application_Model_User();
        
        $user  = $userModel->findByBraintreeSubscriptionId($webhookNotification->subscription->id);
        
        //TODO: behaviour still to be confirmed
        switch ($webhookNotification->kind){
            
            case Braintree_WebhookNotification::SUBSCRIPTION_CANCELED:
                $user->setPlanId(0);
                $user->setBraintreeSubscriptionId(null);
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY:
                $user->setGroup('commercial-user');
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY: 
                $user->setGroup('awaiting-user');
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_EXPIRED:
                $user->setGroup('awaiting-user');
                $user->setPlanId(0);
                $user->setBraintreeSubscriptionId(null);
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_TRIAL_ENDED:
                /* we dont have trials, but when we do, 
                 * send a email notification to user instead of unsetting 
                 * subscription data, maybe (s)he wants to pay?
                 */
                $user->setPlanId(0);
                $user->setBraintreeSubscriptionId(null);
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_WENT_ACTIVE:
                $user->setGroup('commercial-user');
            break;
        
            case Braintree_WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE;
                $user->setGroup('awaiting-user');
            break;
        }
        $user->save();
                
        echo $verifyResult;
    }
}
