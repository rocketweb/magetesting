<?php

class RocketWeg_Cli_Kit_SshTest extends PHPUnit_Framework_TestCase
{
    protected $_kit;
    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('ssh');
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Ssh', $this->_kit);
    }

    public function testSshConnection()
    {
        $this->_kit->connect('user', 'pass', 'http://somewhere.com', 80);

        $this->assertEquals(
            "sshpass -p 'pass' ssh -t -t -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no 'user'@'http://somewhere.com' -p '80' 2>&1",
            $this->_kit->_prepareCall($this->_kit)
        );
    }

    public function testSshRemoteCall()
    {
        $cli = new RocketWeb_Cli();
        $this->_kit = $cli->kit('ssh');

        $this->assertEquals(
            "'echo '\''test'\''' 2>&1",
            $this->_kit->_prepareCall($this->_kit->remoteCall($cli->createQuery('echo ?', 'test')))
        );
    }

    public function testPipePackUnpack()
    {
        $cli = new RocketWeb_Cli();
        $tar = $cli->kit('tar');

        $components = 3;
        $customRemotePath = 'remote/path';

        $pack = $tar->newQuery('cd /;');
        $pack->pack('-', ltrim($customRemotePath,'/'), false)->isCompressed(true);
        $pack->exclude(array($customRemotePath.'var', $customRemotePath.'media'));

        $unpack = $tar->unpack('-', '.')->isCompressed()->strip($components);

        $command = $this->_kit->cloneObject()->bindAssoc('-t -t', '', false)->remoteCall($pack, true)->pipe($unpack);
//        ->bindAssoc('2>&1', '', false);

        $this->assertEquals(
            "'cd /; tar zcf - '\''remote/path'\'' --exclude='\''remote/pathvar'\'' --exclude='\''remote/pathmedia'\''' 2>/dev/null | tar zxvof - -C '.' --delay-directory-restore --strip-components='3' 2>&1",
            $this->_kit->_prepareCall($command)
        );
    }
    
    /* expectOutputRegex('regex') */
    /* assertContainsOnlyInstancesOf(string $classname, Traversable|array $haystack[, string $message = '']) */
    /* assertInstanceOf($expected, $actual[, $message = '']) */
    /* assertRegExp(string $pattern, string $string[, string $message = '']) */
}