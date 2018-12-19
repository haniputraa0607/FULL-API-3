<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionTableFieldToTransactionTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement('ALTER TABLE transactions CHANGE trasaction_payment_type trasaction_payment_type ENUM("Manual","Midtrans","Offline","Balance") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });

        Schema::table('log_topups', function (Blueprint $table) {
            $table->enum('topup_payment_status', array('Pending','Completed','Cancelled'))->after('transaction_reference')->default('Pending');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement('ALTER TABLE transactions CHANGE trasaction_payment_type trasaction_payment_type ENUM("Manual","Midtrans","Offline") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });

        Schema::table('log_topups', function (Blueprint $table) {
            $table->dropColumn('topup_payment_status');
        });
    }
}