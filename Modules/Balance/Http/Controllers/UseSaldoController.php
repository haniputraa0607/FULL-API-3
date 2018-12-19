<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use App\Http\Models\TransactionBalance;
use App\Http\Models\UsersMembership;
use App\Http\Models\Setting;
use App\Http\Models\LogBalance;

use App\Lib\MyHelper;

use DB;
use Hash;

class UseSaldoController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->pos     = "Modules\POS\Http\Controllers\ApiPOS";
        $this->topup   = "Modules\Balance\Http\Controllers\NewTopupController";
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance = "Modules\Balance\Http\Controllers\BalanceController";
    }

    public function use(Request $request)
    {
        DB::beginTransaction();

        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 

        $checkOutlet = Outlet::where('outlet_code', $post['store_code'])->first();
        if (empty($checkOutlet)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Store code is not valid']]);
        }

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

        $checkHashBefore = app($this->topup)->checkHash('log_balances', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $data = [
            'receipt_number' => 'USE-'.date('Ymdis').'-BL',
            'id_user'        => $user['id'],
            'id_outlet'      => $checkOutlet['id_outlet'],
            'nominal'        => $post['amount'],
            'approval_code'  => MyHelper::createrandom(5, 'Besar Angka'),
            'expired_at'     => date('Y-m-d H:i:s', strtotime('+5 minutes')),
            'status'         => 'Pending'
        ];

        $ins = TransactionBalance::create($data);
        if (!$ins) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Use balance failed']]);
        }

        $dataIns = TransactionBalance::where('id_transaction_balance', $ins['id_transaction_balance'])->first();
        if (empty($dataIns)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Use balance failed']]);
        }

        $send = app($this->autocrm)->SendAutoCRM('Generate Approval Code', $user['phone'], ['notif_type' => 'generate_code', 'code' => $dataIns['approval_code']]);
        if (!$send) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Create approval code failed']]);
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }

    public function approved(Request $request)
    {
        DB::beginTransaction();
        $post = $request->json()->all();

        $checkAp = TransactionBalance::where('approval_code', $post['approval_code'])->first();
        if (empty($checkAp)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Data not found']]);
        }

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

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        }

        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($checkAp['expired_at']))) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Approval code has been expired']]);
        }

        if ($checkAp['status'] != 'Pending') {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Approval code has been confirmed']]);
        }

        $checkAp->status = 'Confirmed';
        $checkAp->update();
        if (!$checkAp) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Confirm failed']]);
        }

        $checkHashBefore = app($this->topup)->checkHash('log_balances', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $checkMembership = UsersMembership::where('id_user', $user['id'])->orderBy('created_at', 'DESC')->get()->toArray();

        if (count($checkMembership) > 0) {
            $level  = $checkMembership[0]['membership_name'];
            $percen = $checkMembership[0]['benefit_cashback_multiplier'];
        } else {
            $level  = null;
            $percen = 0;
        }

        $settingCashback = Setting::where('key', 'cashback_conversion_value')->first();

        $dataSetBalance = [
            'id_user'                        => $user['id'],
            'balance'                        => -$checkAp['nominal'],
            'balance_before'                 => app($this->balance)->balanceNow($user['id']),
            'balance_after'                  => app($this->balance)->balanceNow($user['id']) - $checkAp['nominal'],
            'id_reference'                   => '',
            'source'                         => 'Transaction',
            'grand_total'                    => $checkAp['nominal'],
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

        $checkAp->approval_code = MyHelper::createrandom(10, 'Besar Angka');
        $checkAp->update();
        if (!$checkAp) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
        }

        DB::commit();
        return response()->json(['status' => 'success', 'result' => ['request_code' => $checkAp->approval_code]]);
    }

    public function useVoid(Request $request)
    {
        DB::beginTransaction();
        $post = $request->json()->all();

        $checkAp = TransactionBalance::where('approval_code', $post['request_code'])->first();
        if (empty($checkAp)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Data not found']]);
        }

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

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        }

        $checkHashBefore = app($this->topup)->checkHash('log_balances', $user['id']);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction user data']]);
        }

        $checkMembership = UsersMembership::where('id_user', $user['id'])->orderBy('created_at', 'DESC')->get()->toArray();

        if (count($checkMembership) > 0) {
            $level  = $checkMembership[0]['membership_name'];
            $percen = $checkMembership[0]['benefit_cashback_multiplier'];
        } else {
            $level  = null;
            $percen = 0;
        }

        $settingCashback = Setting::where('key', 'cashback_conversion_value')->first();

        $dataSetBalance = [
            'id_user'                        => $user['id'],
            'balance'                        => $checkAp['nominal'],
            'balance_before'                 => app($this->balance)->balanceNow($user['id']),
            'balance_after'                  => app($this->balance)->balanceNow($user['id']) + $checkAp['nominal'],
            'id_reference'                   => '',
            'source'                         => 'Transaction',
            'grand_total'                    => $checkAp['nominal'],
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
        return response()->json(['status' => 'success']);
    }
}
