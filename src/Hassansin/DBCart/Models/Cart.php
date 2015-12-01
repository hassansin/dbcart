<?php

namespace Hassansin\DBCart\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cart';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'session', 'name', 'status', 'total_price', 'item_count', 'placed_at', 'completed_at'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['placed_at', 'completed_at'];


    public function user()
    {
        return $this->belongsTo(config('cart.user_model'));
    }

    public function items(){
        return $this->hasMany(config('cart.cart_line_model'));
    }

    public function scopePending($query){
        return $query->where('status', 'pending' );
    }

    public function scopeCompleted($query){
        return $query->where('status', 'completed' );
    }

    public function scopeExpired($query){
        return $query->where('status', 'expired' );
    }

    public function scopeActive($query){
        return $query->where('status', 'active' );
    }

    public function scopeInstance($query, $instance_name = 'default'){
        return $query->where('name',  $instance_name);
    }

    /**
     * Get the current cart instance
     *
     * @param  string  $instance_name
     * @return mixed
     */
    public static function current($instance_name = 'default'){
        return static::init($instance_name);       
    }

    /**
     * Get the current cart instance
     * Initialize the cart
     * If no cart found for current user/session, then creates a new empty cart
     * @param  string  $instance_name
     * @return mixed
     */
    public static function init($instance_name){
        $request = \App::make('request');
        $sessionId = $request->session()->getId();
        
        //if user logged in
        if(Auth::check()){
            $userId = \Auth::id();

            //get active cart for current user
            $user_cart = static::active()->where('name', $instance_name)->where('user_id', $userId)->first();
            $session_cart = is_null($request->session()->get('cart_id'))? null: static::active()->where('name', $instance_name)->where('session', $request->session()->get('cart_id'))->first();

            switch (true) {
                //no user cart or session cart
                case is_null($user_cart) && is_null($session_cart) && !$check_only:
                    $cart = static::create(array(
                        'user_id' => $userId,
                        'name' => $instance_name,
                        'status' => 'active'
                    ));
                    break;
                //only user cart
                case !is_null($user_cart) && is_null($session_cart):
                    $cart = $user_cart;
                    break;
                //only session cart
                case is_null($user_cart) && !is_null($session_cart):
                    $cart = $session_cart;
                    $cart->user_id = $userId;
                    $cart->session = null;
                    $cart->save(); 
                    break;
                //both user cart and session cart exists
                case !is_null($user_cart) && !is_null($session_cart):
                    $cart = $user_cart;
                    //copy items from session cart to user cart
                    $session_cart->items()->update(['buyorder_id' => $user_cart->id] );                    
                    //delete session cart
                    $session_cart->delete();
                    break;
                default:
                    # code...
                    break;
            }            
            //no longer need it.
            $request->session()->forget('cart_id');
            return $cart;      
        } 
        //guest user, create cart with session id
        else{
            $cart = static::firstOrCreate(array(
                'session' => $sessionId,
                'name' => $instance_name,
                'status' => 'active'
            )); 

            //save current session id, since upon login session id will be regenerated
            //we will use this id to get back the cart before login
            $request->session()->put('cart_id', $sessionId);
            return $cart;
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        //delete line items
        static::deleting(function($cart) {
            $cart->items()->delete();
        });        
    }

    /**
     * Copy cart items to another cart
     *
     */
    public function copyTo(Cart $cart){
        $this->items()->update([ $this->items()->getForeignKey() => $cart->{$cart->primaryKey}] );
    }

    /**
     * If a change is made to items, then reset lazyloaded relations to reflect new changes
     *
     */
    public function resetRelations(){        
        foreach($this->relations as $key => $value){
            $this->getRelationshipFromMethod($key);
        }        
        return $this;        
    }

    /**
     * Add item to a cart
     *
     * @param  array $attributes
     */    
    public function addItem(array $attributes = []){        
        return $this->items()->create($attributes);                     
    }

    /**
     * remove item from a cart
     *     
     * @param  array $attributes
     */  
    public function removeItem(array $attributes = []){
        return $this->items()->where($attributes)->first()->delete();        
    }

    /**
     * update item in a cart
     *     
     * @param  array $attributes
     */
    public function updateItem(array $where, array $values){
        return $this->items()->where($where)->first()->update($values);
    }    


    /**
     * Cart checkout. 
     *
     */
    public function checkout(){
        return $this->update( ['status' => 'pending', 'placed_at' => Carbon::now()]);
    }    

    /**
     * Expires a cart
     *
     */
    public function expire(){
        return $this->update('status', 'expired');
    }

    /**
     * Empties a cart
     *
     */
    public function clear(){
        $this->items()->delete();
        $this->resetRelations()->updateTimestamps();
        $this->total_price = 0;
        $this->item_count = 0;
        return $this->save();        
    }

}
