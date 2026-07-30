<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_id')->unique();
            $table->string('payment_id')->index();
            $table->string('status')->index();

            $table->unsignedInteger('amount');
            $table->string('currency', 3);

            $table->json('notes')->nullable();
            $table->string('receipt')->nullable();
            $table->string('speed_requested')->nullable();
            $table->string('speed_processed')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_refunds');
    }
};
