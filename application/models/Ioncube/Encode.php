<?php

abstract class Application_Model_Ioncube_Encode
{
    protected $_config;

    protected $_cli;
    protected $_log;
    protected $_ssh;
    protected $_scp;

    protected $_remoteCodingTmpPath;

    abstract public function process();

    protected function cli($kit = '')
    {
        if(!$this->_cli) {
            $this->_cli = new RocketWeb_Cli();

            if($this->_log) {
                $this->_cli->setLogger($this->_log);
                $this->_cli->enableLogging(true);
            }
        }
        if($kit) {
            return $this->_cli->kit($kit);
        }
        return $this->_cli;
    }

    protected function _call(RocketWeb_Cli_Query $query, $exceptionMessage = '')
    {
        // @todo remove before commit
        //Zend_Debug::dump((string) $query);

        if(0 !== (int) $query->call()->getLastStatus()) {
            //Zend_Debug::dump($this->_cli->getLastStatus());
            //Zend_Debug::dump($this->_cli->getLastOutput());
            
            throw new Application_Model_Ioncube_Exception((string) $exceptionMessage);
        }
    }

    protected function _createRemoteTmpDir($dir = '')
    {
        $file = $this->cli('file');
        $query = $file->create(
            $this->_remoteCodingTmpPath.'/' . $dir,
            $file::TYPE_DIR
        );

        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Creating tmp encoding dir on remote server failed.'
        );
    }
}
