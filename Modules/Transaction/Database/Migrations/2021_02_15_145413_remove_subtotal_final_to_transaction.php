<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSubtotalFinalToTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_subtotal_final');
            $table->dropColumn('transaction_discount_all');
            $table->integer('transaction_gross')->nullable()->after('transaction_subtotal');
            $table->integer('transaction_discount_bill')->nullable()->after('transaction_discount');
            $table->integer('transaction_discount_item')->nullable()->after('transaction_discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('transaction_subtotal_final')->nullable()->after('transaction_subtotal');
            $table->integer('transaction_discount_all')->nullable()->after('transaction_discount');
            $table->dropColumn('transaction_gross');
            $table->dropColumn('transaction_discount_bill');
            $table->dropColumn('transaction_discount_item');
        });
    }
}
