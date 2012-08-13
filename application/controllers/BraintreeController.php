<?php


class BraintreeController extends Integration_Controller_Action
{
    
    public function init(){
        require_once 'Braintree.php';
        Braintree_Configuration::environment('sandbox');
        Braintree_Configuration::merchantId('gy64jt4tk79ry5sf');
        Braintree_Configuration::publicKey('xwkcvn9jvrmzv68k');
        Braintree_Configuration::privateKey('f33vs7ytx4zd62xd');
    }
    
    public function paymentAction(){
        
        $this->init();
        
        $plan = $this->getRequest()->getParam('plan','standard'); 
        if ($plan == 'standard' ){
            $amount = 5;
        } elseif ($plan == 'business' ){
            $amount = 10;
        } else {
            return $this->_helper->redirector->gotoRoute(
                array('controller'=>'braintree','action'=>'subscribe','plan'=>$plan), 'default', false
            );
        }        
        
        $user = new Application_Model_User();
        $user->find($this->auth->getIdentity()->id);
	
	if ($user->getBraintreeVaultId() != 0){
            return $this->_helper->redirector->gotoRoute(
                array('controller'=>'braintree','action'=>'subscribe','plan'=>$plan), 'default', false
            );
	} else {
        
            /* this is for storing customer in valut*/
            $trData = Braintree_TransparentRedirect::createCustomerData(
                array('redirectUrl' => $this->view->serverUrl().$this->view->url(array('controller'=>'braintree','action'=>'result','plan'=>$plan)))
            );
        }
        
        $this->view->trData = $trData;
        $this->view->user = $user;
        
    }
    

    public function resultAction(){
        
        $this->init();
        
        /*this is for creating customer*/
        $queryString = $_SERVER['QUERY_STRING'];
        $plan = $this->getRequest()->getParam('plan','standard'); 
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
        
        $plan = $this->getRequest()->getParam('plan','standard'); 
        if ($plan =='standard'){
            $planId = 'srzg';
            $internalPlanId = 1;
        } elseif ($plan =='business'){
            $planId = 'ctn6';
            $internalPlanId = 2;
        }
        
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
                $message = "Subscription status ".$result->subscription->status;
                $user->setPlanId($internalPlanId);
                $user->setBraintreeSubscriptionId($result->subscription->id);
                $user->save();
            }
            else {
                $message = $result->errors->deepAll();
            }
        } else {
            $message = 'The customer cannot be found in our subscription payments vault';
        }
        
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
                    $this->view->messages[] = array('type' => 'success', 'message' => "Subscription status ".$result->subscription->status);                
                } else {
                    $message = $result->errors->deepAll();
                    foreach ($message as $m){
                        $this->_helper->flashMessenger(array('type' => 'error', 'message' => "Subscription status ".$m->message));
                    }
                }
            
            } catch (Exception $e){
                //TODO: check if this works beyond sandbox, in sandbox it throws 404
                $this->_helper->flashMessenger(array('type' => 'error', 'message' => "There was a problem while cancelling the subscription, please contact us with your details"));
            }
            
            
            
            return $this->_helper->redirector->goToRoute(array('controller'=>'my-account','action'=>'compare'), 'default', true);
    }
}