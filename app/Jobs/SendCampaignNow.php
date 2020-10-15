<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\Users\Http\Controllers\ApiUser;

use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class SendCampaignNow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->camp="Modules\Campaign\Http\Controllers\ApiCampaign";
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id_campaign=$this->data['id_campaign'];
        $getCampaign = Campaign::where('id_campaign','=',$id_campaign)->first()->toArray();
        $send = app($this->camp)->sendCampaignInternal($getCampaign);

        return $send;
    }
}
