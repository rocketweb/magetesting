<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_EditionTest extends ModelTestCase
{

    protected $model;

    protected $_editionData = array(
        'key' => 'GO',
        'name' => 'PHPUnit test'
    );



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Edition();
        $this->assertInstanceOf('Application_Model_Edition', $this->model);

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

    private function savedObject(Application_Model_Edition $edition){
        $allEditions = $edition->fetchAll();
        $lastEdition = null;
        foreach($allEditions as $m){
            if($lastEdition == null) $lastEdition = $m;
            if($m->getId() > $lastEdition->getId()) $lastEdition = $m;
        }

        return $lastEdition;
    }

    public function testSave()
    {
        $edition = new Application_Model_Edition();
        $edition->setOptions($this->_editionData);

        try{
            $edition->save();
            $edition = $this->savedObject($edition);
            $this->assertGreaterThan(0, (int)$edition->getId(), 'Application_Model_Edition::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Edition::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $edition = new Application_Model_Edition();
        $edition->setOptions($this->_editionData);
        $edition->save();
        $edition = $this->savedObject($edition);

        $edition->setName('PHPUnit test change');
        try{
            $edition->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Edition::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_editionData;

        $edition = new Application_Model_Edition();
        $edition->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($edition);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$edition->$method());
            }
        }
        unset($edition);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_editionData;

        $edition = new Application_Model_Edition();
        $edition->setOptions($data);

        $exportData = $edition->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($edition);
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $edition = new Application_Model_Edition();
        $edition->setOptions($this->_editionData);
        $edition->save();
        $edition = $this->savedObject($edition);

        $editionId = $edition->getId();

        $edition->delete($editionId);

        $find =  new Application_Model_Edition();
        $find = $find->find($editionId);
        $this->assertNull($find->getId(),'Application_Model_Edition::delete(\'`id` = '.$editionId.'\') failed.');
    }
}
