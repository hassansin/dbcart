<?php

namespace Hassansin\DBCart\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class CartCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cleanup expired carts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //expire carts whose session is expired
        if(config('cart.expire_cart', false)){
            $session_lifetime = config('session.lifetime');
            $cart_model = config('cart.cart_model');
            $date = Carbon::now()->subMinutes($session_lifetime);
            $cart_model::where('updated_at', '<', $date)
                ->where('session', '!=', '')
                ->where('status','active')
                ->update(['status' => 'expired']);
        }

        //delete expired carts
        if(config('cart.delete_expired', false)){
            $cart_model = config('cart.cart_model');
            $cart_line_model = config('cart.cart_line_model');
            $cart_ids = $cart_model::where('status','expired')->pluck('id');

            $cart_line_model::whereIn('cart_id', $cart_ids->toArray())->delete();
            $cart_model::where('status','expired')->delete();
        }
    }
}
