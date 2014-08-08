<?php

class Application_Model_Ioncube_Encode_Extension 
    extends Application_Model_Ioncube_Encode
{
    protected $_extension;

    public function process()
    {
        // create new dir on mtencode
        $this->_createRemoteTmpDir('/files');
        $this->_createRemoteTmpDir('/files_encoded');

        // upload extension to mtencode
        $this->_uploadExtension();

        // unpack extension
        $this->_unpackRemoteExtension();

        // validate extension file (check if app/ exists)
        $this->_validateExtension();

        // encode extension
        $this->_encodeExtension();

        // pack encoded extension
        $this->_packEncodedExtension();

        // download encoded extension
        $this->_downloadExtension();

        // clean temp data on encoding server
        $this->_cleanFileSystem();

        // return encoded extension file name
        return $this->_getEncodedExtensionName();
    }

    public function setup($extension, $config, $log = null)
    {
        $this->_extension = $extension;
        $this->_config = $config;

        if($log instanceof Zend_Log) {
            $this->_log = $log;
        }

        $ioncube = $this->_config->ioncubeEncoder;
        $server = $ioncube->server;
        
        $this->_remoteCodingTmpPath =
            rtrim($ioncube->codingTmpPath, '/') . '/' . $this->_extension->getId();

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

    protected function _uploadExtension()
    {
        $query = $this->_scp->cloneObject()->upload(
            $this->_getExtensionDir().$this->_extension->getExtension(),
            $this->_remoteCodingTmpPath
        );
        $this->_call(
            $query,
            'Uploading extension package from local server to encoding server failed.'
        );
    }

    protected function _unpackRemoteExtension()
    {
        $query = $this
            ->cli('tar')
            ->unpack(
                $this->_remoteCodingTmpPath . '/' . $this->_extension->getExtension(),
                $this->_remoteCodingTmpPath . '/files'
            )
            /*->strip($strip)*/;
        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Unpacking extension package on remote server failed.'
        );
    }
    
    protected function _validateExtension()
    {
        $error = 'Directory app/ has not been found in unpacked extension package.';

        $file = $this->cli('file');
        $query = $file
            ->find('app', RocketWeb_Cli_Kit_File::TYPE_DIR, $this->_remoteCodingTmpPath, true);

        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            $error
        );

        $output = $this->_cli->getLastOutput();

        // no matches found
        if (count($output) == 0) {
            throw new Application_Model_Ioncube_Encode_Extension_Exception($error);
        }
        
        $found = false;

        // app should be in unpacked tar.gz root
        foreach ($output as $line){
            $line = str_replace($this->_remoteCodingTmpPath. '/files/', '', $line);
            if($line === 'app'){
                $found = true;
                break;
            }
        }

        if ($found === false) {
            throw new Application_Model_Ioncube_Encode_Extension_Exception($error);
        }

        return true;
    }

    protected function _encodeExtension()
    {
        $ioncube = $this->_config->ioncubeEncoder;
        $encode = $ioncube->encode;
        $query = $this->cli()->createQuery(
            rtrim($ioncube->executablePath, '/')
        );

        $key = $encode->obfuscationKey;

        $query
            ->append('--allowed-server ?', 'magetesting.com,*.magetesting.com')
            ->append('--obfuscate all')
            ->append('--obfuscation-key ?', $key)
            ->append('--obfuscation-ex /home/ioncube/extensions/ioncube.blist')
            ->append('--ignore ?', '.svn/')
            ->append('--ignore ?', '.DS_Store')
            ->append('--encode ?', '*.php')
            ->append('--encode ?', '*.phtml')
            ->append(':decodedPath')
            ->append('--replace-target')
            ->append('-o :encodedPath');
        $query->bindAssoc(array(
            ':decodedPath' => $this->_remoteCodingTmpPath . '/files',
            ':encodedPath' => $this->_remoteCodingTmpPath . '/files_encoded'
        ));

        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Encoding extension using ioncube failed.'
        );
    }

    protected function _packEncodedExtension()
    {
        $query = $this
            ->cli('tar')
            ->pack(
                $this->_remoteCodingTmpPath . '/' . $this->_getEncodedExtensionName(),
                $this->_remoteCodingTmpPath . '/files_encoded',
                true, // verbose 
                true // change dir
            );
        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Packing encoded extension on remote server failed.'
        );
    }

    protected function _downloadExtension()
    {
        $query = $this->_scp->cloneObject()->download(
            $this->_remoteCodingTmpPath.'/'.$this->_getEncodedExtensionName(),
            $this->_getExtensionDir('encoded').$this->_getEncodedExtensionName()
        );
        $this->_call(
            $query,
            'Downloading encoded extension package from remote server failed.'
        );
    }

    protected function _cleanFileSystem()
    {
        $query = $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->remove($this->_remoteCodingTmpPath)
        );
        $this->_call(
            $query,
            'Cleaning file system on encoding server failed.'
        );
    }

    protected function _getEncodedExtensionName()
    {
        $name = $this->_extension->getExtension();
        
        $name = str_replace(array('.tar.gz', '.tgz'), '', $name);

        return $name . '-encoded.tar.gz';
    }
    
    protected function _getExtensionDir($type = 'open')
    {
        return APPLICATION_PATH.'/../data/extensions/'.$this->_extension->getEdition().'/'.$type.'/';
    }
}
