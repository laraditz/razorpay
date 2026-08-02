<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('razorpay_payment_links', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_id')->unique();
            $table->string('payment_id')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->string('status')->index();

            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_paid')->nullable();
            $table->string('currency', 3);

            $table->string('reference_id')->nullable()->index();
            $table->text('description')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_contact')->nullable();

            $table->boolean('notify_sms')->default(false);
            $table->boolean('notify_email')->default(false);
            $table->boolean('reminder_enable')->default(false);
            $table->boolean('accept_partial')->default(false);
            $table->unsignedInteger('first_min_partial_amount')->nullable();

            $table->json('notes')->nullable();
            $table->text('callback_url')->nullable();
            $table->string('callback_method')->nullable();
            $table->text('short_url')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamp('expire_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_payment_links');
    }
};
