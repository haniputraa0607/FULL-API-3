<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumBundlingToTransactionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->decimal('transaction_product_bundling_charged_central', 8, 2)->default(0)->after('transaction_product_note');
            $table->decimal('transaction_product_bundling_charged_outlet', 8, 2)->default(0)->after('transaction_product_note');
            $table->decimal('transaction_product_bundling_discount', 30, 2)->default(0)->after('transaction_product_note');
            $table->unsignedInteger('id_bundling_product')->nullable()->after('transaction_product_note');
            $table->unsignedInteger('id_transaction_bundling_product')->nullable()->after('transaction_product_note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dropColumn('transaction_product_bundling_charged_central');
            $table->dropColumn('transaction_product_bundling_charged_outlet');
            $table->dropColumn('transaction_product_bundling_discount');
            $table->dropColumn('id_bundling_product');
            $table->dropColumn('id_transaction_bundling_product');
        });
    }
}
