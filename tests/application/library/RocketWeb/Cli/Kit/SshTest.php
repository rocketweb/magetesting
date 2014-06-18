<?php

class RocketWeg_Cli_Kit_SshTest extends PHPUnit_Framework_TestCase
{
    public function testSshConnection()
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');
        $ssh->connect('user', 'pass', 'http://somewhere.com', 80);

        $this->assertInstanceOf('RocketWeb_Cli_Kit_Ssh', $ssh);
        $this->assertEquals(
            "sshpass -p 'pass' ssh -t -t -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no 'user'@'http://somewhere.com' -p '80' 2>&1",
            $ssh->_prepareCall($ssh)
        );
    }

    public function testSshRemoteCall()
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');

        $this->assertEquals(
            "'echo '\''test'\''' 2>&1",
            $ssh->_prepareCall($ssh->remoteCall($cli->createQuery('echo ?', 'test')))
        );
    }

    public function testPipePackUnpack()
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');
        $tar = $cli->kit('tar');

        $components = 3;
        $customRemotePath = 'remote/path';

        $pack = $tar->newQuery('cd /;');
        $pack->pack('-', ltrim($customRemotePath,'/'), false)->isCompressed(true);
        $pack->exclude(array($customRemotePath.'var', $customRemotePath.'media'));

        $unpack = $tar->unpack('-', '.')->isCompressed()->strip($components);

        $command = $ssh->cloneObject()->bindAssoc('-t -t', '', false)->remoteCall($pack, true)->pipe($unpack);
//        ->bindAssoc('2>&1', '', false);

        $this->assertEquals(
            "'cd /; tar zcf - '\''remote/path'\'' --exclude='\''remote/pathvar'\'' --exclude='\''remote/pathmedia'\''' 2>/dev/null | tar zxvof - -C '.' --delay-directory-restore --strip-components='3' 2>&1",
            $ssh->_prepareCall($command)
        );
    }
    
    /* expectOutputRegex('regex') */
    /* assertContainsOnlyInstancesOf(string $classname, Traversable|array $haystack[, string $message = '']) */
    /* assertInstanceOf($expected, $actual[, $message = '']) */
    /* assertRegExp(string $pattern, string $string[, string $message = '']) */
}