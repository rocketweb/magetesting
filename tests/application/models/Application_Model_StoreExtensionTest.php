<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_StoreExtensionTest extends ModelTestCase
{

    /*
     * TODO: Application_Model_StoreExtension::delte() doesn't exists
     * */
    protected $model;

    protected $_storeExtensionData = array(
        'store_id' => 0,
        'extension_id' => 0,
        'added_date' => '2014-01-01 00:00:00',
        'braintree_transaction_id' => NULL,
        'braintree_transaction_confirmed' => NULL,
        'reminder_sent' => 1,
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

    private function setStoreAndExtension()
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
        $extension = $extensionModel->findByFilters(array('edition' => 'CE'));
        if($extension == null)
        {
            $this->markTestIncomplete('No extensions found to test StoreExtension model');
            return false;
        }
        $this->_storeExtensionData['extension_id'] = $extension->getId();
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_StoreExtension', $this->model);
    }

    public function testSave()
    {
        $storeExtension = new Application_Model_StoreExtension();
        if($this->setStoreAndExtension() === false) return ;
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
        $storeExtension = new Application_Model_StoreExtension();
        if($this->setStoreAndExtension() === false) return ;
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtension->setAddedDate('2014-01-01 00:00:01');
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

    public function testFetchAll()
    {
        $storeExtension = new Application_Model_StoreExtension();
        if($this->setStoreAndExtension() === false) return ;
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtensionModel = new Application_Model_StoreExtension();
        $storeExtensions = $storeExtensionModel->fetchAll(true);

        $this->assertGreaterThan(0,sizeof($storeExtensions),'Application_Model_StoreExtension::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($storeExtensions as $storeExtension){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_StoreExtension', $storeExtension);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $storeExtension = new Application_Model_StoreExtension();
        if($this->setStoreAndExtension() === false) return ;
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtensionId = $storeExtension->getId();

        $find =  new Application_Model_StoreExtension();
        $find = $find->find($storeExtensionId);
        $this->assertNotNull($find->getId(),'Application_Model_StoreExtension::find('.$storeExtensionId.') failed.');
    }
    /**
     * depends testSave
     */
    /* delete() function doesn't exists in mapper
    public function testDelete()
    {
        $storeExtension = new Application_Model_StoreExtension();
        $this->setStoreAndExtension();
        $storeExtension->setOptions($this->_storeExtensionData);
        $storeExtension->save();

        $storeExtensionId = $storeExtension->getId();

        $storeExtension->delete($storeExtensionId);

        $find =  new Application_Model_StoreExtension();
        $find = $find->find($storeExtensionId);
        $this->assertNull($find->getId(),'Application_Model_StoreExtension::delete(\'`id` = '.$storeExtensionId.'\') failed.');
    }*/
}
