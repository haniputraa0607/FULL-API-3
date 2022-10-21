<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use DB;
use Storage;
use Excel;
use File;
use Symfony\Component\HttpFoundation\Request;
use App\Lib\MyHelper;

class ExportPromoCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$promo_campaign;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $promo_campaign = PromoCampaign::where('id_promo_campaign', $data['id_promo_campaign'])->first();

        if ($promo_campaign) {
	        $code_used 		= app($this->promo_campaign)->exportPromoCode(['id_promo_campaign' => $data['id_promo_campaign'], 'status' => 'used']);
	        $code_unused 	= app($this->promo_campaign)->exportPromoCode(['id_promo_campaign' => $data['id_promo_campaign'], 'status' => 'unused']);
	        $old_file 		= $promo_campaign['export_url'];

	        $generateExcel = [
	        	'used' 		=> $code_used,
	        	'unused' 	=> $code_unused
	        ];

	        $sheets = new SheetCollection($generateExcel);

	        $fileName = urlencode('Promo_code_'.str_replace(" ","", $promo_campaign['campaign_name']));

	        if($sheets){
	            $folder1 = 'promo_campaign';
	            $folder2 = 'promo_code';
	            $folder3 = $promo_campaign['id_promo_campaign'];

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
	            $store = (new FastExcel($sheets))->export(public_path().'/'.$directory);

	            if(config('configs.STORAGE') != 'local'){
	                $contents = File::get(public_path().'/'.$directory);
	                $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
	                if($store){
	                    $delete = File::delete(public_path().'/'.$directory);
	                }
	            }

	            if($store){
	            	$file = public_path().'/'.$old_file;
		            if(config('configs.STORAGE') == 'local'){
		                $delete = File::delete($file);
		            }else{
		                $delete = MyHelper::deleteFile($file);
		            }
	                $promo_campaign->update(['export_url' => $directory, 'export_status' => 'Ready']);
	            }
	        }
        }
    }
}
