<?php

namespace Hassansin\DBCart\Models;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'unit_price' => 'float',
    ];

    /**
    * Get the product record associated with the item.
    */
    public function product()
    {
        return $this->belongsTo(config('cart.product_model'), 'product_id');
    }

    /**
    * Get the cart that owns the item.
    */
    public function cart()
    {
        return $this->belongsTo(config('cart.cart_model'), 'cart_id');
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
        return $this->quantity * $this->unit_price;
    }

    /*
    * Get Item original price before update
    *
    * @return integer
    */
    public function getOriginalPrice(){
        return $this->getOriginalQuantity() * $this->getOriginalUnitPrice();
    }

    /*
    * Get the singleton cart of this line item.
    *
    */
    public function getCartInstance(){
        $carts = app('cart_instances');        
        foreach ($carts as $name => $cart) {
            if($cart->id === $this->cart_id){
                return $cart;
            }
        }
        return null;
    }

    /*
    * Move this item to another cart
    *
    * @param Cart $cart    
    */
    public function moveTo(Cart $cart){
        $this->delete(); // delete from own cart
        return $cart->items()->create($this->attributes);
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        //when an item is created
        static::created(function(CartLine $line){
            $cart = $line->getCartInstance() ?: $line->cart;            
            $cart->resetRelations();

            $cart->total_price = $cart->total_price + $line->getPrice();
            $cart->item_count = $cart->item_count + $line->quantity;
            $cart->save();
        });

        //when an item is updated
        static::updated(function(CartLine $line){
            $cart = $line->getCartInstance() ?: $line->cart;    
            $cart->resetRelations();

            $cart->total_price = $cart->total_price - $line->getOriginalPrice() + $line->getPrice();
            $cart->item_count = $cart->item_count - $line->getOriginalQuantity() + $line->quantity;
            $cart->save();
        });

        //when item deleted
        static::deleted(function(CartLine $line){            
            $cart = $line->getCartInstance() ?: $line->cart;    
            $cart->resetRelations();

            $cart->total_price = $cart->total_price - $line->getPrice();
            $cart->item_count = $cart->item_count - $line->quantity;
            $cart->save();
        });
    }
}

