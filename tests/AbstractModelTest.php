<?php
//require_once 'Zend/Test/PHPUnit/DatabaseTestCase.php';
//
//abstract class AbstractModelTest extends Zend_Test_PHPUnit_DatabaseTestCase
//{
//    /**
//     * Database connection
//     * @var Zend_Test_PHPUnit_Db_Connection
//     */
//    protected $_db;
//
//    /**
//     * Instance of the model to be tested, instantiated by this class
//     * @var object
//     */
//    protected $_model;
//
//    /** 
//     * Name of the model class to be tested, intended to be overridden by 
//     * subclasses for use by this class when instantiating the model
//     * @var string
//     */
//    protected $_modelClass;
//
//    /** 
//     * Path to the directory for data set fixture files
//     * @var string
//     */
//    protected $_filesDir;
//
//    /** 
//     * Initializes the model.
//     * @return void
//     */
//    public function setUp()
//    {   
//        $this->_filesDir = dirname(__FILE__) . '/_files/' . $this->_modelClass;
//        $this->_model = new $this->_modelClass($this->getAdapter());
//        parent::setUp();
//    }
//
//    /**
//     * Implements PHPUnit_Extensions_Database_TestCase::getConnection().
//     * @return Zend_Test_PHPUnit_Db_Connection
//     */
//    protected function getConnection()
//    {
//        if (empty($this->_db)) {
//            $app = new Zend_Application(APPLICATION_ENV, APPLICATION_CONFIG);
//            $app->bootstrap();
//            $options = $app->getOptions();
//            $schema = $options['resources']['db']['params']['dbname'];
//            $db = $app->getBootstrap()->getPluginResource('db')->getDbAdapter();
//            $this->_db = $this->createZendDbConnection($db, $schema);
//        }
//        return $this->_db;
//    }
//
//    /**
//     * Implements PHPUnit_Extensions_Database_TestCase::getDataSet().
//     * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
//     */
//    protected function getDataSet()
//    {
//        return $this->createXmlDataSet(dirname(__FILE__) . '/_files/seed.xml');
//    }
//}