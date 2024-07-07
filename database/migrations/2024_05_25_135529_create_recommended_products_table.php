<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recommended_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('skindetail_id');

            $table->timestamps();

             //relationship product
            $table->foreign('product_id')->references('id')->on('products');

             //relationship customer
            $table->foreign('customer_id')->references('id')->on('customers');

              //relationship skindetail
            $table->foreign('skindetail_id')->references('id')->on('skin_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommended_products');
    }
};
