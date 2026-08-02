<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_payments', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_id')->unique();
            $table->string('order_id')->nullable()->index();
            $table->string('status')->index();
            $table->string('method')->nullable();

            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_refunded')->nullable();
            $table->string('currency', 3);
            $table->boolean('captured')->default(false);

            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->json('notes')->nullable();

            $table->unsignedInteger('fee')->nullable();
            $table->unsignedInteger('tax')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_description')->nullable();

            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_payments');
    }
};
