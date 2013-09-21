<?php

abstract class Application_Model_Ioncube_Encode
{
    protected $_config;
    protected $_store;
    protected $_user;
    protected $_storeDir;
    protected $_cli;
    protected $_log;

    protected $_remoteCodingTmpPath;
    protected $_remoteEnterpisePackagePath;

    protected $_ssh;
    protected $_scp;

    public function setup($store, $config, $log = null)
    {
        $this->_store = $store;
        $this->_config = $config;

        $this->_user = new Application_Model_User();
        $this->_user->find($this->_store->getUserId());

        if($log instanceof Zend_Log) {
            $this->_log = $log;
        }


        $ioncube = $this->_config->ioncubeEncoder;
        $server = $ioncube->server;
        $this->_remoteCodingTmpPath =
            rtrim($ioncube->codingTmpPath, '/') . '/' . $this->_store->getDomain();
        $this->_remoteEnterpisePackagePath =
            rtrim($ioncube->enterprisePackagesPath, '/') . '/enterprise-' . $this->_store->getVersion() . '.tar.gz';

        $this->_scp = $this->cli('scp');
        $this->_scp->connect(
            $server->user,
            $server->pass,
            $server->host,
            $server->port
        );
        $this->_ssh = $this->cli('ssh');
        $this->_ssh->connect(
            $server->user,
            $server->pass,
            $server->host,
            $server->port
        );

        return $this;
    }

    protected function _getStoreDir()
    {
        if(!$this->_storeDir) {
            $config = $this->_config->magento;

            $pathParts = array(
                $config->systemHomeFolder,
                $config->userprefix . $this->_user->getLogin(),
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

    protected function _createRemoteTmpDir()
    {
        $file = $this->cli('file');
        $query = $file->create(
            $this->_remoteCodingTmpPath.'/decoded',
            $file::TYPE_DIR
        );
        $secondPath = $query->cloneObject()->bindAssoc('/decoded', '/encoded', false);
        $query->append('; '.$secondPath->toString());
        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _unpackRemoteDecodedEnterprise($strip)
    {
        $query = $this
            ->cli('tar')
            ->unpack(
                $this->_remoteCodingTmpPath . '/decoded-enterprise.tar.gz',
                $this->_remoteCodingTmpPath . '/decoded'
            )
            ->strip($strip);
        $this->_unpackRemoteDecodedEnterpriseBeforeCall($query);
        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _unpackRemoteDecodedEnterpriseBeforeCall($query) {}

    protected function _encodeEnterprise()
    {
        $query = $this->cli()->createQuery(
            rtrim($this->_config->ionCube->executablePath, '/')
        );

        $ioncube = $this->config->ioncube->encode;

        $server = str_ireplace(
            array('#{username}', '#{serverNumber}'),
            array($this->_user->getLogin(), $this->_user->getServerId()),
            $ioncube->allowedServer
        );

        $additionalComment = $server = str_ireplace(
            array('#{username}', '#{serverNumber}'),
            array($this->_user->getLogin(), $this->_user->getServerId()),
            $ioncube->additionalComment
        );

        $key = $ioncube->obfuscationKey;

        $query
            ->append('--allowed-server ?', $server)
            ->append('--obfuscate all')
            ->append('--obfuscation-key ?', $key)
            ->append('--add-comment ?', $additionalComment)
            ->append('--ignore ?', '.svn/')
            ->append('--encode ?', '*.php')
            ->append('--encode ?', '*.phtml')
            ->append(':decodedPath')
            ->append('--replace-target')
            ->append('-o :encodedPath');
        $query->bindAssoc(array(
            ':decodedPath' => $this->_remoteCodingTmpPath . '/decoded',
            ':encodedPath' => $this->_remoteCodingTmpPath . '/encoded'
        ));

        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _packRemoteEncodedEnterprise()
    {
        $query = $this
            ->cli('tar')
            ->pack(
                $this->_remoteCodingTmpPath . '/encoded-enterprise.tar.gz',
                $this->_remoteCodingTmpPath . '/encoded'
            )
            ->strip(3);
        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _downloadEnterpise()
    {
        $this->_scp->cloneObject()->download(
            $this->_remoteCodingTmpPath.'/enterprise.tar.gz',
            $this->_getStoreDir().'/encoded-enterprsie.tar.gz'
        )->call();
    }

    protected function _unpackLocalEncodedEnterprise()
    {
        $this
            ->cli('tar')
            ->unpack($this->_getStoreDir().'/encoded-enterprise.tar.gz', $this->_getStoreDir().'/app/code/core')
            ->strip(1)
            ->call();
    }

    protected function _cleanFileSystem()
    {
        $this->cli('file')->remove($this->_getStoreDir().'/decoded-enterprise.tar.gz')->call();
        $this->cli('file')->remove($this->_getStoreDir().'/encoded-enterprise.tar.gz')->call();
        $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->remove($this->_remoteCodingTmpPath)
        )->call();
    }
}