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

    public function testContactUsPagePost()
    {
        $this->request->setMethod('POST')
            ->setPost(array(
                'sender_name'    => 'PHPUnit tester',
                'sender_email' => 'gregor@rocketweb.com',
                'subject' => 'PHPUnit controller test',
                'message' => 'PHPUnit controller test main message'
            ));
        $this->dispatch('/contact-us');

        $this->assertController('index');
        $this->assertAction('contact-us');
        $this->assertRedirectTo('/contact-us');
        $this->assertResponseCode(302);

        $this->resetRequest()->resetResponse();

        $this->dispatch('/contact-us');

        $this->assertController('index');
        $this->assertAction('contact-us');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('strong', 'You successfully sent your message.');
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

    public function testAboutUs()
    {
        $this->dispatch('/about-us');

        $this->assertController('index');
        $this->assertAction('about-us');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'About Us');
    }

    public function testPartners()
    {
        $this->dispatch('/partners');

        $this->assertController('index');
        $this->assertAction('partners');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('h1', 'Partners');
    }

}