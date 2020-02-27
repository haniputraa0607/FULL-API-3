<?php

namespace Modules\MokaPOS\Jobs;

use App\Http\Models\LogBackendError;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class SyncProductOutlet implements ShouldQueue
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
        $this->data = json_decode($data, true);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        foreach ($this->data as $valueProduct) {
            try {
                $product = Product::updateOrCreate(['product_code' => $valueProduct['products']['product_code']], $valueProduct['products']);
            } catch (\Exception $e) {
                LogBackendError::logExceptionMessage("SyncProductOutlet/queueProduct=>" . $e->getMessage(), $e);
                DB::rollback();
            }
            try {
                ProductPrice::updateOrCreate(['id_product' => $product->id_product, 'id_outlet' => $valueProduct['product_prices']['id_outlet']], $valueProduct['product_prices']);
            } catch (\Exception $e) {
                LogBackendError::logExceptionMessage("SyncProductOutlet/queueProductPrice=>" . $e->getMessage(), $e);
                DB::rollback();
            }
        }
        DB::commit();
    }
}
