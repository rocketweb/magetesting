<?php

class IndexControllerTest extends ControllerTestCase
{
    public function testHomePage()
    {
        $this->dispatch('/');
        
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);
        $this->assertQuery('.content');
    }
    
    public function testPartnersPage()
    {
        $this->dispatch('/partners');

        $this->assertController('index');
        $this->assertAction('partners');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Partners');
    }

    public function testPrivacyPage()
    {
        $this->dispatch('/privacy');

        $this->assertController('index');
        $this->assertAction('privacy');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Privacy Policy');
    }

    public function testTermsOfServicePage()
    {
        $this->dispatch('/terms-of-service');

        $this->assertController('index');
        $this->assertAction('terms-of-service');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Terms of Service');
    }

    public function testContactUsPage()
    {
        $this->dispatch('/contact-us');

        $this->assertController('index');
        $this->assertAction('contact-us');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Contact the Mage Testing Team');
    }

    public function testOurPlansPage()
    {
        $this->dispatch('/our-plans');

        $this->assertController('index');
        $this->assertAction('our-plans');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Plan Comparison and Pricing');
    }
}