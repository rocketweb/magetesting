<?php

class RocketWeg_Cli_Kit_WgetTest extends PHPUnit_Framework_TestCase
{
    protected $_connection;
    protected $_user = 'user';
    protected $_pass = 'pass';
    protected $_host= 'ftp://test.host.com';
    protected $_port= '80';
    protected $_remote_path = 'some/dir_with_files';

    public function setUp()
    {
        $cli = new RocketWeb_Cli();
        $kit = $cli->kit('wget');
        $kit->ftpConnect($this->_user, $this->_pass, $this->_host, $this->_port);
        $this->assertInstanceOf('RocketWeb_Cli_Kit_Wget', $kit);
        $this->_connection = $kit;
    }

    public function testFtpWgetConnection()
    {
        $this->assertEquals(
            $this->_getConnectionQuery().' --spider 2>&1',
            $this->_connection->_prepareCall($this->_connection->cloneObject()->setRootPath($this->_remote_path))
        );
    }

    public function testLimits()
    {
        $this->assertEquals(
            $this->_getConnectionQuery().' --spider --timeout=\'30\' --tries=\'2\' 2>&1',
            $this->_connection->_prepareCall($this->_connection->cloneObject()->setRootPath($this->_remote_path)->addLimits(30,2))
        );
    }

    public function testDownloadRecursive()
    {
        $this->assertEquals(
            $this->_getConnectionQuery().' -nH -Q300m -m -np -R \'sql,tar,gz,zip,rar\' -N -X \'.htaccess\' -I \'one,two\' 2>&1',
            $this->_connection->_prepareCall($this->_connection->cloneObject()->setRootPath($this->_remote_path)->downloadRecursive(array('one', 'two')))
        );
    }

    public function testDownloadFile()
    {
        $this->assertEquals(
            str_replace('some/dir_with_files', 'some/dir_with_files/test.txt', $this->_getConnectionQuery()).' -N 2>&1',
            $this->_connection->_prepareCall($this->_connection->cloneObject()->downloadFile('some/dir_with_files/test.txt'))
        );
    }

    public function testFileSize()
    {
        $this->assertEquals(
            str_replace('some/dir_with_files', 'some/dir_with_files/test.txt', $this->_getConnectionQuery()).' --spider 2>&1 | grep \'SIZE\' 2>&1 | awk \'$5 ~ /[0-9]+/ {print $5}\' 2>&1',
            $this->_connection->_prepareCall($this->_connection->cloneObject()->setRootPath($this->_remote_path.'/test.txt')->getFileSize())
        );
    }

    protected function _getConnectionQuery()
    {
        return "wget --passive-ftp '{$this->_host}':'{$this->_port}''{$this->_remote_path}' --user='{$this->_user}' --password='{$this->_pass}'";
    }

    public function tearDown()
    {
        unset($this->_connection);
    }
}