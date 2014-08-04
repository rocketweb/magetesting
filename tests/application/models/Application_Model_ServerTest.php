<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_ServerTest extends ModelTestCase
{

    protected $model;

    protected $_serverData = array(
        'name' => 'PHPUnit server',
        'description' => 'PHPUnit test server',
        'domain' => 'phpunit.dev.magetesting.com',
        'ip' => '0.0.0.1'
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Server();
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
        $this->assertInstanceOf('Application_Model_Server', $this->model);
    }

    public function testSave()
    {
        $server = new Application_Model_Server();
        $server->setOptions($this->_serverData);

        try{
            $server->save();
            $this->assertGreaterThan(0, (int)$server->getId(), 'Application_Model_Server::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Server::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $server = new Application_Model_Server();
        $server->setOptions($this->_serverData);
        $server->save();

        $server->setIp('1.0.0.0');
        try{
            $server->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Server::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_serverData;

        $server = new Application_Model_Server();
        $server->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($server);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$server->$method());
            }
        }
        unset($server);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_serverData;

        $server = new Application_Model_Server();
        $server->setOptions($data);

        $exportData = $server->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($server);
    }

    public function testFetchAll()
    {
        $serverModel = new Application_Model_Server();
        $servers = $serverModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($servers),'Application_Model_Server::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($servers as $server){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Server', $server);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $server = new Application_Model_Server();
        $server->setOptions($this->_serverData);
        $server->save();

        $serverId = $server->getId();

        $find =  new Application_Model_Server();
        $find = $find->find($serverId);
        $this->assertNotNull($find->getId(),'Application_Model_Server::find('.$serverId.') failed.');
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $server = new Application_Model_Server();
        $server->setOptions($this->_serverData);
        $server->save();

        $serverId = $server->getId();

        $server->delete($serverId);

        $find =  new Application_Model_Server();
        $find = $find->find($serverId);
        $this->assertNull($find->getId(),'Application_Model_Server::delete(\'`id` = '.$serverId.'\') failed.');
    }
}
