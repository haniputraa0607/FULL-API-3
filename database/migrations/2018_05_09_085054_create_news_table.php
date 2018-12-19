<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNewsTable extends Migration {

	public function up()
	{
		Schema::create('news', function(Blueprint $table)
		{
			$table->increments('id_news');
			$table->string('news_slug')->nullable();
			$table->string('news_title', 250);
			$table->string('news_content_short', 191);
			$table->text('news_content_long');
			$table->string('news_video', 200)->nullable();
			$table->string('news_image_luar', 200)->nullable();
			$table->string('news_image_dalam', 200)->nullable();
			$table->dateTime('news_post_date')->nullable();
			$table->dateTime('news_publish_date');
			$table->dateTime('news_expired_date')->nullable();
			$table->date('news_event_date_start')->nullable();
			$table->date('news_event_date_end')->nullable();
			$table->time('news_event_time_start')->nullable();
			$table->time('news_event_time_end')->nullable();
			$table->string('news_event_location_name', 150)->nullable();
			$table->string('news_event_location_phone', 100)->nullable();
			$table->text('news_event_location_address', 16777215)->nullable();
			$table->string('news_event_location_map', 200)->nullable();
			$table->string('news_event_latitude', 25)->nullable();
			$table->string('news_event_longitude', 25)->nullable();
			$table->string('news_outlet_text', 150)->nullable();
			$table->string('news_product_text', 150)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('news');
	}

}
