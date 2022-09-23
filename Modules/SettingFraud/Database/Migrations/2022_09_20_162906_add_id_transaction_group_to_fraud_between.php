<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionGroupToFraudBetween extends Migration
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
        Schema::table('fraud_between_transaction', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->nullable()->after('id_transaction');
            $table->unsignedInteger('id_transaction')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fraud_between_transaction', function (Blueprint $table) {
            $table->dropColumn('id_transaction_group');
            $table->unsignedInteger('id_transaction')->change();
        });
    }
}
