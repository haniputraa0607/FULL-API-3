<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeUserToCrmLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrm_email_logs', function (Blueprint $table) {
            $table->dropForeign('fk_autocrm_email_logs_users');
            $table->dropIndex('fk_autocrm_email_logs_users');
            $table->string('user_type')->default('users');
        });
        Schema::table('autocrm_push_logs', function (Blueprint $table) {
            $table->dropForeign('fk_autocrm_push_logs_users');
            $table->dropIndex('fk_autocrm_push_logs_users');
            $table->string('user_type')->default('users');
        });
        Schema::table('autocrm_sms_logs', function (Blueprint $table) {
            $table->dropForeign('fk_autocrm_sms_logs_users');
            $table->dropIndex('fk_autocrm_sms_logs_users');
            $table->string('user_type')->default('users');
        });
        Schema::table('autocrm_whatsapp_logs', function (Blueprint $table) {
            $table->dropForeign('fk_autocrm_whatsapp_logs_autocrms');
            $table->dropIndex('fk_autocrm_whatsapp_logs_autocrms');
            $table->string('user_type')->default('users');
        });
        Schema::table('autocrm_email_logs', function (Blueprint $table) {
            $table->integer('id_user')->change();
        });
        Schema::table('autocrm_push_logs', function (Blueprint $table) {
            $table->integer('id_user')->change();
        });
        Schema::table('autocrm_sms_logs', function (Blueprint $table) {
            $table->integer('id_user')->change();
        });
        Schema::table('autocrm_whatsapp_logs', function (Blueprint $table) {
            $table->integer('id_user')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrm_email_logs', function (Blueprint $table) {
            $table->integer('id_user')->unsigned()->change();
        });
        Schema::table('autocrm_push_logs', function (Blueprint $table) {
            $table->integer('id_user')->unsigned()->change();
        });
        Schema::table('autocrm_sms_logs', function (Blueprint $table) {
            $table->integer('id_user')->unsigned()->change();
        });
        Schema::table('autocrm_whatsapp_logs', function (Blueprint $table) {
            $table->integer('id_user')->unsigned()->change();
        });
        Schema::table('autocrm_email_logs', function (Blueprint $table) {
            $table->foreign('id_user','fk_autocrm_email_logs_users')->references('id')->on('users')->onDelete('restrict');
            $table->dropColumn('user_type');
        });
        Schema::table('autocrm_push_logs', function (Blueprint $table) {
            $table->foreign('id_user','fk_autocrm_push_logs_users')->references('id')->on('users')->onDelete('restrict');
            $table->dropColumn('user_type');
        });
        Schema::table('autocrm_sms_logs', function (Blueprint $table) {
            $table->foreign('id_user','fk_autocrm_sms_logs_users')->references('id')->on('users')->onDelete('restrict');
            $table->dropColumn('user_type');
        });
        Schema::table('autocrm_whatsapp_logs', function (Blueprint $table) {
            $table->foreign('id_user','fk_autocrm_whatsapp_logs_autocrms')->references('id')->on('users')->onDelete('restrict');
            $table->dropColumn('user_type');
        });
    }
}
