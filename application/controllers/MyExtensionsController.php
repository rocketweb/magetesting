<?php

require_once dirname(__FILE__).'/ExtensionController.php';

class MyExtensionsController extends  ExtensionController
{
    private $_user;
    public function init()
    {
        parent::init();
        $this->_user = Zend_Auth::getInstance()->getIdentity();
        $this->_controllerUser = 'extension-owner';
    }

    private function _checkExtensionOwner($param = 'id')
    {
        $id = (int) $this->_getParam($param, 0);
        $isUserExtensionOwner = false;
        if($id > 0){
            $extensionModel = new Application_Model_Extension();
            $editingExtension = $extensionModel->find($id);
            if($editingExtension->getExtensionOwner() == $this->_user->id){
                $isUserExtensionOwner = true;
            }
        }
        if(!$isUserExtensionOwner){
            $this->_helper->FlashMessenger(
                array(
                    'type' => 'error',
                    'message' => 'You don\'t have the permission for this action!'
                )
            );
            return $this->_helper->redirector->gotoRoute(array(
                'module'     => 'default',
                'controller' => $this->_gridController,
                'action'     => 'index',
            ), 'default', true);
        }
        return true;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $filter = $request->getParam('filter', array());
        if(!is_array($filter)) {
            $filter = array();
        }
        /*
         * We set the filter for extension_owner on current user id
         */
        $filter['extension_owner'] = $this->_user->id;
        $request->setParam('filter',$filter);

        parent::listAction();
    }
    public function editAction($action = 'Edit')
    {
        if($action == 'Edit') {
            if($this->_checkExtensionOwner() !== true) return;
        }
        $postData = $_POST;
        $postData['extension_owner'] = $this->_user->id;
        $_POST = $postData;
        parent::editAction($action);

    }
    public function deleteAction()
    {
        if($this->_checkExtensionOwner() === true) {
            parent::deleteAction();
        }
    }

    public function listVersionsAction()
    {
        if($this->_checkExtensionOwner() === true) {
            parent::listVersionsAction();
        }
    }

    public function addVersionToExtensionAction(){
        if($this->_checkExtensionOwner() === true) {
            parent::addVersionToExtensionAction();
        }
    }

    public function syncAction()
    {
        if($this->_checkExtensionOwner('extension_id') === true) {
            return parent::syncAction();
        }
    }

}