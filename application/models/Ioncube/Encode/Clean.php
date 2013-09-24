<?php

class Application_Model_Ioncube_Encode_Clean
    extends Application_Model_Ioncube_Encode
{
    public function process()
    {
        $this->_createRemoteTmpDir();
        $this->_removeEnterpriseDirContent();
        $this->_copyEnterprisePackage();
        $this->_unpackRemoteDecodedEnterprise(5);
        $this->_encodeEnterprise();
        $this->_packRemoteEncodedEnterprise();
        $this->_downloadEnterpise();
        $this->_unpackLocalEncodedEnterprise();
        $this->_cleanFileSystem();
    }

    protected function _unpackRemoteDecodedEnterpriseBeforeCall($query)
    {
        // extract only Enterprise from store package
        // it requires also strip(5) to not create useless folders
        $query->append('--wildcards ?', 'magento/app/code/core/Enterprise/*');
    }

    protected function _copyEnterprisePackage()
    {
        $file = $this->cli('file');
        $query = $file->clear()->copy(
            $this->_remoteEnterpisePackagePath,
            $this->_remoteCodingTmpPath . '/' . $this->_decodedEntepriseFilename
        );
        $this->_call(
            $this->_ssh->cloneObject()->remoteCall($query),
            'Copying enterpise to tmp dir on remote server failed.'
        );
    }
}