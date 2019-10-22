<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogActivitiesAppsTable extends Migration
{
    public $set_schema_table = 'log_activities_apps';
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
            $table->increments('id_log_activities_apps');
            $table->string('module', 100);
            $table->string('url', 200);
            $table->string('subject', 200);
            $table->string('phone', 45)->nullable();
            $table->text('user')->nullable();
            $table->longText('request')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('response_status', 7)->nullable();
            $table->longText('response')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
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
