<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOutletJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$autocrm;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->data   	= $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // sent pin to outlet
        $data = $this->data;
        foreach ($data as $key => $value) {
	        if (isset($value['outlet_email'])) {
		        $send 	= app($this->autocrm)->SendAutoCRM('Outlet Pin Sent', $value['outlet_email'], [
			                'pin' 			=> $value['pin'],
			                'date_sent' 	=> date('Y-m-d H:i:s'),
			                'outlet_name' 	=> $value['outlet_name'],
			                'outlet_code' 	=> $value['outlet_code'],
			            ], null, false, false, 'outlet');
	        }
        }
        return true;
    }
}
