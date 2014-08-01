<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_ExtensionTest extends ModelTestCase
{

    protected $model;
    
    protected $_extensionData = array(
        'name' =>'PHPUnit extension',
        'description' => 'Description',
        'category_id' => "4",
        'author' => 'PHPUnit',
        'version' => '0.0.0.1',
        'logo' => NULL,
        'extension' => 'phpunit_extension-0.0.0.1.tgz',
        'extension_encoded' => NULL,
        'extension_key' => 'phpunit_extension',
        'from_version' => '1.4.0.0',
        'to_version' => NULL,
        'edition' => 'CE',
        'is_visible' => "1",
        'price' => '0.00',
        'sort' => '0',
        'extension_detail' => NULL,
        'extension_documentation' => NULL

    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Extension();
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
        $this->assertInstanceOf('Application_Model_Extension', $this->model);
    }
    public function testSave()
    {
        $extension = new Application_Model_Extension();
        $extension->setOptions($this->_extensionData);

       try{
            $extension->save();
            $this->assertGreaterThan(0, (int)$extension->getId(), 'Application_Model_Extension::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Extension::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $extension = new Application_Model_Extension();
        $extension->setOptions($this->_extensionData);
        $extension->save();

        $extension->setAuthor('AuthorChange');
        try{
            $extension->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Extension::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_extensionData;

        $extension = new Application_Model_Extension();
        $extension->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($extension);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$extension->$method());
            }
        }
        unset($extension);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_extensionData;

        $extension = new Application_Model_Extension();
        $extension->setOptions($data);

        $exportData = $extension->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($extension);
    }

    public function testFetchAll()
    {
        $extensionModel = new Application_Model_Extension();
        $extensions = $extensionModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($extensions),'Application_Model_Extension::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($extensions as $extension){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Extension', $extension['item']);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $extension = new Application_Model_Extension();
        $extension->setOptions($this->_extensionData);
        $extension->save();

        $extensionId = $extension->getId();

        $find =  new Application_Model_Extension();
        $find = $find->find($extensionId);
        $this->assertNotNull($find->getId(),'Application_Model_Extension::find('.$extensionId.') failed.');
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $extension = new Application_Model_Extension();
        $extension->setOptions($this->_extensionData);
        $extension->save();

        $extensionId = $extension->getId();

        $extension->delete($extensionId);

        $find =  new Application_Model_Extension();
        $find = $find->find($extensionId);
        $this->assertNull($find->getId(),'Application_Model_Extension::delete(\'`id` = '.$extensionId.'\') failed.');
    }
}
