<?php 

namespace Hassansin\Cart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->publishes([
	        __DIR__.'/config/cart.php' => config_path('cart.php'),
	    ]);
	}
}