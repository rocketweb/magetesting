<?php

class RocketWeg_Cli_Kit_Compression_TarTest extends PHPUnit_Framework_TestCase
{
    public function testSshConnection()
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');
        $ssh->connect('user', 'pass', 'http://somewhere.com', 80);
        $ssh->asSuperUser(true);

        $this->assertInstanceOf('RocketWeb_Cli_Kit_Ssh', $ssh);
        $this->assertEquals(
            "sudo sshpass -p 'pass' ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no 'user'@'http://somewhere.com' -p '80' 2>&1",
            $ssh->toString()
        );
    }

    public function testSshRemoteCall()
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');

        $this->assertEquals(
            "'echo '\''test'\'' 2>&1' 2>&1",
            $ssh->remoteCall($cli->createQuery('echo ?', 'test'))->toString()
        );
    }


    
    /* expectOutputRegex('regex') */
    /* assertContainsOnlyInstancesOf(string $classname, Traversable|array $haystack[, string $message = '']) */
    /* assertInstanceOf($expected, $actual[, $message = '']) */
    /* assertRegExp(string $pattern, string $string[, string $message = '']) */
}