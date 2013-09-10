<?php

class Application_Model_Ioncube_Encode_Clean
    extends Application_Model_Ioncube_Encode
{
    public function process()
    {
        $this->_packLocalDecodedEnterprise();
        $this->_uploadEnterprise();
        $this->_createRemoteTmpDir();
        $this->_unpackRemoteDecodedEnterprise();
        $this->_encodeEnterprise();
        $this->_pakcRemoteEncodedEnterprise();
        $this->_downloadEnterpise();
        $this->_unpackLocalEncodedEnterprise();
        $this->_cleanFileSystem();
    }

    protected function _packLocalDecodedEnterprise()
    {
        $this->cli('tar')->pack(
            $this->_getStoreDir().'/decoded-enterprsie.tar.gz',
            $this->_getStoreDir().'/app/code/core/Enterprise'
        )->isCompressed()->call();
    }

    protected function _uploadEnterpise()
    {
        $this->_scp->cloneObject()->upload(
            $this->_getStoreDir().'/decoded-enterprsie.tar.gz',
            $this->_remoteCodingTmpPath . '/..'
        )->call();
    }

    protected function _createRemoteTmpDir()
    {
        $file = $this->cli('file');
        $query = $file->create(
            $this->_remoteCodingTmpPath,
            $file::TYPE_DIR
        );
        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _unpackRemoteDecodedEnterprise()
    {
        $query = $this
            ->cli('tar')
            ->unpack(
                $this->_remoteCodingTmpPath . '/encoded-enterprise.tar.gz',
                $this->_getStoreDir() . '/app/code/core'
            )
            ->strip(3);
        $this->_ssh->cloneObject()->remoteCall($query)->call();
    }

    protected function _encodeEnterprise()
    {
        $query = $this->cli()->createQuery('ioncube');
        $query
            ->append('--allowed-server ?', $servers)
            ->append('--obfuscate all')
            ->append('--obfuscation-key ?', $key)
            ->append('--ignore .svn/')
            ->append('--encode ?', '*.php')
            ->append('--encode ?', '*.phtml')
            ->append(':decodedPath')
            ->append(':encodedPath');
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
            ->unpack($this->_remoteCodingTmpPath.'/encoded-enterprise.tar.gz', $this->_getStoreDir().'/app/code/core')
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
            ->strip(3)
            ->call();
    }

    protected function _cleanFileSystem()
    {
        $this->cli('file')->remove($this->_getStoreDir().'/encoded-enterprise.tar.gz')->call();
        $this->cli('file')->remove($this->_getStoreDir().'/decoded-enterprise.tar.gz')->call();
        $this->_ssh->cloneObject()->remoteCall(
            $this->cli('file')->remove($this->_remoteCodingTmpPath)
        )->call();
    }
}