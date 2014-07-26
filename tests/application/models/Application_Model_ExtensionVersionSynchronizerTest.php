<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_ExtensionVersionSynchronizerTest extends ModelTestCase
{

    protected $model;




    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_ExtensionVersionSynchronizer();
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
        $this->assertInstanceOf('Application_Model_ExtensionVersionSynchronizer', $this->model);
    }

    public function testCheckVersionNotExisting()
    {
        $this->assertEquals(Application_Model_ExtensionVersionSynchronizer::EXTENSION_DOES_NOT_EXIST, $this->model->checkVersion('nonExistingExtensionKey','9.9.9.9'));
    }

    public function testCheckVersion()
    {
        $this->assertNotEquals(Application_Model_ExtensionVersionSynchronizer::EXTENSION_DOES_NOT_EXIST, $this->model->checkVersion('Lib_Google_Checkout','1.8.1.0'));
    }
}
