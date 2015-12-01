<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('session');
            $table->string('name');
            $table->string('status', 20);
            $table->decimal('total_price');
            $table->integer('item_count');
            $table->timestamp('placed_at');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index('name');
            $table->index(['user_id', 'status']);
            $table->index(['session','status']);            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cart');
    }
}
