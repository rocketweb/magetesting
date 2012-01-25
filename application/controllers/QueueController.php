<?php

class QueueController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {      
    }
    
    public function addAction()
    { 
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->isPost()){
            
            //needs validation!
            $data = array(
                'version_id' => $request->getParam('version'),
                'edition' => $request->getParam('edition'),
                'user_id' => 1,
                'domain' => substr(
                        str_shuffle(
                                str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)
                        )
                        , 0, 5),
                'status' => 'inprogress');

            //insert into queue
            Application_Model_Queue::add($data);
            
            $this->_helper->redirector('index', 'index');
        }
        //assign to templates
        $this->view->editions = Application_Model_Edition::getAll();
    }
    
    public function getversionsAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $edition = $request->getParam('edition','CE');
        if ($request->isPost()){
            $versions = Application_Model_Version::getAllForEdition($edition);
            if(empty($versions)){
                $versions = array(
                    array(
                        'id' => '',
                        'version'=> 'no version found')
                    );
            }
            echo Zend_Json_Encoder::encode($versions);
        }
    }


}