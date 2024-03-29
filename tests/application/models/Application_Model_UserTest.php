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
        $this->model = new Application_Model_User();
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

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_User', $this->model);
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

    public function testFetchList()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);
        $user->save();

        $_storeData = array(
            'edition' => 'EE',
            'status' => 'ready',
            'version_id' => '20',
            'user_id' => $user->getId(),
            'server_id' => '1',
            'domain' => 'rt34tsrgs',
            'store_name' => 'PHPUnit user fetchList test',
            'description' => NULL,
            'backend_name' => 'phpunit',
            'type' => 'clean',
            'custom_protocol' => NULL,
            'custom_host' => NULL,
            'custom_port' => NULL,
            'custom_remote_path' => NULL,
            'custom_file' => NULL,
            'sample_data' => 1,
            'custom_login' => NULL,
            'custom_sql' => NULL,
            'error_message' => NULL,
            'revision_count' => 1,
            'papertrail_syslog_hostname' => 'mage-testing1.papertrailapp.com',
            'papertrail_syslog_port' => '60305',
            'do_hourly_db_revert' => 0
        );

        $store = new Application_Model_Store();
        $store->setOptions($_storeData);
        $store->save();

        $userModel = new Application_Model_User();
        $userList = $userModel->fetchList();

        $this->assertGreaterThan(0,sizeof($userList),'Application_Model_User::fetchList() returned size 0');
        $this->assertInstanceOf('Zend_Paginator',$userList,'Application_Model_User::fetchList() is not Zend_Paginator instance.');

    }

    /**
     * @depends testSave
     */
    public function testFetchAll()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);
        $user->save();

        $userModel = new Application_Model_User();
        $users = $userModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($users),'Application_Model_User::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($users as $user){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_User', $user);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $user = new Application_Model_User();
        $user->setOptions($this->_userData);
        $user->save();

        $userId = $user->getId();

        $find =  new Application_Model_User();
        $find = $find->find($userId);
        $this->assertNotNull($find->getId(),'Application_Model_User::find('.$userId.') failed.');
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

    /**
     * @depends testSave
     */
    public function testActivateUser()
    {
        $user = new Application_Model_User();
        $this->_userData['status'] = 'inactive';

        $user->setOptions($this->_userData);
        $user->save();

        $userId = $user->getId();

        $stringToHash = $user->getLogin().$user->getEmail().$user->getAddedDate();
        $userHash = substr(sha1($stringToHash),0,20);

        $user->activateUser($userId,$userHash);
    }
}
