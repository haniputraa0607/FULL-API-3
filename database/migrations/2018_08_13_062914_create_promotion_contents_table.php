<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_contents', function (Blueprint $table) {
            $table->increments('id_promotion_content');
			$table->integer('id_promotion')->unsigned()->index('fk_promotion_content_promotions');
			$table->integer('promotion_series_days')->default(0);
			$table->integer('id_deals')->unsigned()->index('fk_promotions_deals')->nullable()->default(null);
			$table->integer('voucher_value')->nullable()->default(null);
			$table->char('promotion_channel_email', 1)->default(0);
			$table->char('promotion_channel_sms', 1)->default(0);
			$table->char('promotion_channel_push', 1)->default(0);
			$table->char('promotion_channel_inbox', 1)->default(0);
			$table->char('promotion_channel_forward', 1)->default(0);
			$table->string('promotion_email_subject',255)->nullable();
			$table->text('promotion_email_content', 16777215)->nullable();
			$table->text('promotion_sms_content', 65535)->nullable();
			$table->text('promotion_push_subject', 65535)->nullable();
			$table->text('promotion_push_content', 65535)->nullable();
			$table->string('promotion_push_image')->nullable();
			$table->string('promotion_push_clickto', 100)->nullable();
			$table->string('promotion_push_link')->nullable();
			$table->string('promotion_push_id_reference')->nullable();
			$table->text('promotion_inbox_subject', 65535)->nullable();
			$table->text('promotion_inbox_content', 65535)->nullable();
			$table->text('promotion_forward_email', 65535)->nullable();
			$table->text('promotion_forward_email_subject', 65535)->nullable();
			$table->text('promotion_forward_email_content', 16777215)->nullable();
			$table->integer('promotion_count_email_sent')->default(0);
			$table->integer('promotion_count_email_read')->default(0);
			$table->integer('promotion_count_email_link_clicked')->default(0);
			$table->integer('promotion_count_sms_sent')->default(0);
			$table->integer('promotion_count_push')->default(0);
			$table->integer('promotion_count_inbox')->default(0);
			$table->integer('promotion_count_voucher_give')->default(0);
			$table->integer('promotion_count_voucher_used')->default(0);
			$table->integer('promotion_sum_voucher_used')->default(0);
			$table->integer('promotion_sum_transaction')->default(0);
			
            $table->timestamps();
        });
		
		Schema::table('promotion_contents', function (Blueprint $table) {
			$table->foreign('id_promotion', 'fk_promotion_content_promotions')->references('id_promotion')->on('promotions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('promotion_contents', function (Blueprint $table) {
			$table->dropForeign('fk_promotion_content_promotions');
		});
		
        Schema::dropIfExists('promotion_contents');
    }
}
