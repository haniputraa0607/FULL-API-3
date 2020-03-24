<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPromosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_promos', function (Blueprint $table) {

            $table->increments('id');
			$table->integer('id_user')->unsigned()->index('fk_users_promo');
			$table->enum('promo_type',['promo_campaign','deals','subscription']);
			$table->integer('id_reference');
			$table->timestamps();

			$table->foreign('id_user', 'fk_users_promo')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_promos');
    }
}
