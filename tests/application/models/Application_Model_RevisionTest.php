<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_RevisionTest extends ModelTestCase
{

    protected $model;

    protected $_revisionData = array(
        'store_id' => 0,
        'user_id' => 0,
        'extension_id' => NULL,
        'type' => 'Magento-init',
        'comment' => 'PHPUnit test',
        'hash' => 'phpunit',
        'filename' => '',
        'db_before_revision' => 'db_backup_2014_01_01_00_00_00.tgz'
    );

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Revision();
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

    private function setStoreAndUser()
    {
        $storeModel = new Application_Model_Store();
        $stores = $storeModel->fetchAll();
        if(sizeOf($stores) == 0)
        {
            $this->markTestIncomplete('No stores found to test Revision model');
            return false;
        }
        $store = $stores[array_rand($stores)];
        $this->_revisionData['store_id'] = $store->getId();

        $userData = array(
            'login' => 'standard-user',
            'password' => 'standard-user',
            'email' => 'no-replay@rocketweb.com',
            'firstname' => 'Standard',
            'lastname' => 'User',
            'status' => 'active',
            'group' => 'commercial-user',
            'plan_id' => 1,
            'plan_active_to' => '2064-07-09 08:12:53'
        );

        $user = new Application_Model_User();
        $user->setOptions($userData);
        $user->save();
        $this->_revisionData['user_id'] = $user->getId();
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('Application_Model_Revision', $this->model);
    }

    public function testSave()
    {
        $revision = new Application_Model_Revision();
        if($this->setStoreAndUser() === false) return ;
        $revision->setOptions($this->_revisionData);

        try{
            $revision->save();
            $this->assertGreaterThan(0, (int)$revision->getId(), 'Application_Model_Revision::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Revision::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $revision = new Application_Model_Revision();
        if($this->setStoreAndUser() === false) return ;
        $revision->setOptions($this->_revisionData);
        $revision->save();

        $revision->setComment('PHPUnit comment changed');
        try{
            $revision->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Revision::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_revisionData;

        $revision = new Application_Model_Revision();
        $revision->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($revision);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$revision->$method());
            }
        }
        unset($revision);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_revisionData;

        $revision = new Application_Model_Revision();
        $revision->setOptions($data);

        $exportData = $revision->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($revision);
    }

    public function testFetchAll()
    {
        $revision = new Application_Model_Revision();
        if($this->setStoreAndUser() === false) return ;
        $revision->setOptions($this->_revisionData);
        $revision->save();

        $revisionModel = new Application_Model_Revision();
        $revisions = $revisionModel->fetchAll(true);

        $this->assertGreaterThan(0,sizeof($revisions),'Application_Model_Revision::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($revisions as $revision){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Revision', $revision);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $revision = new Application_Model_Revision();
        if($this->setStoreAndUser() === false) return ;
        $revision->setOptions($this->_revisionData);
        $revision->save();

        $revisionId = $revision->getId();

        $find =  new Application_Model_Revision();
        $find = $find->find($revisionId);
        $this->assertNotNull($find->getId(),'Application_Model_Revision::find('.$revisionId.') failed.');
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $revision = new Application_Model_Revision();
        if($this->setStoreAndUser() === false) return ;
        $revision->setOptions($this->_revisionData);
        $revision->save();

        $revisionId = $revision->getId();

        $revision->delete($revisionId);

        $find =  new Application_Model_Revision();
        $find = $find->find($revisionId);
        $this->assertNull($find->getId(),'Application_Model_Revision::delete(\'`id` = '.$revisionId.'\') failed.');
    }
}
