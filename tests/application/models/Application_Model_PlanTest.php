<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_PlanTest extends ModelTestCase
{

    protected $model;

    protected $_planData = array(
        'name' => 'PHPUnit plan',
        'stores' => 10,
        'price' => '0.00',
        'price_description' => 'one time per store',
        'billing_period' => '10 days',
        'billing_description' => '10 day pass',
        'ftp_access' => 0,
        'phpmyadmin_access' => 0,
        'can_add_custom_store' => 1,
        'is_hidden' => 0,
        'auto_renew' => 0,
        'can_do_db_revert' => 0,
        'max_stores' => 10,
        'store_price' => 20
    );
  



    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Plan();
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
        $this->assertInstanceOf('Application_Model_Plan', $this->model);
    }

    public function testSave()
    {
        $plan = new Application_Model_Plan();
        $plan->setOptions($this->_planData);

        try{
            $plan->save();
            $this->assertGreaterThan(0, (int)$plan->getId(), 'Application_Model_Plan::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Plan::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */

    public function testUpdate()
    {
        $plan = new Application_Model_Plan();
        $plan->setOptions($this->_planData);
        $plan->save();

        $plan->setName('NameChange');
        try{
            $plan->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Plan::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_planData;

        $plan = new Application_Model_Plan();
        $plan->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($plan);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$plan->$method());
            }
        }
        unset($plan);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_planData;

        $plan = new Application_Model_Plan();
        $plan->setOptions($data);

        $exportData = $plan->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($plan);
    }

    public function testFetchAll()
    {
        $planModel = new Application_Model_Plan();
        $plans = $planModel->fetchAll();

        $this->assertGreaterThan(0,sizeof($plans),'Application_Model_Plan::fetchAll() failed. Returned size is 0');

        $counter = 0;
        foreach($plans as $plan){
            if($counter > $this->_fetchAllBreaker) break;
            $counter++;

            $this->assertInstanceOf('Application_Model_Plan', $plan);
        }
    }

    /**
     * @depends testSave
     */
    public function testFind()
    {
        $plan = new Application_Model_Plan();
        $plan->setOptions($this->_planData);
        $plan->save();

        $planId = $plan->getId();

        $find =  new Application_Model_Plan();
        $find = $find->find($planId);
        $this->assertNotNull($find->getId(),'Application_Model_Plan::find('.$planId.') failed.');
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $plan = new Application_Model_Plan();
        $plan->setOptions($this->_planData);
        $plan->save();

        $planId = $plan->getId();

        $plan->delete($planId);

        $find =  new Application_Model_Plan();
        $find = $find->find($planId);
        $this->assertNull($find->getId(),'Application_Model_Plan::delete(\'`id` = '.$planId.'\') failed.');
    }
}
