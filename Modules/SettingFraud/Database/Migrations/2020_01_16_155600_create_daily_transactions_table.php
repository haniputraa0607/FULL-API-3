<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyTransactionsTable extends Migration
{
    public $set_schema_table = 'daily_transactions';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
            Schema::connection('mysql2')->create($this->set_schema_table, function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id_daily_transaction');
                $table->timestamp('transaction_date')->nullable();
                $table->unsignedInteger('id_transaction');
                $table->unsignedInteger('id_user');
                $table->unsignedInteger('id_outlet');
                $table->tinyInteger('flag_check')->default(0);
                $table->nullableTimestamps();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->dropIfExists('daily_transactions');
    }
}
