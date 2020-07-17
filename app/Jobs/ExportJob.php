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
    protected $data,$payment;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->payment="Modules\Report\Http\Controllers\ApiReportPayment";
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

                $directory = $folder1.'/'.$folder2.'/'.$folder3.'/Report Payment '.$filter['type'].'-'.mt_rand(0, 1000).''.time().''.'.xlsx';
                $store = (new FastExcel($generateExcel))->export(public_path().'/'.$directory);

                if(env('STORAGE') != 'oss'){
                    $contents = File::get(public_path().'/'.$directory);
                    $store = Storage::disk(env('STORAGE'))->put($directory,$contents, 'public');
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
