<?php

use Hassansin\DBCart\Models\Cart;
use Hassansin\DBCart\Models\CartLine;

class CartTest extends Orchestra\Testbench\TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->loadMigrationsFrom([
            '--database' => 'testbench',
            '--path' => realpath(__DIR__.'/../src/Hassansin/DBCart/database/migrations'),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [Hassansin\DBCart\CartServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'DBCart' => Hassansin\DBCart\Facades\Cart::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('cart.expire_cart', true);
        $app['config']->set('cart.delete_expired', true);
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['request']->setLaravelSession($app['session']->driver('array'));
    }

    public function testCreateItem()
    {
        $cart = Cart::current();
        DB::enableQueryLog();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $this->assertCount(3, DB::getQueryLog());
        $this->assertEquals(2, $cart->item_count);
        $this->assertCount(1, $cart->items);
        $this->assertCount(1, $cart->items()->get());
        $this->assertCount(1, $cart->items()->where('quantity', '=', 2)->get());
        $this->assertCount(1, $cart->items()->where('unit_price', '>=', 1)->get());
    }

    public function testCreateDuplicateItem()
    {
        $cart = Cart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        DB::enableQueryLog();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 3
        ]);
        $this->assertCount(3, DB::getQueryLog());
        $this->assertEquals(5, $cart->item_count);
        $this->assertEquals(52.5, $cart->total_price);
        $this->assertCount(1, $cart->items);
        $this->assertCount(1, $cart->items()->get());
        $this->assertCount(1, $cart->items()->where('quantity', '=', 5)->get());
        $this->assertCount(1, $cart->items()->where('unit_price', '>=', 1)->get());
    }
    public function testRemoveItem(){
        $cart = Cart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $this->assertEquals(2, $cart->item_count);
        $this->assertCount(1, $cart->items);
        $this->assertCount(1, $cart->items()->get());
        $this->assertCount(1, $cart->items()->where('quantity', '=', 2)->get());
        $this->assertCount(1, $cart->items()->where('unit_price', '>=', 1)->get());
        $this->assertEquals(21, $cart->total_price);
        $this->assertFalse($cart->isEmpty());

        DB::enableQueryLog();
        $cart->removeItem(['product_id' => 1]);
        $this->assertCount(3, DB::getQueryLog());
        $this->assertEquals(0, $cart->item_count);
        $this->assertCount(0, $cart->items()->get());
        $this->assertCount(0, $cart->items);
        $this->assertTrue($cart->isEmpty());
    }
    public function testUpdateItem(){
        $cart = Cart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $this->assertEquals(2, $cart->item_count);
        $this->assertCount(1, $cart->items()->get());
        $this->assertCount(1, $cart->items);
        $this->assertEquals(10.5, $cart->items->first()->unit_price);
        $this->assertCount(1, $cart->items()->where('quantity', '=', 2)->get());
        $this->assertCount(1, $cart->items()->where('unit_price', '>=', 1)->get());
        $this->assertEquals(21, $cart->total_price);

        DB::enableQueryLog();
        $cart->updateItem(['product_id' => 1], ['unit_price' => 15.5, 'quantity' => 3]);
        $this->assertCount(3, DB::getQueryLog());

        $this->assertCount(1, $cart->items()->get());
        $this->assertCount(1, $cart->items);
        $this->assertEquals(15.5, $cart->items->first()->unit_price);
        $this->assertEquals(3, $cart->item_count);
        $this->assertEquals(46.5, $cart->total_price);
    }

    public function testMoveItemsToEmptyCart()
    {
        $cart1 = app('cart', ['name' => 'cart1']);
        $cart2 = app('cart', ['name' => 'cart2']);
        $cart1->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $cart1->addItem([
            'product_id' => 2,
            'unit_price' => 11.5,
            'quantity' => 1
        ]);
        $this->assertFalse($cart1->isEmpty());
        $this->assertTrue($cart2->isEmpty());
        $this->assertCount(2, $cart1->items()->get());
        $this->assertCount(0, $cart2->items()->get());
        $this->assertEquals(3, $cart1->item_count);
        $this->assertEquals(32.5, $cart1->total_price);
        $this->assertEquals(0, $cart2->item_count);
        $this->assertEquals(0, $cart2->total_price);

        DB::enableQueryLog();
        $cart1->moveItemsTo($cart2);
        $this->assertCount(5, DB::getQueryLog());
        $this->assertTrue($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(0, $cart1->items()->get());
        $this->assertCount(2, $cart2->items()->get());
        $this->assertEquals(0, $cart1->item_count);
        $this->assertEquals(0, $cart1->total_price);
        $this->assertEquals(3, $cart2->item_count);
        $this->assertEquals(32.5, $cart2->total_price);
    }
    public function testMoveItemsToNonEmptyCart()
    {
        $cart1 = app('cart', ['name' => 'cart1']);
        $cart2 = app('cart', ['name' => 'cart2']);
        $cart1->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $cart1->addItem([
            'product_id' => 2,
            'unit_price' => 11.5,
            'quantity' => 1
        ]);

        $cart2->addItem([
            'product_id' => 3,
            'unit_price' => 10,
            'quantity' => 1
        ]);

        $this->assertFalse($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(2, $cart1->items()->get());
        $this->assertCount(1, $cart2->items()->get());
        $this->assertEquals(3, $cart1->item_count);
        $this->assertEquals(32.5, $cart1->total_price);
        $this->assertEquals(1, $cart2->item_count);
        $this->assertEquals(10, $cart2->total_price);

        DB::enableQueryLog();
        $cart1->moveItemsTo($cart2);
        $this->assertCount(5, DB::getQueryLog());
        $this->assertTrue($cart1->isEmpty());
        $this->assertTrue($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(0, $cart1->items()->get());
        $this->assertCount(3, $cart2->items()->get());
        $this->assertEquals(0, $cart1->item_count);
        $this->assertEquals(0, $cart1->total_price);
        $this->assertEquals(4, $cart2->item_count);
        $this->assertEquals(42.5, $cart2->total_price);
    }

    public function testMoveItemsToNonEmptyCartWithDuplicateProduct()
    {
        $cart1 = app('cart', ['name' => 'cart1']);
        $cart2 = app('cart', ['name' => 'cart2']);
        $cart1->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $cart1->addItem([
            'product_id' => 2,
            'unit_price' => 11.5,
            'quantity' => 1
        ]);

        $cart2->addItem([
            'product_id' => 1,
            'unit_price' => 11.5,
            'quantity' => 1
        ]);

        $this->assertFalse($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(2, $cart1->items()->get());
        $this->assertCount(1, $cart2->items()->get());
        $this->assertEquals(3, $cart1->item_count);
        $this->assertEquals(32.5, $cart1->total_price);
        $this->assertEquals(1, $cart2->item_count);
        $this->assertEquals(11.5, $cart2->total_price);

        $cart1->moveItemsTo($cart2);
        $this->assertFalse($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(1, $cart1->items()->get());
        $this->assertCount(2, $cart2->items()->get());
        $this->assertEquals(2, $cart1->item_count);
        $this->assertEquals(21, $cart1->total_price);
        $this->assertEquals(2, $cart2->item_count);
        $this->assertEquals(23, $cart2->total_price);
    }

    public function testMoveEmptyCart(){
        $cart1 = app('cart', ['name' => 'cart1']);
        $cart2 = app('cart', ['name' => 'cart2']);

        DB::enableQueryLog();
        $cart1->moveItemsTo($cart2);
        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($cart1->isEmpty());
        $this->assertTrue($cart2->isEmpty());
    }

    public function testMoveSingleItemToEmptyCart()
    {
        $cart1 = app('cart', ['name' => 'cart1']);
        $cart2 = app('cart', ['name' => 'cart2']);
        $item = $cart1->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $cart1->addItem([
            'product_id' => 2,
            'unit_price' => 11.5,
            'quantity' => 1
        ]);
        $this->assertFalse($cart1->isEmpty());
        $this->assertTrue($cart2->isEmpty());
        $this->assertCount(2, $cart1->items()->get());
        $this->assertCount(0, $cart2->items()->get());
        $this->assertEquals(3, $cart1->item_count);
        $this->assertEquals(32.5, $cart1->total_price);
        $this->assertEquals(0, $cart2->item_count);
        $this->assertEquals(0, $cart2->total_price);
        $this->assertEquals($item->cart->id, $cart1->id);

        $item = $item->moveTo($cart2);
        $this->assertFalse($cart1->isEmpty());
        $this->assertFalse($cart2->isEmpty());
        $this->assertCount(1, $cart1->items()->get());
        $this->assertCount(1, $cart2->items()->get());
        $this->assertEquals(1, $cart1->item_count);
        $this->assertEquals(11.5, $cart1->total_price);
        $this->assertEquals(2, $cart2->item_count);
        $this->assertEquals(21, $cart2->total_price);
        $this->assertEquals($item->cart->id, $cart2->id);
    }
    public function testIsEmpty(){
        $cart = Cart::current();
        DB::enableQueryLog();
        $this->assertTrue($cart->isEmpty());
        $this->assertCount(0, DB::getQueryLog());
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);

        $this->assertFalse($cart->isEmpty());
    }

    public function testHasItem(){
        $cart = Cart::current();
        DB::enableQueryLog();
        $this->assertFalse($cart->hasItem(['product_id' => 1]));
        $this->assertCount(1, DB::getQueryLog());
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);

        $this->assertTrue($cart->hasItem(['product_id' => 1]));
    }

    public function testUnknownAttribute(){
        $cart = Cart::current();
        $cart->addItem([
            'foo' => 1,
            'product_id' => 2,
            'quantity' => 1,
            'unit_price' => 0,
        ]);
        $this->assertFalse($cart->hasItem(['foo' => 1]));
    }

    public function testClear(){
        $cart = Cart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);

        DB::enableQueryLog();
        $cart->clear();
        $this->assertCount(2, DB::getQueryLog());
    }
    public function testCartStatuses(){
        $cart = Cart::current();
        $this->assertCount(1, Cart::active()->get());
        $this->assertCount(0, Cart::expired()->get());
        $this->assertCount(0, Cart::pending()->get());
        $this->assertCount(0, Cart::completed()->get());

        DB::enableQueryLog();
        $cart->checkout();
        $this->assertCount(1, DB::getQueryLog());
        $this->assertCount(0, Cart::active()->get());
        $this->assertCount(0, Cart::expired()->get());
        $this->assertCount(1, Cart::pending()->get());
        $this->assertCount(0, Cart::completed()->get());

        $cart->complete();
        $this->assertCount(0, Cart::active()->get());
        $this->assertCount(0, Cart::expired()->get());
        $this->assertCount(0, Cart::pending()->get());
        $this->assertCount(1, Cart::completed()->get());

        $cart->expire();
        $this->assertCount(0, Cart::active()->get());
        $this->assertCount(1, Cart::expired()->get());
        $this->assertCount(0, Cart::pending()->get());
        $this->assertCount(0, Cart::completed()->get());
    }

    public function testInstance(){
        $cart = Cart::current("cart1");
        $this->assertEquals($cart->id, Cart::instance("cart1")->first()->id);

        $cart = Cart::current("cart2");
        $this->assertEquals($cart->id, Cart::instance("cart2")->first()->id);
    }

    public function testCartDelete(){
        $cart = Cart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        DB::enableQueryLog();
        $cart->delete();
        $this->assertCount(2, DB::getQueryLog());
    }

    public function testFacade(){
        $cart = DBCart::current();
        $this->assertEquals(1, $cart->id);
    }

    public function testCleanup(){
        $cart = DBCart::current();
        $cart->addItem([
            'product_id' => 1,
            'unit_price' => 10.5,
            'quantity' => 2
        ]);
        $cart->expire();

        $cmd = new Hassansin\DBCart\Console\Commands\CartCleanup();
        DB::enableQueryLog();
        $cmd->handle();
        $this->assertCount(4, DB::getQueryLog());
    }

    public function testScopeUser(){
        $cart = DBCart::current();
        $cart->user_id = 100;
        $cart->save();
        $this->assertEquals(null, Cart::active()->user()->first());
        $this->assertEquals(null, Cart::active()->user(200)->first());
        $this->assertEquals($cart->id, Cart::active()->user(100)->first()->id);
    }

    public function testSession(){
        $cart = DBCart::current();
        $cart2 = DBCart::current('another');
        $this->assertEquals($cart->id, Cart::session()->first()->id);
        $this->assertCount(2, Cart::session()->get());
    }
}
