<?php

class Application_Model_Ioncube_Encode_Custom
    extends Application_Model_Ioncube_Encode
{
    public function process()
    {
        $this->_createRemoteTmpDir();
        $this->_packLocalDecodedEnterprise();
        $this->_removeEnterpriseDirContent();
        $this->_uploadEnterprise();
        $this->_unpackRemoteDecodedEnterprise(3);
        $this->_encodeEnterprise();
        $this->_packRemoteEncodedEnterprise();
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

    protected function _removeEnterpriseDirContent()
    {
        $startCwd = getcwd();
        setcwd($this->_getStoreDir() . '/app/code/core/Enterprise');
        $this->cli('file')->remove('')->append('*')->call();
        setcwd($startCwd);
    }

    protected function _uploadEnterprise()
    {
        $this->_scp->cloneObject()->upload(
            $this->_getStoreDir().'/decoded-enterprsie.tar.gz',
            $this->_remoteCodingTmpPath
        )->call();
    }
}