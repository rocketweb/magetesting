<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_StoreExtensionTest extends ModelTestCase
{

    protected $model;

    protected $_storeExtensionData = array(
        'store_id' => 0,
        'extension_id' => 0,
        'added_date' => '2014-01-01 00:00:00',
        'braintree_transaction_id' => NULL,
        'braintree_transaction_confirmed' => NULL,
        'reminder_sent' => true,
        'status' => 'ready'
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_StoreExtension();
        $this->assertInstanceOf('Application_Model_StoreExtension', $this->model);

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

    protected function setStoreAndExtension()
    {
        $storeModel = new Application_Model_Store();
        $stores = $storeModel->fetchAll();
        if(sizeOf($stores) == 0)
        {
            $this->markTestIncomplete('No stores found to test StoreExtension model');
            return false;
        }
        $store = $stores[array_rand($stores)];
        $this->_storeExtensionData['store_id'] = $store->getId();

        $extensionModel = new Application_Model_Extension();
        $extensions = $extensionModel->fetchAll();
        if(sizeOf($extensions) == 0)
        {
            $this->markTestIncomplete('No extensions found to test StoreExtension model');
            return false;
        }
        $extension = $extensions[array_rand($extensions)];
        $this->_storeExtensionData['extension_id'] = $extension->getId();
    }

    public function testSave()
    {
        if($this->setStoreAndExtension() === false) return;

        $storeExtension = new Application_Model_StoreExtension();
        $storeExtension->setOptions($this->_storeExtensionData);

        try{
            $storeExtension->save();
            $this->assertGreaterThan(0, (int)$storeExtension->getId(), 'Application_Model_StoreExtension::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_StoreExtension::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        if($this->setStoreAndExtension() === false) return;

        $storeExtension = new Application_Model_StoreExtension();
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtension->setAuthor('AuthorChange');
        try{
            $storeExtension->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_StoreExtension::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_storeExtensionData;

        $storeExtension = new Application_Model_StoreExtension();
        $storeExtension->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($storeExtension);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$storeExtension->$method());
            }
        }
        unset($storeExtension);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_storeExtensionData;

        $storeExtension = new Application_Model_StoreExtension();
        $storeExtension->setOptions($data);

        $exportData = $storeExtension->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($storeExtension);
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        if($this->setStoreAndExtension() === false) return;

        $storeExtension = new Application_Model_StoreExtension();
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtensionId = $storeExtension->getId();

        $storeExtension->delete($storeExtensionId);

        $find =  new Application_Model_StoreExtension();
        $find = $find->find($storeExtensionId);
        $this->assertNull($find->getId(),'Application_Model_StoreExtension::delete(\'`id` = '.$storeExtensionId.'\') failed.');
    }
}
