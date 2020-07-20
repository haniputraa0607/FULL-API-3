<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPaymentOvosTable extends Migration
{
    public function up()
    {
        Schema::create('deals_payment_ovos', function (Blueprint $table) {
            $table->increments('id_deals_payment_ovo');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_deals_user');
            $table->boolean('is_production');
            $table->dateTime('push_to_pay_at');
            $table->enum('reversal',['no', 'not yet', 'yes'])->default('not yet');
            $table->integer('amount');
            $table->string('order_id', 191);
            $table->string('trace_number')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('response_code')->nullable();
            $table->string('response_detail')->nullable();
            $table->text('response_description')->nullable();
            $table->string('batch_no')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('ovoid')->nullable();
            $table->integer('cash_used')->nullable();
            $table->integer('ovo_points_earned')->nullable();
            $table->integer('cash_balance')->nullable();
            $table->integer('full_name')->nullable();
            $table->integer('ovo_points_used')->nullable();
            $table->integer('ovo_points_balance')->nullable();
            $table->string('payment_type')->nullable();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_id_deals_deals_payment_ovo_deals')
                ->references('id_deals')->on('deals')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('id_deals_user', 'fk_id_deals_user_deals_payment_ovo_deals_user')
                ->references('id_deals_user')->on('deals_users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('deals_payment_ovos');
    }
}
