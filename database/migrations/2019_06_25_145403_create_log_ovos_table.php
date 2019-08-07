<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogOvosTable extends Migration
{
    public $set_schema_table = 'log_ovos';
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
            $table->increments('id_log_ovo');
            $table->unsignedInteger('id_transaction_payment_ovo')->nullable();
            $table->string('transaction_receipt_number')->nullable();
            $table->string('url');
            $table->string('header')->nullable();
            $table->text('request')->nullable();
            $table->string('response_status', 7)->nullable();
            $table->string('response_code', 7)->nullable();
            $table->text('response')->nullable();
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
        Schema::dropIfExists($this->set_schema_table);
    }
}
