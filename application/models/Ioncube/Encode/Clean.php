<?php

class Application_Model_Ioncube_Encode_Clean
{
    protected $_config;
    protected $_storeDir;
    protected $_cli;
    protected $_log;

    public function setup($storeDir, $config, $log = null)
    {
        $this->_storeDir = trim($storeDir, '/');
        $this->_config = $config;

        if($log instanceof Zend_Log) {
            $this->_log = $log;
        }
        return $this;
    }

    protected function cli($kit = '')
    {
        if(!$this->_cli) {
            $this->_cli = new RocketWeb_Cli();

            if($this->_log) {
                $this->_cli->setLogger($log);
                $this->_cli->enableLogging(true);
            }
        }
        if($kit) {
            return $this->_cli->kit($kit);
        }
        return $this->_cli;
    }

    public function process($packSettings)
    {
        $this->_packDecodedEnterprise();
        $this->_encodeEnterprise($packSettings);
        $this->_unpackEncodedEnterprise();

        $this->cli()->call();
    }

    protected function _packDecodedEnterprise()
    {
        $this->cli()->append(
            $this->cli('tar')->pack('-', $this->_storeDir.'/app/code/core/Enterprise')->isCompressed()
        );
    }
    protected function _encodeEnterprise(array $packSettings)
    {
        $ssh = $this->cli('ssh');
        $ioncube = $this->_config->ioncubeEncoder;

        $ssh->connect(
            $ioncube->user,
            $ioncube->pass,
            $ioncube->host,
            $ioncube->port
        );

        $ssh->remoteCall('put here proper code or create new kit');

        $this->cli()->pipe(
            $ssh
        );
    }
    protected function _unpackEncodedEnterprise()
    {
        $this->cli()->pipe(
            $this->cli('tar')->unpack('-', $this->_storeDir.'/app/code/core/Enterprise')
        );
    }
}