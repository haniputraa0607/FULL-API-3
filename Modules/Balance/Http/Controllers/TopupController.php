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

class TopupController extends Controller
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

        if (!isset($post['user_type'])) {
            return $this->topupPos($post);
        }

        if (isset($post['user_type'])) {
            if ($post['user_type'] == 'Merchant App') {
                # code...
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
        $post['topup_payment_status'] = 'Pending';

        $qr = $post['uid'];
        $timestamp = substr($qr, 0, 10);
        $phoneqr = str_replace($timestamp,'',$qr);

        $time = date('Y-m-d h:i:s', strtotime('+10 minutes', strtotime(date('Y-m-d h:i:s', $timestamp))));
        if (date('Y-m-d h:i:s') > $time) {
            // DB::rollback();
            // return response()->json(['status' => 'fail', 'messages' => ['Mohon refresh qrcode dan ulangi scan member']]); 
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

        $otp = MyHelper::createrandom(6, 'Angka');

        $dataTopupPos = [
            'id_log_topup' => $createTopUp['id_log_topup'],
            'id_outlet'    => $checkOutlet['id_outlet'],
            'otp'          => '',
            'status'       => 'Pending',
            'expired_at'   => ''
        ];

        $createTopupPos = LogTopupPos::create($dataTopupPos);
        if (!$createTopupPos) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Create approval code failed']]);
        }

        $send = app($this->autocrm)->SendAutoCRM('Generate Approval Code', $user['phone'], ['notif_type' => 'generate_code']);
        if (!$send) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Create approval code failed']]);
        }

        DB::commit();
        return response()->json(['status' => 'success']);
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

        $data['id_user'] = $user['id'];
        $data['nominal_bayar'] = $bayar;
        $data['balance_before'] = app($this->balance)->balanceNow($user['id']);
        $data['balance_after'] = $data['balance_before'] + $data['topup_value'];
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

    public function generateCode(Request $request)
    {
        DB::beginTransaction();

        $user = $request->user();
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['User not login']]);
        }

        $post = $request->json()->all();
        $checkLog = LogTopupPos::where('id_log_topup_pos', $post['id_log_topup_pos'])->first();
        if (empty($checkLog)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup not found']]);
        }

        $otp = MyHelper::createrandom(6, 'Angka');

        $checkLog->otp = $otp;
        $checkLog->expired_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $checkLog->update();
        if (!$checkLog) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Create approval code failed']]);
        }

        /* QR CODE */
        $qr      = $checkLog->otp;
        $qr      = urlencode("#".MyHelper::encryptQRCode($qr)."#");
        
        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode = html_entity_decode($qrCode);

        DB::commit();
        return response()->json([
            'status' => 'success',
            'result' => ['code' => $checkLog->otp, 'expired' => $checkLog->expired_at, 'qrcode' => $qrCode]
        ]);
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

        DB::beginTransaction();
        $checkId = LogTopupPos::where('id_log_topup_pos', $post['id_log_topup_pos'])->first();
        if (empty($checkId)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Id invalid']]);
        }

        $checkOtp = LogTopupPos::where('otp', $post['otp'])->first();
        if (empty($checkOtp)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Approval code invalid']]);
        }

        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($checkId['expired_at']))) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Approval code has been expired']]);
        }

        $checkId->status = 'Confirmed';
        $checkId->update();
        if (!$checkId) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Trnasaction topup failed']]);
        }

        $checkLogTopup = LogTopup::where('id_log_topup', $checkId['id_log_topup'])->first();
        if (empty($checkLogTopup)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup not completed']]);
        }

        $checkLogTopup->topup_payment_status = 'Completed';
        $checkLogTopup->update();
        if (!$checkLogTopup) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup failed']]);
        }

        $dataHash = [
            'id_log_topup'          => $checkLogTopup['id_log_topup'],
            'id_user'               => $checkLogTopup['id_user'],
            'balance_before'        => $checkLogTopup['balance_before'],
            'nominal_bayar'         => $checkLogTopup['nominal_bayar'],
            'topup_value'           => $checkLogTopup['topup_value'],
            'balance_after'         => $checkLogTopup['balance_after'],
            'transaction_reference' => null,
            'source'                => null,
            'topup_payment_status'  => $checkLogTopup['topup_payment_status'],
            'payment_type'          => $checkLogTopup['payment_type']
        ];

        $encodeCheck = json_encode($dataHash);
        $enc = Hash::make($encodeCheck);

        $checkUpdate = LogTopup::where('id_log_topup', $checkLogTopup['id_log_topup'])->first();
        if (!$checkUpdate) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup failed']]);        }

        $checkUpdate->enc = $enc;
        $checkUpdate->update();
        if (!$checkUpdate) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction topup failed']]);
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

        DB::commit();
        return response()->json([
            'status' => 'success'
        ]);

    }

    public function topupConfirmManual($post)
    {
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

        $dataManual = [
            'id_transaction'           => $check['id_transaction'],
            'payment_date'             => $post['payment_date'],
            'id_bank_method'           => $post['id_bank_method'],
            'id_bank'                  => $post['id_bank'],
            'id_manual_payment'        => $post['id_manual_payment'],
            'payment_time'             => $post['payment_time'],
            'payment_bank'             => $post['payment_bank'],
            'payment_method'           => $post['payment_method'],
            'payment_account_number'   => $post['payment_account_number'],
            'payment_account_name'     => $post['payment_account_name'],
            'payment_nominal'          => $check['transaction_grandtotal'],
            'payment_receipt_image'    => $post['payment_receipt_image'],
            'payment_note'             => $post['payment_note']
        ];

        $insertPayment = MyHelper::manualPayment($dataManual, 'logtopup');

        if (isset($insertPayment) && $insertPayment == 'success') {
            $update = LogTopup::where('id_log_topup', $post['id'])->update(['topup_payment_status' => 'Paid']);

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

        $receipt = 'TOP-'.date('YmdHisis');

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

            $insertNotifMidtrans = LogTopupMidtrans::create($dataNotifMidtrans);
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
                'grand_total' => $checkLog['nominal_bayar']
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
        
        if (empty($check) || empty($check['enc'])) {
            return true;
        }

        if ($table == 'log_topups') {
            $dataHash = [
                'id_log_topup'          => $check['id_log_topup'],
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

        $encodeCheck = json_encode($dataHash);

        if (MyHelper::decrypt2019($check['enc']) == $encodeCheck) {
            return true;
        }

        return false;
    }
}
