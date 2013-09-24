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

    protected $_encodedEntepriseFilename = 'encoded-enterprise.tar.gz';
    protected $_decodedEntepriseFilename = 'decoded-enterprise.tar.gz';

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

        $version = $this->_getStoreVersion();

        $this->_remoteEnterpisePackagePath =
            rtrim($ioncube->enterprisePackagesPath, '/') . '/enterprise-' . $version . '.tar.gz';

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

    protected function _getStoreVersion()
    {
        $version = new Application_Model_Version();
        $version = $version->find($this->_store->getVersionId());
        return $version->getVersion();
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
                $this->_cli->setLogger($this->_log);
                $this->_cli->enableLogging(true);
            }
        }
        if($kit) {
            return $this->_cli->kit($kit);
        }
        return $this->_cli;
    }

    abstract public function process();

    protected function _createRemoteTmpDir()
    {
        $file = $this->cli('file');
        $query = $file->create(
            $this->_remoteCodingTmpPath.'/decoded',
            $file::TYPE_DIR
        );
        $secondPath = $query->cloneObject()->bindAssoc('/decoded', '/encoded', false);
        $query->append('; '.$secondPath->toString());

        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Creating tmp encoding dir on remote server failed.'
        );
    }

    protected function _removeEnterpriseDirContent()
    {
        $startCwd = getcwd();
        chdir($this->_getStoreDir() . '/app/code/core/Enterprise');
        $this->_call(
            $this->cli('file')->remove('')->append('*'),
            'Removing content of /app/code/core/Enterprise on local server failed.'
        );
        chdir($startCwd);
    }

    protected function _unpackRemoteDecodedEnterprise($strip)
    {
        $query = $this
            ->cli('tar')
            ->unpack(
                $this->_remoteCodingTmpPath . '/' . $this->_decodedEntepriseFilename,
                $this->_remoteCodingTmpPath . '/decoded'
            )
            ->strip($strip);
        $this->_unpackRemoteDecodedEnterpriseBeforeCall($query);
        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Unpacking decoded enterprise package on remote server failed.'
        );
    }

    protected function _unpackRemoteDecodedEnterpriseBeforeCall($query) {}

    protected function _encodeEnterprise()
    {
        $ioncube = $this->_config->ioncubeEncoder;
        $encode = $ioncube->encode;
        $query = $this->cli()->createQuery(
            rtrim($ioncube->executablePath, '/')
        );

        $server = str_ireplace(
            array('#{username}', '#{serverNumber}'),
            array($this->_user->getLogin(), $this->_user->getServerId()),
            $encode->allowedServer
        );

        $additionalComment = str_ireplace(
            array('#{username}', '#{serverNumber}'),
            array($this->_user->getLogin(), $this->_user->getServerId()),
            $encode->additionalComment
        );

        $key = $encode->obfuscationKey;

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

        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Encoding enterprise by ioncube failed.'
        );
    }

    protected function _packRemoteEncodedEnterprise()
    {
        $query = $this
            ->cli('tar')
            ->pack(
                $this->_remoteCodingTmpPath . '/' . $this->_encodedEntepriseFilename,
                $this->_remoteCodingTmpPath . '/encoded'
            );
        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Packing encoded enterprise on remote server failed.'
        );
    }

    protected function _downloadEnterpise()
    {
        $query = $this->_scp->cloneObject()->download(
            $this->_remoteCodingTmpPath.'/'.$this->_encodedEntepriseFilename,
            $this->_getStoreDir().'/'.$this->_encodedEntepriseFilename
        );
        $this->_call(
            $query,
            'Downloading encoded package from remote server failed.'
        );
    }

    protected function _unpackLocalEncodedEnterprise()
    {
        $query = $this
            ->cli('tar')
            ->unpack($this->_getStoreDir().'/'.$this->_encodedEntepriseFilename, $this->_getStoreDir().'/app/code/core')
            ->strip(count(explode('/', $this->_remoteCodingTmpPath . '/encoded')));
        $this->_call(
            $query,
            'Unpacking encoded enterprise on local server failed.'
        );
    }

    protected function _cleanFileSystem()
    {
        $this->cli('file')->remove($this->_getStoreDir().'/'.$this->_decodedEntepriseFilename)->call();
        $this->cli('file')->remove($this->_getStoreDir().'/'.$this->_encodedEntepriseFilename)->call();
        $query = $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->remove($this->_remoteCodingTmpPath)
        );
        $this->_call(
            $query,
            'Cleaning file system on local server failed.'
        );
    }

    protected function _call(RocketWeb_Cli_Query $query, $exceptionMessage = '')
    {
        if(0 !== (int) $query->call()->getLastStatus()) {
            throw new Application_Model_Ioncube_Exception((string) $exceptionMessage);
        }
    }
}