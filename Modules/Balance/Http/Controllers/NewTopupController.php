<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;

use App\Lib\MyHelper;
use App\Lib\Midtrans;

use App\Http\Models\TopupNominal;
use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\LogTopup;
use App\Http\Models\LogBalance;
use App\Http\Models\LogTopupPos;
use App\Http\Models\UsersMembership;
use App\Http\Models\Outlet;
use App\Http\Models\LogTopupMidtrans;
use App\Http\Models\ManualPaymentMethod;

use Hash;
use DB;

class NewTopupController extends Controller
{
    private $model = 'App\Http\Models\\';
    public $saveImage = "img/topup/manual/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->pos     = "Modules\POS\Http\Controllers\ApiPOS";
        $this->balance = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";

        $this->middleware('auth:api', ['only' => ['topupCustomer', 'topupConfirmMidtrans']]);
    }

    public function topupNominalList(Request $request)
    {
        $post = $request->json()->all();
        $canInput = false;

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 

        if (!isset($post['type'])) {
            $post['type'] = 'POS';
        }

        if (isset($post['type'])) {
            if ($post['type'] == 'Customer') {
                $post['type'] = 'Customer App';
            } elseif ($post['type'] == 'Merchant') {
                $post['type'] = 'Merchant App';
            }
        }

        $list = TopupNominal::where('type', $post['type'])->get()->toArray();
        if (empty($list)) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['List not found']
            ]);
        }

        if ($post['type'] == 'POS') {
            $key = 'pos_topup_input_manual';
        } elseif ($post['type'] == 'Customer App') {
            $key = 'customerapp_topup_input_manual';
        } elseif ($post['type'] == 'Merchant App') {
            $key = 'merchantapp_topup_input_manual';
        }

        $setting = Setting::where('key', $key)->first();
        if (!empty($setting)) {
            if ($setting['value'] == '1') {
                $canInput = true;
            }
        }

        return response()->json([
            'status'           => 'success',
            'result'           => ['saldo_packages' => $list, 'can_manual_input' => $canInput]
        ]);
    }

    public function topupNominalDo(Request $request)
    {
        DB::beginTransaction();

        $post = $request->json()->all();
        $post['receipt'] = 'TOP-'.date('sYmdHs');

        if (!isset($post['user_type'])) {
            return $this->topupPos($post);
        }

        if (isset($post['user_type'])) {
            if ($post['user_type'] == 'Merchant App') {
                return $this->topupPos($post);
            } elseif ($post['user_type'] == 'Customer App') {
                return $this->topupCustomer($post);
            }
        }
    }

    public function topupCustomer($post)
    {
        $user = auth('api')->user();
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['User not login']]);
        }

        $checkHashBefore = $this->checkHash('log_topups', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $post['payment_type'] = '';
        $post['topup_payment_status'] = '';

        $createTopUp = $this->createLogTopup($post, $user);
        if (!$createTopUp) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup failed']]);
        }

        DB::commit();
        return response()->json([
            'status' => 'success'
        ]);
    }

    public function topupConfirmManual($post)
    {
        DB::beginTransaction();

        if (isset($post['id_manual_payment_method'])) {
            $checkPaymentMethod = ManualPaymentMethod::where('id_manual_payment_method', $post['id_manual_payment_method'])->first();
            if (empty($checkPaymentMethod)) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Payment Method Not Found']
                ]);
            }
        }

        if (isset($post['payment_receipt_image'])) {
            if (!file_exists($this->saveImage)) {
                mkdir($this->saveImage, 0777, true);
            }

            $save = MyHelper::uploadPhotoStrict($post['payment_receipt_image'], $this->saveImage, 300, 300);

            if (isset($save['status']) && $save['status'] == "success") {
                $post['payment_receipt_image'] = $save['path'];
            }
            else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ]);
            }
        } else {
            $post['payment_receipt_image'] = null;
        }

        $check = LogTopup::where('id_log_topup', $post['id_log_topup'])->first();
        if (empty($check)) {
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Payment Method Not Found']
            ]);
        }

        $dataManual = [
            'id_log_topup'             => $post['id_log_topup'],
            'payment_date'             => $post['payment_date'],
            'id_manual_payment_method' => $post['id_manual_payment_method'],
            'payment_time'             => $post['payment_time'],
            'payment_bank'             => $post['payment_bank'],
            'payment_method'           => $post['payment_method'],
            'payment_account_number'   => $post['payment_account_number'],
            'payment_account_name'     => $post['payment_account_name'],
            'payment_nominal'          => $check['nominal_bayar'],
            'payment_receipt_image'    => $post['payment_receipt_image'],
            'payment_note'             => $post['payment_note']
        ];

        $insertPayment = MyHelper::manualPayment($dataManual, 'logtopup');

        if (isset($insertPayment) && $insertPayment == 'success') {
            $update = LogTopup::where('id_log_topup', $post['id_log_topup'])->update(['topup_payment_status' => 'Paid']);

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Topup Failed']
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'result' => $check
            ]);
        } elseif (isset($insertPayment) && $insertPayment == 'fail') {
            DB::rollback();
            return response()->json([
                'status' => 'fail',
                'messages' => ['Transaction Failed']
            ]);
        } else {
            DB::rollback();
            return response()->json([
                'status' => 'fail',
                'messages' => ['Transaction Failed']
            ]);
        }
    }

    public function topupConfirmMidtrans($post)
    {
        DB::beginTransaction();

        $user = auth('api')->user();
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['User not login']]);
        }

        $dataUser = [
            'first_name' => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone']
        ];

        $checkLog = LogTopup::where('id_log_topup', $post['id_log_topup'])->first();
        if (empty($checkLog)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Log topup not found']]);
        }

        $checkLog->topup_payment_status = 'Pending';
        $checkLog->payment_type = 'Midtrans';
        $checkLog->update();
        if (!$checkLog) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Confirm topup failed']]);
        }

        $dataHash = [
            'id_log_topup'          => $checkLog['id_log_topup'],
            'receipt_number'        => $checkLog['receipt_number'],
            'id_user'               => $checkLog['id_user'],
            'balance_before'        => $checkLog['balance_before'],
            'nominal_bayar'         => $checkLog['nominal_bayar'],
            'topup_value'           => $checkLog['topup_value'],
            'balance_after'         => $checkLog['balance_after'],
            'transaction_reference' => null,
            'source'                => null,
            'topup_payment_status'  => $checkLog['topup_payment_status'],
            'payment_type'          => $checkLog['payment_type']
        ];

        $encodeCheck = json_encode($dataHash);
        $enc = Hash::make($encodeCheck);

        $checkLog->enc = $enc;
        $checkLog->update();
        if (!$checkLog) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Confirm topup failed']]);
        }

        $receipt = $checkLog['receipt_number'];

        $connectMidtrans = Midtrans::token($receipt, $checkLog['nominal_bayar'], $dataUser);

        if (empty($connectMidtrans['token'])) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => [
                    'Midtrans token is empty. Please try again.'
                ]
            ]);
        } else {
            $dataNotifMidtrans = [
                'id_log_topup'   => $checkLog['id_log_topup'],
                'gross_amount'   => $checkLog['nominal_bayar'],
                'order_id'       => $receipt,
            ];

            $insertNotifMidtrans = LogTopupMidtrans::updateOrCreate(['order_id' => $receipt], $dataNotifMidtrans);
            if (!$insertNotifMidtrans) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => [
                        'Payment Midtrans Failed.'
                    ]
                ]);
            }

            $detailPayment = [
                'grand_total'    => $checkLog['nominal_bayar'],
                'receipt_number' => $checkLog['receipt_number']
            ];

            $dataMidtrans['payment'] = $detailPayment;

            DB::commit();
            return response()->json([
                'status'           => 'success',
                'snap_token'       => $connectMidtrans['token'],
                'transaction_data' => $dataMidtrans,
            ]);
        }
    }

    public function topupPos($post)
    {
        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            DB::rollback();
            return response()->json($api); 
        }

        $checkOutlet = Outlet::where('outlet_code', $post['store_code'])->first();
        if (empty($checkOutlet)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Store code is not valid']]);
        }

        $post['payment_type'] = 'Cash';
        $post['topup_payment_status'] = 'Completed';

        $qr = $post['uid'];
        $timestamp = substr($qr, 0, 10);
        $phoneqr = str_replace($timestamp,'',$qr);

        $time = date('Y-m-d h:i:s', strtotime('+10 minutes', strtotime(date('Y-m-d h:i:s', $timestamp))));
        if (date('Y-m-d h:i:s') > $time) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Mohon refresh qrcode dan ulangi scan member']]); 
        }

        $user = User::where('phone', $phoneqr)->first(); 
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
        }

        $checkHashBefore = $this->checkHash('log_topups', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $createTopUp = $this->createLogTopup($post, $user);
        if (!$createTopUp) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup failed']]);
        }

        $checkLogTopup = LogTopup::where('id_log_topup', $createTopUp['id_log_topup'])->first();
        if (empty($checkLogTopup)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup not completed']]);
        }

        $checkHashBefore = $this->checkHash('log_balances', $checkLogTopup['id_user']);

        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $checkMembership = UsersMembership::where('id_user', $checkLogTopup['id_user'])->orderBy('created_at', 'DESC')->get()->toArray();

        if (count($checkMembership) > 0) {
            $level  = $checkMembership[0]['membership_name'];
            $percen = $checkMembership[0]['benefit_cashback_multiplier'];
        } else {
            $level  = null;
            $percen = 0;
        }

        $settingCashback = Setting::where('key', 'cashback_conversion_value')->first();

        $dataSetBalance = [
            'id_user'                        => $checkLogTopup['id_user'],
            'balance'                        => $checkLogTopup['topup_value'],
            'balance_before'                 => $checkLogTopup['balance_before'],
            'balance_after'                  => $checkLogTopup['balance_after'],
            'id_reference'                   => $checkLogTopup['id_log_topup'],
            'source'                         => 'Topup',
            'grand_total'                    => $checkLogTopup['topup_value'],
            'ccashback_conversion'           => $settingCashback['value'],
            'membership_level'               => $level,
            'membership_cashback_percentage' => $percen
        ];

        $insertDataBalance = LogBalance::create($dataSetBalance);
        if (!$insertDataBalance) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $checkUpdateEnc = LogBalance::where('id_log_balance', $insertDataBalance['id_log_balance'])->first();
        if (empty($checkUpdateEnc)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $dataHashBalance = [
            'id_log_balance'                 => $checkUpdateEnc['id_log_balance'],
            'id_user'                        => $checkUpdateEnc['id_user'],
            'balance'                        => $checkUpdateEnc['balance'],
            'balance_before'                 => $checkUpdateEnc['balance_before'],
            'balance_after'                  => $checkUpdateEnc['balance_after'],
            'id_reference'                   => $checkUpdateEnc['id_reference'],
            'source'                         => $checkUpdateEnc['source'],
            'grand_total'                    => $checkUpdateEnc['grand_total'],
            'ccashback_conversion'           => $checkUpdateEnc['ccashback_conversion'],
            'membership_level'               => $checkUpdateEnc['membership_level'],
            'membership_cashback_percentage' => $checkUpdateEnc['membership_cashback_percentage']
        ];

        $encodeCheck = json_encode($dataHashBalance);
        $enc = Hash::make($encodeCheck);

        $checkUpdateEnc->enc = $enc;
        $checkUpdateEnc->update();
        if (!$checkUpdateEnc) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $send = app($this->autocrm)->SendAutoCRM('Topup Success', $user['phone'], ['notif_type' => 'topup', 'name' => $user['name'], 'date' => $createTopUp['created_at'], 'status' => $createTopUp['topup_payment_status']]);
        if (!$send) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Create approval code failed']]);
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }

    public function topupConfirm(Request $request)
    {

        $post = $request->json()->all();

        if (isset($post['payment_type'])) {
            if ($post['payment_type'] == 'Midtrans') {
                return $this->topupConfirmMidtrans($post);
            } elseif ($post['payment_type'] == 'Manual') {
                return $this->topupConfirmManual($post);
            }
        }
    }

    public function createLogTopup($post, $user)
    {
        if (isset($post['id_saldo_package'])) {
            $checkSaldo = TopupNominal::where('id_topup_nominal', $post['id_saldo_package'])->first();

            if (empty($checkSaldo)) {
                return false;
            }

            $data['topup_value'] = $checkSaldo['nominal_topup'];
            $bayar = $checkSaldo['nominal_bayar'];
        }

        if (isset($post['nominal'])) {
            $data['topup_value'] = $post['nominal'];
            $bayar = $post['nominal'];
        }

        $data['payment_type'] = $post['payment_type'];
        $data['topup_payment_status'] = $post['topup_payment_status'];

        $data['id_user']        = $user['id'];
        $data['receipt_number'] = $post['receipt'];
        $data['nominal_bayar']  = $bayar;
        $data['balance_before'] = app($this->balance)->balanceNow($user['id']);
        $data['balance_after']  = $data['balance_before'] + $data['topup_value'];

        $createTopUp = LogTopup::create($data);
        if (!$createTopUp) {
            return false;
        }

        $checkUpdate = LogTopup::where('id_log_topup', $createTopUp['id_log_topup'])->first();
        if (!$checkUpdate) {
            return false;
        }

        $dataHash = [
            'id_log_topup'          => $checkUpdate['id_log_topup'],
            'receipt_number'        => $checkUpdate['receipt_number'],
            'id_user'               => $checkUpdate['id_user'],
            'balance_before'        => $checkUpdate['balance_before'],
            'nominal_bayar'         => $checkUpdate['nominal_bayar'],
            'topup_value'           => $checkUpdate['topup_value'],
            'balance_after'         => $checkUpdate['balance_after'],
            'transaction_reference' => null,
            'source'                => null,
            'topup_payment_status'  => $checkUpdate['topup_payment_status'],
            'payment_type'          => $checkUpdate['payment_type']
        ];

        $encodeCheck = json_encode($dataHash);
        $enc = Hash::make($encodeCheck);

        $checkUpdate->enc = $enc;
        $checkUpdate->update();
        if (!$checkUpdate) {
            return false;
        }

        return $checkUpdate;
    }

    public function checkHash($table, $id_user)
    {
        $columns = Schema::getColumnListing($table);
        if (empty($columns)) {
            return false;
        }

        if (!in_array('enc', $columns)) {
            return false;
        }

        $className = $this->model . studly_case(str_singular($table));
        if (!class_exists($className)) {
            return false;
        }

        $check = $className::where('id_user', $id_user)->orderBy('created_at', 'DESC')->first();

        if (count($check->toArray()) < 1) {
            return true;
        }

        if (!isset($check['enc'])) {
            return true;
        }

        if ($table == 'log_topups') {
            $dataHash = [
                'id_log_topup'          => $check['id_log_topup'],
                'receipt_number'        => $check['receipt_number'],
                'id_user'               => $check['id_user'],
                'balance_before'        => $check['balance_before'],
                'nominal_bayar'         => $check['nominal_bayar'],
                'topup_value'           => $check['topup_value'],
                'balance_after'         => $check['balance_after'],
                'transaction_reference' => null,
                'source'                => null,
                'topup_payment_status'  => $check['topup_payment_status'],
                'payment_type'          => $check['payment_type']
            ];
        } elseif ($table == 'log_balances') {
            $dataHash = [
                'id_log_balance'                 => $check['id_log_balance'],
                'id_user'                        => $check['id_user'],
                'balance'                        => $check['balance'],
                'balance_before'                 => $check['balance_before'],
                'balance_after'                  => $check['balance_after'],
                'id_reference'                   => $check['id_reference'],
                'source'                         => $check['source'],
                'grand_total'                    => $check['grand_total'],
                'ccashback_conversion'           => $check['ccashback_conversion'],
                'membership_level'               => $check['membership_level'],
                'membership_cashback_percentage' => $check['membership_cashback_percentage']
            ];
        }
        
        $encodeCheck = utf8_encode(json_encode($dataHash));
        if (MyHelper::decryptkhususnew($check['enc']) == $encodeCheck) {
            return true;
        }
        // if (Hash::check($encodeCheck, $check['enc'])) {
        //     return true;
        // }

        return false;
    }

    public function refund(Request $request)
    {
        DB::beginTransaction();

        $post = $request->json()->all();
        $check = LogTopup::where('receipt_number', $post['receipt_number'])->first();
        if (empty($check)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $checkBalance = LogBalance::where(['id_reference' => $check['id_log_topup'], 'source' => 'Topup'])->first();
        if (empty($checkBalance)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $check->topup_payment_status = 'Cancelled';
        $check->update();
        if (!$check) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $user = User::where('id', $check['id_user'])->first();
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $checkHashBefore = $this->checkHash('log_topups', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $dataSetBalance = [
            'id_user'                        => $checkBalance['id_user'],
            'balance'                        => -$checkBalance['topup_value'],
            'balance_before'                 => $checkBalance['balance_after'],
            'balance_after'                  => $checkBalance['balance_before'],
            'id_reference'                   => '',
            'source'                         => 'Topup',
            'grand_total'                    => $checkBalance['topup_value'],
            'ccashback_conversion'           => $checkBalance['ccashback_conversion'],
            'membership_level'               => $checkBalance['membership_level'],
            'membership_cashback_percentage' => $checkBalance['membership_cashback_percentage']
        ];

        $insertDataBalance = LogBalance::create($dataSetBalance);
        if (!$insertDataBalance) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $checkUpdateEnc = LogBalance::where('id_log_balance', $insertDataBalance['id_log_balance'])->first();
        if (empty($checkUpdateEnc)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        $dataHashBalance = [
            'id_log_balance'                 => $checkUpdateEnc['id_log_balance'],
            'id_user'                        => $checkUpdateEnc['id_user'],
            'balance'                        => $checkUpdateEnc['balance'],
            'balance_before'                 => $checkUpdateEnc['balance_before'],
            'balance_after'                  => $checkUpdateEnc['balance_after'],
            'id_reference'                   => $checkUpdateEnc['id_reference'],
            'source'                         => $checkUpdateEnc['source'],
            'grand_total'                    => $checkUpdateEnc['grand_total'],
            'ccashback_conversion'           => $checkUpdateEnc['ccashback_conversion'],
            'membership_level'               => $checkUpdateEnc['membership_level'],
            'membership_cashback_percentage' => $checkUpdateEnc['membership_cashback_percentage']
        ];

        $encodeCheck = utf8_encode(json_encode(($dataHashBalance)));
        $enc = MyHelper::encryptkhususnew($encodeCheck);

        $checkUpdateEnc->enc = $enc;
        $checkUpdateEnc->update();
        if (!$checkUpdateEnc) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }
}
