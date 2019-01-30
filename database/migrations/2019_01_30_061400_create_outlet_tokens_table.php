<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_tokens', function (Blueprint $table) {
            $table->increments('id_outlet_token');
			$table->integer('id_outlet');
			$table->string('token');
            $table->timestamps();
        });
		
		Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('push_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_tokens');
		
		Schema::table('outlets', function (Blueprint $table) {
            $table->string('push_token')->default(null)->after('outlet_longitude');
        });
    }
}
