<?php

namespace App\Jobs;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Plastic\Entities\PlasticType;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductModifierGroupPivot;
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
use Illuminate\Support\Facades\Cache;

class SyncronPlasticTypeOutlet implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $plasticType = PlasticType::get()->toArray();
        foreach ($plasticType as $pt) {
            app('Modules\Plastic\Http\Controllers\ApiPlasticTypeController')->syncPlasticTypeOutlet($pt['id_plastic_type']);
        }
        return true;
    }
}
