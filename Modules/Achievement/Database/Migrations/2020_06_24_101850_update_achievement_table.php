<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateAchievementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE achievement_groups CHANGE order_by order_by VARCHAR(200)');
        Schema::table('achievement_categories', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('name');
        });
        Schema::create('achievement_progress', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_progress');
            $table->bigInteger('id_achievement_detail')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->integer('progress')->default(0);
            $table->integer('end_progress')->default(0);
            $table->timestamps();

            $table->foreign('id_achievement_detail', 'fk_achievement_progress_id_achievement_detail')->references('id_achievement_detail')->on('achievement_details')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_achievement_progress_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
}
