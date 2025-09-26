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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('foreign_currency_id');
            $table->string('originating_currency');
            $table->decimal('exchange_rate', 18, 8);
            $table->decimal('surcharge_percentage', 5, 2);
            $table->decimal('foreign_amount', 18, 2);
            $table->decimal('originating_amount', 18, 2);
            $table->decimal('surcharge_amount', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->decimal('special_discount_percentage', 5, 2);
            $table->decimal('special_discount_amount', 18, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('foreign_currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['foreign_currency_id']);
        });
    }
};
