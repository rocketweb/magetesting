<?php

class Api_StoreController extends Integration_Controller_Action
{
    public function indexAction()
    {
        /* render wrong rest call message */
        $this->getAction();
    }
    public function getAction()
    {
        $this->render('rest-error');
    }
    public function putAction()
    {
        $this->getAction();
    }
    public function deleteAction()
    {
        $this->getAction();
    }

    protected $_response_object = array(
            'type' => 'error',
            'message' => 'Wrong REST api call'
    );
    public function postAction()
    {
        $username = $this->getParam('username');
        $apikey = $this->getParam('apikey');
        if($username && $apikey) {
            $userModel = new Application_Model_User();
            if($this->_authenticate($username, $apikey, $userModel)) {
                if(!$this->_checkStoreLimit($userModel)) {
                    $this->_addCustomStore($userModel);
                }
            }
        }

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setBody(json_encode($this->_response_object))->setHeader('Content-Type', 'text/json');
    }
    protected function _authenticate($username, $apikey, Application_Model_User $userObject)
    {
        $authenticated = $userObject->authenticateApiCall($username, $apikey);
        if(!$authenticated) {
            $this->_response_object['message'] = 'Wrong username or apikey';
        }
        return $authenticated;
    }
    protected function _checkStoreLimit(Application_Model_User $userObject)
    {
        if('admin' != $userObject->getGroup()) {
            if('free-user' == $userObject->getGroup()) {
                $maxStores =
                (int) $this->getInvokeArg('bootstrap')
                           ->getResource('config')
                           ->magento
                           ->standardUser
                           ->stores;
            } else {
                $planModel = new Application_Model_Plan();
                $planModel->find($userObject->getPlanId());
                $maxStores = (int) $planModel->getStores();
            }

            $storeModel = new Application_Model_Store();
            $userStores = $storeModel->countUserStores($userObject->getId());
            if($userStores >= $maxStores) {
                $this->_response_object['message'] = 'Reached store limit';
                return true;
            }
        }
        return false;
    }
    protected function _addCustomStore(Application_Model_User $userObject)
    {
        $this->auth->getStorage()->write((object)$userObject->__toArray());
        $form = new Application_Form_StoreAddCustom();
        $path_type = $this->_getParam('input-radio');
        if(!in_array($path_type, array('remote_path', 'file'))) {
            $path_type = 'remote_path';
        }
        if($path_type == 'remote_path') {
            $form->custom_remote_path->setRequired(true);
            $form->custom_file->setRequired(false);
        } elseif($path_type == 'file') {
            $form->custom_remote_path->setRequired(false);
            $form->custom_file->setRequired(true);
        }

        if($form->isValid($this->getRequest()->getParams())) {
            // fix version number
            $form->version->setValue(substr($form->version->getValue(),2));

            try {
                $storeModel = new Application_Model_Store();
                $storeModel->setOptions($form->getValues())
                           ->setType('custom')
                           ->setBackendName('admin')
                           ->setDomain(Integration_Generator::generateRandomString(5, 4))
                           ->setStatus('downloading-magento')
                           ->setVersionId($form->version->getValue())
                           ->setServerId($userObject->getServerId())
                           ->setUserId($userObject->getId())
                           ->setSampleData(1);

                $storeId = $storeModel->save();

                $queueModel = new Application_Model_Queue();
                //TODO: Add queue item with MagentoDownload
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('MagentoDownload');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($userObject->getId());
                $queueModel->setServerId($userObject->getServerId());
                $queueModel->setParentId(0);
                $queueModel->setExtensionId(0);
                $queueModel->save();
                $installId = $queueModel->getId();
                
                unset($queueModel);
                
                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('RevisionInit');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($userObject->getId());
                $queueModel->setServerId($userObject->getServerId());
                $queueModel->setExtensionId(0);
                $queueModel->setParentId($installId);
                $queueModel->save();
                unset($queueModel);
                
                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('RevisionCommit');
                $queueModel->setTaskParams(
                        array(
                                'commit_comment' => 'Initial Magento Commit',
                                'commit_type' => 'magento-init'
                        )
                );
                $queueModel->setStatus('pending');
                $queueModel->setUserId($userObject->getId());
                $queueModel->setServerId($userObject->getServerId());
                $queueModel->setExtensionId(0);
                $queueModel->setParentId($installId);
                $queueModel->save();
                
                unset($queueModel);
                //Add queue create user in Papertrail
                if(!$userObject->getHasPapertrailAccount()) {
                    $queueModel = new Application_Model_Queue();
                    $queueModel->setStoreId($storeId);
                    $queueModel->setTask('PapertrailUserCreate');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($userObject->getId());
                    $queueModel->setServerId($userObject->getServerId());
                    $queueModel->setExtensionId(0);
                    $queueModel->setParentId($installId);
                    $queueModel->save();
                
                    $installId = $queueModel->getId();
                }
                
                unset($queueModel);
                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('PapertrailSystemCreate');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($userObject->getId());
                $queueModel->setServerId($userObject->getServerId());
                $queueModel->setExtensionId(0);
                $queueModel->setParentId($installId);
                $queueModel->save();

                $this->_response_object['type'] = 'success';
                $this->_response_object['message'] = 'Store added to queue';
            } catch(Exception $e) {
                if($log = $this->getLog()) {
                    $log->log('Api module add custom store', LOG_ERR, $e->getMessage());
                }
                $this->_response_object['message'] = 'Couldn\'t add store';
            }
        } else {
            var_dump($form->getMessages());die;
            $this->_response_object['message'] = 'Invalid data';
        }
        $this->auth->getStorage()->clear();
    }
}