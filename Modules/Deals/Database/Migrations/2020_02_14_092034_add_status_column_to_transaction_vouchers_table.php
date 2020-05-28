<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnToTransactionVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_vouchers', function (Blueprint $table) {
        	$table->enum('status',['success','failed'])->default('success')->after('deals_voucher_invalid');
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
        	$table->dropColumn('status');
        });
    }
}
