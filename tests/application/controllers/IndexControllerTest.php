<?php
require_once realpath(dirname(__FILE__) . '/../../ControllerTestCase.php');

class IndexControllerTest extends ControllerTestCase
{
    public function testHomePage()
    {
        $this->dispatch('/');
        
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);
        $this->assertQuery('div.hero-unit');
    }

}