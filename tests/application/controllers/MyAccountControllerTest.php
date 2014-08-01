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
        $this->loginUser($this->_userData['login'], $this->_userData['password']);

        $this->dispatch('/my-account/index');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Your Mage Testing Account Details');
    }


    public function testEditAccount()
    {
        $this->loginUser($this->_userData['login'], $this->_userData['password']);

        $this->dispatch('/my-account/edit-account');

        $this->assertController('my-account');
        $this->assertAction('edit-account');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

    }

    public function testEditAccountPost()
    {
        $this->loginUser($this->_userData['login'], $this->_userData['password']);

        $this->request->setMethod('POST')
            ->setPost(array(
                'login' => $this->_userData['login'],
                'email' => $this->_userData['email'],
                //'password'    => $this->_userData['password'],
                //'password_repeat' => $this->_userData['password'],
                'firstname' => 'PHPfirstname',
                'lastname' => 'PHPlastname',
                'country' => 'Zimbabwe',
                'state' => 'Choose state ...'
            ));
        $this->dispatch('/my-account/edit-account');

        $this->assertController('my-account');
        $this->assertAction('edit-account');
        $this->assertRedirect();
        $this->assertResponseCode(302);

        $this->resetRequest()->resetResponse();

        $this->dispatch('/my-account');

        $this->assertController('my-account');
        $this->assertAction('index');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('strong', 'You successfully edited your details.');
    }
}