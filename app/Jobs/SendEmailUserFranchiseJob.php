<?php

namespace App\Jobs;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Disburse\Entities\Disburse;
use Modules\Disburse\Entities\DisburseOutlet;
use DB;
use App\Lib\SendMail as Mail;
use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Rap2hpoutre\FastExcel\FastExcel;
use File;
use Storage;
use App\Lib\MyHelper;

class SendEmailUserFranchiseJob implements ShouldQueue
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
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->data as $data) {
            $pin = MyHelper::createrandom(6);
            $user = UserFranchise::where('id_user_franchise', $data)->first();
            $updatePin = UserFranchise::where('id_user_franchise', $data)->update(['password' => bcrypt($pin)]);
            $franchiseOutlet = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                ->where('id_user_franchise', $data)->first();
            $outletCode = $franchiseOutlet['outlet_name'] ?? null;
            $outletName = $franchiseOutlet['outlet_code'] ?? null;

            if ($updatePin) {
                $autocrm = app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                    'New User Franchise',
                    $user['username'],
                    [
                        'password' => $pin,
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'url' => env('URL_PORTAL_MITRA'),
                        'outlet_code' => $outletCode,
                        'outlet_name' => $outletName
                    ],
                    null,
                    false,
                    false,
                    'franchise',
                    1
                );
            }
        }

        return true;
    }
}
