<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_settlement_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('entity_id');
            $table->string('type')->index();
            $table->string('settlement_id')->nullable()->index();

            $table->unsignedInteger('debit')->nullable();
            $table->unsignedInteger('credit')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('fee')->nullable();
            $table->unsignedInteger('tax')->nullable();

            $table->boolean('settled')->default(false);
            $table->boolean('on_hold')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('transaction_created_at')->nullable();

            $table->string('payment_id')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->string('order_receipt')->nullable();
            $table->string('settlement_utr')->nullable()->index();
            $table->string('dispute_id')->nullable();

            $table->string('method')->nullable();
            $table->string('card_network')->nullable();
            $table->string('card_issuer')->nullable();
            $table->string('card_type')->nullable();

            $table->text('description')->nullable();
            $table->json('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('credit_type')->nullable();

            $table->timestamps();

            $table->unique(['entity_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_settlement_transactions');
    }
};
