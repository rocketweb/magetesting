<?php

class RocketWeg_Cli_Kit_PkillTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('pkill');

        $this->assertInstanceOf('RocketWeb_Cli_Kit_Pkill', $this->_kit);
    }

    public function testPkill()
    {
        $this->assertEquals(
            "pkill -u test_user pure-ftpd",
            $this->_kit->pkill('test_user')
        );
    }
}