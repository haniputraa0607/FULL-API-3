<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Lib\Midtrans;

use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\User;
use App\Http\Models\LogBalance;
use App\Http\Models\Setting;

use Modules\Deals\Http\Controllers\ApiDealsVoucher;
use Modules\Deals\Http\Controllers\ApiDealsClaim;
use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\Balance\Http\Controllers\BalanceController;

use Modules\Deals\Http\Requests\Deals\Voucher;
use Modules\Deals\Http\Requests\Claim\Paid;
use Modules\Deals\Http\Requests\Claim\PayNow;

use Illuminate\Support\Facades\Schema;

use DB;
use Hash;

class ApiDealsClaimPay extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->deals   = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->voucher = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->claim   = "Modules\Deals\Http\Controllers\ApiDealsClaim";
        $this->balance = "Modules\Balance\Http\Controllers\BalanceController";
        if(\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public $saveImage = "img/receipt_deals/";

    /* BAYAR DEALS */
    function bayar(Request $request)
    {

    }

    /* CLAIM DEALS */
    function claim(Paid $request)
    {
        $post      = $request->json()->all();
        $dataDeals = app($this->claim)->chekDealsData($request->json('id_deals'));
        $id_user   = $request->user()->id;
        if (empty($dataDeals)) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data deals not found']
            ]);
        }
        else {
            DB::beginTransaction();
            // CEK VALID DATE
            if (app($this->claim)->checkValidDate($dataDeals)) {

                if (!empty($dataDeals->deals_voucher_price_point) || !empty($dataDeals->deals_voucher_price_cash)) {
                    if (!empty($dataDeals->deals_voucher_price_point)) {
                        if (!app($this->claim)->checkDealsPoint($dataDeals, $request->user()->id)) {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Your point not enough.']
                            ]);
                        }
                    }

                    //CEK IF BALANCE O
                    if(isset($post['balance']) && $post['balance'] == true){
                        if(app($this->claim)->getPoint($request->user()->id) <= 0){
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Your need more point.']
                            ]);
                        }
                    }

                    // CEK USER ALREADY CLAIMED
                    if (app($this->claim)->checkUserClaimed($request->user(), $dataDeals->id_deals)) {

                        // if deals subscription
                        if ($dataDeals->deals_type == "Subscription") {
                            $id_deals = $dataDeals->id_deals;

                            // count claimed deals by id_deals_subscription (how many times deals are claimed)
                            $dealsVoucherSubs = DealsVoucher::where('id_deals', $id_deals)->count();
                            $voucherClaimed = 0;
                            if ($dealsVoucherSubs > 0) {
                                $voucherClaimed = $dealsVoucherSubs / $dataDeals->total_voucher_subscription;
                                if(is_float($voucherClaimed)){ // if miss calculate use deals_total_claimed
                                    $voucherClaimed = $dataDeals->deals_total_claimed;
                                }
                            }

                            // check available voucher
                            if ($dataDeals->deals_total_voucher > $voucherClaimed || $dataDeals->deals_voucher_type == "Unlimited") {
                                $deals_subs = $dataDeals->deals_subscriptions()->get();
                                // dd($deals_subs);

                                // create deals voucher and deals user x times
                                $user_voucher = [];
                                $apiDealsVoucher = new ApiDealsVoucher();
                                $apiDealsClaim   = new ApiDealsClaim();

                                foreach ($deals_subs as $key => $deals_sub) {
                                    // deals subscription may have > 1 voucher
                                    for ($i=1; $i <= $deals_sub->total_voucher; $i++) {
                                        // generate voucher code
                                        do {
                                            $code = $apiDealsVoucher->generateCode($dataDeals->id_deals);
                                            $voucherCode = DealsVoucher::where('id_deals', $id_deals)->where('voucher_code', $code)->first();
                                        } while (!empty($voucherCode));

                                        $voucher = DealsVoucher::create([
                                            'id_deals'             => $id_deals,
                                            'id_deals_subscription'=> $deals_sub->id_deals_subscription,
                                            'voucher_code'         => strtoupper($code),
                                            'deals_voucher_status' => 'Sent',
                                        ]);
                                        if (!$voucher) {
                                            DB::rollback();
                                            return response()->json([
                                                'status'   => 'fail',
                                                'messages' => ['Failed to save data.']
                                            ]);
                                        }

                                        // create user voucher
                                        // give price to user voucher only if first voucher
                                        if ($key==0 && $i==1) {
                                            $voucher = $apiDealsClaim->createVoucherUser($id_user, $voucher->id_deals_voucher, $dataDeals, $deals_sub);
                                        }
                                        else{
                                            // price or point = null
                                            $voucher = $apiDealsClaim->createVoucherUser($id_user, $voucher->id_deals_voucher, $dataDeals, $deals_sub, 0);
                                        }
                                        if (!$voucher) {
                                            DB::rollback();
                                            return response()->json([
                                                'status'   => 'fail',
                                                'messages' => ['Failed to save data.']
                                            ]);
                                        }
                                        // keep user voucher in order to return in response
                                        array_push($user_voucher, $voucher);

                                    }   // end of for

                                }   // end of foreach

                                // update deals total claim
                                $updateDeals = $apiDealsClaim->updateDeals($dataDeals);

                                // multi vouchers
                                $voucher = $user_voucher;
                            }
                            else {
                                DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['Voucher is runs out.']
                                ]);
                            }
                        }
                        else{
                            // CHECK TYPE VOUCHER
                            // IF LIST VOUCHER, GET 1 FROM DEALS VOUCHER
                            if ($dataDeals->deals_voucher_type == "List Vouchers") {
                                $voucher = app($this->claim)->getVoucherFromTable($request->user(), $dataDeals);

                                if (!$voucher) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Voucher is runs out.']
                                    ]);
                                }
                            }
                            // GENERATE VOUCHER CODE & ASSIGN
                            else {
                                $voucher = app($this->claim)->getVoucherGenerate($request->user(), $dataDeals);

                                if (!$voucher) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Voucher is runs out.']
                                    ]);
                                }
                            }
                        }

                        if ($voucher) {

                            if (!empty($dataDeals->deals_voucher_price_point)){
                                $req['payment_deals'] = 'balance';
                            }
                            $req['id_deals_user'] =  $voucher['id_deals_user'];
                            $payNow = new PayNow($req);

                            DB::commit();
                            return $this->bayarSekarang($payNow);
                        }
                        else {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Transaction is failed.']
                            ]);
                        }
                        DB::commit();
                        return response()->json(MyHelper::checkCreate($voucher));
                    }
                    else {
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['You have participated.']
                        ]);
                    }

                }
                else {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['This is a free voucher.']
                    ]);
                }
            }
            else {
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Date valid '.date('d F Y', strtotime($dataDeals->deals_start)).' until '.date('d F Y', strtotime($dataDeals->deals_end))]
                ]);
            }
        }
    }

    /* BAYAR SEKARANG */
    /* KARENA TADI NGGAK BAYAR MAKANYA SEKARANG KUDU BAYAR*/
    function bayarSekarang(PayNow $request)
    {
        DB::beginTransaction();
        $post      = $request->json()->all();
        $dataDeals = $this->deals($request->get('id_deals_user'));

        if ($dataDeals) {
            $voucher = DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $request->get('id_deals_user'))->first();
            // if deals subscription re-get the user voucher
            if ($voucher->dealVoucher->id_deals_subscription != null) {
                $id_user = $voucher->id_user;
                $id_deals = $voucher->id_deals;
                // get user voucher with price
                // deals subscription : multi vouchers, but only 1 that has price
                $voucher = DealsUser::with(['userMid', 'dealVoucher'])
                    ->where('id_deals', $id_deals)
                    ->where('id_user', $id_user)
                    ->whereNotNull('voucher_price_cash')
                    ->latest()
                    ->first();
            }

            if ($voucher) {
                $pay = $this->paymentMethod($dataDeals, $voucher, $request);
            }

            // if deals subscription and pay completed, update paid_status another user vouchers
            if ($voucher->dealVoucher->id_deals_subscription != null && $pay['voucher']->paid_status == "Completed") {
                $total_voucher_subs = $voucher->deals->total_voucher_subscription;
                $voucher_subs_ids = DealsUser::with(['userMid', 'dealVoucher'])
                    ->where('id_deals', $id_deals)
                    ->where('id_user', $id_user)
                    ->latest()
                    ->take($total_voucher_subs)
                    ->pluck('id_deals_user')->toArray();

                $update = DealsUser::whereIn('id_deals_user', $voucher_subs_ids)->update(['paid_status' => "Completed"]);
                // update voucher to multi vouchers
                $pay['voucher'] = DealsUser::whereIn('id_deals_user', $voucher_subs_ids)->get();

                if ($pay && $update) {
                    DB::commit();
                    return response()->json(MyHelper::checkCreate($pay));
                }
            }
            elseif ($pay) {
                DB::commit();
                $return = MyHelper::checkCreate($pay);
                if(isset($return['status']) && $return['status'] == 'success'){
                    if(\Module::collections()->has('Autocrm')) {
                        $phone=User::where('id', $voucher->id_user)->pluck('phone')->first();
                        $voucher->load('dealVoucher.deals');
                        $autocrm = app($this->autocrm)->SendAutoCRM('Claim Deals Success', $phone,
                            [
                                'claimed_at'       => $voucher->claimed_at, 
                                'deals_title'      => $voucher->dealVoucher->deals->deals_title,
                                'id_deals_user'    => $return['result']['voucher']['id_deals_user'],
                                'deals_voucher_price_point' => $voucher->dealVoucher->deals->deals_voucher_price_point
                            ]
                        );
                    }
                    $result = [
                        'id_deals_user'=>$return['result']['voucher']['id_deals_user'],
                        'id_deals_voucher'=>$return['result']['voucher']['id_deals_voucher'],
                        'paid_status'=>$return['result']['voucher']['paid_status'],
                    ];
                    if(isset($return['result']['midtrans'])){
                        $result['redirect'] = true;
                        $result['midtrans'] = $return['result']['midtrans'];
                    }else{
                        $result['redirect'] = false;
                    }
                    $result['webview_later'] = env('APP_URL').'webview/mydeals/'.$return['result']['voucher']['id_deals_user'];
                    unset($return['result']);
                    $return['result'] = $result;
                }
                return response()->json($return);
            }
        }

        DB::rollback();
        return response()->json([
            'status' => 'fail',
            'messages' => ['Failed to pay.']
        ]);
    }

    /* DEALS */
    function deals($idDealsUser)
    {
        $deals = Deal::leftjoin('deals_vouchers', 'deals_vouchers.id_deals', '=', 'deals.id_deals')->leftjoin('deals_users', 'deals_users.id_deals_voucher', '=', 'deals_vouchers.id_deals_voucher')->select('deals.*')->where('deals_users.id_deals_user', $idDealsUser)->first();
        return $deals;
    }

    /* PAYMENT */
    function paymentMethod($dataDeals, $voucher, $request)
    {
        //IF USING BALANCE
        if ($request->get('balance') && $request->get('balance') == true){
            /* BALANCE */
            $pay = $this->balance($dataDeals, $voucher,$request->get('payment_deals') );
        }else{

            /* BALANCE */
            if ($request->get('payment_deals') && $request->get('payment_deals') == "balance") {
                $pay = $this->balance($dataDeals, $voucher);
            }

           /* MIDTRANS */
            if ($request->get('payment_deals') && $request->get('payment_deals') == "midtrans") {
                $pay = $this->midtrans($dataDeals, $voucher);
            }

            /* MANUAL */
            if ($request->get('payment_deals') && $request->get('payment_deals') == "manual") {
                $post             = $request->json()->all();
                $post['id_deals'] = $dataDeals->id_deals;

                $pay = $this->manual($voucher, $post);
            }
        }

        if(!isset($pay)){
            $pay = $this->midtrans($dataDeals, $voucher);
        }


        return $pay;
    }

    /* MIDTRANS */
    function midtrans($deals, $voucher, $grossAmount=null)
    {
        // simpan dulu di deals payment midtrans
        $data = [
            'id_deals'      => $deals->id_deals,
            'id_deals_user' => $voucher->id_deals_user,
            'gross_amount'  => $voucher->voucher_price_cash,
            'order_id'      => time().sprintf("%05d", $voucher->id_deals_user)
        ];

        if (is_null($grossAmount)) {
            if (!$this->updateInfoDealUsers($voucher->id_deals_user, ['payment_method' => 'Midtrans'])) {
                 return false;
            }
        }
        else {
            $data['gross_amount'] = $grossAmount;
        }

        $tembakMitrans = Midtrans::token($data['order_id'], $data['gross_amount']);

        // print_r($tembakMitrans); exit();

        if (isset($tembakMitrans['token'])) {
            if (DealsPaymentMidtran::create($data)) {
                return [
                    'midtrans' => $tembakMitrans,
                    'voucher'  => DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $voucher->id_deals_user)->first(),
                    'data'     => $data,
                    'deals'    => $deals
                ];
            }
        }

        return false;
    }

    /* MANUAL */
    function manual($voucher, $post)
    {
        $data = [];
        $data['id_deals_user'] = $voucher->id_deals_user;

        if (isset($post['payment_receipt_image'])) {
            $upload = MyHelper::uploadPhoto($post['payment_receipt_image'], $this->saveImage);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['payment_receipt_image'] = $upload['path'];
            }
            else {
                return false;
            }
        }

        isset($post['id_deals']) ? $data['id_deals'] = $post['id_deals'] : null;

        isset($post['id_bank']) ? $data['id_bank'] = $post['id_bank'] : null;
        isset($post['id_bank_method']) ? $data['id_bank_method'] = $post['id_bank_method'] : null;
        isset($post['id_manual_payment']) ? $data['id_manual_payment'] = $post['id_manual_payment'] : null;

        isset($post['id_manual_payment_method']) ? $data['id_manual_payment_method'] = $post['id_manual_payment_method'] : null;
        isset($post['payment_date']) ? $data['payment_date'] = date('Y-m-d', strtotime($post['payment_date'])) : null;
        isset($post['payment_time']) ? $data['payment_time'] = date('H:i:s', strtotime($post['payment_time'])) : null;
        isset($post['payment_bank']) ? $data['payment_bank'] = $post['payment_bank'] : null;
        isset($post['payment_method']) ? $data['payment_method'] = $post['payment_method'] : null;
        isset($post['payment_account_number']) ? $data['payment_account_number'] = $post['payment_account_number'] : null;
        isset($post['payment_account_name']) ? $data['payment_account_name'] = $post['payment_account_name'] : null;
        isset($post['payment_nominal']) ? $data['payment_nominal'] = $post['payment_nominal'] : null;
        isset($post['payment_note']) ? $data['payment_note'] = $post['payment_note'] : null;

        $save = DealsPaymentManual::create(array_filter($data));

        if ($save) {
            if ($this->updateInfoDealUsers($voucher->id_deals_user, ['payment_method' => 'Manual', 'paid_status' => 'Paid'])) {
                return [
                    'manual'  => $data,
                    'voucher' => DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $voucher->id_deals_user)->first(),
                ];
            }
        }

        return false;
    }

    /* BALANCE */
    function balance($deals, $voucher, $paymentMethod = null)
    {
        $myBalance   = app($this->balance)->balanceNow($voucher->id_user);
        $kurangBayar = $myBalance - $voucher->voucher_price_cash;

        if($paymentMethod == null){
            $paymentMethod = 'balance';
        }

        // jika kurang bayar
        if ($kurangBayar < 0) {
            $dataDealsUserUpdate = [
                'payment_method'  => $paymentMethod,
                'balance_nominal' => $myBalance,
            ];

            if ($this->updateLogPoint(- $myBalance, $voucher)) {
                if ($this->updateInfoDealUsers($voucher->id_deals_user, $dataDealsUserUpdate)) {
                    if($paymentMethod == 'midtrans'){
                        return $this->midtrans($deals, $voucher, $dataDealsUserUpdate['balance_nominal']);
                    }
                }
            }

        } else {
            // update log balance
            $price = 0;
            if(!empty($voucher->voucher_price_cash)){
                $price = $voucher->voucher_price_cash;
            }
            if(!empty($voucher->voucher_price_point)){
                $price = $voucher->voucher_price_point;
            }
            if ($this->updateLogPoint(- $price, $voucher)) {
                $dataDealsUserUpdate = [
                    'payment_method'  => 'Balance',
                    'balance_nominal' => $voucher->voucher_price_cash,
                    'paid_status'     => 'Completed'
                ];

                // update deals user
                if ($this->updateInfoDealUsers($voucher->id_deals_user, $dataDealsUserUpdate)) {
                    return $result = [
                        'voucher'  => DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $voucher->id_deals_user)->first(),
                        'data'     => $dataDealsUserUpdate,
                        'deals'    => $deals
                    ];
                }
            }
        }

        return false;
    }

    /* UPDATE BALANCE */
    function updateLogPoint($balance_nominal, $voucher)
    {
        $user = User::with('memberships')->where('id', $voucher->id_user)->first();

        // $balance_nominal = -$voucher->voucher_price_cash;
        $grand_total = 0;
        if(!empty($voucher->voucher_price_cash)){
            $grand_total = $voucher->voucher_price_cash;
        }
        if(!empty($voucher->voucher_price_point)){
            $grand_total = $voucher->voucher_price_point;
        }
        $id_reference = $voucher->id_deals_user;

        // add log balance (with balance hash check) & update user balance
        $balanceController = new BalanceController();
        $addLogBalance = $balanceController->addLogBalance($user->id, $balance_nominal, $id_reference, "Deals Balance", $grand_total);
        return $addLogBalance;
    }

    /* UPDATE HARGA BALANCE */
    function updateInfoDealUsers($idDealsUser, $data)
    {
        $update = DealsUser::where('id_deals_user', $idDealsUser)->update($data);

        return $update;
    }


}