<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistUpdateDataLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_update_datas', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_update_data');
            $table->integer('id_user_hair_stylist')->unsigned()->index();
            $table->unsignedInteger('approve_by')->nullable();
            $table->string('field');
            $table->string('new_value');
            $table->text('notes')->nullable();
            $table->datetime('approve_at')->nullable();
            $table->datetime('reject_at')->nullable();
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
        Schema::dropIfExists('hairstylist_update_datas');
    }
}
