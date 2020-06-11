<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVoidReferenceIdToPaymentShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->string('refund_reference_id')->nullable()->after('redirect_url_http');
            $table->string('void_reference_id')->nullable()->after('refund_reference_id');
        });
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->string('refund_reference_id')->nullable()->after('redirect_url_http');
            $table->string('void_reference_id')->nullable()->after('refund_reference_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('refund_reference_id');
            $table->dropColumn('void_reference_id');
        });
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('refund_reference_id');
            $table->dropColumn('void_reference_id');
        });
    }
}
