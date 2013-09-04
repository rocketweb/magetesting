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

    public function testRestart()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Service', $this->_kit);
        $this->assertEquals(
            "sudo service 'mysqld' restart 2>&1",
            $this->_kit->restart('mysqld')->toString()
        );
    }

    public function testReload()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Service', $this->_kit);
        $this->assertEquals(
            "sudo service 'mysqld' reload 2>&1",
            $this->_kit->reload('mysqld')->toString()
        );
    }
}