<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddErrcodeAndErrReason extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->string('errcode')->nullable()->after('void_reference_id');
            $table->string('err_reason')->nullable()->after('errcode');
        });
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->string('errcode')->nullable()->after('void_reference_id');
            $table->string('err_reason')->nullable()->after('errcode');
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
            $table->dropColumn('errcode');
            $table->dropColumn('err_reason');
        });
        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->dropColumn('errcode');
            $table->dropColumn('err_reason');
        });
    }
}
