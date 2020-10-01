<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\Users\Http\Controllers\ApiUser;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use App\Lib\MyHelper;

class GeneratePromoCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $status,$id, $prefix_code, $number_last_code, $total_coupon;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($status, $id, $prefix_code, $number_last_code, $total_coupon)
    {
        $this->status           = $status;
        $this->id               = $id;
        $this->prefix_code      = $prefix_code;
        $this->number_last_code = $number_last_code;
        $this->total_coupon     = $total_coupon;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        for ($i = 0; $i < $this->total_coupon; $i++) {
            $generateCode[$i]['id_promo_campaign']  = $this->id;
            $generateCode[$i]['promo_code']         = implode('', [$this->prefix_code, MyHelper::createrandom($this->number_last_code, 'PromoCode')]);
            $generateCode[$i]['created_at']         = date('Y-m-d H:i:s');
            $generateCode[$i]['updated_at']         = date('Y-m-d H:i:s');
        }

        $data = collect($generateCode);
        $chunks = $data->chunk(500);
        $chunks = $chunks->toArray();

        // dd($this->status);exit();
        // dd($chunks);exit();
        if ($this->status == 'insert') 
        {
            try 
            {
                foreach ($chunks as $chunk) 
                {
                    PromoCampaignPromoCode::insert($chunk);
                }
                
                return true;
            } 
            catch (\Exception $e) 
            {
                echo 'Insert Promo Codes failed';
                return true;
            }
        } 
        else 
        {
            try 
            {
                PromoCampaignPromoCode::where('id_promo_campaign', $this->id)->delete();
                foreach ($chunks as $chunk) 
                {
                    PromoCampaignPromoCode::insert($chunk);
                }
                return true;

            } 
            catch (\Exception $e) 
            {
                echo 'Update Promo Codes failed';
                return true;
            }
        }
    }
}
