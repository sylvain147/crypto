<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BuygList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buy_list', function (Blueprint $table){
            $table->id();
            $table->string('action');
            $table->float('rsi');
            $table->string('crypto');
            $table->float('price');
            $table->string('quantity');
            $table->float('crypto_before');
            $table->float('crypto_after');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
