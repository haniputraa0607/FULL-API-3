<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Subscription\Entities\Subscription;

class SendSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data, $autocrm, $subscription_voucher, $subscription_claim;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   	= $data;
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->subscription_voucher = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->subscription_claim   = "Modules\Subscription\Http\Controllers\ApiSubscriptionClaim";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;

        $count = 0;
        $total_subs = 0;
        foreach ($data['subs'] as $val){
        	$data_subs = Subscription::where('id_subscription', $val['id_subscription'])->first();

        	if ( $data_subs['subscription_total'] > $data_subs['subscription_bought'] || $data_subs['subscription_total'] == 0) {
	            $generateUser = app($this->subscription_voucher)->autoClaimedAssign($data_subs, $data['user']);
	            $count++;
	            $dataSubs = Subscription::where('id_subscription', $data_subs['id_subscription'])->first(); // get newest update of total claimed subscription
	            app($this->subscription_claim)->updateSubs($dataSubs);
        	}
        }

        if (!empty($count)) {
	        $autocrm = app($this->autocrm)->SendAutoCRM('Receive Welcome Subscription', $data['phone'],
	            [
	                'count_subscription'      => (string)$count
	            ]
        	);
        }

        return true;
    }
}
