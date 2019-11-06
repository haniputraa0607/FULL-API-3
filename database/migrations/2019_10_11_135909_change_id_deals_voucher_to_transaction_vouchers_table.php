<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdDealsVoucherToTransactionVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_vouchers', function (Blueprint $table) {
            $table->integer( 'id_deals_voucher' )->nullable()->unsigned()->change();
            $table->foreign('id_deals_voucher')->references('id_deals_voucher')->on('deals_vouchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
