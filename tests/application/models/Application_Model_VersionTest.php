<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_VersionTest extends ModelTestCase
{

    protected $model;

    protected $_versionData = array(
        'edition' => 'EE',
        'version' => '1.99.0',
        'sample_data_version' => '1.99.1'
    );


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Version();
        $this->assertInstanceOf('Application_Model_Version', $this->model);

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

    public function testSetOptions()
    {
        $data = $this->_versionData;

        $version = new Application_Model_Version();
        $version->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($version);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$version->$method());
            }
        }
        unset($version);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_versionData;

        $version = new Application_Model_Version();
        $version->setOptions($data);

        $exportData = $version->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($version);
    }

}
