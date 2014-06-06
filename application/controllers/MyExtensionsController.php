<?php

require_once dirname(__FILE__).'/ExtensionController.php';

class MyExtensionsController extends  ExtensionController
{
    private $_user;
    public function init() {
        parent::init();
        $this->_user = Zend_Auth::getInstance()->getIdentity();
    }
    public function listAction() {
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
    public function editAction($action = 'Edit'){
        $postData = $_POST;
        $postData['extension_owner'] = $this->_user->id;
        $_POST = $postData;
        parent::editAction($action = 'Edit');

    }

}