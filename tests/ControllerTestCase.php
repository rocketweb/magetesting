<?php
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

/**
 * Abstract Class for Test Controllers
 * Define setUp method
 */
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    public $bootstrap = null;

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(
            'testing',
            APPLICATION_PATH . '/configs/application.ini'
        );

        $this->bootstrap->bootstrap('db');

        parent::setUp();
    }

    public function loginUser($user, $password)
    {
        $this->request->setMethod('POST')
                      ->setPost(array(
                          'login' => $user,
                          'password' => $password,
                      ));
        $this->dispatch('/user/login');
        
        $this->assertRedirectTo('/user/dashboard');
        
        $this->resetRequest()->resetResponse();
    }
}
