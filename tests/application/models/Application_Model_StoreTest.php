<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_StoreTest extends ModelTestCase
{

    protected $model;

    protected $_storeData = array(
        'edition' => 'EE',
        'status' => 'ready',
        'version_id' => '20',
        'user_id' => '276',
        'server_id' => '1',
        'domain' => 'rt34tsrgs',
        'store_name' => 'PHPUnit store test',
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
        'revision_count' => 5,
        'papertrail_syslog_hostname' => 'mage-testing1.papertrailapp.com',
        'papertrail_syslog_port' => '60305',
        'do_hourly_db_revert' => 0
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Store;
        $this->assertInstanceOf('Application_Model_Store', $this->model);

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
        $store = new Application_Model_Store();
        $store->setOptions($this->_storeData);

        try{
            $storeId = $store->save();
            $this->assertGreaterThan(0, (int)$storeId, 'Application_Model_Store::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Store::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $store = new Application_Model_Store();
        $store->setOptions($this->_storeData);
        $store->save();

        $store->setStoreName('New PHPUnit store name');
        try{
            $store->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Store::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_storeData;

        $store = new Application_Model_Store();
        $store->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($store);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$store->$method());
            }
        }
        unset($store);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_storeData;

        $store = new Application_Model_Store();
        $store->setOptions($data);

        $exportData = $store->__toArray();

        unset($exportData['id']);
        unset($exportData['backend_password'], $exportData['custom_pass']);

        $this->assertModelArray($data,$exportData);
        unset($store);
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $store = new Application_Model_Store();
        $store->setOptions($this->_storeData);
        $storeId = $store->save();

        $store->delete('`id` = '.$storeId);

        $find =  new Application_Model_Store();
        $find = $find->find($storeId);
        $this->assertNull($find->getId(),'Application_Model_Store::delete(\'`id` = '.$storeId.'\') failed.');
    }
}
