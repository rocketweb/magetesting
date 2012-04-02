<?php

class QueueController extends Integration_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $queueModel = new Application_Model_Queue();

        $timeExecution = $this->getInvokeArg('bootstrap')
                              ->getResource('config')
                              ->magento
                              ->instanceTimeExecution;
        $queueCounter = $queueModel->getPendingItems($timeExecution);

        $page = (int) $this->_getParam('page', 0);
        $paginator = $queueModel->getWholeQueue();
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->queue = $paginator;
        $this->view->queueCounter = $queueCounter;
    }

    public function addAction()
    {
        $form = new Application_Form_QueueAdd();
        $form->populate($this->getRequest()->getParams());

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->isPost()){

            $userGroup = $this->auth->getIdentity()->group;

            if($this->auth->getIdentity()->group != 'admin') {
                
                $versionModel = new Application_Model_Version();
                $version = $versionModel->find((int)$request->getParam('version', 0));
                
                if($version->getEdition() != 'CE') {
                    $this->_helper->FlashMessenger('Hacking forbidden.');
                    return $this->_helper->redirector->gotoRoute(
                            array(), 'default', false
                    );
                }
            }
            
            if ($form->isValid($this->getRequest()->getParams())){
                //needs validation!
                $queueModel = new Application_Model_Queue();
                $userId = $this->auth->getIdentity()->id;
                $maxInstances = (int)$this->getInvokeArg('bootstrap')
                                     ->getResource('config')
                                     ->magento
                                     ->standardUser
                                     ->instances;
                $userInstances = $queueModel->countUserInstances($userId);

                if(
                    $userGroup != 'standard-user'
                    OR ($userInstances < $maxInstances)
                ) {
                    $queueModel->setVersionId( $form->version->getValue() )
                    ->setEdition($form->edition->getValue())
                    ->setUserId($userId)
                    ->setSampleData($form->sample_data->getValue())
                    ->setInstanceName($form->instance_name->getValue())
                    ->setDomain(
                            substr(
                                str_shuffle(
                                    str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 5)
                                )
                                , 0, 5).substr(
                                str_shuffle(
                                    str_repeat('0123456789', 5)
                                )
                                , 0, 4)
                    )
                    ->setStatus( 'pending' );
                    $queueModel->save();
                    $this->_helper->FlashMessenger('New installation added to queue');
                    
                    //magentointegration user creates database
                    try{
                        $db = Zend_Db_Table::getDefaultAdapter();
                        $DbManager = new Application_Model_DbTable_Privilege($db,$this->getInvokeArg('bootstrap')
                                     ->getResource('config'));
                        $DbManager->createDatabase($this->auth->getIdentity()->login.'_'.$queueModel->getDomain());
                        
                        if (!$DbManager->checkIfUserExists($this->auth->getIdentity()->login)){
                            $DbManager->createUser($this->auth->getIdentity()->login);
                        }                       
                    } catch(PDOException $e){
                        $message = 'Could not create database for instance, aborting';
                        echo $message;
                        if ($log = $this->getLog()) {
                            $log->log($message, LOG_ERR);
                        }
                        throw $e;
                    }                  
                    
                } else {
                    $this->_helper->FlashMessenger('You cannot have more instances.');
                }

                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
            } else {
                $this->_helper->FlashMessenger('Form needs verification');
            }
        }
        //assign to templates
        $editionModel = new Application_Model_Edition();
        $this->view->editions = $editionModel->getAll();
        $this->view->form = $form;
    }

    public function closeAction()
    {
        $form = new Application_Form_QueueClose();
        $this->view->form = $form;

        $domain = $this->getRequest()->getParam('domain');

        if($this->getRequest()->isPost()) {

            $close = (int)$this->getRequest()->getParam('close');

            if($close AND $domain) {
                $queue = new Application_Model_Queue();
                $queue->setUserId($this->auth->getIdentity()->id)
                      ->setDomain($domain);
                $byAdmin = $this->auth->GetIdentity()
                                ->group == 'admin'
                                ? true : false;

                $queue->changeStatusToClose($byAdmin);

                $this->_helper->FlashMessenger('Store added to close queue.');
            }

            $controller = 'user';
            $action     = 'dashboard';
            if($this->_getParam('redirect', 0) === 'admin') {
                $controller = 'queue';
                $action     = 'index';
            }
            return $this->_helper->redirector->gotoRoute(array(
                    'module'     => 'default',
                    'controller' => $controller,
                    'action'     => $action,
            ), 'default', true);
        }

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

    public function editAction(){
        $id = (int) $this->_getParam('id', 0);
        
        $queueItem = new Application_Model_Queue();
        $instance = $queueItem->find($id);
        
        if ($instance->getUserId() == $this->auth->getIdentity()->id){
            //its ok to edit
        } else {
            if($this->auth->getIdentity()->group != 'admin'){
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
            }
        }
        
        $form = new Application_Form_QueueEdit();
        $form->populate($instance->__toArray());

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if($form->isValid($formData)) {
                $instance->setOptions($form->getValues());
                $instance->save();

                $this->_helper->FlashMessenger('Store data has been changed successfully');
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'user',
                        'action'     => 'dashboard',
                ), 'default', true);
            }
        }
        $this->view->form = $form;
    }

}