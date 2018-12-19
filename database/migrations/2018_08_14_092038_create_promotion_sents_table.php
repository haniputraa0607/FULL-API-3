<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create('promotion_sents', function (Blueprint $table) {
            $table->increments('id_promotion_sent');
			$table->integer('id_user')->unsigned()->index('fk_promotion_sents_users');
			$table->integer('id_promotion_content')->unsigned()->index('fk_promotion_sents_promotion_contents');
			$table->tinyInteger('series_no')->default(0);
			$table->dateTime('send_at')->nullable();
            $table->timestamps();
        });
		
		Schema::table('promotion_sents', function (Blueprint $table) {
			$table->foreign('id_user', 'fk_promotion_sents_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
			
			$table->foreign('id_promotion_content', 'fk_promotion_sents_promotion_contents')->references('id_promotion_content')->on('promotion_contents')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_sents', function (Blueprint $table) {
			$table->dropForeign('fk_promotion_sents_users');
			$table->dropForeign('fk_promotion_sents_promotion_contents');
		});
		
        Schema::dropIfExists('promotion_sents');
    }
}
