<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_id')->unique();

            $table->unsignedInteger('amount');
            $table->unsignedInteger('fees')->nullable();
            $table->unsignedInteger('tax')->nullable();
            $table->string('utr')->nullable()->index();

            $table->string('status')->index();
            $table->timestamp('settled_at')->nullable();

            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_settlements');
    }
};
