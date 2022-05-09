<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionGroup extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_multiple_payments', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_balances', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_offlines', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_payment_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });

        Schema::table('transaction_vouchers', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->after('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->foreign('id_transaction_group')->on('transaction_groups')->references('id_transaction_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_multiple_payments', function (Blueprint $table) {
            $table->dropForeign('transaction_multiple_payments_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_balances', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_balances_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_ipay88s_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_manuals_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_midtrans_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_offlines', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_offlines_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_ovos_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_shopee_pays_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_payment_subscriptions', function (Blueprint $table) {
            $table->dropForeign('transaction_payment_subscriptions_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });

        Schema::table('transaction_vouchers', function (Blueprint $table) {
            $table->dropForeign('transaction_vouchers_id_transaction_group_foreign');
            $table->unsignedInteger('id_transaction')->nullable(false)->change();
            $table->dropColumn('id_transaction_group');
        });
    }
}
