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
        return $this->belongsTo(config('cart.user_model'), config('cart.user_model_user_id', null));
    }

    /**
    * Get the items for the cart.
    */
    public function items(){
        return $this->hasMany(config('cart.cart_line_model'), config('cart.cart_line_model_cart_id', null));
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
        $user_id = $user_id ?: Auth::id();
        return $query->where('user_id', $user_id);
    }

    public function scopeSession($query, $session_id = null){
        $session_id = $session_id ?: app('request')->session()->getId();
        return $query->where('session', $session_id);
    }

    public function setTotalPriceAttribute($value){
        $this->attributes['total_price'] = number_format($value, 2) > 0 ? number_format($value, 2) : 0;        
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
        $sessionId = $request->session()->getId();
        
        //if user logged in
        if(Auth::check()){
            $userId = Auth::id();

            //get active cart for current user
            $user_cart = static::active()->user()->where('name', $instance_name)->first();

            //check if session cart exists
            $session_cart_id = $request->session()->get('cart_'.$instance_name);
            $session_cart = is_null($session_cart_id)? null: static::active()->session($session_cart_id)->where('name', $instance_name)->first();

            switch (true) {
                //no user cart or session cart
                case is_null($user_cart) && is_null($session_cart):
                    $attributes = array(
                        'user_id' => $userId,
                        'name' => $instance_name,
                        'status' => 'active'
                    );
                    if($save_on_demand)
                        $cart = new static($attributes);                        
                    else
                        $cart = static::create($attributes);

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
                    //move items from session cart to user cart                    
                    $session_cart->items()->update(['cart_id' => $user_cart->id] );                    
                    $cart->item_count += $session_cart->item_count;
                    $cart->total_price += $session_cart->total_price;
                    //delete session cart
                    $cart->save();
                    $session_cart->delete();
                    break;
                default:
                    # code...
                    break;
            }            
            //no longer need it.
            $request->session()->forget('cart_'.$instance_name);
            return $cart;      
        } 
        //guest user, create cart with session id
        else{
            $attributes = array(
                'session' => $sessionId,
                'name' => $instance_name,
                'status' => 'active'
            );
            $cart = static::firstOrNew($attributes);

            if(!$save_on_demand)
                $cart->save();

            //save current session id, since upon login session id will be regenerated
            //we will use this id to get back the cart before login
            $request->session()->put('cart_'.$instance_name, $sessionId);
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
     * Copy cart items to another cart [TODO]
     *
     */
    public function copyItemsTo(Cart $cart){
        if(!$cart->exists){
            $cart->save();
        }
        //$this->items()->update([ $this->items()->getForeignKey() => $cart->{$cart->primaryKey}] );
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
