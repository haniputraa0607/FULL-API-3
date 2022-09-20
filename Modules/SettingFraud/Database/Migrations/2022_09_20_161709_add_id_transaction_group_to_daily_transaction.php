<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionGroupToDailyTransaction extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public $set_schema_table = 'daily_transactions';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->table($this->set_schema_table, function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_group')->nullable()->after('id_transaction');
            $table->unsignedInteger('id_transaction')->nullable()->change();
            $table->unsignedInteger('id_outlet')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->table($this->set_schema_table, function (Blueprint $table) {
            $table->dropColumn('id_transaction_group');
            $table->unsignedInteger('id_transaction')->change();
            $table->unsignedInteger('id_outlet')->change();
        });
    }
}
