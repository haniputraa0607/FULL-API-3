<?php

namespace App\Jobs;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariantGroup;
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

class RefreshVariantTree implements ShouldQueue
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
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $products = Product::where('product_variant_status', 1)->get()->toArray();
        foreach ($products as $p){
            $basePrice = ProductVariantGroup::orderBy('product_variant_group_price', 'asc')->where('id_product', $p['id_product'])->first();
            if($basePrice){
                ProductGlobalPrice::updateOrCreate(['id_product' => $p['id_product']], ['product_global_price' => $basePrice['product_variant_group_price']]);

                $getAllOutlets = Outlet::get();
                foreach ($getAllOutlets as $o){
                    Product::refreshVariantTree($p['id_product'], $o);
                    if($o['outlet_different_price'] == 1){
                        $basePriceDiferrentOutlet = ProductVariantGroup::leftJoin('product_variant_group_special_prices as pgsp', 'pgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                            ->orderBy('product_variant_group_price', 'asc')
                            ->select(DB::raw('(CASE
                        WHEN pgsp.product_variant_group_price is NOT NULL THEN pgsp.product_variant_group_price
                        ELSE product_variant_groups.product_variant_group_price END)  as product_variant_group_price'))
                            ->where('id_product', $p['id_product'])->where('id_outlet', $o['id_outlet'])->first();
                        if($basePriceDiferrentOutlet){
                            ProductSpecialPrice::updateOrCreate(['id_outlet' => $o['id_outlet'], 'id_product' => $p['id_product']], ['product_special_price' => $basePriceDiferrentOutlet['product_variant_group_price']]);
                        }else{
                            ProductSpecialPrice::updateOrCreate(['id_outlet' => $o['id_outlet'], 'id_product' => $p['id_product']], ['product_special_price' => $basePrice['product_variant_group_price']]);
                        }
                    }
                }
            }
        }

        return true;
    }
}
