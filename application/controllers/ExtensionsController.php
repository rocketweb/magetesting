<?php
/**
 * Extensions controller shows all extensions available on magetesting
 * but singular Extension controller is allowed only for admin
 * and is not the same as controller below
 * @author Grzegorz <grzegorz@rocketweb.com>
 *
 */
class ExtensionsController extends Integration_Controller_Action
{
    public function indexAction()
    {
        $extension = new Application_Model_Extension();
        $this->view->extensions = $extension->fetchFullListOfExtensions();

        $extensionCategoryModel = new Application_Model_ExtensionCategory();
        $this->view->categories = $extensionCategoryModel->fetchAll();
    }
}