<?php
class Integration_Mail_ReminderBuyExtensionTest extends PHPUnit_Framework_TestCase
{
    protected $_mail;
    protected $_application;

    public function setUp() 
    {
        $this->_application = new Zend_Application(
            APPLICATION_ENV, 
            APPLICATION_PATH . '/configs/application.ini'
        );
        $this->_application->bootstrap(array('layout', 'config'));

        $this->_mail = new Integration_Mail_ReminderBuyExtension(); 
        parent::setUp();
    }
    
    public function testBuyExtensionLinkInEmailTest() 
    {
        $this->_mail->setup(
            $this->_application->getBootstrap()->getResource('config'),
            array(array(
                'firstname'    => 'John',
                'url'          => 'http://dev.magetesting.com',
                'domain'       => 'oEjUo0894',
                'extension_id' => '426',
                'name'         => 'Google Base Feed Generator',
                'email'        => 'john@example.com'
            ))
        );

        $this->assertContains(
            'http://dev.magetesting.com/payment/payment/pay-for/extension/source/extension/domain/oEjUo0894/id/426',
            quoted_printable_decode($this->_mail->getMail()->getBodyHtml(true)));
    }
}
