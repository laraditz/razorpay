<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('razorpay_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('status')->index();

            // longText, not json — guards against a JSON-column strict-validation
            // error if a payload ever contains non-JSON-safe content.
            $table->longText('payload')->nullable();

            $table->string('reference_id')->nullable()->index();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('razorpay_webhook_logs');
    }
};
