<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('countitem')->nullable($value = true);
            $table->string('sum')->nullable($value = true);
            $table->unsignedBigInteger('deliverytype');
            $table->foreign('deliverytype')->references('id')->on('type_deliveries');
            $table->string('name');
            $table->string('phone');
            $table->string('secondphone');
            $table->string('email');
            $table->string('endsum')->nullable($value = true);
            $table->unsignedBigInteger('typepayment');
            $table->foreign('typepayment')->references('id')->on('type_payments');
            $table->string('paid')->nullable($value = true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
