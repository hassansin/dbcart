# DBCart

Shopping Cart library for Laravel 5. It stores cart instances in database instead of session.


## Features

* Cart for guest users
* Cart of logged in users
* Guest Cart is merged with User Cart when logged in
* Singleton Cart instance to avoid unnecessary database queries
* Built on top of Eloquent Model, so easily extendable and all eloquent methods can be used.

## Installation

Edit your project's composer.json file to require hassansin/DBCart.
    

        "require": {
            " hassansin/DBCart": "dev-master"
        }
Then install dependecies with composer command:

    composer update

Next, add a new provider to the providers array in config/app.php:

        'providers' => [
            //...
            Hassansin\DBCart\CartServiceProvider::class,
            //...
        ],

Then, publich database migrations and run migration:

    php artisan vendor:publish --provider="Hassansin\DBCart\CartServiceProvider" --tag=migrations
    php artisan migrate


## Configuration

Optionally, you can publish package config files:

    php artisan vendor:publish --provider="Hassansin\DBCart\CartServiceProvider" --tag=migrations
    
Now, update `config/cart.php` to 

## Usage



#### Get Cart Instance:
Get the current cart instance. It returns a singleton cart:

    $cart = app('cart');
    
alternatively, you can use the model class to load the cart instance from database:

    use Hassansin\DBCart\Models\Cart;
    
    //...
    
    $cart = Cart::current();

#### Add an Item: `$cart->addItem($attributes)`
    
    $cart->add([
        'product_id' => 1,
        'unit_price' => 10.5,
        'quantity' => 1
    ]); 
    
which is equivalent to `$cart->items()->create($attributes)`

#### Get Items: `$cart->items`

Since `$cart` is eloquent model instance, you can use any of the eloquent methods to get items
    
    $items = $cart->items // by dynamic property access
    $items = $cart->items()->get()  
    $items = $cart->items()->where('quantity', '=>', 2)->get()
    
#### Update an Item: `$cart->updateItem($where, $attributes)`
    
    $cart->update([
        'id' => 2
    ]
    ,[
        'product_id' => 1,
        'unit_price' => 10.5,
        'quantity' => 1
    ]); 
    
which is equivalent to `$cart->items()->where($where)->first()->update($values)`

#### Remove an Item: `$cart->removeItem($where)`
    
    $cart->remove([
        'id' => 2
    ]); 
    
which is equivalent to `$cart->items()->where($where)->first()->delete()`

#### Clear Cart Items: `$cart->clear()`

Remove all items from the cart
    
    $cart->clear(); 
    
#### Checkout cart: `$cart->checkout()`

This method only updates `status` and `placed_at` column values. 
    
    $cart->checkout();