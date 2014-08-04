<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_LogReindexTest extends ModelTestCase
{

    protected $model;

    protected $_logReindexData = array(
        'store_id' => 977,
        'time' => '2014-01-01 00:00:00'
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_LogReindex();
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

    protected function setStore()
    {
        $storeModel = new Application_Model_Store();
        $stores = $storeModel->fetchAll();
        if(sizeOf($stores) == 0)
        {
            $this->markTestIncomplete('No stores found to test LogReindexer model');
            return false;
        }
        $store = $stores[array_rand($stores)];
        $this->_logReindexData['store_id'] = $store->getId();
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_LogReindex', $this->model);
    }

    public function testSave()
    {
        if($this->setStore() === false) return;

        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($this->_logReindexData);

        try{
            $logReindex->save();
            $this->assertGreaterThan(0, (int)$logReindex->getId(), 'Application_Model_LogReindex::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_LogReindex::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        if($this->setStore() === false) return;

        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($this->_logReindexData);
        $logReindex->save();

        $logReindex->setTime('2014-01-01 00:00:01');
        try{
            $logReindex->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_LogReindex::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_logReindexData;

        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($logReindex);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$logReindex->$method());
            }
        }
        unset($logReindex);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_logReindexData;

        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($data);

        $exportData = $logReindex->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($logReindex);
    }

    public function testFetchAll()
    {
        $logReindexModel = new Application_Model_LogReindex();
        $logReindexs = $logReindexModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($logReindexs),'Application_Model_LogReindex::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($logReindexs as $logReindex){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;
            $this->assertInstanceOf('Application_Model_LogReindex', $logReindex);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        if($this->setStore() === false) return;

        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($this->_logReindexData);
        $logReindex->save();

        $logReindexId = $logReindex->getId();

        $find =  new Application_Model_LogReindex();
        $find = $find->find($logReindexId);
        $this->assertNotNull($find->getId(),'Application_Model_LogReindex::find('.$logReindexId.') failed.');
    }

    /**
     * depends testSave
     */
    /*public function testDelete()
    {
        $logReindex = new Application_Model_LogReindex();
        $logReindex->setOptions($this->_logReindexData);
        $logReindex->save();

        $logReindexId = $logReindex->getId();

        $logReindex->delete('`id` = '.$logReindexId);

        $find =  new Application_Model_LogReindex();
        $find = $find->find($logReindexId);
        $this->assertNull($find->getId(),'Application_Model_LogReindex::delete(\'`id` = '.$logReindexId.'\') failed.');
    }*/
}
