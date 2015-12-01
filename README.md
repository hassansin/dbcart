# DBCart

Shopping Cart library for Laravel 5 that uses database instead of sessions to store carts.


## Features

* Cart for guest users
* Cart of logged in users
* Guest Cart is merged with User Cart when logged in
* Singleton Cart instance to avoid unnecessary database queries. But also possible to avoid signleton cart if needed.
* Built on top of Eloquent Model, so easily extendable and any eloquent method can be used.
* Multiple instances of cart
* Schedule expired carts for deletion [TODO]

## Installation

1. Edit your project's composer.json file to require hassansin/DBCart.

    ```js
    "require": {
        "hassansin/dbcart": "dev-master"
    }
    ```

2. Then install dependecies with composer command:

        composer update

3. Next, add a new provider to the providers array in config/app.php:

    ```php
    'providers' => [
        //...
        Hassansin\DBCart\CartServiceProvider::class,
        //...
    ],
    ```
    
4. Then, publish database migrations and run migration:

    ```sh
    php artisan vendor:publish --provider="Hassansin\DBCart\CartServiceProvider" --tag=migrations
    php artisan migrate
    ```

## Configuration

Optionally, you can publish package config file:

```sh
php artisan vendor:publish --provider="Hassansin\DBCart\CartServiceProvider" --tag=config
```
Now, update `config/cart.php` if required 

## Usage

#### Get Cart Instance:
Get the current cart instance. It returns a singleton cart instance:
```php
$cart = app('cart'); //using app() helper
```
or,

```php
$cart = App::make('cart');
```
alternatively, you can avoid singleton instance and use the model class to load the cart instance from database everytime:

```php
use Hassansin\DBCart\Models\Cart;
//...
$cart = Cart::current();
```

#### Add an Item: `$cart->addItem($attributes)`

```php
$cart->add([
    'product_id' => 1,
    'unit_price' => 10.5,
    'quantity' => 1
]); 
```

which is equivalent to `$cart->items()->create($attributes)`

#### Get Items: `$cart->items`

Since `$cart` is eloquent model instance, you can use any of the eloquent methods to get items

```php
$items = $cart->items // by dynamic property access
$items = $cart->items()->get()  
$items = $cart->items()->where('quantity', '=>', 2)->get()
```

#### Update an Item: `$cart->updateItem($where, $attributes)`

```php  
$cart->update([
    'id' => 2
], [
    'product_id' => 1,
    'unit_price' => 10.5,
    'quantity' => 1
]); 
```

which is equivalent to `$cart->items()->where($where)->first()->update($values)`

#### Remove an Item: `$cart->removeItem($where)`

```php
$cart->remove([
    'id' => 2
]); 
```
    
which is equivalent to `$cart->items()->where($where)->first()->delete()`

#### Clear Cart Items: `$cart->clear()`

Remove all items from the cart

```php
$cart->clear(); 
```    
#### Checkout cart: `$cart->checkout()`

This method only updates `status` and `placed_at` column values. 

```php    
$cart->checkout();
```

#### Get Cart Attributes

```php
$total_price = $cart->total_price; // cart total price
$item_count = $cart->item_count; // cart items count
$date_placed = $cart->placed_at; // returns Carbon instance
```
#### Cart Statuses

Supports several cart statuses:
* `active`: currently adding items to the cart
* `expired`: cart is expired, meaningful for session carts
* `pending`: checked out carts 
* `complete`: completed carts 

```php
use Hassansin\DBCart\Models\Cart;

// get carts based on their status: active/expired/pending/complete
$active_carts = Cart::active()->get();
$expired_carts = Cart::expired()->get();
$pending_carts = Cart::pending()->get();
$completed_carts = Cart::complete()->get();
```
#### Cart Instances

By default, cart instances are named as `default`. You can load other instances by providing a name:

```php
$cart = app('cart'); // default cart, same as: app('cart', [ 'name' => 'default'];
$sales_cart = app('cart', [ 'name' => 'sales'];
$wishlist = app('cart', [ 'name' => 'wishlist'];
```
or, without singleton carts:

```php
use Hassansin\DBCart\Models\Cart;
//...
$cart = Cart::current();
$sales_cart =  Cart::current('sales');
$wishlist =  Cart::current('wishlist');
```

To get carts other than `default`:

```php
$pending_sales_carts = Cart::instance('sales')->pending()->get();
```

#### Other features:

```php
$cart_user = $cart->user; // get cart user
```


## Extending Cart Model
Follow these steps to extend the cart model:

1. Create a model by extending `Hassansin\DBCart\Models\CartLine``:
    ```php    
    namespace App;
        
    use Hassansin\DBCart\Models\Cart as BaseCart;

    class Cart extends BaseCart
    {
        //override or add your methods here ...
        public function setTotalPriceAttribute(){
            return $this->total_price;
        }
        
    }
    ```
2. Update `cart_model` in `config/cart.php`
  
    ```php
    'cart_model' => App\Cart::class,
    ```
3. Now use either `App::make('cart')` or your new model class:

    ```php
    use App\Cart;
    //...
    $cart = Cart::current();    
    ```

You can also follow the above steps and create your own `CartLine` model by extending `Hassansin\DBCart\Models\CartLine`. Be sure to update `config/cart.php` to reflect your changes.
