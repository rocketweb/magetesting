<?php
/**
 * Extensions controller shows all extensions available on magetesting
 * but singular Extension controller is allowed only for admin
 * and is not related to controller below
 * @author Grzegorz <grzegorz@rocketweb.com>
 *
 */
class ExtensionsController extends Integration_Controller_Action
{
    public function init()
    {
        $this->_helper->sslSwitch(false);
        parent::init();
    }
    public function indexAction()
    {
        $extensionModel = new Application_Model_Extension();

        $request = $this->getRequest();
        $filter = $request->getParam('filter', array());
        if(!is_array($filter)) {
            $filter = array();
        }
        $order = $request->getParam('order', array());
        if(!is_array($order)) {
            $order = array();
        }
        $offset = $request->getParam('offset', 0);
        $offset = !is_numeric($offset) ? 0 : (int)$offset;
        $limit = 50;

        unset($filter['restricted']);
        $filter['restricted'] = true;
        $this->view->extensions = $extensionModel->fetchFullListOfExtensions($filter, $order, $offset, $limit);

        $extensionCategoryModel = new Application_Model_ExtensionCategory();
        $this->view->categories = $extensionCategoryModel->fetchAll();

        if($request->isPost() && $request->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            $this->renderScript('extension/tiles.phtml');
        } else {
            $this->renderScript('extension/list.phtml');
        }
    }
	
}