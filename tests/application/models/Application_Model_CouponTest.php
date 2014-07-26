<?php
require_once realpath(dirname(__FILE__) . '/../../ModelTestCase.php');

class Application_Model_CouponTest extends ModelTestCase
{

    protected $model;

    protected $_couponData = array(
        'code' => 'xyz12345',
        'used_date' => NULL,
        'user_id' => NULL,
        'plan_id' => 2,
        'duration' => '1 month',
        'active_to' => '2015-01-01 00:00:00',
        'extension_key' => NULL
    );


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new Application_Model_Coupon();
        $this->assertInstanceOf('Application_Model_Coupon', $this->model);

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

    public function testSave()
    {
        $coupon = new Application_Model_Coupon();
        $coupon->setOptions($this->_couponData);

        try{
            $coupon->save();
            $this->assertGreaterThan(0, (int)$coupon->getId(), 'Application_Model_Coupon::save() failed. ID not set after trying to save!');
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to save model Application_Model_Coupon::save(): '.$e->getMessage());
        }
    }

    /**
     * @depends testSave
     */
    public function testUpdate()
    {
        $coupon = new Application_Model_Coupon();
        $coupon->setOptions($this->_couponData);
        $coupon->save();

        $coupon->setCode('1234xyz');
        try{
            $coupon->save();
        }catch(DatabseException $e){
            $this->markTestIncomplete('Database error when trying to update model Application_Model_Coupon::save(): '.$e->getMessage());
        }
    }


    public function testSetOptions()
    {
        $data = $this->_couponData;

        $coupon = new Application_Model_Coupon();
        $coupon->setOptions($data);

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($coupon);

        foreach($data as $key => $value){
            $method = 'get' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->assertEquals($value,$coupon->$method());
            }
        }
        unset($coupon);
    }

    /**
     * @depends testSetOptions
     */
    public function testToArray()
    {
        $data = $this->_couponData;

        $coupon = new Application_Model_Coupon();
        $coupon->setOptions($data);

        $exportData = $coupon->__toArray();

        unset($exportData['id']);

        $this->assertModelArray($data,$exportData);
        unset($coupon);
    }

    /**
     * @depends testSave
     */
    public function testDelete()
    {
        $coupon = new Application_Model_Coupon();
        $coupon->setOptions($this->_couponData);
        $coupon->save();

        $couponId = $coupon->getId();

        $coupon->delete($couponId);

        $find =  new Application_Model_Coupon();
        $find = $find->find($couponId);
        $this->assertNull($find->getId(),'Application_Model_Coupon::delete(\'`id` = '.$couponId.'\') failed.');
    }
}
