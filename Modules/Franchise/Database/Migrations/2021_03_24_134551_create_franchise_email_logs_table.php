<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFranchiseEmailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('franchise_email_logs', function (Blueprint $table) {
            $table->increments('id_franchise_email_log');
			$table->integer('id_outlet')->unsigned()->index('fk_franchise_email_logs_outlets');
			$table->string('email_log_to');
			$table->string('email_log_subject');
			$table->text('email_log_message', 16777215);
			$table->boolean('email_log_is_read')->nullable();
			$table->boolean('email_log_is_clicked')->nullable();
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
        Schema::dropIfExists('franchise_email_logs');
    }
}
