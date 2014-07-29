<?php

class RocketWeg_Cli_Kit_ServiceTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('service');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Service', $this->_kit);
    }

    public function testRestart()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Service', $this->_kit);
        $this->assertEquals(
            "/usr/bin/service 'mysqld' restart 2>&1",
            $this->_kit->_prepareCall($this->_kit->restart('mysqld'))
        );
    }

    public function testReload()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Service', $this->_kit);
        $this->assertEquals(
            "/usr/bin/service 'mysqld' reload 2>&1",
            $this->_kit->_prepareCall($this->_kit->reload('mysqld'))
        );
    }
}