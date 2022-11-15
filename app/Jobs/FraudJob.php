<?php

namespace App\Jobs;

use App\Http\Models\Configs;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\LogPoint;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionSetting;
use App\Http\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTime;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\Transaction\Entities\TransactionGroup;

class FraudJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $user;
    protected $data;
    protected $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $data, $type)
    {
        $this->user   = $user;
        $this->data   = $data;
        $this->type   = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->type == 'transaction') {
            $dataTrx = TransactionGroup::where('id_transaction_group', $this->data['id_transaction_group'])->first();
            $dataUser = User::where('id', $this->data['id_user'])->with('memberships')->first();

            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraudTrxOnline($dataUser, $dataTrx);
        } elseif ($this->type == 'referral user') {
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->fraudCheckReferralUser($this->data);
        } elseif ($this->type == 'referral') {
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->fraudCheckReferral($this->data);
        } elseif ($this->type == 'transaction_in_between') {
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->cronFraudInBetween($this->user);
        }

        return 'success';
    }
}
