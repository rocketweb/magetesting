<?php
require_once realpath(dirname(__FILE__) . '/../../ControllerTestCase.php');

class MyAccountControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createFakeUser();
    }

    public function testAccountDetails()
    {
        $this->loginUser('standard-user', 'standard-user');

        $this->dispatch('/my-account/index');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Your Mage Testing Account Details');
    }
}