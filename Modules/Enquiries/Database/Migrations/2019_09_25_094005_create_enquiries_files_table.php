<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnquiriesFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enquiries_files', function (Blueprint $table) {
            $table->increments('id_enquiries_file');
            $table->unsignedInteger('id_enquiry');
            $table->string('enquiry_file', 200);
            $table->timestamps();

            $table->foreign('id_enquiry', 'fk_enquiries_files_id_enquiry')->references('id_enquiry')->on('enquiries')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('enquiries_files');
    }
}
