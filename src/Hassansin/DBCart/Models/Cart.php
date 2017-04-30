<?php

namespace Hassansin\DBCart\Models;

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

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'total_price' => 'float',
    ];

    /**
    * Get the user that owns the cart.
    */
    public function user()
    {
        return $this->belongsTo(config('cart.user_model'));
    }

    /**
    * Get the items for the cart.
    */
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

    public function scopeUser($query, $user_id = null){
        $user_id = $user_id ?: config('cart.user_id');
        if ($user_id instanceof \Closure)
            $user_id = $user_id();
        return $query->where('user_id', $user_id);
    }

    public function scopeSession($query, $session_id = null){
        $session_id = $session_id ?: app('request')->session()->getId();
        return $query->where('session', $session_id);
    }

    public function setTotalPriceAttribute($value){
        $this->attributes['total_price'] = $value;
    }

    /**
     * Get the current cart instance
     *
     * @param  string  $instance_name
     * @return mixed
     */
    public static function current($instance_name = 'default', $save_on_demand = null){
        $save_on_demand = is_null($save_on_demand)? config('cart.save_on_demand', false): $save_on_demand;
        return static::init($instance_name, $save_on_demand);
    }

    /**
     * Initialize the cart
     * 
     * @param  string  $instance_name
     * @return mixed
     */
    public static function init($instance_name, $save_on_demand){        

        $request = app('request');
        $session_id = $request->session()->getId();        
        $user_id = config('cart.user_id');

        if ($user_id instanceof \Closure)
            $user_id = $user_id();

        //if user logged in
        if( $user_id ){
            $user_cart = static::active()->user()->where('name', $instance_name)->first();

            $session_cart_id = $request->session()->get('cart_'.$instance_name);
            $session_cart = is_null($session_cart_id)? null: static::active()->session($session_cart_id)->where('name', $instance_name)->first();

            switch (true) {
                
                case is_null($user_cart) && is_null($session_cart): //no user cart or session cart
                    $attributes = array(
                        'user_id' => $user_id,
                        'name' => $instance_name,
                        'status' => 'active'
                    );
                    if($save_on_demand)
                        $cart = new static($attributes);                        
                    else
                        $cart = static::create($attributes);

                    break;
                
                case !is_null($user_cart) && is_null($session_cart): //only user cart
                    $cart = $user_cart;
                    break;
                
                case is_null($user_cart) && !is_null($session_cart): //only session cart
                    $cart = $session_cart;
                    $cart->user_id = $user_id;
                    $cart->session = null;
                    $cart->save(); 
                    break;
                
                case !is_null($user_cart) && !is_null($session_cart): //both user cart and session cart exists                   
                    $session_cart->moveItemsTo($user_cart); //move items from session cart to user cart  
                    $session_cart->delete(); //delete session cart
                    $cart = $user_cart;
                    break;
            }            
            
            $request->session()->forget('cart_'.$instance_name); //no longer need it.
            return $cart;      
        } 
        //guest user, create cart with session id
        else{
            $attributes = array(
                'session' => $session_id,
                'name' => $instance_name,
                'status' => 'active'
            );
            $cart = static::firstOrNew($attributes);

            if(!$save_on_demand)
                $cart->save();

            //save current session id, since upon login session id will be regenerated
            //we will use this id to get back the cart before login
            $request->session()->put('cart_'.$instance_name, $session_id);
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
        static::deleting(function(Cart $cart) {
            $cart->items()->delete();
        });        
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
     * Set a cart as complete
     *
     */
    public function complete(){
        return $this->update('status', 'complete');
    }

    /**
     * Check if cart is empty
     *
     */
    public function isEmpty(){
        return $this->items->count() === 0;
    }

    public function hasItem($where){
        return !is_null($this->items()->where($where)->first());
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

    /**
     * Move Items to another cart instance
     *
     * @param Cart $cart
     */
    public function moveItemsTo(Cart $cart){
        $this->items()->update(['cart_id' => $cart->id] );                    
        $cart->item_count += $this->item_count;
        $cart->total_price += $this->total_price;
        $cart->save();

        $this->item_count = 0;
        $this->total_price = 0;
        return $this->save();
    }
    

}

