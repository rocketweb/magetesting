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
        $form = new Application_Form_QueueAdd();
        $form->populate($this->getRequest()->getParams());
        
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->isPost()){
            
            if ($form->isValid($this->getRequest()->getParams())){
            //needs validation!
            $queueModel = new Application_Model_Queue();
            $queueModel->setVersionId( $form->version->getValue() )
            		->setEdition( $form->edition->getValue() )
            		->setUserId( 1 )
            		->setDomain(
            			substr(
                  	str_shuffle(
                  		str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)
                  )
                	, 0, 5)
            		)
            		->setStatus( 'pending' );
            $queueModel->save();
            $this->_helper->FlashMessenger('New installation added to queue');
            $this->_helper->redirector('index', 'index');
            }else {
                $this->_helper->FlashMessenger('Form needs verification');
            }
            
            
            
        }
        //assign to templates
        $editionModel = new Application_Model_Edition();
        $this->view->editions = $editionModel->getAll();
        $this->view->form = $form;
    }
    
    public function getversionsAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $edition = $request->getParam('edition','CE');
        if ($request->isPost()){
            $versionModel = new Application_Model_Version();
            $versions = $versionModel->getAllForEdition($edition);
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