<?php

namespace Modules\Quest\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Quest\Entities\QuestDetail;
use Modules\Quest\Entities\QuestUser;
use Modules\Quest\Entities\QuestUserDetail;
use Illuminate\Http\Request;

class AutoclaimQuest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $quest;
    protected $users;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quest, $users)
    {
        $this->quest = $quest;
        $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dataQuestUser = [];
        $dataQuestUserDetail = [];
        $questDetails = QuestDetail::where('id_quest', $this->quest->id_quest)->get();
        $params = [
            'id_quest' => $this->quest->id_quest
        ];
        foreach ($this->users as $id_user) {
            $claim = app('Modules\Quest\Http\Controllers\ApiQuest')->doTakeMission($id_user, $this->quest->id_quest);
        }
    }
}
