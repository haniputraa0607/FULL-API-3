<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Modules\Quest\Entities\Quest;
use Modules\Quest\Entities\QuestUser;
use Modules\Quest\Entities\QuestUserDetail;

class UpdateQuestsTableStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('quest_user_details', 'quest_user_details_old');
        Schema::rename('quest_users', 'quest_user_details');
        Schema::rename('quest_user_details_old', 'quest_users');
        Schema::table('quest_users', function (Blueprint $table) {
            $table->dropForeign('fk_quest_user_details_id_quest_detail');
            $table->dropColumn('id_quest_detail');
            $table->boolean('is_done')->default(0)->after('id_user');
            $table->dateTime('date_start')->after('is_done')->nullable();
            $table->dateTime('date_end')->after('date_start')->nullable();
            $table->renameColumn('id_quest_user_detail', 'id_quest_user');
        });
        Schema::table('quest_user_details', function (Blueprint $table) {
            $table->renameColumn('id_quest_user','id_quest_user_detail');
        });
        Schema::table('quest_user_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_quest_user')->nullable()->after('id_quest');
            $table->foreign('id_quest_user', 'fk_quest_details_id_quest_user')->references('id_quest_user')->on('quest_users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
        $details = QuestUserDetail::select(\DB::raw('id_quest,id_user,max(date) as date'))->groupBy('id_quest', 'id_user')->get()->toArray();
        foreach ($details as $detail) {
            $quest = Quest::find($detail['id_quest']);
            if (!$quest) {
                continue;
            }
            $is_done = QuestUserDetail::where(['is_done'=> 0, 'id_quest' => $detail['id_quest']])->exists() ? 0 : 1;
            $quest_user = QuestUser::create($detail + ['is_done' => $is_done, 'date_start' => $quest->date_start, 'date_end' => $quest->date_end]);
            QuestUserDetail::where(['id_quest' => $detail['id_quest'], 'id_user' => $detail['id_user']])->update(['id_quest_user' => $quest_user['id_quest_user']]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_user_details', function (Blueprint $table) {
            $table->dropForeign('fk_quest_details_id_quest_user');
            $table->dropColumn('id_quest_user');
            $table->renameColumn('id_quest_user_detail', 'id_quest_user');
        });
        Schema::table('quest_users', function (Blueprint $table) {
            $table->dropColumn('is_done');
            $table->dropColumn('date_start');
            $table->dropColumn('date_end');
            $table->bigInteger('id_quest_detail')->unsigned()->after('id_quest')->nullable();
            $table->foreign('id_quest_detail', 'fk_quest_user_details_id_quest_detail')->references('id_quest_detail')->on('quest_details')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->renameColumn('id_quest_user','id_quest_user_detail');
        });
        \DB::table('quest_users')->truncate();
        Schema::rename('quest_user_details', 'quest_user_details_old');
        Schema::rename('quest_users', 'quest_user_details');
        Schema::rename('quest_user_details_old', 'quest_users');
    }
}
