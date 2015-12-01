'Provider' =>
Hassansin\Cart\CartServiceProvider::class,

Aliases =>
'Cart'      => Hassansin\Cart\Facades\Cart::class

php artisan vendor:publish --provider="Hassansin\Cart\CartServiceProvider" --tag=config

php artisan vendor:publish --provider="Hassansin\Cart\CartServiceProvider" --tag=migrations