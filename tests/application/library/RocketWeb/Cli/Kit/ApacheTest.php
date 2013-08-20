<?php

class RocketWeg_Cli_Kit_ApacheTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('apache')->asSuperUser(true);
    }

    public function tearDown()
    {
        unset($this->_kit);
    }
    

    public function testEnableSite()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Apache', $this->_kit);
        $this->assertEquals(
            "sudo a2ensite 'site_name' 2>&1",
            $this->_kit->enableSite('site_name')->toString()
        );
    }

    public function testDisableSite()
    {
        $this->assertEquals(
            "sudo a2dissite 'site_name' 2>&1",
            $this->_kit->disableSite('site_name')->toString()
        );
    }
}