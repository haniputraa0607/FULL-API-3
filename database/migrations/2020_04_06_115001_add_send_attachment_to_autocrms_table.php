<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSendAttachmentToAutocrmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrms', function (Blueprint $table) {
            $table->boolean('attachment_mail')->after('custom_text_replace')->default(0);
            $table->boolean('attachment_forward')->after('custom_text_replace')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrms', function (Blueprint $table) {
            $table->dropcolumn('attachment_mail');
            $table->dropcolumn('attachment_forward');
        });
    }
}
