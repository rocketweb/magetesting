<?php

class RocketWeg_Cli_Kit_Compression_TarTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('tar');
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Compression_Tar', $this->_kit);
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testPack()
    {
        $this->assertEquals(
            "tar zcvf 'file.tar.gz' 'dir/to/pack' 2>&1",
            $this->_kit->_prepareCall($this->_kit->pack('file.tar.gz', 'dir/to/pack')->isCompressed(true))
        );
    }

    public function testPackWithChangeDir()
    {
        $this->assertEquals(
            "tar zcvf 'file.tar.gz' -C 'dir/to/pack' '.' 2>&1",
            $this->_kit->_prepareCall($this->_kit->pack('file.tar.gz', 'dir/to/pack', true, true)->isCompressed(true))
        );
    }

    public function testUnpack()
    {
        $this->assertEquals(
            "tar xvof 'file.tar.gz' -C 'dir/to/unpack' --delay-directory-restore 2>&1",
            $this->_kit->_prepareCall($this->_kit->unpack('file.tar.gz', 'dir/to/unpack'))
        );
    }

    public function testStripComponents()
    {
        $this->assertEquals(
            "--strip-components='5' 2>&1",
            $this->_kit->_prepareCall($this->_kit->clear()->strip(5))
        );
    }

    public function testCompressionTest()
    {
        $this->assertEquals(
            "tar tf 'file.tar.gz' 2>&1",
            $this->_kit->_prepareCall($this->_kit->clear()->test('file.tar.gz'))
        );
    }
}