<?php

class RocketWeg_Cli_Kit_MysqlTest extends PHPUnit_Framework_TestCase
{
    protected $_connection;
    protected $_user = 'user';
    protected $_pass = 'pass';
    protected $_database = 'database';

    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $kit = $cli->kit('mysql');
        $kit->connect($this->_user, $this->_pass, $this->_database);
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Mysql', $kit);
        $this->_connection = $kit;
    }

    public function testMysqlConnection()
    {
        $this->assertEquals(
            $this->_getConnectionQuery().' 2>&1',
            $this->_connection->_prepareCall($this->_connection)
        );
    }

    /**
     * @depends testMysqlConnection
     */
    public function testImport()
    {
        $this->assertEquals(
            $this->_getConnectionQuery()." < 'file.sql' 2>&1",
            $this->_connection->_prepareCall($this->_connection->cloneObject()->import('file.sql'))
        );
    }

    /**
     * @depends testMysqlConnection
     */
    public function testExport()
    {
        $class = get_class($this->_connection);
        $query = $this->_connection->cloneObject()->export(
            'file.sql',
            $class::EXPORT_DATA_AND_SCHEMA,
            array('table1')
        )->toString();
        $this->assertEquals(
            str_replace('mysql', 'mysqldump', $this->_getConnectionQuery())
                ." 'table1' > 'file.sql' 2>&1",
            $this->_connection->_prepareCall($query)
        );
    }

    protected function _getConnectionQuery()
    {
        return "mysql -u '{$this->_user}' -p'{$this->_pass}' '{$this->_database}'";
    }

    public function tearDown()
    {
        unset($this->_connection);
    }
}