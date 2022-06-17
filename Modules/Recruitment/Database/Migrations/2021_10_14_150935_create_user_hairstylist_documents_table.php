<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHairstylistDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hair_stylist_documents', function (Blueprint $table) {
            $table->bigIncrements('id_user_hair_stylist_document');
            $table->unsignedInteger('id_user_hair_stylist');
            $table->string('document_type', 255);
            $table->dateTime('process_date')->nullable();
            $table->string('process_name_by')->nullable();
            $table->mediumText('process_notes')->nullable();
            $table->mediumText('attachment')->nullable();
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
        Schema::dropIfExists('user_hair_stylist_documents');
    }
}
