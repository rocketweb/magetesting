<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_PaymentTest extends ModelTestCase
{
    /*
     * TODO: Application_Model_Payment::save() must return id/object on call.
     * TODO: Application_Model_Payment::delete() doesn't exists
     * */

    protected $model;

    protected $_paymentData = array(
        'price' => '99.99',
        'first_name' => 'PHPUnit first name',
        'last_name' => 'PHPUnit last name',
        'street' => 'PHPUnit street',
        'postal_code' => '',
        'city' => 'PHPUnit city',
        'state' => 'PHPUnit state',
        'country' => 'PHPUnit country',
        'date' => '2014-01-01 00:00:00',
        'transaction_name' => 'Sandbox Exploration',
        'transaction_type' => 'subscription',
        'user_id' => 0,
        'braintree_transaction_id' => 'qwertz12'
    );

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Payment();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unset($this->model);
        parent::tearDown();
    }

    private function setUser()
    {
        $userData = array(
            'login' => 'standard-user',
            'password' => 'standard-user',
            'email' => 'no-replay@rocketweb.com',
            'firstname' => 'Standard',
            'lastname' => 'User',
            'status' => 'active',
            'group' => 'commercial-user',
            'plan_id' => 1,
            'plan_active_to' => '2064-07-09 08:12:53'
        );

        $user = new Application_Model_User();
        $user->setOptions($userData);
        $user->save();
        $this->_paymentData['user_id'] = $user->getId();
    }

    private function savedObject(Application_Model_Payment $payment)
    {
        $allPayments = $payment->fetchAll();
        $lastPayment = null;
        foreach($allPayments as $m){
            if($lastPayment == null) $lastPayment = $m;
            if($m->getId() > $lastPayment->getId()) $lastPayment = $m;
        }

        return $lastPayment;
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_Payment', $this->model);
    }
    
    public function testSave()
    {
        $payment = new Application_Model_Payment();
        $this->setUser();
        $payment->setOptions($this->_paymentData);

        try{
            $payment->save();
            $payment = $this->savedObject($payment);
            $this->assertGreaterThan(0, (int)$payment->getId(), 'Application_Model_Payment::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Payment::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $payment = new Application_Model_Payment();
        $this->setUser();
        $payment->setOptions($this->_paymentData);
        $payment->save();
        $payment = $this->savedObject($payment);

        $payment->setCity('PHPUnit changed city');
        try{
            $payment->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Payment::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_paymentData;

        $payment = new Application_Model_Payment();
        $payment->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($payment);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$payment->$method(),
                    'Option returned value is wrong.'."\n".
                    'Method: '.$method."\n".
                    'Expected: '.$value."\n".
                    'Received: '.$payment->$method()."\n"
                );
            }
        }
        unset($payment);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_paymentData;

        $payment = new Application_Model_Payment();
        $payment->setOptions($data);

        $exportData = $payment->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($payment);
    }

    public function testFetchAll()
    {
        $paymentModel = new Application_Model_Payment();
        $payments = $paymentModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($payments),'Application_Model_Payment::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($payments as $payment){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Payment', $payment);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $payment = new Application_Model_Payment();
        $this->setUser();
        $payment->setOptions($this->_paymentData);
        $payment->save();
        $payment = $this->savedObject($payment);

        $paymentId = $payment->getId();

        $find =  new Application_Model_Payment();
        $find = $find->find($paymentId);
        $this->assertNotNull($find->getId(),'Application_Model_Payment::find('.$paymentId.') failed.');
    }

    /**
     * depends testSave
     */
    /*Function not in use!
     *
     * public function testDelete()
    {
        $payment = new Application_Model_Payment();
        $this->setUser();
        $payment->setOptions($this->_paymentData);
        $payment->save();
        $payment = $this->savedObject($payment);

        $paymentId = $payment->getId();

        $payment->delete($paymentId);

        $find =  new Application_Model_Payment();
        $find = $find->find($paymentId);
        $this->assertNull($find->getId(),'Application_Model_Payment::delete(\'`id` = '.$paymentId.'\') failed.');
    }*/
}
