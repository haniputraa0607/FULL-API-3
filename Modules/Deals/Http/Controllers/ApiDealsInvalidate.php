<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;

use Modules\Deals\Http\Requests\Deals\Voucher;

use DB;

class ApiDealsInvalidate extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm   = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    /* INVALIDATE */
    function invalidate(Request $request)
    {
        DB::beginTransaction();

        $fail['status'] = "fail";

        // CHECK OUTLET AND GET DEALS USER
        $deals = $this->outletAvailable($request->user(), $request->json('id_deals_user'), $request->json('outlet_code'));
        // dd($deals);

        if ($deals&&optional($deals)->status_outlet) {
            $now = date('Y-m-d H:i:s');
            // if deals subscription, check voucher start time
            if ($deals->deals->deals_type == "Subscription") {
                $condition = (strtotime($deals->deals->deals_voucher_start??$deals->voucher_active_at) <= strtotime($now) &&
                    strtotime($deals->deals->deals_voucher_start??$deals->voucher_expired_at) >= strtotime($now));
                $voucher_active  = date('d F Y H:i:s', strtotime($deals->deals->deals_voucher_start??$deals->voucher_active_at));
                $voucher_expired = date('d F Y H:i:s', strtotime($deals->voucher_expired_at));
                $messages = ['Voucher belum aktif'];
            }
            else{
                if($deals->deals->deals_voucher_start){
                    $condition = (strtotime($deals->deals->deals_voucher_start) <= strtotime($now) &&
                        strtotime($deals->voucher_expired_at) >= strtotime($now));
                    $voucher_active  = date('d F Y H:i:s', strtotime($deals->deals->deals_voucher_start));
                    $voucher_expired = date('d F Y H:i:s', strtotime($deals->voucher_expired_at));
                    $messages = ['Voucher belum aktif'];
                }else{
                    $condition = strtotime($deals->voucher_expired_at) >= strtotime($now);
                    $messages = ['Voucher is expired.'];
                }
            }

            if ($condition) {

                if ($this->checkPaidOrNot($deals)) {

                    // UPDATE STATUS REDEEM
                    $redeem = $this->redeem($deals);

                    if ($redeem) {
                         // UPDATE TOTAL REDEEM
                        $totalRedeem = $this->updateTotalRedemeedDeals($deals->id_deals);

                        if ($totalRedeem) {
                            // query lagi
                            $deals = $this->outletAvailable($request->user(), $request->json('id_deals_user'), $request->json('outlet_code'))->toArray();

                            // add voucher invalidate success webview url
                            $deals['webview_url'] = env('API_URL') ."api/webview/voucher/v2/". $deals['id_deals_user'];

                            // SEND NOTIFICATION
                            $send = app($this->autocrm)->SendAutoCRM('Redeem Voucher Success',
                                $deals['user']['phone'],
                                [
                                    'redeemed_at'       => $deals['redeemed_at'],
                                    'id_deals_user'     => $deals['id_deals_user'],
                                    'voucher_code'      => $deals['deal_voucher']['voucher_code'],
                                    'outlet_name'       => $deals['outlet_name'],
                                    'outlet_code'       => $deals['outlet_code']
                                ]);

                            // RETURN INFO REDEEM
                            DB::commit();
                            return response()->json(MyHelper::checkGet($deals));
                        }
                        else {
                            $fail['messages'] = ['Proses penukaran Voucher gagal, silakan mencoba kembali'];
                        }
                    }else{
                        $fail['messages'] = ['Redeem voucher failed.', 'Voucher has been redeemed.'];
                        // $fail['messages'] = ['Voucher is expired.'];
                    }
                }
                else {
                    $fail['messages'] = ['Please pay first.'];
                }
            }
            else {
                $fail['messages'] = $messages;
            }
        }
        else {
            $fail['messages'] = ['Kode outlet yang kamu masukkan tidak terdaftar'];
            if(optional($deals)->id_outlet){
                $fail['messages'] = ['Kode outlet yang kamu masukkan salah'];
            }
        }

        DB::rollback();

        return response()->json($fail);
    }

    /* CHECK BERBAYAR APA BELUM DAN UDAH LUNAS BELUM */
    function checkPaidOrNot($deals)
    {
        if (!empty($deals->voucher_price_cash)) {
            if ($deals->paid_status == "Pending" || $deals->paid_status == "Cancelled") {
                return false;
            }
        }

        return true;
    }

    /* CHECK OUTLET AVAILABLE */
    function outletAvailable($user, $id_deals_user, $outlet_code)
    {
        $deals = DealsUser::join('deals_vouchers', 'deals_vouchers.id_deals_voucher', '=', 'deals_users.id_deals_voucher')
        ->leftjoin('deals_outlets', 'deals_vouchers.id_deals', '=', 'deals_outlets.id_deals')
        ->leftjoin('outlets as o2', 'o2.id_outlet', '=', 'deals_users.id_outlet')
        ->leftjoin('outlets', 'outlets.id_outlet', '=', 'deals_outlets.id_outlet')
        ->where('outlets.outlet_code', strtoupper($outlet_code))
        ->where('id_user', $user->id)
        ->where('id_deals_user', $id_deals_user)
        ->addSelect(DB::raw('*,((deals_users.id_outlet is null) or deals_users.id_outlet = outlets.id_outlet) as status_outlet,o2.outlet_name as old_outlet_name,outlets.outlet_name as outlet_name,outlets.id_outlet as id_outlet'))
        ->with('user', 'dealVoucher', 'dealVoucher.deal')
        ->first();

        return $deals;
    }

    /* UPDATE STATUS REDEEM */
    function redeem($deals)
    {
        if (!empty($deals->redeemed_at)) {
            return false;
        }

        $code   = $this->checkRandomVoucher();

        $update = $this->updateStatusDealsUser($deals->id_deals_user, [
            'redeemed_at'       => date('Y-m-d H:i:s'),
            // 'used_at'           => date('Y-m-d H:i:s'),
            'voucher_hash'      => 'https://chart.googleapis.com/chart?chl='.MyHelper::encryptQRCode($code).'&chs=250x250&cht=qr&chld=H%7C0',
            // 'voucher_hash'      => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.MyHelper::encryptQRCode($code),
            'voucher_hash_code' => $code,
            'id_outlet'         => $deals->id_outlet
        ]);

        return $update;
    }

    /* CHECK RANDOM VOUCHER HASH */
    function checkRandomVoucher()
    {
        do {
            $random = MyHelper::createRandomPIN(6);
            $cek    = DealsUser::where('voucher_hash_code', $random)->first();
        } while ($cek);

       return $random;
    }

    /* UPDATE STATUS REDEEM */
    function updateStatusDealsUser($id_deals_user, $data)
    {
        $update = DealsUser::where('id_deals_user', $id_deals_user)->update($data);
        return $update;
    }

    /* UPDATE TOTAL REDEMEED DEALS */
    function updateTotalRedemeedDeals($id_deals)
    {
          //update count deals
          $deal = Deal::find($id_deals);
          $deal->deals_total_redeemed = $deal->deals_total_redeemed + 1;
          $deal->update();

          return $deal;
    }

}