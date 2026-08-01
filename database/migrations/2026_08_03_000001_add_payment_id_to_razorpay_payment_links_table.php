<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('razorpay_payment_links', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->index()->after('razorpay_id');
        });
    }

    public function down()
    {
        Schema::table('razorpay_payment_links', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
    }
};
