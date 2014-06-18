<?php

class RocketWeg_Cli_Kit_Compression_GzipTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('gzip');
    }

    public function tearDown()
    {
        unset($this->_kit);
    }

    public function testUnpack()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Compression_gzip', $this->_kit);
        $this->assertEquals(
            "gunzip 'file.gz' 2>&1",
            $this->_kit->_prepareCall($this->_kit->unpack('file.gz'))
        );
    }

    public function testGetPackedFilename()
    {
        $this->assertEquals(
            "gunzip -l 'file.gz' 2>&1 | awk '{ if($3 ~ /%$/) {print $4} }' 2>&1",
            $this->_kit->_prepareCall($this->_kit->getPackedFilename('file.gz'))
        );
    }

    public function testGzipedTest()
    {
        $this->assertEquals(
            "gunzip -tv 'file.gz' 2>&1 | awk '{ if($1 == \"file.gz:\") {print $2} }' 2>&1",
            $this->_kit->_prepareCall($this->_kit->test('file.gz'))
        );
    }
}