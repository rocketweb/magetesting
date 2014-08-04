<?php

class RocketWeg_Cli_Kit_FileTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('file');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_File', $this->_kit);
    }

    public function testMove()
    {
        $this->assertEquals(
            "mv 'asd.txt' 'test.txt' 2>&1",
            $this->_kit->_prepareCall($this->_kit->move('asd.txt', 'test.txt'))
        );
    }

    public function testCreate()
    {
        $this->assertEquals(
            "touch 'file.txt' 2>&1",
            $this->_kit->_prepareCall($this->_kit->create('file.txt', RocketWeb_Cli_Kit_File::TYPE_FILE))
        );
    }

    public function testFileMode()
    {
        $this->assertEquals(
            "chmod -R '777' 'dir/' 2>&1",
            $this->_kit->_prepareCall($this->_kit->fileMode('dir/', 777, true))
        );
    }

    public function testFileOwner()
    {
        $this->assertEquals(
            "chown 'test:test' 'file.txt' 2>&1",
            $this->_kit->_prepareCall($this->_kit->fileOwner('file.txt', 'test:test', false))
        );
    }

    public function testListAll()
    {
        $this->assertEquals(
            "ls -al 'dir/' 2>&1",
            $this->_kit->_prepareCall($this->_kit->listAll('dir/'))
        );
    }

    public function testCopy()
    {
        $this->assertEquals(
            "cp 'test.txt' 'test 2.txt' 2>&1",
            $this->_kit->_prepareCall($this->_kit->copy('test.txt', 'test 2.txt', false))
        );
    }

    public function testRemove()
    {
        $this->assertEquals(
            "rm -rf 'path' 2>&1",
            $this->_kit->_prepareCall($this->_kit->remove('path'))
        );
    }

    public function testFindAndPrints()
    {
        $class = $this->_kit;
        $this->assertEquals(
            "find -type d -name 'some_dir' -print0 -printf \"`pwd`/%h\\n\" 2>&1",
            $this->_kit->_prepareCall($this->_kit->find('some_dir', $class::TYPE_DIR)->printFiles(true)->printPaths(true))
        );
    }

    public function testGetSize()
    {
        $this->assertEquals(
            "du -b 'custom_file' 2>&1",
            $this->_kit->_prepareCall($this->_kit->getSize('custom_file'))
        );
    }
}