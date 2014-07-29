<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_ExtensionCategoryTest extends ModelTestCase
{
    /*
     * TODO: Application_Model_DbTable_ExtensionCategory::delete() method is missing.
     */


    protected $model;
    
    protected $_extensionCategoryData = array(
        'name' =>'PHPUnit extension',
        'class' => 'other',
        'logo' => 'other.jpg'
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_ExtensionCategory();
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
        $this->assertInstanceOf('Application_Model_ExtensionCategory', $this->model);
    }

    public function testSave()
    {
        $extension = new Application_Model_ExtensionCategory();
        $extension->setOptions($this->_extensionCategoryData);

       try{
            $extension->save();
            $this->assertGreaterThan(0, (int)$extension->getId(), 'Application_Model_ExtensionCategory::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_ExtensionCategory::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $extension = new Application_Model_ExtensionCategory();
        $extension->setOptions($this->_extensionCategoryData);
        $extension->save();

        $extension->setLogo('promotion.jpg');
        try{
            $extension->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_ExtensionCategory::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_extensionCategoryData;

        $extension = new Application_Model_ExtensionCategory();
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

    public function testFetchAll()
    {
        $extensionCategoryModel = new Application_Model_ExtensionCategory();
        $extensionCategories = $extensionCategoryModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($extensionCategories),'Application_Model_ExtensionCategory::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($extensionCategories as $extensionCateogry){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_ExtensionCategory', $extensionCateogry);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $extensionCategory = new Application_Model_ExtensionCategory();
        $extensionCategory->setOptions($this->_extensionCategoryData);
        $extensionCategory->save();

        $extensionCategoryId = $extensionCategory->getId();

        $find =  new Application_Model_ExtensionCategory();
        $find = $find->find($extensionCategoryId);
        $this->assertNotNull($find->getId(),'Application_Model_ExtensionCategory::find('.$extensionCategoryId.') failed.');
    }
    
    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_extensionCategoryData;

        $extension = new Application_Model_ExtensionCategory();
        $extension->setOptions($data);

        $exportData = $extension->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($extension);
    }
}
