<?php

namespace Modules\POS\Jobs;

use App\Http\Models\Outlet;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Http\Models\OutletSchedule;
use Modules\Disburse\Entities\UserFranchise;
use Modules\Disburse\Entities\UserFranchiseOultet;

class SyncOutletSeed implements ShouldQueue
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
        DB::beginTransaction();

        foreach ($this->data as $key => $value) {
            $franchise = ($value['status_franchise'] == 0) ? 'Non Franchise' : 'Franchise' ;

            $id_outlet = Outlet::updateOrCreate([
                'id_outlet_seed'            => $value['id']
            ], [
                'id_outlet_seed'            => $value['id'],
                'outlet_code'               => 'JJ' . $value['id'],
                'outlet_name'               => $value['name'],
                'outlet_address'            => $value['address'],
                'outlet_longitude'          => $value['long'],
                'outlet_latitude'           => $value['lang'],
                'outlet_status'             => $value['status'],
                'id_city'                   => $value['id_city'],
                'status_franchise'          => $value['status_franchise']
            ]);
            $id_user_franchise = UserFranchise::updateOrCreate([
                'id_user_franchise_seed'   => $value['user_franchisee']['id']
            ], [
                'id_user_franchise_seed'    => $value['user_franchisee']['id'],
                'phone'                     => $value['user_franchisee']['phone'],
                'email'                     => $value['user_franchisee']['email'],
                'user_franchise_type'       => $franchise
            ]);
            UserFranchiseOultet::updateOrCreate([
                'id_outlet'                 => $id_outlet->id_outlet,
                'id_user_franchise'         => $id_user_franchise->id_user_franchise
            ],[
                'id_outlet'                 => $id_outlet->id_outlet,
                'id_user_franchise'         => $id_user_franchise->id_user_franchise,
                'status_franchise'          => $value['status_franchise']
            ]);

            //create schedule for new outlet
            $schedule = OutletSchedule::where('id_outlet', $id_outlet->id_outlet)->first();
            if(empty($schedule)){
                $dataSchedule = [];
                $now = date('Y-m-d H:i:s');
                $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                foreach($days as $day){
                    $dataSchedule[] = [
                        'id_outlet'     => $id_outlet->id_outlet,
                        'day'           => $day,
                        'open'          => '07:00',
                        'close'         => '22:00',
                        'created_at'    => $now,
                        'updated_at'    => $now
                    ];
                }
                OutletSchedule::insert($dataSchedule);
            }
        }

        DB::commit();
    }
}
