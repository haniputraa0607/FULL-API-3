<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogActivitiesTable extends Migration
{
	public $set_schema_table = 'log_activities';
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
            $table->increments('id_log_activity');
            $table->string('module', 100);
            $table->string('url', 200);
            $table->string('subject', 200);
            $table->string('phone', 45)->nullable();
            $table->text('user')->nullable();
            $table->text('request')->nullable();
            $table->string('response_status', 7)->nullable();
            $table->text('response')->nullable();
            $table->string('ip', 25)->nullable();
            $table->string('useragent', 200)->nullable();
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
