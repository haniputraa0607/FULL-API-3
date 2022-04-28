<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMainAddressStatusToUserAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->integer('main_address')->default(0)->nullable()->after('longitude');
            $table->integer('id_city')->nullable()->after('longitude');
            $table->string('receiver_email')->nullable()->after('longitude');
            $table->string('receiver_phone')->nullable()->after('longitude');
            $table->string('receiver_name')->nullable()->after('longitude');
            $table->string('postal_code', 30)->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn('main_address');
            $table->dropColumn('id_city');
            $table->dropColumn('receiver_email');
            $table->dropColumn('receiver_phone');
            $table->dropColumn('receiver_name');
            $table->dropColumn('postal_code');
        });
    }
}
