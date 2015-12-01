<?php 

namespace Hassansin\DBCart;

use Illuminate\Support\ServiceProvider;

class Singleton {

	protected $instances = [];

	public static function get(){

	}

}

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

		$this->app->instance('cart_instances', [] );

		$this->app->bind('cart', function($app, $params){
			$instance_name = !empty($params['name']) ? $params['name'] : 'default';
			$cart_instances = $app['cart_instances'];

			if(empty($cart_instances[$instance_name])){
				$model = config('cart.cart_model');	
				$cart_instances[$instance_name] = $model::current($instance_name);				
				$app['cart_instances'] = $cart_instances;				
			}
			return $app['cart_instances'][$instance_name];			
		});

		/*$this->app->singleton('cart', function($app, $params){
			$instance_name = !empty($params['name']) ? $params['name'] : 'default';
			$model = config('cart.cart_model');			
			return $model::current($instance_name);
		});*/
		
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