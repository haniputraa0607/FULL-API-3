<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFaqNumberListToFaqDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faq_doctors', function (Blueprint $table) {
            $table->integer('faq_number_list')->default(0)->after('id_faq_doctor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faq_doctors', function (Blueprint $table) {
            $table->dropColumn('faq_number_list');
        });
    }
}
