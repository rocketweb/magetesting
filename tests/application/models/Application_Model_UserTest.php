<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_UserTest extends ModelTestCase
{

    protected $model;
    private $_userData = array(
        'login'             => 'model-user',
        'password'          => 'model-user',
        'email'             => 'no-replay@rocketweb.com',
        'firstname'         => 'Model',
        'lastname'          => 'User',
        'status'            => 'active',
        'group'             => 'free-user'
    );
    private $_userExtraData = array(
        'street'            => 'Street',
        'postal_code'       => 'Postal code',
        'city'              => 'City',
        'state'             => 'State',
        'country'           => 'Country',
        'added_date'        => '2010-01-01 00:00:00',
        'status'            => 'inactive',
        'plan_id'           => 0,
        'plan_active_to'    => '2010-01-01 00:00:00',
        'has_system_account' => true,
        'system_account_name' => 'mt_model',
        'downgraded'        => 0,
        'server_id'         => 1,
        'braintree_vault_id' => 0,
        'braintree_transaction_id' => 0,
        'braintree_transaction_confirmed' => false,
        'plan_raised_to_date' => 0,
        'plan_id_before_raising' => '2010-01-01 00:00:00',
        'has_papertrail_account' => 0,
        'papertrail_api_token' => '',
        'preselected_plan_id' => 0,
        'apikey' => 'apikey',
        'active_from' => '2010-01-01 00:00:00',
        'active_from_reminded' => '2010-01-01 00:00:00',
        'additional_stores' => 2,
        'additional_stores_removed' => 0
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_User;
        $this->assertInstanceOf('Application_Model_User', $this->model);

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

    public function testSave()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);

        try{
            $user->save();
            $this->assertGreaterThan(0, (int)$user->getId(), 'Application_Model_User::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_User::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);
        $user->save();

        $user->setFirstname('ModelChange');
        try{
            $user->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_User::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = array_merge($this->_userData,$this->_userExtraData);

        $user = new Application_Model_User();
        $user->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($user);

        //unset password, because it is removed in backend
        unset($data['password']);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$user->$method());
            }
        }
        unset($user);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = array_merge($this->_userData,$this->_userExtraData);

        $user = new Application_Model_User();
        $user->setOptions($data);

        $exportData = $user->__toArray();

        unset($exportData['id']);
        unset($data['password']);

        $this->assertModelArray($data,$exportData);
        unset($user);
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);
        $user->save();

        $userId = $user->getId();

        $user->delete('`id` = '.$userId);

        $find =  new Application_Model_User();
        $find = $find->find($userId);
        $this->assertNull($find->getId(),'Application_Model_User::delete(\'`id` = '.$userId.'\') failed.');
    }
}
