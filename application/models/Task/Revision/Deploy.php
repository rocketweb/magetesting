<?php

class Application_Model_Task_Revision_Deploy 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function setup(Application_Model_Queue &$queueElement) {
        
        parent::setup($queueElement);
        
        $params = $this->_queueObject->getTaskParams();
        $revisionModel = new Application_Model_Revision;
        $revisionModel->find($params['revision_id']);
        
        $this->_revisionObject  = $revisionModel;
    }
    
    public function process(Application_Model_Queue &$queueElement = null) {
        
        $this->_deploy();
        $this->_updateFilename();
        
        
    }

    protected function _deploy() {
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
        
    }

}
