<?php

class Api_StoreController extends Integration_Controller_Action
{
    protected $_response_object = array(
            'type' => 'error',
            'message' => 'Wrong REST api call'
    );

    protected $_map_api_keys = array(
        'name'         => 'store_name',
        'host'         => 'custom_host',
        'login'        => 'custom_login',
        'password'     => 'custom_pass',
        'path_sql'     => 'custom_sql',
        'path_store'   => 'custom_remote_path',
        'path_backup'  => 'custom_file',
        'port'         => 'custom_port',
        'protocol'     => 'custom_protocol'
    );
    protected $_map_form_keys = array();

    public function init()
    {
        parent::init();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_map_form_keys = 
            array_combine(
                array_values($this->_map_api_keys),
                array_keys($this->_map_api_keys)
            );
    }

    public function indexAction()
    {
        /* render wrong rest call message */
        $this->getAction();
    }
    public function getAction()
    {
        $this->getResponse()->setBody(json_encode($this->_response_object));
    }
    public function putAction()
    {
        $this->getAction();
    }
    public function deleteAction()
    {
        $this->getAction();
    }

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

        $this->getResponse()->setBody(json_encode($this->_response_object))->setHeader('Content-Type', 'text/json');
    }
    protected function _authenticate($username, $apikey, Application_Model_User $userObject)
    {
        $authenticated = $userObject->authenticateApiCall($username, $apikey);
        if(!$authenticated) {
            $this->_response_object['message'] = 'Username or apikey is invalid.';
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
                $this->_response_object['message'] = 'You have reached number of stores limit, please remove any store in Mage Testing and try again.';
                return true;
            }
        }
        return false;
    }
    protected function _addCustomStore(Application_Model_User $userObject)
    {
        $this->auth->getStorage()->write((object)$userObject->__toArray());
        $form = new Application_Form_StoreAddCustom();

        // change api field names to add-custom form fields
        $params = $this->_mapApiKeys($this->_getAllParams());

        // set custom_file as required field
        $form->custom_remote_path->setRequired(false);
        $form->custom_file->setRequired(true);

        $params['do_hourly_db_revert'] = 0;

        // find version id
        if(isset($params['version']) && isset($params['edition'])) {
            $params['version'] = $params['version'];
            $versionModel = new Application_Model_Version();
            $versions = $versionModel->getAllForEdition($params['edition']);
            if(is_array($versions)) {
                foreach($versions as $row) {
                    if($row['version'] == $params['version'] && $row['edition'] == $params['edition']) {
                        $params['version'] = $params['edition'].$row['id'];
                    }
                }
            }
        }
        if($form->isValid($params)) {
            // fix version id
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
                $this->_response_object['message'] = 'Store has been added successfully, you will receive e-mail confirmaton when import is complete.';
                $server = new Application_Model_Server();
                $server = $server->find($storeModel->getServerId());
                $this->_response_object['store_frontend_url'] = 
                    'http://' . $params['username'] . '.' .
                    $server->getDomain() . '/' . $storeModel->getDomain();
                $this->_response_object['store_backend_url'] = 
                    $this->_response_object['store_frontend_url'] . '/' .
                    $storeModel->getBackendName();
            } catch(Exception $e) {
                if($log = $this->getLog()) {
                    $log->log('Api module add custom store', LOG_ERR, $e->getMessage());
                }
                $this->_response_object['message'] = 'There was a problem adding store, please contact with our support team.';
            }
        } else {
            $fields = $form->getMessages();
            if(!$fields) { $fields == array(); }
            $fields = $this->_mapFormKeys($fields);
            $fields = array_keys($fields);
            if(!$fields) { $fields == array(); }
            $this->_response_object['message'] = 'Following field values are invalid: '. implode(', ',$fields).'. Please fix them and try again.';
        }
        $this->auth->getStorage()->clear();
    }

    protected function _mapApiKeys($array)
    {
        $mapped_array = array();
        foreach($array as $key => $value) {
            if(isset($this->_map_api_keys[$key])) {
                $mapped_array[$this->_map_api_keys[$key]] = $value;
            } else {
                $mapped_array[$key] = $value;
            }
        }
        return $mapped_array;
    }
    protected function _mapFormKeys($array)
    {
        $mapped_array = array();
        foreach($array as $key => $value) {
            if(isset($this->_map_form_keys[$key])) {
                $mapped_array[$this->_map_form_keys[$key]] = $value;
            } else {
                $mapped_array[$key] = $value;
            }
        }
        return $mapped_array;
    }
}