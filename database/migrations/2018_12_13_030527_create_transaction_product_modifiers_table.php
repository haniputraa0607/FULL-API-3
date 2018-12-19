<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_modifiers', function (Blueprint $table) {
            $table->increments('id_transaction_product_modifier');
            $table->unsignedInteger('id_transaction_product');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_product_modifier');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_user');
            $table->string('type',50)->nullable()->default(null);
            $table->string('code',25);
            $table->string('text',100)->nullable()->default(null);
            $table->integer('qty')->default(1);
            $table->datetime('datetime')->nullable()->default(null);
            $table->string('trx_type')->nullable()->default(null);
            $table->string('sales_type')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('id_transaction_product', 'fk_transaction_product_modifiers_transaction_products')->references('id_transaction_product')->on('transaction_products')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_transaction_product_modifiers_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_transaction_product_modifiers_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product_modifier', 'fk_transaction_product_modifiers_product_modifiers')->references('id_product_modifier')->on('product_modifiers')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_transaction_product_modifiers_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_transaction_product_modifiers_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_product_modifiers');
    }
}
