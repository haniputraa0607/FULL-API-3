<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreDetailToUserAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_addresses');
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('id_user_address');
            $table->integer('id_user')->unsigned();
            $table->string('name', 191);
            $table->string('type')->nullable();
            $table->boolean('favorite')->default(0);
            $table->string('short_address')->nullable();
            $table->text('address', 65535)->nullable();
            $table->text('description', 65535)->nullable();
            $table->decimal('latitude',11,8)->nullable();
            $table->decimal('longitude',11,8)->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_addresses');
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('id_user_address');
            $table->string('name', 191);
            $table->string('phone', 191);
            $table->integer('id_user')->unsigned()->nullable()->index('fk_user_addresses_users');
            $table->integer('id_city')->unsigned()->index('fk_user_addresses_cities');
            $table->text('address', 65535)->nullable();
            $table->string('postal_code')->nullable();
            $table->text('description', 65535)->nullable();
            $table->enum('primary', array('0','1'))->default('0');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
