<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Outlet;
use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\SpinTheWheel;

use DB;

use Modules\Deals\Http\Requests\Deals\Create;
use Modules\Deals\Http\Requests\Deals\Update;
use Modules\Deals\Http\Requests\Deals\Delete;
use Modules\Deals\Http\Requests\Deals\ListDeal;

use Illuminate\Support\Facades\Schema;

class ApiDeals extends Controller
{

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/deals/";

    /* CHECK INPUTAN */
    function checkInputan($post) {

        $data = [];

        if (isset($post['deals_promo_id_type'])) { 
            $data['deals_promo_id_type'] = $post['deals_promo_id_type'];
        }
        if (isset($post['deals_type'])) { 
            $data['deals_type'] = $post['deals_type'];
        }
        if (isset($post['deals_voucher_type'])) { 
            $data['deals_voucher_type'] = $post['deals_voucher_type'];
        }
        if (isset($post['deals_promo_id'])) { 
            $data['deals_promo_id'] = $post['deals_promo_id'];
        }
        if (isset($post['deals_title'])) { 
            $data['deals_title'] = $post['deals_title'];
        }
        if (isset($post['deals_second_title'])) { 
            $data['deals_second_title'] = $post['deals_second_title'];
        }
        if (isset($post['deals_description'])) { 
            $data['deals_description'] = $post['deals_description'];
        }
        if (isset($post['deals_short_description'])) { 
            $data['deals_short_description'] = $post['deals_short_description'];
        }
        if (isset($post['deals_image'])) { 

            if (!file_exists($this->saveImage)) {
                mkdir($this->saveImage, 0777, true);
            }

            $upload = MyHelper::uploadPhotoStrict($post['deals_image'], $this->saveImage, 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['deals_image'] = $upload['path'];
            }
            else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }
        // if (isset($post['deals_video'])) { 
        //     $data['deals_video'] = $post['deals_video'];
        // }
        if (isset($post['id_product'])) { 
            $data['id_product'] = $post['id_product'];
        }
        if (isset($post['deals_start'])) { 
            $data['deals_start'] = date('Y-m-d H:i:s', strtotime($post['deals_start']));
        }
        if (isset($post['deals_end'])) { 
            $data['deals_end'] = date('Y-m-d H:i:s', strtotime($post['deals_end']));
        }
        if (isset($post['deals_publish_start'])) { 
            $data['deals_publish_start'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_start']));
        }
        if (isset($post['deals_publish_end'])) { 
            $data['deals_publish_end'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_end']));
        }

        // ---------------------------- DURATION 
        if (isset($post['deals_voucher_duration'])) { 
            $data['deals_voucher_duration'] = $post['deals_voucher_duration'];
        }
        if (empty($post['deals_voucher_duration']) || is_null($post['deals_voucher_duration'])) {
            $data['deals_voucher_duration'] = null;
        }

        // ---------------------------- EXPIRED 
        if (isset($post['deals_voucher_expired'])) { 
            $data['deals_voucher_expired'] = $post['deals_voucher_expired'];
        }
        if (empty($post['deals_voucher_expired']) || is_null($post['deals_voucher_expired'])) {
            $data['deals_voucher_expired'] = null;
        }

        // ---------------------------- POINT 
        if (isset($post['deals_voucher_price_point'])) { 
            $data['deals_voucher_price_point'] = $post['deals_voucher_price_point'];
        }

        if (empty($post['deals_voucher_price_point']) || is_null($post['deals_voucher_price_point'])) {
            $data['deals_voucher_price_point'] = null;
        }

        // ---------------------------- CASH
        if (isset($post['deals_voucher_price_cash'])) { 
            $data['deals_voucher_price_cash'] = $post['deals_voucher_price_cash'];
        }
        if (empty($post['deals_voucher_price_cash']) || is_null($post['deals_voucher_price_cash'])) {
            $data['deals_voucher_price_cash'] = null;
        }

        if (isset($post['deals_total_voucher'])) { 
            $data['deals_total_voucher'] = $post['deals_total_voucher'];
        }
        if (isset($post['deals_total_claimed'])) { 
            $data['deals_total_claimed'] = $post['deals_total_claimed'];
        }
        if (isset($post['deals_total_redeemed'])) { 
            $data['deals_total_redeemed'] = $post['deals_total_redeemed'];
        }
        if (isset($post['deals_total_used'])) { 
            $data['deals_total_used'] = $post['deals_total_used'];
        }
        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }
        if (isset($post['user_limit'])) {
            $data['user_limit'] = $post['user_limit'];
        }

        return $data;
    }

    /* CREATE */
    function create($data) {
        $data = $this->checkInputan($data);

        // error
        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }
        $save = Deal::create($data);

        if ($save) {
            if (isset($data['id_outlet'])) {
                $saveOutlet = $this->saveOutlet($save->id_deals, $data['id_outlet']);

                if (!$saveOutlet) {
                    return false;
                }
            }
        }
        return $save;
    }

