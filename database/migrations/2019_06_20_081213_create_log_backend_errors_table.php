<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogBackendErrorsTable extends Migration
{
    public $set_schema_table = 'log_backend_errors';
    public function up()
    {
		if (Schema::hasTable($this->set_schema_table)) return;
        Schema::connection('mysql2')->create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id_log_backend_error');
            $table->string('response_status', 7)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('request_method', 7)->nullable();
            $table->text('error')->nullable();
            $table->string('file', 255)->nullable();
            $table->string('line', 10)->nullable();
			$table->string('ip_address', 25)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->timestamps();
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
