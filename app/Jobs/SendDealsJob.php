<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Models\Deal;

class SendDealsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data, $autocrm, $hidden_deals, $deals_claim;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
    	$this->data   		= $data;
        $this->hidden_deals = "Modules\Deals\Http\Controllers\ApiHiddenDeals";
        $this->deals_claim  = "Modules\Deals\Http\Controllers\ApiDealsClaim";
        $this->autocrm 		= "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
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

        foreach ($data['deals'] as $val){
        	$data_deals = Deal::where('id_deals', $val['id_deals'])->first();
        	$total_voucher = $data_deals['deals_total_voucher'];
        	$total_claimed = $data_deals['deals_total_claimed'];

	        for($i=0;$i<$val['deals_total'];$i++){
       			if ($total_voucher > $total_claimed) {
	                $generateVoucher = app($this->hidden_deals)->autoClaimedAssign($data_deals, $data['user']);
	                $count++;
	            	app($this->deals_claim)->updateDeals($data_deals);
        			$data_deals = Deal::where('id_deals', $val['id_deals'])->first();
	            	$total_claimed = $data_deals['deals_total_claimed'];
	            }
        	}
        }

        if (!empty($count)) {
	        $autocrm = app($this->autocrm)->SendAutoCRM('Receive Welcome Voucher', $data['phone'],
	            [
	                'count_voucher'      => (string)$count
	            ]
	        );
	    }

        return true;
    }
}
