<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExportQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_queues', function (Blueprint $table) {
            $table->bigIncrements('id_export_queue');
            $table->integer('id_user');
            $table->string('filter', 255)->nullable();
            $table->enum('report_type', array('Payment', 'Transaction'))->nullable();
            $table->string('url_export', 200)->nullable();
            $table->enum('status_export', array('Running', 'Ready', 'Deleted'))->nullable();
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
        Schema::dropIfExists('export_queues');
    }
}
