<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistInboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_inboxes', function (Blueprint $table) {
            $table->increments('id_hairstylist_inboxes');
			$table->integer('id_campaign')->unsigned()->nullable()->index('fk_hairstylist_inboxes_campaigns');
			$table->integer('id_user_hair_stylist')->unsigned()->index('fk_hairstylist_inboxes_users');
			$table->string('inboxes_subject', 191);
			$table->text('inboxes_content', 65535)->nullable();
			$table->string('inboxes_clickto', 191);
			$table->string('inboxes_link')->nullable();
			$table->string('inboxes_id_reference', 20)->nullable();
			$table->dateTime('inboxes_send_at')->nullable();
			$table->char('read', 1)->default(0);
			$table->integer('id_brand')->unsigned()->nullable();
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_inboxes');
    }
}
