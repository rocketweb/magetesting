<?php

class RocketWeg_Cli_Kit_UserTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('user');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    protected function _scriptPath()
    {
        return APPLICATION_PATH."/../scripts/worker";
    }

    public function testInit()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_User', $this->_kit);
        $this->assertEquals(
            "sudo '".$this->_scriptPath()."/create_user.sh' 'login' 'pass' 'salt_hash' '/home/login_dir' 2>&1",
            $this->_kit->create('login', 'pass', 'salt_hash', '/home/login_dir')->toString()
        );
    }

    public function testDelete()
    {
        $this->assertEquals(
                "sudo '".$this->_scriptPath()."/remove_user.sh' 'login' 2>&1",
                $this->_kit->delete('login')->toString()
        );
    }

    public function testAddFtp()
    {
        $this->assertEquals(
                "sudo '".$this->_scriptPath()."/ftp-user-add.sh' 'login' 2>&1",
                $this->_kit->addFtp('login')->toString()
        );
    }

    public function testDeleteFtp()
    {
        $this->assertEquals(
                "sudo '".$this->_scriptPath()."/ftp-user-remove.sh' 'login' 2>&1",
                $this->_kit->removeFtp('login')->toString()
        );
    }

    public function testRebuildPhpMyAdmin()
    {
        $this->assertEquals(
                "sudo '".$this->_scriptPath()."/phpmyadmin-user-rebuild.sh' 'whole denied list of users' 2>&1",
                $this->_kit->rebuildPhpMyAdmin('whole denied list of users')->toString()
        );
    }
}