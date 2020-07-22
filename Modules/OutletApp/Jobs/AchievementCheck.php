<?php

namespace Modules\OutletApp\Jobs;

use App\Http\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;
use Modules\Achievement\Http\Controllers\ApiAchievement;

class AchievementCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $getUser = Transaction::where('id_transaction', $this->data['id_transaction'])->first();

        $getAchievement = AchievementDetail::select('achievement_details.*', 'achievement_groups.order_by')
            ->join('achievement_groups', 'achievement_details.id_achievement_group', 'achievement_groups.id_achievement_group')
            ->get()->toArray();

        $data = [];
        foreach ($getAchievement as $value) {
            $data[$value['order_by']][] = $value;
        }
        foreach ($data as $keyD => $d) {
            ApiAchievement::checkAchievement($getUser->id_user, $d, $keyD);
        }
    }
}
