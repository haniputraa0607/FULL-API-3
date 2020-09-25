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
class FraudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user,$data,$type;
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
        if($this->type == 'transaction'){
            $dataTrx = Transaction::where('transaction_receipt_number', $this->data['trx_id']??$this->data['transaction_receipt_number'])->first();
            $dataUser = User::where('id', $this->user['id'])->with('memberships')->first();
            $outlet = Outlet::where('id_outlet', $dataTrx['id_outlet'])->first();

            if (!empty($dataTrx['id_user'])) {
                $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->where('fraud_settings_status', 'Active')->first();
                $fraudTrxWeek = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->where('fraud_settings_status', 'Active')->first();

                $geCountTrxDay = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('transactions.id_user', $this->user['id'])
                    ->whereRaw('DATE(transactions.transaction_date) = "' . date('Y-m-d', strtotime($this->data['date_time']??$this->data['transaction_date'])) . '"')
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->where('transactions.id_transaction','<',$dataTrx['id_transaction'])
                    ->count();

                $currentWeekNumber = date('W', strtotime($this->data['date_time']??$this->data['transaction_date']));
                $currentYear = date('Y', strtotime($this->data['date_time']??$this->data['transaction_date']));
                $dto = new DateTime();
                $dto->setISODate($currentYear, $currentWeekNumber);
                $start = $dto->format('Y-m-d');
                $dto->modify('+6 days');
                $end = $dto->format('Y-m-d');

                $geCountTrxWeek = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                    ->where('id_user', $this->user['id'])
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->whereRaw('Date(transactions.transaction_date) BETWEEN "' . $start . '" AND "' . $end . '"')
                    ->where('transactions.id_transaction','<',$dataTrx['id_transaction'])
                    ->count();

                $countTrxDay = $geCountTrxDay + 1;
                $countTrxWeek = $geCountTrxWeek + 1;

                $pointBefore = 0;
                $pointValue = 0;


                if ($countTrxDay > $fraudTrxDay['parameter_detail'] && $fraudTrxDay) {
                    $dataUpdate = [
                        'fraud_flag' => 'transaction day',
                        'transaction_point_earned' => NULL,
                        'transaction_cashback_earned' => NULL,
                    ];
                } elseif ($countTrxWeek > $fraudTrxWeek['parameter_detail'] && $fraudTrxWeek) {
                    $dataUpdate = [
                        'fraud_flag' => 'transaction week',
                        'transaction_point_earned' => NULL,
                        'transaction_cashback_earned' => NULL,
                    ];
                } else {
                    $dataUpdate = [
                        'fraud_flag' => NULL
                    ];

                    if($dataTrx['trasaction_type'] == 'Offline'){
                        $config['point']    = Configs::where('config_name', 'point')->first()->is_active;
                        $config['balance']  = Configs::where('config_name', 'balance')->first()->is_active;
                        $settingPoint       = Setting::where('key', 'point_conversion_value')->first()->value;

                        if ($config['point'] == '1') {
                            if (isset($user['memberships'][0]['membership_name'])) {
                                $level = $dataUser['memberships'][0]['membership_name'];
                                $percentageP = $dataUser['memberships'][0]['benefit_point_multiplier'] / 100;
                            } else {
                                $level = null;
                                $percentageP = 0;
                            }
                        }

                        if ($dataTrx['transaction_point_earned'] && $dataTrx['transaction_payment_type'] != 'Balance') {
                            $dataLog = [
                                'id_user'                     => $dataTrx['id_user'],
                                'point'                       => $dataTrx['transaction_point_earned'],
                                'id_reference'                => $dataTrx['id_transaction'],
                                'source'                      => 'Transaction',
                                'grand_total'                 => $dataTrx['transaction_grandtotal'],
                                'point_conversion'            => $settingPoint,
                                'membership_level'            => $level,
                                'membership_level'            => $level,
                                'membership_point_percentage' => $percentageP * 100
                            ];

                            $insertDataLog = LogPoint::updateOrCreate(['id_user' => $dataTrx['id_user'], 'id_reference' => $dataTrx['id_transaction']], $dataLog);
                            if (!$insertDataLog) {
                                DB::rollBack();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Point Failed'
                                ];
                            }

                            $pointValue = $insertDataLog->point;

                            //update user point
                            $dataUser->points = $pointBefore + $pointValue;
                            $dataUser->update();
                            if (!$dataUser) {
                                DB::rollBack();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Point Failed'
                                ];
                            }
                        }

                        if ($dataTrx['transaction_cashback_earned'] && ($dataTrx['trasaction_type'] == 'Offline' || $dataTrx['transaction_payment_type'] == 'Balance')) {

                            $insertDataLogCash = app('Modules\Balance\Http\Controllers\BalanceController')->addLogBalance($dataTrx['id_user'], $dataTrx['transaction_cashback_earned'], $dataTrx['id_transaction'], 'Transaction', $dataTrx['transaction_grandtotal']);
                            if (!$insertDataLogCash) {
                                DB::rollBack();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Cashback Failed'
                                ];
                            }
                            $usere = User::where('id', $dataTrx['id_user'])->first();
                            $send = app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                                'Transaction Point Achievement',
                                $usere->phone,
                                [
                                    "outlet_name"       => $outlet['outlet_name'],
                                    "transaction_date"  => $dataTrx['transaction_date'],
                                    'id_transaction'    => $dataTrx['id_transaction'],
                                    'receipt_number'    => $dataTrx['transaction_receipt_number'],
                                    'received_point'    => (string) $dataTrx['transaction_cashback_earned'],
                                    'order_id'          => $order_id??''
                                ]
                            );
                            if ($send != true) {
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => 'Failed Send notification to customer'
                                ]);
                            }
                            $pointValue = $insertDataLogCash->balance;
                        }
                        $checkMembership = app('Modules\Membership\Http\Controllers\ApiMembership')->calculateMembership($dataUser['phone']);
                    }

                }

                $updatePointCashback = Transaction::where('id_transaction', $dataTrx['id_transaction'])
                    ->update($dataUpdate);

                if (!$updatePointCashback) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed update Point and Cashback']
                    ]);
                }

                if ($fraudTrxDay) {
                    $checkFraud = app('Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraud($fraudTrxDay, $this->user, null, $countTrxDay, $countTrxWeek, $this->data['date_time']??$this->data['transaction_date'], 0, $this->data['trx_id']??$dataTrx['transaction_receipt_number']);
                }

                if ($fraudTrxWeek) {
                    $checkFraud = app('Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraud($fraudTrxWeek, $this->user, null, $countTrxDay, $countTrxWeek, $this->data['date_time']??$this->data['transaction_date'], 0, $this->data['trx_id']??$dataTrx['transaction_receipt_number']);
                }
            }
        }elseif ($this->type == 'referral user'){
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->fraudCheckReferralUser($this->data);
        }elseif ($this->type == 'referral'){
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->fraudCheckReferral($this->data);
        }elseif ($this->type == 'transaction_in_between'){
            app('Modules\SettingFraud\Http\Controllers\ApiFraud')->cronFraudInBetween($this->user);
        }

        return 'success';
    }
}
