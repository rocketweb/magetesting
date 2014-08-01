<?php
require_once realpath(dirname(__FILE__) . '/../../ControllerTestCase.php');

class UserControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createFakeUser();
    }
    
    public function testValidLoginShouldGoToDashboard()
    {
        $this->loginUser($this->_userData['login'], $this->_userData['password']);
        
        $this->dispatch('/user/dashboard');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('strong', 'You have been logged in successfully');
        
        $this->resetRequest()->resetResponse();
        $this->dispatch('/user/logout');
    }
    
    public function testNotValidLogin()
    {
        $this->request->setMethod('POST')
              ->setPost(array(
                  'login'    => 'standard-user1',
                  'password' => 'standard-user'
              ));
        $this->dispatch('/user/login');
        
        $this->assertModule('default');
        $this->assertController('user');
        $this->assertAction('login');
        $this->assertRedirectTo('/user/login');
        
        $this->resetRequest()->resetResponse();
        $this->dispatch('/user/login');

        $this->assertQuery('form');
        $this->assertQueryContentContains('strong', 'You have entered wrong credentials. Please try again.');
    }
    
    public function testValidLogoutShouldGoToHomePage()
    {
        $this->request->setMethod('POST')
              ->setPost(array(
                  'login'    => $this->_userData['login'],
                  'password' => $this->_userData['password']
              ));
        $this->dispatch('/user/login');
        
        $this->resetRequest()->resetResponse();
        $this->dispatch('/user/logout');
        
        $this->assertRedirectTo('/');
    }
    
    public function testValidResetPassword()
    {
        $this->request->setMethod('POST')
              ->setPost(array(
                  'email' => 'no-replay@rocketweb.com',
              ));
        $this->dispatch('/user/reset-password');
        
        $this->assertRedirectTo('/user/reset-password');
        
        $this->resetRequest()->resetResponse();
        $this->request->setMethod('GET')->setPost(array());
        $this->dispatch('/user/reset-password');
        
        $this->assertQueryContentContains('strong', 'We sent you link with form to set your new password.');

    }

    public function testNotValidResetPassword()
    {
        $this->request->setMethod('POST')
            ->setPost(array(
                'email' => 'some-non-existing-email@rocketweb.com',
            ));
        $this->dispatch('/user/reset-password');

        $this->assertRedirectTo('/user/reset-password');

        $this->resetRequest()->resetResponse();
        $this->request->setMethod('GET')->setPost(array());
        $this->dispatch('/user/reset-password');

        $this->assertQueryContentContains('strong', 'Wrong credentials.');

    }
    
    public function testRegistrationShouldFailWithInvalidData()
    {
        $data = array(
            'login'           => 'This will not work',
            'email'           => 'This is an invalid email',
            'firstname'       => 'This will not work',
            'lastname'        => 'This will not work',
            'password'        => 'Th1s!s!nv@l1d',
            'password_repeat' => 'wrong!',
        );
        $request = $this->getRequest();
        $request->setMethod('POST')
                ->setPost($data);
        $this->dispatch('/user/register');
        
        $this->assertNotRedirect();
        $this->assertQuery('form .errors');
    }

    /**
     * Before run the test, please change param in local.ini 
     * In register.useCoupons set 0. register.useCoupons = 0
     */
    public function testValidRegistration()
    {
        $data = array(
            'login'           => 'phpunittest',
            'email'           => 'email@rocketweb.com',
            'firstname'       => 'First',
            'lastname'        => 'Last Name',
            'password'        => 'password',
            'password_repeat' => 'password',
        );
        $request = $this->getRequest();
        $request->setMethod('POST')->setPost($data);
        $this->dispatch('/user/register');
        
        $this->assertRedirectTo('/user/login');

        $this->resetRequest()->resetResponse();
        $this->request->setMethod('GET')->setPost(array());
        $this->dispatch('/user/login');
        
        $this->assertQueryContentContains('strong', 'You have been registered successfully');
    }
}