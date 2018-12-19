<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePhotoEnquiries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enquiries_photo', function (Blueprint $table) {
            $table->increments('id_ep');
            $table->unsignedInteger('id_enquiry')->nullable();
            $table->foreign('id_enquiry', 'fk_enquiries_photos')->references('id_enquiry')->on('enquiries')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->string('enquiry_photo', 200)->nullable();
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
        Schema::dropIfExists('enquiries_photo');
    }
}
