<?php
//require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
//require_once 'Braintree/Braintree.php';

require_once 'Zend/Db.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Test/PHPUnit/Db/Connection.php';
require_once 'Zend/Test/PHPUnit/Db/SimpleTester.php';
/**
 * Abstract Class for Test Controllers
 * Define setUp method
 */
abstract class ModelTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    public $bootstrap = null;
    protected $_db;
    protected $traceError = true;

    protected function setUp()
    {
        $this->bootstrap = new Zend_Application(
            'testing',
            APPLICATION_PATH . '/configs/application.ini'
        );

        parent::setUp();
        $this->_db = $this->bootstrap->getBootstrap()->getResource('db');
        $this->_db->beginTransaction();
    }


    protected function tearDown()
    {
        if($this->_db != null) $this->_db->rollback();
        parent::tearDown();
    }


    protected function assertModelArray($expected = array(), $given = array())
    {
        $this->assertGreaterThan(0,count(array_diff_assoc($expected, $given)),
            'Model array size missmatched.'."\n".
            'Expected: '.sizeOf($expected).''."\n".
            'Received: '.sizeOf($given)
        );

        foreach($expected as $key => $value) {
            $this->assertTrue(isset($given[$key]),
                'Model array key not found in given array.'."\n".
                'Expected: '.$key.''."\n".
                'Received: NULL'
            );
            $this->assertEquals($value,$given[$key]);
            unset($given[$key]);
        }
        $this->assertEquals(0,sizeOf($given),
            'Model array keys not found in expected array'."\n".
            'Expected: NULL'."\n".
            'Received: '.implode(", ", array_keys($given))
        );
    }
}
