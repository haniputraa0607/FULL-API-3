<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldTransactionDealsManualPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_payment_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_bank')->nullable()->after('id_manual_payment_method');
            $table->unsignedInteger('id_bank_method')->nullable()->after('id_bank');
            $table->unsignedInteger('id_manual_payment')->nullable()->after('id_manual_payment_method');
            
            $table->foreign('id_bank', 'fk_transaction_payment_manuals_bank_deals')->references('id_bank')->on('banks')->onUpdate('CASCADE')->onDelete('CASCADE');
            
            $table->foreign('id_bank_method', 'fk_transaction_payment_manuals_bank_method_deals')->references('id_bank_method')->on('bank_methods')->onUpdate('CASCADE')->onDelete('CASCADE');
            
            $table->foreign('id_manual_payment', 'fk_transaction_payment_manuals_manual_payment_deals')->references('id_manual_payment')->on('manual_payments')->onUpdate('CASCADE')->onDelete('CASCADE');
        });

        DB::statement('ALTER TABLE `deals_payment_manuals` CHANGE `id_manual_payment_method` `id_manual_payment_method` INT(10) UNSIGNED NULL DEFAULT NULL;');


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_payment_manuals', function (Blueprint $table) {
            $table->dropForeign('fk_transaction_payment_manuals_bank_deals');
            $table->dropColumn('id_bank');
            $table->dropForeign('fk_transaction_payment_manuals_bank_method_deals');
            $table->dropColumn('id_bank_method');
            $table->dropForeign('fk_transaction_payment_manuals_manual_payment_deals');
            $table->dropColumn('id_manual_payment');
        });

        DB::statement('ALTER TABLE `deals_payment_manuals` CHANGE `id_manual_payment_method` `id_manual_payment_method` INT(10) UNSIGNED NULL;');
    }
}
