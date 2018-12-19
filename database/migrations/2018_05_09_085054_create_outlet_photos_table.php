<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOutletPhotosTable extends Migration {

	public function up()
	{
		Schema::create('outlet_photos', function(Blueprint $table)
		{
			$table->increments('id_outlet_photo');
			$table->integer('id_outlet')->unsigned()->index('fk_outlet_photos_outlets');
			$table->string('outlet_photo', 100);
			$table->smallInteger('outlet_photo_order')->default(0);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('outlet_photos');
	}

}
