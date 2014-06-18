<?php

class RocketWeg_Cli_Kit_GitTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('git');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testInit()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Git', $this->_kit);
        $this->assertEquals(
            "git init 2>&1",
            $this->_kit->_prepareCall($this->_kit->init())
        );
    }

    public function testAddAll()
    {
        $this->assertEquals(
            "git add -A 2>&1",
            $this->_kit->_prepareCall($this->_kit->addAll())
        );
    }

    public function testCommit()
    {
        $this->assertEquals(
            "git commit -m 'test message' 2>&1",
            $this->_kit->_prepareCall($this->_kit->commit('test message'))
        );
    }

    public function testDeploy()
    {
        $this->assertEquals(
            "git archive --format zip --output 'var/deployment/revision_hash.zip' 'revision_hash' `git diff 'revision_hash' 'revision_hash'~1 --name-only` 2>&1",
            $this->_kit->_prepareCall($this->_kit->deploy('revision_hash', 'var/deployment/revision_hash.zip'))
        );
    }

    public function testRollback()
    {
        $this->assertEquals(
            "git revert 'revision_hash' --no-edit 2>&1",
            $this->_kit->_prepareCall($this->_kit->rollback('revision_hash'))
        );
    }
}