<?php

namespace Hassansin\Cart\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{

    protected $searchableColumns = [ 'user_id', 'orderdate'];

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
    protected $fillable = ['user_id', 'session', 'status', 'orderdate'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    protected $userClass = 'App\Models\User';

    protected $itemClass = 'App\Models\CartLine';

    public function user()
    {
        return $this->belongsTo($this->userClass);
    }

    public function items(){
        return $this->hasMany($this->itemClass);
    }

    public function scopeActive($query){
        return $query->where('status', 'active' );
    }

    public function scopeCurrent($query){
        $request = \App::make('request');
        $sessionId = $request->session()->getId();
        
        //if user logged in
        if(Auth::check()){
            $userId = \Auth::id();

            //get active cart for current user
            $user_cart = static::active()->where('user_id', $userId)->first();
            $session_cart = is_null($request->session()->get('cart_id'))? null: static::active()->where('session', $request->session()->get('cart_id'))->first();

            switch (true) {
                //no user cart or session cart
                case is_null($user_cart) && is_null($session_cart):
                    $cart = static::create(array(
                        'user_id' => $userId,
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
                    //TODO: Copy session cart items to user cart
                    $session_cart->delete();
                    break;
                default:
                    # code...
                    break;
            }            
            //no longer need it.
            $request->session()->forget('cart_id');
            return $query->where('user_id', $userId);      
        } 

        //guest user, create cart with session id
        else{
            $cart = static::firstOrCreate(array(
                'session' => $sessionId,
                'status' => 'active'
            )); 

            //save current session id, since upon login session id will be regenerated
            //we will use this id to get back the cart before login
            $request->session()->put('cart_id', $sessionId);
            return $query->where('session', $sessionId);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        static::deleting(function($order) { // before delete() method call this
            $order->items()->delete();
        });
    }


    public function clear(){
        $this->items()->delete();
    }

    public function destroy(){
        $this->delete();
    }
}
