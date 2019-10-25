<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealsVoucherInvalidToTransactionVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_vouchers', function($table) {
            $table->string('deals_voucher_invalid',100)->nullable()->after('id_deals_voucher');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_vouchers', function (Blueprint $table) {
            $table->dropColumn('deals_voucher_invalid');
        });
    }
}
