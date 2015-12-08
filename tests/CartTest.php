<?php

use Hassansin\DBCart\Models\Cart;
use Hassansin\DBCart\Models\CartLine;

class CartTest extends Orchestra\Testbench\TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/../src/Hassansin/DBCart/database/migrations'),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [Hassansin\DBCart\CartServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
    
    public function testCreateItem()
    {
        $cart = Cart::create();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 1
        ]); 
        $this->assertCount(1, $cart->items);
    }
}
