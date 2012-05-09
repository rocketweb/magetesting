<?php

/**
 * Hendles responses from paypal
 * @author golaod
 * @package PayPalController
 * @method cancelAction
 * @method successAction
 * @method notifyAction
 */
class PayPalController extends Integration_Controller_Action
{
    /**
     * called when user canceled billing agreement
     * @method cancelAction
     */
    public function cancelAction()
    {
        $this->_helper->flashMessenger(array('type' => 'notice', 'message' => 'Your subscription sign up has been canceled.'));
        return $this->_helper->redirector->goToRoute(
                array(
                    'controller' => 'my-account',
                    'action'     => 'compare'
                ),
                'default',
                true
        );
    }

    /**
     * called when user agreed subscribtion
     * @method successAction
     */
    public function successAction()
    {
        // log response from success event
        $this->getInvokeArg('bootstrap')->getResource('log')->log('PayPal - Success', Zend_Log::DEBUG, json_encode($_POST));
        
        $this->_helper->flashMessenger(array('type' => 'success', 'message' => 'You have been successfully signed up to the subscription.'));
        $this->_helper->flashMessenger(array('from_scratch' => true, 'type' => 'notify', 'message' => '<strong>Remember!</strong> You have to wait (max 24h) for confirmation.'));
        return $this->_helper->redirector->goToRoute(
                array(
                        'controller' => 'my-account',
                        'action'     => 'compare'
                ),
                'default',
                true
        );
    }

    /**
     * paypal notifies goes here
     * @method notifyAction
     */
    public function notifyAction()
    {
        // log response for notification from paypal
        $log = $this->getInvokeArg('bootstrap')->getResource('log');
        $log->log('PayPal - Notify', Zend_Log::DEBUG, json_encode($_POST));
        if(isset($_POST['custom']) AND isset($_POST['subscr_id']) AND isset($_POST['txn_type'])) {
            // find out if custom param has our recognition data
            list($user_id, $plan_id) = explode(':', $_POST['custom']);
            $user = new Application_Model_User();
            $plan = new Application_Model_Plan();
            // find rows for that recognition data
            $user->find($user_id); $plan->find($plan_id);
            // and break on failure
            if(!$plan->getId() OR !$user->getId()) {
                break;
            }
            $this->db->beginTransaction();
            // check what kind of notice sends to us paypal 
            switch($_POST['txn_type']) {
                // client paid
                case 'subscr_payment':
                    if(isset($_POST['payment_gross'])) {
                        if($plan->getPrice() == $_POST['payment_gross']) {

                            $activeTo = $user->getPlanActiveTo();
                            if(!$activeTo OR $_POST['subscr_id'] != $user->getSubscrId()) {
                                $activeTo = $_POST['payment_date'];
                            }
                            $activeTo = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($activeTo)));

                            $user->setPlanActiveTo($activeTo);
                            
                            $payment = new Application_Model_Payment();
                            $payment_data = array(
                                    'city' => $_POST['address_city'],
                                    'country' => $_POST['address_country'],
                                    'state' => $_POST['address_state'],
                                    'street' => $_POST['address_street'],
                                    'postal_code' => $_POST['address_zip'],
                                    'first_name' => $_POST['first_name'],
                                    'last_name' => $_POST['last_name'],
                                    'price' => $_POST['payment_gross'],
                                    'date' => date('Y-m-d H:i:s', strtotime($_POST['payment_date'])),
                                    'plan_id' => $plan_id,
                                    'user_id' => $user_id,
                                    'subscr_id' => $_POST['subscr_id']
                            );
                            $payment->setOptions($payment_data);
                            $payment->save();
                        }
                    }
                break;
                // client signed
                case 'subscr_signup':
                    if(isset($_POST['mc_amount3'])) {
                        if($plan->getPrice() == $_POST['mc_amount3']) {
                            $subscrDate = strtotime('+1 month', strtotime($_POST['subscr_date']));
 
                            $date = date('Y-m-d H:i:s', $subscrDate);
                            $user->setPlanActiveTo($date);
                        }
                    }
                break;
                default:
                    $log->log('PayPal - Notify - use case', Zend_Log::DEBUG, 'wrong switch ???' );
                break;
            }

            // set subscription id for future usage
            $user->setSubscrId($_POST['subscr_id']);

            // set plan id for user
            $user->setPlanId($plan_id);

            // check if user has still active account
            if((int)strtotime($user->getPlanActiveTo()) - time() >= 0) {
                $user->setGroup('commercial-user');
            } else {
                $user->setGroup('awaiting-user');
            }

            $user->save();
            $this->db->commit();
        } else {
            $log->log('PayPal - Notify - use case', Zend_Log::DEBUG, 'Wrong paypal data - maybe hack attempt.'.json_encode($_POST) );
        }
        return $this->_helper->redirector->goToRoute(
                array(
                    'controller' => 'index',
                    'action'     => 'index'
                ),
                'default',
                true
        );
    }
}