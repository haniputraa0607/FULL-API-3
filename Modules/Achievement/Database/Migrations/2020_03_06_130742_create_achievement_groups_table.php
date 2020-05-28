<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_groups', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_group');
            $table->bigInteger('id_achievement_category')->unsigned();
            $table->string('name');
            $table->string('logo_badge_default');
            $table->timestamp('date_start')->nullable();
            $table->timestamp('date_end')->nullable();
            $table->timestamp('publish_start')->nullable();
            $table->timestamp('publish_end')->nullable();
            $table->text('description')->nullable();
            $table->enum('order_by', ['trx_nominal', 'trx_total', 'different_outlet', 'different_province'])->nullable();
            $table->timestamps();

            $table->foreign('id_achievement_category', 'fk_achievement_groups_id_achievement_category')->references('id_achievement_category')->on('achievement_categories')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_groups');
    }
}
