<?php

namespace Modules\MokaPOS\Jobs;

use App\Http\Models\LogBackendError;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\MokaPOS\Entities\MokaPOSOutlet;
use Modules\MokaPOS\Entities\OutletMokaPOS;

class SyncOutlet implements ShouldQueue
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
        foreach ($this->data as $value) {
            try {
                $outlet = OutletMokaPOS::updateOrCreate(['id_moka_outlet' => $value['outlet']['id_moka_outlet']], $value['outlet']);
            } catch (\Exception $e) {
                LogBackendError::logExceptionMessage("SyncOutlet/queueOutlet=>" . $e->getMessage(), $e);
                DB::rollback();
            }

            $value['moka_outlet']['id_outlet'] = $outlet->id_outlet;
            try {
                MokaPOSOutlet::updateOrCreate(['id_moka_outlet' => $value['moka_outlet']['id_moka_outlet']], $value['moka_outlet']);
            } catch (\Exception $e) {
                LogBackendError::logExceptionMessage("SyncOutlet/queueOutletMoka=>" . $e->getMessage(), $e);
                DB::rollback();
            }
        }
        DB::commit();
    }
}
