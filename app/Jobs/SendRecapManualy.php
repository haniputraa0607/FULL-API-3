<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendRecapManualy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$disburse;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->data['type'] == 'recap_disburse'){
            app('Modules\Disburse\Http\Controllers\ApiDisburseController')->shortcutRecap($this->data['date']);
        }elseif($this->data['type'] == 'recap_transaction_to_outlet'){
            app('Modules\Disburse\Http\Controllers\ApiDisburseController')->cronSendEmailDisburse($this->data['date']);
        }elseif($this->data['type'] == 'recap_transaction_each_outlet'){
            app('Modules\Disburse\Http\Controllers\ApiDisburseController')->exportToOutlet($this->data['data']);
        }elseif($this->data['type'] == 'recap_disburse_each_outlet'){
            app('Modules\Disburse\Http\Controllers\ApiDisburseController')->sendDisburseWithRangeDate($this->data['data']);
        }

        return 'success';
    }
}
