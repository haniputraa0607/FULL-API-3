<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInboxGlobalReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbox_global_reads', function (Blueprint $table) {
            $table->increments('id_inbox_global_read');
            $table->unsignedInteger('id_inbox_global');
            $table->unsignedInteger('id_user');
            $table->timestamps();

            $table->foreign('id_inbox_global', 'fk_inbox_global_reads_inbox_globals')->references('id_inbox_global')->on('inbox_globals')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_inbox_global_reads_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inbox_global_reads');
    }
}
