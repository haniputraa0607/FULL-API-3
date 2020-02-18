<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogOvoDealsTable extends Migration
{
    public $set_schema_table = 'log_ovo_deals';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('mysql2')->hasTable($this->set_schema_table)) return;
        Schema::connection('mysql2')->create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id_log_ovo_deals');
            $table->unsignedInteger('id_deals_payment_ovo')->nullable();
            $table->string('order_id')->nullable();
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
        Schema::connection('mysql2')->dropIfExists($this->set_schema_table);
    }
}
