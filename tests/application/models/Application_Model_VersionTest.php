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

    private function savedObject(Application_Model_Version $version){
        $allVersions = $version->fetchAll();
        $lastVersion = null;
        foreach($allVersions as $m){
            if($lastVersion == null) $lastVersion = $m;
            if($m->getId() > $lastVersion->getId()) $lastVersion = $m;
        }

        return $lastVersion;
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_Version', $this->model);
    }
    
    public function testSave()
    {
        $version = new Application_Model_Version();
        $version->setOptions($this->_versionData);

        try{
            $version->save();
            $version = $this->savedObject($version);
            $this->assertGreaterThan(0, (int)$version->getId(), 'Application_Model_Version::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Version::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $version = new Application_Model_Version();
        $version->setOptions($this->_versionData);
        $version->save();
        $version = $this->savedObject($version);

        $version->setVersion('CE');
        try{
            $version->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Version::save(): '.$e->getMessage());
        }
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

    public function testFetchAll()
    {
        $version = new Application_Model_Version();
        $version->setOptions($this->_versionData);
        $version->save();
        $version = $this->savedObject($version);

        $versionModel = new Application_Model_Version();
        $versions = $versionModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($versions),'Application_Model_Version::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($versions as $version){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Version', $version);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $version = new Application_Model_Version();
        $version->setOptions($this->_versionData);
        $version->save();
        $version = $this->savedObject($version);

        $versionId = $version->getId();

        $find =  new Application_Model_Version();
        $find = $find->find($versionId);
        $this->assertNotNull($find->getId(),'Application_Model_Version::find('.$versionId.') failed.');
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $version = new Application_Model_Version();
        $version->setOptions($this->_versionData);
        $version->save();
        $version = $this->savedObject($version);

        $versionId = $version->getId();

        $version->delete('`id` = '.$versionId);

        $find =  new Application_Model_Version();
        $find = $find->find($versionId);
        $this->assertNull($find->getId(),'Application_Model_Version::delete(\'`id` = '.$versionId.'\') failed.');
    }
}
