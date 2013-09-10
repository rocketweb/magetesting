<?php

abstract class Application_Model_Ioncube_Encode
{
    protected $_config;
    protected $_store;
    protected $_storeDir;
    protected $_cli;
    protected $_log;

    protected $_remoteCodingTmpPath;
    protected $_ssh;
    protected $_scp;

    public function setup($store, $config, $log = null)
    {
        $this->_store = $store;
        $this->_config = $config;

        if($log instanceof Zend_Log) {
            $this->_log = $log;
        }


        $ioncube = $this->_config->ioncubeEncoder;
        $this->_remoteCodingTmpPath= $ioncube->codingTmpPath . '/' . $this->_store->getDomain();

        $this->_scp = $this->cli('scp');
        $this->_scp->connect(
            $ioncube->user,
            $ioncube->pass,
            $ioncube->host,
            $ioncube->port
        );
        $this->_ssh = $this->cli('ssh');
        $this->_ssh->connect(
            $ioncube->user,
            $ioncube->pass,
            $ioncube->host,
            $ioncube->port
        );

        return $this;
    }

    protected function _getStoreDir()
    {
        if(!$this->_storeDir) {
            $config = $this->_config->magento;
            $user = new Application_Model_User();
            $user->find($this->_store->getUserId());
            $pathParts = array(
                $config->systemHomeFolder,
                $config->userprefix . $user->getLogin(),
                'public_html',
                $this->_store->getDomain()
            );
            $this->_storeDir = implode('/',
                $pathParts
            );
        }
        return $this->_storeDir;
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

    abstract public function process() {}
}