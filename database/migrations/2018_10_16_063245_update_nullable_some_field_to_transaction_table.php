<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNullableSomeFieldToTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement('ALTER TABLE transactions CHANGE trasaction_payment_type trasaction_payment_type ENUM("Manual","Midtrans","Offline","Balance") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
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
            DB::statement('ALTER TABLE transactions CHANGE trasaction_payment_type trasaction_payment_type ENUM("Manual","Midtrans","Offline","Balance") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT "Midtrans";');
        });
    }
}
