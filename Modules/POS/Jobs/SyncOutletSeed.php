<?php

namespace Modules\POS\Jobs;

use App\Http\Models\Outlet;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\Disburse\Entities\UserFranchisee;
use Modules\Disburse\Entities\UserFranchiseeOultet;

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
                'id_city'                   => $value['id_city']
            ]);
            $id_user_franchise = UserFranchisee::updateOrCreate([
                'id_user_franchisee_seed'   => $value['user_franchisee']['id']
            ], [
                'id_user_franchisee_seed'   => $value['user_franchisee']['id'],
                'phone'                     => $value['user_franchisee']['phone'],
                'email'                     => $value['user_franchisee']['email']
            ]);
            UserFranchiseeOultet::updateOrCreate([
                'id_outlet'                 => $id_outlet->id_outlet,
                'id_user_franchisee'        => $id_user_franchise->id_user_franchisee
            ],[
                'id_outlet'                 => $id_outlet->id_outlet,
                'id_user_franchisee'        => $id_user_franchise->id_user_franchisee
            ]);
        }
        
        DB::commit();
    }
}
