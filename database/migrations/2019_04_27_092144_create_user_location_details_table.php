<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserLocationDetailsTable extends Migration
{
	public $set_schema_table = 'user_location_details';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->increments('id_user_location_detail');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_reference');
            $table->string('activity');
            $table->string('action')->nullable();
            $table->string('latitude');
            $table->string('longitude');
            $table->longText('response_json');
            $table->string('street_address')->nullable();
            $table->string('route')->nullable();
            $table->string('administrative_area_level_5')->nullable();
            $table->string('administrative_area_level_4')->nullable();
            $table->string('administrative_area_level_3')->nullable();
            $table->string('administrative_area_level_2')->nullable();
            $table->string('administrative_area_level_1')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('formatted_address')->nullable();
            $table->timestamps();

            $table->foreign('id_user', 'fk_user_location_details_users')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
