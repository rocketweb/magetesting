<?php
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';
require_once 'Braintree/Braintree.php';

require_once 'Zend/Db.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Test/PHPUnit/Db/Connection.php';
require_once 'Zend/Test/PHPUnit/Db/SimpleTester.php';
/**
 * Abstract Class for Test Controllers
 * Define setUp method
 */
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    public $bootstrap = null;
    protected $_db;
    protected $traceError = true;

    protected $_userData = array(
        'login' => 'standard-user',
        'password' => 'standard-user',
        'email' => 'no-replay@rocketweb.com',
        'firstname' => 'Standard',
        'lastname' => 'User',
        'status' => 'active',
        'group' => 'commercial-user',
        'plan_id' => 3,
        'additional_stores' => 10,
        'plan_active_to' => '2064-07-09 08:12:53'

    );

    public function setUp()
    {   
        //$this->setupDatabase();
        $this->bootstrap = new Zend_Application(
            'testing',
            APPLICATION_PATH . '/configs/application.ini'
        );

        parent::setUp();
        $this->_db = $this->bootstrap->getBootstrap()->getResource('db');
        $this->_db->beginTransaction();

        $this->_userData['login'] = 'standard-u'.mt_rand(10,99);
        $this->_userData['password'] = 'standard-p'.mt_rand(10,99);
    }


    public function createFakeUser()
    {
        $userData = $this->_userData;

        $user = new Application_Model_User();
        $user->setOptions($userData);
        $user->save();
        $this->_userData['id'] = $user->getId();

    }

    public function loginUser($user, $password)
    {
        $this->request
             ->setMethod('POST')
             ->setPost(array(
                'login'    => $user,
                'password' => $password,
            )
        );
        $this->dispatch('/user/login');


        $this->assertRedirectTo('/user/dashboard');
        $this->getResponse()->clearBody();
        
        $this->resetRequest()->resetResponse();

        $this->request->setMethod('GET')->setPost(array());
        
        $layout = Zend_Controller_Action_HelperBroker::getStaticHelper('layout');
        $layout->enableLayout();

        $user = new Application_Model_User();
        $user = $user->find($this->_userData['id']);
        $user->setPlanId(3);
        $user->save();

        Zend_Auth::getInstance()->getStorage()->write((object) $user->__toArray());
    }


    protected function tearDown()
    {

        if($this->_db != null){
            $this->_db->rollback();
            $this->_db->closeConnection();
        }
        parent::tearDown();
    }
//    public function setupDatabase()
//    {
//        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'testing');
//        
//        $db = Zend_Db::factory('PDO_MYSQL', $config->resources->db->params);
//        $connection = new Zend_Test_PHPUnit_Db_Connection($db, 'magetesting');
//        $databaseTester = new Zend_Test_PHPUnit_Db_SimpleTester($connection);
//
//        $databaseFixture =
//            new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(
//                dirname(__FILE__) . '/_files/initialUserFixture.xml'
//        );
//
//        $databaseTester->setupDatabase($databaseFixture);
//    }
}
