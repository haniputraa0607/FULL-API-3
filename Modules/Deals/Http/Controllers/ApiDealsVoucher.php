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
use App\Http\Models\Outlet;

use Modules\Deals\Http\Requests\Deals\Voucher;
use DB;

class ApiDealsVoucher extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->deals        = "Modules\Deals\Http\Controllers\ApiDeals";
    }

    /* CREATE VOUCHER */
    function create($post) {
        DB::beginTransaction();

        if (is_array($post['voucher_code'])) {
            $data = [];

            foreach ($post['voucher_code'] as $value) {
                array_push($data, [
                    'id_deals'             => $post['id_deals'],
                    'voucher_code'         => $value,
                    'deals_voucher_status' => 'Available',
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s')
                ]);    
            }   

            if (!empty($data)) {
                $save = DealsVoucher::insert($data);
                
                if ($save) {
                    // UPDATE VOUCHER TOTAL DEALS TABLE
                    $updateDealsTable = $this->updateTotalVoucher($post);

                    if ($updateDealsTable) {
                        DB::commit();
                        $save = true;
                    }
                    else {
                        DB::rollback();
                        $save = false;
                    }
                }
            }
            else {
                DB::rollback();
                $save = false;
            }

            return MyHelper::checkUpdate($save);
        }
        else {
            $save = DealsVoucher::create([
                'id_deals'             => $post['id_deals'],
                'voucher_code'         => $post['voucher_code'],
                'deals_voucher_status' => 'Available'
            ]);

            return MyHelper::checkCreate($save);
        }
    }

    /* UPDATE TOTAL VOUCHER DEALS TABLE */
    function updateTotalVoucher($post) {
        $jumlahVoucher = Deal::where('id_deals', $post['id_deals'])->select('deals_total_voucher')->first();

        if (!empty($jumlahVoucher)) {
            // UPDATE DATA DEALS
            $save = Deal::where('id_deals', $post['id_deals'])->update([
                'deals_total_voucher' => $jumlahVoucher->deals_total_voucher + count($post['voucher_code'])
            ]);

            if ($save) {
                return true;
            }
        }

        return false;
    }

    /* DELETE VOUCHER */
    function deleteReq(Request $request) {
        if (is_array($request->json('id_deals_voucher'))) {
            $delete = DealsVoucher::whereIn('id_deals_voucher', $request->json('id_deals_voucher'))->where('deals_voucher_status', '=', 'Available')->delete();
        }
        else {
            $delete = DealsVoucher::where('id_deals_voucher', $request->json('id_deals_voucher'))->where('deals_voucher_status', '=', 'Available')->delete();
        }

        if ($request->json('id_deals')) {
            $delete = DealsVoucher::where('id_deals')->where('deals_voucher_status', '=', 'Available')->delete();
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* CREATE VOUCHER REQUEST */
    function createReq(Voucher $request) {

        if ($request->json('type') == "generate") {
            $save = $this->generateVoucher($request->json('id_deals'), $request->json('total'));
            return response()->json(MyHelper::checkUpdate($save));
        }
        else {
            $save = $this->create($request->json()->all());
            return response()->json($save);
        }
    }

    /* GENERATE VOUCHER */
    function generateVoucher($id_deals, $total, $status=0) {
        $data = [];
        // pengecekan database
        $voucherDB = $this->voucherDB($id_deals);

        if ($total > 1) {
            for ($i=0; $i < $total; $i++) { 
                // generate code
                $code = $this->generateCode($id_deals);
                
                // unique code in 1 deals
                while (in_array($code, $voucherDB)) {
                    $code = $this->generateCode($id_deals);
                }

                // push for voucher DB, to get unique code
                array_push($voucherDB, $code);

                // push for save db
                array_push($data, [
                    'id_deals'             => $id_deals,
                    'voucher_code'         => $code,
                    'deals_voucher_status' => 'Available',
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s')
                ]);
            }

            $save = DealsVoucher::insert($data);
        }
        else {
            // generate code
            $code = $this->generateCode($id_deals);
            
            // unique code in 1 deals
            while (in_array($code, $voucherDB)) {
                $code = $this->generateCode($id_deals);
            }

            $data = [
                'id_deals'             => $id_deals,
                'voucher_code'         => $code,
            ];

            if ($status != 0) {
                $data['deals_voucher_status'] = "Sent";
            }
            else {
                $data['deals_voucher_status'] = "Available";
            }

            $save = DealsVoucher::create($data);
        }

        return $save;
    }

    /* CHECK VOUCHER DATABASE */
    function voucherDB($id_deals) {
        $dbVoucher = DealsVoucher::where('id_deals', $id_deals)->get()->toArray();

        if (!empty($dbVoucher)) {
            $dbVoucher = array_pluck($dbVoucher, 'voucher_code');
        }

        return $dbVoucher;
    }

    /* GENERATE CODE */
    function generateCode($id_deals) {
        $code = sprintf('%03d', $id_deals).MyHelper::createRandomPIN(5);
        
        return $code;
    }

    /* UPDATE VOUCHER */
    function update($id_deals_voucher, $post) {
        $update = DealsVoucher::where('id_deals_voucher', $id_deals_voucher)->update($post);

        return $update;
    }

    /* CREATE VOUCHER USER */
    function createVoucherUser($post) {
        $create = DealsUser::create($post);

        if ($create) {
            $create = DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $create->id_deals_user)->first();
            
            // add notif mobile
            $addNotif = MyHelper::addUserNotification($create->id_user,'voucher');
        }

        return $create;
    }

    /* UPDATE VOUCHER USER */
    function updateVoucherUser($id_deals_user, $post) {
        $update = DealsVoucher::where('id_deals_user', $id_deals_user)->update($post);
        
        return $update;
    }

    /* MY VOUCHER */
    function myVoucher(Request $request) {
        $post = $request->json()->all();

        $voucher = DealsUser::where('id_user', $request->user()->id)->with(['dealVoucher', 'dealVoucher.deal', 'dealVoucher.deal.outlets', 'dealVoucher.deal.outlets.city']);

        if (isset($post['used']) && $post['used'] == 1)  {
            $voucher->whereNotNull('used_at');
        }

        if (isset($post['used']) && $post['used'] == 0) {
            $voucher->whereNull('used_at');
        }

        if (isset($post['id_deals_user'])) {
            $voucher->where('id_deals_user', $post['id_deals_user']);
        }
		
		$voucher->where('voucher_expired_at', '>=',date('Y-m-d H:i:s'));
		$voucher->orderBy('id_deals_user', 'desc');
        
        $voucher = $voucher->get()->toArray();

        //add outlet name
        foreach($voucher as $index => $datavoucher){
            $outlet = null;
            if($datavoucher['id_outlet']){
                $getOutlet = Outlet::find($datavoucher['id_outlet']);
                if($getOutlet){
                    $outlet = $getOutlet['outlet_name'];
                }
            }

            $voucher[$index] = array_slice($voucher[$index], 0, 4, true) +
            array("outlet_name" => $outlet) +
            array_slice($voucher[$index], 4, count($voucher[$index]) - 1, true) ;
            
            // get new voucher code
            // beetwen "https://chart.googleapis.com/chart?chl="
            // and "&chs=250x250&cht=qr&chld=H%7C0"
            preg_match("/chart.googleapis.com\/chart\?chl=(.*)&chs=250x250/", $datavoucher['voucher_hash'], $matches);
            // replace voucher_code with code from voucher_hash
            if (isset($matches[1])) {
                $voucher[$index]['deal_voucher']['voucher_code'] = $matches[1];
            }
            else {
                $voucher[$index]['deal_voucher']['voucher_code'] = "";
            }
            
        }
        
        $voucher = $this->kotacuks($voucher);

        // if voucher detail, add webview url & btn text
        if (isset($post['used']) && $post['used'] == 0) {
            foreach($voucher as $index => $dataVou){
                $voucher[$index]['webview_url'] = env('APP_URL') ."webview/voucher/". $dataVou['id_deals_user'];
                $voucher[$index]['button_text'] = 'INVALIDATE';
            }
        }

        return response()->json(MyHelper::checkGet($voucher));
    }

    function kotacuks($deals)
    {
        $timeNow = date('Y-m-d H:i:s');

        // print_r($deals); exit();

        foreach ($deals as $key => $value) {
            $markerCity = 0;

            $deals[$key]['deal_voucher']['deal']['outlet_by_city'] = [];

            // set time
            $deals[$key]['deal_voucher']['deal']['time_server'] = $timeNow;

            if (!empty($deals[$key]['deal_voucher']['deal']['outlets'])) {
                // ambil kotanya dulu

                // print_r($value['deal_voucher']['deal']); exit();
                $kota = array_column($value['deal_voucher']['deal']['outlets'], 'city');
                $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));


                // jika ada pencarian kota
                if (!empty($city)) {
                    $cariKota = array_search($city, array_column($kota, 'id_city'));

                    if (is_integer($cariKota)) {
                        $markerCity = 1;
                    }
                }

                foreach ($kota as $k => $v) {
                    $kota[$k]['outlet'] = [];

                    foreach ($value['deal_voucher']['deal']['outlets'] as $outlet) {
                        if ($v['id_city'] == $outlet['id_city']) {
                            unset($outlet['pivot']);
                            unset($outlet['city']);

                            array_push($kota[$k]['outlet'], $outlet);
                        }
                    }
                }

                $deals[$key]['deal_voucher']['deal']['outlet_by_city'] = $kota;
            }

            // unset($deals[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($deals[$key]);
                }
            }

            // kalkulasi point
            $calc = $value['deal_voucher']['deal']['deals_total_voucher'] - $value['deal_voucher']['deal']['deals_total_claimed'];

            if ($value['deal_voucher']['deal']['deals_voucher_type'] == "Unlimited") {
                $calc = '*';
            }

            $deals[$key]['deal_voucher']['deal']['available_voucher'] = $calc;

            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }
}
