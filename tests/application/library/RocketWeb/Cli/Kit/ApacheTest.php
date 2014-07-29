<?php

class RocketWeg_Cli_Kit_ApacheTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('apache');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Apache', $this->_kit);
    }

    public function testEnableSite()
    {
        $this->assertEquals(
            "/usr/sbin/a2ensite 'site_name' 2>&1",
            $this->_kit->_prepareCall($this->_kit->enableSite('site_name'))
        );
    }

    public function testDisableSite()
    {
        $this->assertEquals(
            "/usr/sbin/a2dissite 'site_name' 2>&1",
            $this->_kit->_prepareCall($this->_kit->disableSite('site_name'))
        );
    }
}