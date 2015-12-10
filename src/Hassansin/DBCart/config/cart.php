<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Cart Model
    |--------------------------------------------------------------------------
    |
    | If you extend the  Hassansin\DBCart\Models\Cart class then specify child 
    | class namespace here
    |
    */

    'cart_model' => Hassansin\DBCart\Models\Cart::class,

    
    /*
    |--------------------------------------------------------------------------
    | Cart Line Model
    |--------------------------------------------------------------------------
    |
    | Cart Line Model to associate with Cart orders
    |
    */

    'cart_line_model' => Hassansin\DBCart\Models\CartLine::class,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
	| User Model to associate with Cart orders
    |
    */

    'user_model' => App\User::class,

    /*
    |--------------------------------------------------------------------------
    | Product Model
    |--------------------------------------------------------------------------
    |
	| Product Model to associate with Cart Lines
    |
    */

    'product_model' => App\Product::class,

    /*
    |--------------------------------------------------------------------------
    | User Id Closure
    |--------------------------------------------------------------------------
    |
    | Closure to find logged in user id; Return null/false if not logged in
    |
    */

    'user_id' => function(){
        return Auth::id();
    },

    /*
    |--------------------------------------------------------------------------
    | Save on demand
    |--------------------------------------------------------------------------
    |
    | When set to true, if a cart doesn't exist on database you'll be given a
    | new instance of cart object. You have to manually save the cart to DB by
    | $cart->save() . Useful if you want to avoid creating unnecessary empty carts     
    | in database. But be sure to save cart before adding items
    |
    */

    'save_on_demand' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto Expire a cart
    |--------------------------------------------------------------------------
    |
    | Enable or disable auto expire a cart. Only applicable for session carts.
    | When set to true, cart status will be set to 'expired' after session 'lifetime' 
    | 
    | Needs Laravel task scheduler to be started: http://laravel.com/docs/master/scheduling
    */

    'expire_cart' => true,

    /*
    |--------------------------------------------------------------------------
    | Delete Expired Cart
    |--------------------------------------------------------------------------
    |
    | Delete expired carts. If set to true, all expired carts will be deleted.
    |
    | Needs Laravel task scheduler to be started: http://laravel.com/docs/master/scheduling
    */
    'delete_expired' => true, 

    /*
    |--------------------------------------------------------------------------
    | Schedule Frequency
    |--------------------------------------------------------------------------
    |
    | How often the scheduled command would run
    |
    */
    'schedule_frequency' => 'hourly', 

];
