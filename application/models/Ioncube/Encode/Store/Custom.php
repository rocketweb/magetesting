<?php

class Application_Model_Ioncube_Encode_Store_Custom
    extends Application_Model_Ioncube_Encode_Store
{
    public function process()
    {
        $this->_createRemoteTmpDir('decoded');
        $this->_createRemoteTmpDir('encoded');
        $strip = $this->_packLocalDecodedEnterprise();
        $this->_removeEnterpriseDirContent();
        $this->_uploadEnterprise();
        $this->_unpackRemoteDecodedEnterprise($strip);
        $this->_encodeRemoteEnterprise();
        $this->_packRemoteEncodedEnterprise();
        $this->_downloadEnterpise();
        $this->_unpackLocalEncodedEnterprise();
        $this->_cleanFileSystem();
    }


    protected function _packLocalDecodedEnterprise()
    {
        $enterprisePath = $this->_getStoreDir().'/app/code/core/Enterprise';
        $query = $this->cli('tar')->pack(
            $this->_getStoreDir().'/'.$this->_decodedEntepriseFilename,
            $enterprisePath
        )->isCompressed();

        $this->_call(
            $query,
            'Packing /app/code/core/Enterprise on local server failed.'
        );

        return count(explode('/', ltrim($enterprisePath, '/')));
    }

    protected function _uploadEnterprise()
    {
        $query = $this->_scp->cloneObject()->upload(
            $this->_getStoreDir().'/'.$this->_decodedEntepriseFilename,
            $this->_remoteCodingTmpPath
        );
        $this->_call(
            $query,
            'Uploading decoded enterprise package from local server failed.'
        );
    }
}