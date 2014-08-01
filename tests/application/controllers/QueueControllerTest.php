<?php
require_once realpath(dirname(__FILE__) . '/../../ControllerTestCase.php');

class QueueControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createFakeUser();
        $this->loginUser($this->_userData['login'], $this->_userData['password']);
    }

    public function testAddStore()
    {
        $this->dispatch('/queue/add');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Add New Magento Store');
    }

    public function testAddCleanStore()
    {
        $this->dispatch('/queue/add-clean');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Install a Clean Magento Demo Store');
    }

    public function testAddCustomStore()
    {
        $this->dispatch('/queue/add-custom');

        $this->assertNotRedirect();
        $this->assertQueryContentContains('h1', 'Import your Magento store');
    }

    public function testAddCleanStoreToQueue()
    {
        $store = array(
            'description' => 'PHPUnit test description',
            'do_hourly_db_revert' => 0,
            'edition' => 'CE',
            'sample_data' => 0,
            'storeAdd' => '',
            'store_name' => 'PHPUnit test name',
            'version' => 14
        );

        $this->request->setMethod('POST')
            ->setPost($store);
        $this->dispatch('/queue/add-clean');

        $this->assertController('queue');
        $this->assertAction('add-clean');
        $this->assertRedirect();
        $this->assertResponseCode(302);

        $this->resetRequest()->resetResponse();

        $this->dispatch('/user/dashboard');

        $this->assertController('user');
        $this->assertAction('dashboard');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('strong', 'New installation added to queue');
    }

    public function testAddCustomStoreToQueue()
    {
        $store = array(
            'custom_file' => '',
            'custom_host' => '192.168.100.152',
            'custom_login' => 'phpunit',
            'custom_pass' => 'phpunit',
            'custom_port' => 22,
            'custom_protocol' => 'ssh',
            'custom_remote_path' => '/site/phpunit/',
            'custom_sql' => '/site/phpunit/dump.sql',
            'description' => 'PHPUnit custom store',
            'do_hourly_db_revert' => 0,
            'edition' => 'CE',
            'input-radio' => 'remote_path',
            'storeAdd' => '',
            'store_name' => 'PHPUnit custom store',
            'version' => 'CE14'
        );

        $this->request->setMethod('POST')
            ->setPost($store);
        $this->dispatch('/queue/add-custom');

        $this->assertController('queue');
        $this->assertAction('add-custom');
        $this->assertRedirect();
        $this->assertResponseCode(302);

        $this->resetRequest()->resetResponse();

        $this->dispatch('/user/dashboard');

        $this->assertController('user');
        $this->assertAction('dashboard');
        $this->assertNotRedirect();
        $this->assertResponseCode(200);

        $this->assertQueryContentContains('strong', 'You have successfully added your custom store to queue.');
    }




}
