<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->integer('created_by')->after('id_deals_promotion_template')->nullable();
            $table->integer('last_updated_by')->after('created_by')->nullable();
        	$table->string('deals_warning_image', 200)->after('deals_image')->nullable();
        	$table->dateTime('deals_voucher_start')->nullable()->after('deals_voucher_duration');
            $table->integer('user_limit')->default(0)->after('deals_voucher_expired');
        	$table->enum('promo_type', ['Product discount', 'Tier discount', 'Buy X Get Y'])->after('user_limit')->nullable();
            $table->boolean('is_online')->nullable()->after('promo_type');
        	$table->boolean('is_offline')->nullable()->after('is_online');
        	$table->enum('product_type', ['single', 'group'])->after('is_offline')->nullable();
        	$table->boolean('step_complete')->nullable()->after('is_offline');
        	$table->text('custom_outlet_text')->after('step_complete')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->dropColumn('created_by');
        	$table->dropColumn('last_updated_by');
        	$table->dropColumn('deals_warning_image');
        	$table->dropColumn('deals_voucher_start');
        	$table->dropColumn('user_limit');
        	$table->dropColumn('promo_type');
        	$table->dropColumn('is_online');
        	$table->dropColumn('is_offline');
        	$table->dropColumn('product_type');
        	$table->dropColumn('step_complete');
        	$table->dropColumn('custom_outlet_text');
        });
    }
}
