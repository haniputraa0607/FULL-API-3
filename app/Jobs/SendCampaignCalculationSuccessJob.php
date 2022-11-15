<?php

namespace App\Jobs;

use App\Http\Models\CampaignPushSent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Users\Http\Controllers\ApiUser;
use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;
use App\Http\Models\User;
use DB;

class SendCampaignCalculationSuccessJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $data;
    protected $camp;

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
        $all_token = $this->data['all_token'];
        $phones = $this->data['recipient'];
        foreach ($this->data['error_token'] as $error_token) {
            $check = array_search($error_token, $all_token);
            if ($check !== false) {
                unset($all_token[$check]);
            }
        }

        $indexing = array_values($all_token);
        $getUserSuccess = User::join('user_devices', 'users.id', 'user_devices.id_user')
                ->whereIn('device_token', $indexing)->whereIn('phone', $phones)
                ->groupBy('phone')->pluck('phone')->toArray();

        $insertData = [];
        foreach ($getUserSuccess as $phone) {
            $insertData[] = [
                'id_campaign' => $this->data['id_campaign'],
                'push_sent_to' => $phone,
                'push_sent_subject' => $this->data['subject'],
                'push_sent_content' => $this->data['content'],
                'push_sent_send_at' => $this->data['date_send']
            ];
        }
        CampaignPushSent::insert($insertData);

        DB::table('campaigns')
            ->where('id_campaign', $this->data['id_campaign'])
            ->update([
                'campaign_push_count_sent' => DB::raw('campaign_push_count_sent + ' . count($getUserSuccess))
            ]);
        return 'success';
    }
}
