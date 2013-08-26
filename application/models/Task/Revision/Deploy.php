<?php

class Application_Model_Task_Revision_Deploy 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    public function setup(Application_Model_Queue $queueElement) {
        parent::setup($queueElement);
        
        $params = $this->_queueObject->getTaskParams();
        $revisionModel = new Application_Model_Revision;
        if((int)$params['revision_id']) {
            $revisionModel->find($params['revision_id']);
        } elseif($params['revision_id'] === 'paid_open_source') {
            foreach($revisionModel->getAllForStore($this->_queueObject->getStoreId()) as $revision) {
                if(
                    (int)$revision->extension_id === (int)$this->_queueObject->getExtensionId()
                    && preg_match('/\(Open Source\)\s*$/', $revision->comment)
                ) {
                    $revisionModel->find($revision->id);
                    break;
                }
            }
        }

        $this->_revisionObject  = $revisionModel;
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('deploying-revision');
        $this->_deploy();
        $this->_updateFilename();
    }

    protected function _deploy(){
        $hash = $this->_revisionObject->getHash();

        $this->logger->log('Preparing deplyment package.', Zend_Log::INFO);

        $startdir  = getcwd();
        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain());
        /* prepare dir for store deploy packages */
        
        $deployPath = $this->_storeFolder.'/'.$this->_storeObject->getDomain().'/'.
        'var/deployment/';
        
        if (!file_exists($deployPath) || !is_dir($deployPath)){
            $file = $this->cli('file');
            $file->create('var/deployment', $file::TYPE_DIR)->call();
        }
        /* create archive */
        
        /**
         * git diff '.$hash.' '.$hash.'~1 --name-only - shows changed files between
         * $hash and $hash~1
         * 
         * git archive --format zip --output var/data/deploys/'.$hash.'.zip '.$hash.'
         * - packs files listed with the command above into zip 
         *  within store /var/data/deploys folder
         */
        $this->cli('git')->deploy($hash, 'var/deployment/'.$hash.'.zip')->call();

        chdir($startdir);
    }
      
    protected function _updateFilename(){
        $this->_revisionObject->setFilename($this->_revisionObject->getHash().'.zip')->save();
    }

}
