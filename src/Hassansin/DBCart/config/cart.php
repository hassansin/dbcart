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

    'product_model' => '', // App\Product::class

    /*
    |--------------------------------------------------------------------------
    | Auto Expire a cart
    |--------------------------------------------------------------------------
    |
    | Enable or disable auto expire a cart
    |
    */

    'expire_cart' => true,

    /*
    |--------------------------------------------------------------------------
    | Expire time
    |--------------------------------------------------------------------------
    |
    | Days after which a cart will be expired and deleted. Only if 'expire_cart' is true.
    |
    */
    'expire_after' => 7,

    /*
    |--------------------------------------------------------------------------
    | Delete Expired Cart
    |--------------------------------------------------------------------------
    |
    | Delete expired carts
    |
    */
    'delete_expired' => true, 

];
