<?php

class RocketWeg_Cli_Kit_ScpTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('scp');
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Scp', $this->_kit);
    }

    public function testScpDownload()
    {
        $this->assertEquals(
            "sshpass -p 'scpPassword' scp -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -P '22'  'scpUser'@'scpHost':/from/here /to/here 2>&1",
            $this->_kit->_prepareCall(
                $this->_kit->connect('scpUser','scpPassword','scpHost',22)->download('/from/here','/to/here')
            )
        );
    }

    /*
     * Recursive not used in code. Does NOT change the output (-r parameter missing)
     * public function testScpDownloadNotRecursive()
    {
        $this->assertEquals(
            "sshpass -p 'scpPassword' scp -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -P '22'  'scpUser'@'scpHost':/from/here /to/here 2>&1",
            $this->_kit->_prepareCall(
                $this->_kit->connect('scpUser','scpPassword','scpHost',22)->download('/from/here','/to/here')->recursive(false)
            )
        );
    }*/

    public function testScpUpload()
    {
        $this->assertEquals(
            "sshpass -p 'scpPassword' scp -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -P '22' /from/here 'scpUser'@'scpHost':/to/here  2>&1",
            $this->_kit->_prepareCall(
                $this->_kit->connect('scpUser','scpPassword','scpHost',22)->upload('/from/here','/to/here')
            )
        );
    }
}