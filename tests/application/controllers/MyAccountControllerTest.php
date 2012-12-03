<?php
require_once realpath(dirname(__FILE__) . '/../../ControllerTestCase.php');

class MyAccountControllerTest extends ControllerTestCase
{
    
    public function testAccountDetails()
    {
        $this->loginUser('standard-user', 'standard-user');

        $this->dispatch('/my-account/index');
        
                
        
        file_put_contents('test.log', var_export($this->getFrontController()->getResponse()->getBody(), true));

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Your account details');
    }
}