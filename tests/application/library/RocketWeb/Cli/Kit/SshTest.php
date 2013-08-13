<?php

class RocketWeg_Cli_Kit_SshTest extends PHPUnit_Framework_TestCase
{
    public function _sshConnectionData()
    {
        return array(
            array('user', 'pass', 'http://somewhere.com', 80),
            array('user1', 'pa\'ss', 'http://somewhere.com', 29722)
        );
    }
    /**
     * @dataProvider _sshConnectionData
     * @expectException PHPUnit_Framework_Error
     */
    public function testSshConnection($user, $pass, $host, $port)
    {
        $cli = new RocketWeb_Cli();
        $ssh = $cli->kit('ssh');
        $ssh->connect($user, $pass, $host, $port);
        $ssh->asSuperUser(true);

        $this->assertInstanceOf('RocketWeb_Cli_Kit_Ssh', $ssh);
        $this->assertRegExp('/ssh(pass)?.*?(\''.$user.'\')@.*/i', $ssh->toString());

        $this->_SshRemoteCall($ssh);
        $this->_asSuperUser($ssh);
    }

    public function _SshRemoteCall(RocketWeb_Cli_Kit_Ssh $connection)
    {
        $call = $connection->cloneObject()->remoteCall('something');
        $this->assertRegExp('/something/i', $call->toString());
    }

    public function _asSuperUser(RocketWeb_Cli_Kit_Ssh $connection)
    {
        $this->assertEquals('sudo', explode(' ', $connection->toString())[0]);
    }

    
    /* expectOutputRegex('regex') */
    /* assertContainsOnlyInstancesOf(string $classname, Traversable|array $haystack[, string $message = '']) */
    /* assertInstanceOf($expected, $actual[, $message = '']) */
    /* assertRegExp(string $pattern, string $string[, string $message = '']) */
}