    /* CREATE REQUEST */
    function createReq(Create $request) {
        DB::beginTransaction();
        $save = $this->create($request->json()->all());

        if ($save) {
            DB::commit();
        }
        else {
            DB::rollback();
        }

        return response()->json(MyHelper::checkCreate($save));
    }

    /* LIST */
    function listDeal(ListDeal $request) {

        // return $request->json()->all();
        $deals = Deal::with(['outlets', 'outlets.city', 'product']);
        
        // deals subscription
        if ($request->json('deals_type') == "Subscription") {
            $deals->with('deals_subscriptions');
        }

        if ($request->json('id_deals')) {
            $deals->with(['deals_vouchers', 
                // 'deals_vouchers.deals_voucher_user', 
                // 'deals_vouchers.deals_user.user'
            ])->where('id_deals', $request->json('id_deals'));
        }

        if ($request->json('publish')) {
            $deals->where('deals_publish_end', '>=', date('Y-m-d H:i:s'));
        }

        if ($request->json('deals_type')) {
            // get > 1 deals types
            if (is_array($request->json('deals_type'))) {
                $deals->whereIn('deals_type', $request->json('deals_type'));
            }
            else {
                $deals->where('deals_type', $request->json('deals_type'));
            }
        }

        if ($request->json('deals_promo_id')) {
            $deals->where('deals_promo_id', $request->json('deals_promo_id'));
        }

        if ($request->json('price_range_start') && $request->json('price_range_end')) {
            $deals->whereBetween('deals_voucher_price_cash', [$request->json('price_range_start'), $request->json('price_range_end')]);
        }

        if ($request->json('key_free')) {
            $deals->where('deals_title', 'LIKE', '%'.$request->json('key_free').'%');
        }

        /* ========================= TYPE ========================= */
        $deals->where(function ($query) use ($request) {
            // cash
            if ($request->json('voucher_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals_voucher_price_cash');
                });
                // print_r('voucher_type_paid'); 
                // print_r($query->get()->toArray());die();
            }

            if ($request->json('voucher_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals_voucher_price_point');
                });
                // print_r('voucher_type_point'); 
                // print_r($query->get()->toArray());die();
            }

            if ($request->json('voucher_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('deals_voucher_price_point')->whereNull('deals_voucher_price_cash');
                });
                // print_r('voucher_type_free'); 
                // print_r($query->get()->toArray());die();
            }
        });

        /* ========================= POINT ========================= */
        $deals->where(function ($query) use ($request) {

            if ($request->json('050')) {
                $point      = explode("-", $request->json('050'));
                $pointStart = $point[0];
                $pointEnd   = $point[1];

                $query->orWhere(function ($amp) use ($pointStart, $pointEnd) {
                    $amp->whereBetween('deals_voucher_price_point', [$pointStart, $pointEnd]);
                });

                // print_r("050"); 
                // print_r($query->get()->toArray());
                // die();
            }

            if ($request->json('50100')) {
                $point      = explode("-", $request->json('50100'));
                $pointStart = $point[0];
                $pointEnd   = $point[1];

                $query->orWhere(function ($amp) use ($pointStart, $pointEnd) {
                    $amp->whereBetween('deals_voucher_price_point', [$pointStart, $pointEnd]);
                });
                // print_r("50100"); 
                // print_r($query->get()->toArray());
                // die();
            }

            if ($request->json('100300')) {
                $point      = explode("-", $request->json('100300'));
                $pointStart = $point[0];
                $pointEnd   = $point[1];
                
                $query->orWhere(function ($amp) use ($pointStart, $pointEnd) {
                    $amp->whereBetween('deals_voucher_price_point', [$pointStart, $pointEnd]);
                });
                // print_r("100300"); 
                // print_r($query->get()->toArray());
                // die();
            }

            if ($request->json('300500')) {
                $point      = explode("-", $request->json('300500'));
                $pointStart = $point[0];
                $pointEnd   = $point[1];

                $query->orWhere(function ($amp) use ($pointStart, $pointEnd) {
                    $amp->whereBetween('deals_voucher_price_point', [$pointStart, $pointEnd]);
                });
                // print_r("300500"); 
                // print_r($query->get()->toArray());
                // die();

            }

            if ($request->json('500up')) {
                $point = str_replace("+", "", $request->json('500up'));
                
                $query->orWhere(function ($amp) use ($point) {
                    $amp->where('deals_voucher_price_point', '>=', $point);
                });
                // print_r("500up"); 
                // print_r($query->get()->toArray());
                // die();
            }
        });

        // print_r($deals->get()->toArray());
        // $deals = $deals->orderBy('deals_start', 'ASC');

        if ($request->json('alphabetical')) {
            $deals->orderBy('deals_title', 'ASC');
        }

        if ($request->json('newest')) {
            $deals->orderBy('id_deals', 'DESC');
        }

        if ($request->json('oldest')) {
            $deals->orderBy('id_deals', 'ASC');
        }

        $deals = $deals->get()->toArray();
        // print_r($deals); exit();

        if (!empty($deals)) {
            $city = "";

            // jika ada id city yg faq
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city);

        }

        if ($request->json('highest_available_voucher')) {
            $tempDeals = [];
            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                $tempTempDeals = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempDeals, $deals[$key]);
                }

                $tempTempDeals = $this->highestAvailableVoucher($tempTempDeals);

                // return $tempTempDeals;
                $tempDeals =  array_merge($tempDeals, $tempTempDeals);
            }

            $deals = $tempDeals;
        }

        if ($request->json('lowest_available_voucher')) {
            $tempDeals = [];

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }

                $tempDeals = $this->lowestAvailableVoucher($tempDeals);
            }

            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $deals = $tempDeals;
        }

        // if deals detail, add webview url & btn text
        if ($request->json('id_deals') && !empty($deals)) {
            $deals[0]['webview_url'] = env('APP_URL') ."webview/deals/". $deals[0]['id_deals'] ."/". $deals[0]['deals_type'];
            $deals[0]['button_text'] = 'BELI';
        }

        // print_r($deals); exit();
        return response()->json(MyHelper::checkGet($deals));
    }

    /* UNLIMITED */
    function unlimited ($deals)
    {
        $unlimited = array_filter(array_column($deals, "available_voucher"), function($deals) {
            if ($deals == "*") {
                return $deals;
            }
        });

        return $unlimited;
    }

    function limited ($deals)
    {
        $limited = array_filter(array_column($deals, "available_voucher"), function($deals) {
            if ($deals != "*") {
                return $deals;
            }
        });

        return $limited;
    }

    /* SORT DEALS */
    function highestAvailableVoucher ($deals)
    {
        usort($deals, function($a, $b) {
            return $a['available_voucher'] < $b['available_voucher']; 
        });

        return $deals;
    }

    function lowestAvailableVoucher($deals) 
    {
        usort($deals, function($a, $b) {
            return $a['available_voucher'] > $b['available_voucher']; 
        });

        return $deals;
    }

    /* INI LIST KOTA */
    function kotacuks($deals, $city="")
    {
        $timeNow = date('Y-m-d H:i:s');

        foreach ($deals as $key => $value) {
            $markerCity = 0;

            $deals[$key]['outlet_by_city'] = [];

            // set time
            $deals[$key]['time_server'] = $timeNow;

            if (!empty($value['outlets'])) {
                // ambil kotanya dulu
                $kota = array_column($value['outlets'], 'city');
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

                    foreach ($value['outlets'] as $outlet) {
                        if ($v['id_city'] == $outlet['id_city']) {
                            unset($outlet['pivot']);
                            unset($outlet['city']);

                            array_push($kota[$k]['outlet'], $outlet);
                        }
                    }
                }

                $deals[$key]['outlet_by_city'] = $kota;
            }

            // unset($deals[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($deals[$key]);
                }
            }

            // kalkulasi point
            $calc = $value['deals_total_voucher'] - $value['deals_total_claimed'];

            if ($value['deals_voucher_type'] == "Unlimited") {
                $calc = '*';
            }

            $deals[$key]['available_voucher'] = $calc;

            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }

    /* LIST USER */
    function listUserVoucher(Request $request) {
        $deals = DealsUser::join('deals_vouchers', 'deals_vouchers.id_deals_voucher', '=', 'deals_users.id_deals_voucher');

        if ($request->json('id_deals')) {
            $deals->where('deals_vouchers.id_deals', $request->json('id_deals'));
        }

        $deals = $deals->with(['user', 'outlet'])->orderBy('claimed_at', "ASC")->paginate(10);

        return response()->json(MyHelper::checkGet($deals));
    }  

    /* LIST VOUCHER */ 
    function listVoucher(Request $request) {
        $deals = DealsVoucher::select('*');

        if ($request->json('id_deals')) {
            $deals->where('id_deals', $request->json('id_deals'));
        }

        $deals = $deals->paginate(10);

        return response()->json(MyHelper::checkGet($deals));
    }

    /* UPDATE */
    function update($id, $data) {
        $data = $this->checkInputan($data);

        // error
        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }

        // delete old images
        if (isset($data['deals_image'])) {
            $this->deleteImage($id);
        }
        
        if (isset($data['id_outlet'])) {

            // DELETE
            $this->deleteOutlet($id);
            
            // SAVE
            $saveOutlet = $this->saveOutlet($id, $data['id_outlet']);
            unset($data['id_outlet']);
        }

        $save = Deal::where('id_deals', $id)->update($data);

        return $save;
    }

    /* DELETE IMAGE */
    function deleteImage($id) {
        $cekImage = Deal::where('id_deals', $id)->get()->first();

        if (!empty($cekImage)) {
            if (!empty($cekImage->deals_image)) {
                $delete = MyHelper::deletePhoto($cekImage->deals_image);
            }
        }
        return true;
    }

    /* UPDATE REQUEST */
    function updateReq(Update $request) {
        DB::beginTransaction();
        $save = $this->update($request->json('id_deals'), $request->json()->all());
        
        if ($save) {
            DB::commit();
        }
        else {
            DB::rollback();
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    /* DELETE */
    function delete($id) {
        // delete outlet
        DealsOutlet::where('id_deals', $id)->delete();

        $delete = Deal::where('id_deals', $id)->delete();
        return $delete;
    }

    /* DELETE REQUEST */
    function deleteReq(Delete $request) {
        DB::beginTransaction();
        
        // check spin the wheel
        if ($request->json('deals_type')!==null && $request->json('deals_type')=="Spin") {
            $spin = SpinTheWheel::where('id_deals', $request->json('id_deals'))->first();
            if ($spin != null) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Item already used in Spin The Wheel Setting.']
                ]);
            }
        }

        $check = $this->checkDelete($request->json('id_deals'));
        if ($check) {
            // delete image first
            $this->deleteImage($request->json('id_deals'));

            $delete = $this->delete($request->json('id_deals'));
            
            if ($delete) {
                DB::commit();
            }
            else {
                DB::rollback();
            }

            return response()->json(MyHelper::checkDelete($delete));
        }
        else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Deal already used.']
            ]);
        }
    }

    /* CHECK DELETE */
    function checkDelete($id) {
        $database = [
            'deals_vouchers',
            'deals_payment_manuals',
            'deals_payment_midtrans',
        ];

        foreach ($database as $val) {
            // check apakah ada atau nggak tablenya
            if (Schema::hasTable($val)) {
                $cek = DB::table($val);

                if ($val == "deals_vouchers") {
                    $cek->where('deals_voucher_status', '=', 'Sent');
                }

                $cek = $cek->where('id_deals', $id)->first();

                if (!empty($cek)) {
                    return false;
                }
            }
        }

        return true;
    }

    /* OUTLET */
    function saveOutlet($id_deals, $id_outlet=[]) {
        $dataOutlet = [];

        if (in_array("all", $id_outlet)) {
            /* SELECT ALL OUTLET */
            $id_outlet = Outlet::select('id_outlet')->get()->toArray();

            if (empty($id_outlet)) {
                return false;
            }
            else {
                $id_outlet = array_pluck($id_outlet, 'id_outlet');
            }
        }

        foreach ($id_outlet as $value) {
            array_push($dataOutlet, [
                'id_outlet' => $value,
                'id_deals'  => $id_deals
            ]);
        }

        if (!empty($dataOutlet)) {
            $save = DealsOutlet::insert($dataOutlet);
            
            return $save;
        }
        else {
            return false;
        }

        return true;
    }

    /* DELETE OUTLET */
    function deleteOutlet($id_deals) {
        $delete = DealsOutlet::where('id_deals', $id_deals)->delete();

        return $delete;
    }
}