<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_id')->unique();
            $table->string('status')->index();

            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_paid')->nullable();
            $table->unsignedInteger('amount_due')->nullable();
            $table->string('currency', 3);

            $table->string('receipt')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->json('notes')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_orders');
    }
};
