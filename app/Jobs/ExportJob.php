<?php

namespace App\Jobs;

use App\Http\Models\DealsUser;
use Modules\Report\Entities\ExportQueue;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Rap2hpoutre\FastExcel\FastExcel;
use DB;
use Storage;
use Excel;
use Mail;
use Mailgun;
use File;
use Symfony\Component\HttpFoundation\Request;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$payment,$trx,$subscription;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->payment="Modules\Report\Http\Controllers\ApiReportPayment";
        $this->trx="Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->subscription="Modules\Subscription\Http\Controllers\ApiSubscriptionReport";
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $val = ExportQueue::where('id_export_queue', $this->data->id_export_queue)->where('status_export', 'Running')->first();
        if(!empty($val)){
            $generateExcel = false;
            $filter = (array)json_decode($val['filter']);
            if($val['report_type'] == 'Payment'){
                $generateExcel = app($this->payment)->exportExcel($filter);
                $fileName = 'Report_'.str_replace(" ","", $val['report_payment']).'_'.$filter['type'];
            }elseif ($val['report_type'] == 'Transaction'){
                $generateExcel = app($this->trx)->returnExportYield($filter);
                if(isset($filter['detail'])){
                    $fileName = 'Report_Detail_'.str_replace(" ","", $val['report_payment']);
                }else{
                    $fileName = 'Report_'.str_replace(" ","", $val['report_payment']);
                }
            }elseif($val['report_type'] == 'Subscription'){
                $generateExcel = app($this->subscription)->exportExcel($filter);
                $fileName = 'Report_'.str_replace(" ","", $val['report_type']).'_'.$filter['type'];
            }

            if($generateExcel){
                $folder1 = 'report';
                $folder2 = $val['report_type'];
                $folder3 = $val['id_user'];

                if(!File::exists(public_path().'/'.$folder1)){
                    File::makeDirectory(public_path().'/'.$folder1);
                }

                if(!File::exists(public_path().'/'.$folder1.'/'.$folder2)){
                    File::makeDirectory(public_path().'/'.$folder1.'/'.$folder2);
                }

                if(!File::exists(public_path().'/'.$folder1.'/'.$folder2.'/'.$folder3)){
                    File::makeDirectory(public_path().'/'.$folder1.'/'.$folder2.'/'.$folder3);
                }

                $directory = $folder1.'/'.$folder2.'/'.$folder3.'/'.$fileName.'-'.mt_rand(0, 1000).''.time().''.'.xlsx';
                $store = (new FastExcel($generateExcel))->export(public_path().'/'.$directory);

                if(config('configs.STORAGE') != 'local'){
                    $contents = File::get(public_path().'/'.$directory);
                    $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
                    if($store){
                        $delete = File::delete(public_path().'/'.$directory);
                    }
                }

                if($store){
                    ExportQueue::where('id_export_queue', $val['id_export_queue'])->update(['url_export' => $directory, 'status_export' => 'Ready']);
                }
            }
        }

        return true;
    }
}
