<?php 

namespace Hassansin\DBCart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {

	/**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{		
		
		$this->app->singleton('cart', function($app, $params){
			$instance_name = !empty($params['name']) ? $params['name'] : 'default';
			$model = config('cart.cart_model');			
			return $model::current($instance_name);
		});
		
		$this->publishes([
	        __DIR__.'/config/cart.php' => config_path('cart.php'),
	    ],'config');

	    $this->publishes([
	        __DIR__.'/database/migrations/' => database_path('migrations')
	    ], 'migrations');

	    $this->mergeConfigFrom(
	        __DIR__.'/config/cart.php', 'cart'
	    );
	}


	/**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['cart'];
    }
}