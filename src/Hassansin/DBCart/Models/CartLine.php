<?php

namespace Hassansin\DBCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartLine extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cart_lines';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['cart_id', 'product_id', 'quantity', 'unit_price'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];


    public function product()
    {
        return $this->belongsTo(config('cart.product_model'));
    }

    public function cart()
    {
        return $this->belongsTo(config('cart.cart_model'));
    }

    /*
    * Get Item orginal quantity before update
    *
    * @return integer
    */

    public function getOriginalQuantity(){
        return $this->original['quantity'];
    }

    /*
    * Get Item original price before update
    *
    * @return float
    */
    public function getOriginalUnitPrice(){
        return $this->original['unit_price'];
    }

    /*
    * Get Item price
    *
    * @return float
    */
    public function getPrice(){
        return number_format($this->quantity * $this->unit_price, 2) ;
    }

    /*
    * Get Item original price before update
    *
    * @return integer
    */
    public function getOriginalPrice(){
        return number_format($this->getOriginalQuantity() * $this->getOriginalUnitPrice(), 2) ;
    }

    public function getCartInstance(){
        $carts = app('cart_instances');
        $instance_name = null;
        foreach ($carts as $name => $cart) {
            if($cart->id === $this->cart_id){
                $instance_name = $name;
                break;
            }
        }
        return is_null($instance_name )? $instance_name : app('cart', ['name' => $instance_name]);
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        //when an item is created
        static::created(function($line){
            $cart = $line->getCartInstance() ?: $line->cart;            
            $cart->resetRelations();

            $cart->total_price = number_format($cart->total_price + $line->getPrice(), 2);
            $cart->item_count = $cart->item_count + $line->quantity;
            $cart->save();
        });

        //when an item is updated
        static::updated(function($line){
            $cart = $line->getCartInstance() ?: $line->cart;    
            $cart->resetRelations();

            $cart->total_price = number_format( $cart->total_price - $line->getOriginalPrice() + $line->getPrice(), 2 );
            $cart->item_count = $cart->item_count - $line->getOriginalQuantity() + $line->quantity;
            $cart->save();
        });

        //when item deleted
        static::deleted(function($line){            
            $cart = $line->getCartInstance() ?: $line->cart;    
            $cart->resetRelations();

            $cart->total_price = number_format($cart->total_price - $line->getPrice(), 2);
            $cart->item_count = $cart->item_count - $line->quantity;
            $cart->save();
        });
    }
}
