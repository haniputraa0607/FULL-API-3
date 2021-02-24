<?php

namespace App\Jobs;

use App\Http\Models\DealsUser;
use Modules\Report\Entities\ExportQueue;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Modules\Franchise\Entities\ExportFranchiseQueue;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Rap2hpoutre\FastExcel\FastExcel;
use DB;
use Storage;
use Excel;
use App\Lib\SendMail as Mail;
use Mailgun;
use File;
use Symfony\Component\HttpFoundation\Request;

class ExportFranchiseJob implements ShouldQueue
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
        $this->data    = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $queue = ExportFranchiseQueue::where('id_export_franchise_queue', $this->data->id_export_franchise_queue)->where('status_export', 'Running')->first();

        if(!empty($queue)){
            $generateExcel = false;
            if ( $queue['report_type'] == 'Transaction' ) {
                app('Modules\Franchise\Http\Controllers\ApiTransactionFranchiseController')->exportExcel($queue);
            }
        }

        return true;
    }
}
