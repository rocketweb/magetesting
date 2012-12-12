<?php

class Application_Model_Task_Revision_Deploy 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    public function setup(Application_Model_Queue &$queueElement) {
        $this->logger = $this->_getLogger();
        
        parent::setup($queueElement);
        
        $params = $this->_queueObject->getTaskParams();
        $revisionModel = new Application_Model_Revision;
        $revisionModel->find($params['revision_id']);
        
        $this->_revisionObject  = $revisionModel;
    }
    
    public function process(Application_Model_Queue &$queueElement = null) {
        $this->_updateStatus('deploying-revision');
        $this->_deploy();
        $this->_updateFilename();
        $this->_updateStatus('ready');
    }

    protected function _deploy(){
        $hash = $this->_revisionObject->getHash();

        $this->logger->log('Preparing deplyment package.', Zend_Log::INFO);

        $startdir  = getcwd();
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        /* prepare dir for instance deply packages */
        
        $deployPath = $this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/'.
        'var/deployment/';
        
        if (!file_exists($deployPath) || !is_dir($deployPath)){
            exec('mkdir -p var/deployment/');
        }
        /* create archive */
        
        /**
         * git diff '.$hash.' '.$hash.'~1 --name-only - shows changed files between
         * $hash and $hash~1
         * 
         * git archive --format zip --output var/data/deploys/'.$hash.'.zip '.$hash.'
         * - packs files listed with the command above into zip 
         *  within instance /var/data/deploys folder
         */
        
        exec('git archive --format zip --output var/deployment/'.$hash.'.zip '.$hash.' `git diff '.$hash.' '.$hash.'~1 --name-only`');
        
        chdir($startdir);
    }
    
    /**
     * @deprectated: left for now for reference
     */
    protected function _deployOld() {
        $hash = $this->_revisionObject->getHash();
        $startdir  = getcwd();
        $output = '';
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        
        /* fetch changed files */
        exec('git show --pretty="format:" --name-only '.$hash,$output);
        
        $changedFiles = array();
        foreach($output as $gitChangedFile){
            $changedFiles[]= $gitChangedFile;
        }
        
        /* create temporary directory */
        $tempdir = sys_get_temp_dir();
        chdir($tempdir);
        exec('mkdir '.$hash.';');
        chdir($tempdir.'/'.$hash);      
        /* clone repository into temporary directory */
        $output = array();
        //var_dump('sudo git clone '.$this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        exec('sudo git clone '.$this->_instanceFolder.'/'.$this->_instanceObject->getDomain(),$output);
        //var_dump($output);
        chdir($tempdir.'/'.$hash.'/'.$this->_instanceObject->getDomain());
        unset($output);
        
        exec('git checkout '.$hash,$output);
        //var_dump($output);
        chdir($tempdir.'/'.$hash);
        exec('sudo mkdir mageroot');
        chdir($tempdir);
        unset($output);
        exec('pwd',$output);
        //var_dump($output);
        
        foreach($changedFiles as $file){
            $pathinfo = pathinfo($file);
            /* create destination dir */
            if (isset($pathinfo['dirname']) &&  (!file_exists($hash.'/mageroot/'.$pathinfo['dirname']) || !is_dir($hash.'/mageroot/'.$pathinfo['dirname']))){
                exec('mkdir -p '.$hash.'/mageroot/'.$pathinfo['dirname']);
            }
            
            //echo 'sudo cp ' . $hash . '/' . $this->_instanceObject->getDomain().'/'.$file. ' '.$hash.'/mageroot/'.$file.PHP_EOL;
            exec('sudo cp ' . $hash . '/' . $this->_instanceObject->getDomain().'/'.$file . ' '.$hash.'/mageroot/'.$file);
        }
       
        /*TODO: decide if move to user revision/ folder next to public_html ? */
        $apppath = str_replace('/application','', APPLICATION_PATH);
        
        exec('sudo tar -zcf '.$apppath.'/data/revision/'.$this->_userObject->getLogin().'/'.$hash.'.tgz '.$hash.'/mageroot');
             
        chdir($startdir);
        
    }
    
    protected function _updateFilename(){
        $this->_revisionObject->setFilename($this->_revisionObject->getHash().'.zip')->save();
    }

}
