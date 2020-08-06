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
use Modules\Membership\Http\Controllers\ApiMembership;

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
        
        $this->achievement = new ApiAchievement();
        $this->achievement->checkAchievementV2($this->data['id_transaction']);
        $this->membership = new ApiMembership();
        $this->membership->calculateMembership($this->data['phone']);
    }
}
