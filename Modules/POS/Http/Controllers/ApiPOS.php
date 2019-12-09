<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionDuplicate;
use App\Http\Models\TransactionDuplicatePayment;
use App\Http\Models\TransactionDuplicateProduct;
use App\Http\Models\TransactionProductModifier;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\TransactionSetting;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductPhoto;
use App\Http\Models\ProductModifier;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\DealsUser;
use App\Http\Models\Deal;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\SpecialMembership;
use App\Http\Models\DealsVoucher;
use App\Http\Models\Configs;
use App\Http\Models\FraudSetting;
use App\Http\Models\LogBackendError;
use App\Http\Models\SyncTransactionFaileds;
use App\Http\Models\SyncTransactionQueues;
use App\Lib\MyHelper;
use Mailgun;

use Modules\POS\Http\Requests\reqMember;
use Modules\POS\Http\Requests\reqVoucher;
use Modules\POS\Http\Requests\voidVoucher;
use Modules\POS\Http\Requests\reqMenu;
use Modules\POS\Http\Requests\reqOutlet;
use Modules\POS\Http\Requests\reqTransaction;
use Modules\POS\Http\Requests\reqTransactionRefund;
use Modules\POS\Http\Requests\reqPreOrderDetail;
use Modules\POS\Http\Requests\reqBulkMenu;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\POS\Entities\SyncMenuRequest;
use Modules\POS\Entities\SyncMenuResult;

use Modules\POS\Http\Controllers\CheckVoucher;
use Exception;

use DB;
use DateTime;

class ApiPOS extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->balance    = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->balance    = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiSettingFraud";

        $this->pos = "Modules\POS\Http\Controllers\ApiPos";
    }

    public function transactionDetail(reqPreOrderDetail $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
        if (empty($outlet)) {
            return response()->json(['status' => 'fail', 'messages' => 'Store not found']);
        }

        $check = Transaction::join('transaction_pickups', 'transactions.id_transaction', '=', 'transaction_pickups.id_transaction')
            ->with(['products', 'product_detail', 'vouchers', 'productTransaction.modifiers'])
            ->where('order_id', '=', $post['order_id'])
            ->where('transactions.transaction_date', '>=', date("Y-m-d") . " 00:00:00")
            ->where('transactions.transaction_date', '<=', date("Y-m-d") . " 23:59:59")
            ->first();

        if ($check) {
            $check = $check->toArray();
            $user = User::where('id', '=', $check['id_user'])->first()->toArray();

            $qrCode = 'https://chart.googleapis.com/chart?chl=' . $check['order_id'] . '&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

            $expired = Setting::where('key', 'qrcode_expired')->first();
            if (!$expired || ($expired && $expired->value == null)) {
                $expired = '10';
            } else {
                $expired = $expired->value;
            }

            $timestamp = strtotime('+' . $expired . ' minutes');
            $memberUid = MyHelper::createQR($timestamp, $user['phone']);

            $transactions = [];
            $transactions['member_uid'] = $memberUid;
            $transactions['trx_id_behave'] = $check['transaction_receipt_number'];
            $transactions['trx_date_time'] = $check['created_at'];
            $transactions['qrcode'] = $qrCode;
            $transactions['order_id'] = $check['order_id'];
            $transactions['process_at'] = $check['pickup_type'];
            $transactions['process_date_time'] = $check['pickup_at'];
            $transactions['accepted_date_time'] = $check['receive_at'];
            $transactions['ready_date_time'] = $check['ready_at'];
            $transactions['taken_date_time'] = $check['taken_at'];
            $transactions['total'] = $check['transaction_subtotal'];
            $transactions['sevice'] = $check['transaction_service'];
            $transactions['tax'] = $check['transaction_tax'];
            $transactions['discount'] = $check['transaction_discount'];
            $transactions['grand_total'] = $check['transaction_grandtotal'];

            $transactions['payments'] = [];
            //cek di multi payment
            $multi = TransactionMultiplePayment::where('id_transaction', $check['id_transaction'])->get();
            if (!$multi) {
                //cek di balance
                $balance = TransactionPaymentBalance::where('id_transaction', $check['id_transaction'])->get();
                if ($balance) {
                    foreach ($balance as $payBalance) {
                        $pay['payment_type'] = 'Kenangan Points';
                        $pay['payment_nominal'] = (int) $payBalance['balance_nominal'];
                        $transactions['payments'][] = $pay;
                    }
                } else {
                    $midtrans = TransactionPaymentMidtran::where('id_transaction', $check['id_transaction'])->get();
                    if ($midtrans) {
                        foreach ($midtrans as $payMidtrans) {
                            $pay['payment_type'] = 'Midtrans';
                            $pay['payment_nominal'] = (int) $payMidtrans['gross_amount'];
                            $transactions['payments'][] = $pay;
                        }
                    }
                }
            } else {
                foreach ($multi as $payMulti) {
                    if ($payMulti['type'] == 'Balance') {
                        $balance = TransactionPaymentBalance::find($payMulti['id_payment']);
                        if ($balance) {
                            $pay['payment_type'] = 'Kenangan Points';
                            $pay['payment_nominal'] = (int) $balance['balance_nominal'];
                            $transactions['payments'][] = $pay;
                        }
                    } elseif ($payMulti['type'] == 'Midtrans') {
                        $midtrans = TransactionPaymentmidtran::find($payMulti['id_payment']);
                        if ($midtrans) {
                            $pay['payment_type'] = 'Midtrans';
                            $pay['payment_nominal'] = (int) $midtrans['gross_amount'];
                            $transactions['payments'][] = $pay;
                        }
                    }
                }
            }

            // 			$transactions['payment_type'] = null;
            // 			$transactions['payment_code'] = null;
            // 			$transactions['payment_nominal'] = null;
            $transactions['menu'] = [];
            $transactions['tax'] = 0;
            $transactions['total'] = 0;
            foreach ($check['products'] as $key => $menu) {
                $val = [];
                $val['plu_id'] = $menu['product_code'];
                $val['name'] = $menu['product_name'];
                $val['price'] = (int) $menu['pivot']['transaction_product_price'];
                $val['qty'] = $menu['pivot']['transaction_product_qty'];
                $val['category'] = $menu['product_category_name'];
                if ($menu['pivot']['transaction_product_note'] != null) {
                    $val['open_modifier'] = $menu['pivot']['transaction_product_note'];
                }
                $val['modifiers'] = $check['product_transaction'][$key]['modifiers'];

                array_push($transactions['menu'], $val);

                $transactions['tax'] = $transactions['tax'] + ($menu['pivot']['transaction_product_qty'] * $menu['pivot']['transaction_product_price_tax']);
                $transactions['total'] = $transactions['total'] + ($menu['pivot']['transaction_product_qty'] * $menu['pivot']['transaction_product_price_base']);
            }
            $transactions['tax'] = round($transactions['tax']);
            $transactions['total'] = round($transactions['total']);

            //update accepted_at
            $trxPickup = TransactionPickup::where('id_transaction', $check['id_transaction'])->first();
            if ($trxPickup && $trxPickup->reject_at == null) {
                $pick = TransactionPickup::where('id_transaction', $check['id_transaction'])->update(['receive_at' => date('Y-m-d H:i:s')]);
            }

            $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'],
                "id_reference" => $check['transaction_receipt_number'] . ',' . $outlet['id_outlet'],
                'id_transaction' => $check['id_transaction'],
                "transaction_date" => $check['transaction_date']
            ]);

            return response()->json(['status' => 'success', 'result' => $transactions]);
        } else {
            return response()->json(['status' => 'fail', 'messages' => 'Invalid Order ID']);
        }
        return response()->json(['status' => 'success', 'messages' => 'API is not ready yet. Stay tuned!', 'result' => $post]);
    }

    public function checkMember(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['api_key']) && !empty($post['api_secret']) &&
            !empty($post['store_code']) && !empty($post['uid'])){

            if(strlen($post['uid']) < 35){
                DB::rollback();
                return ['status' => 'fail', 'messages' => 'Minimum length of member uid is 35'];
            }

            $api = $this->checkApi($post['api_key'], $post['api_secret']);
            if ($api['status'] != 'success') {
                return response()->json($api);
            }

            $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
            if (empty($outlet)) {
                return response()->json(['status' => 'fail', 'messages' => 'Store not found']);
            }

            $qr = MyHelper::readQR($post['uid']);
            $timestamp = $qr['timestamp'];
            $phoneqr = $qr['phone'];

            if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', $timestamp)) {
                return response()->json(['status' => 'fail', 'messages' => 'Mohon refresh qrcode dan ulangi scan member']);
            }

            $user = User::where('phone', $phoneqr)->first();
            if (empty($user)) {
                return response()->json(['status' => 'fail', 'messages' => 'User not found']);
            }

            //suspend
            if (isset($user['is_suspended']) && $user['is_suspended'] == '1') {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => 'Maaf, akun Anda sedang di-suspend'
                ]);
            }

            $result['uid'] = $post['uid'];
            $result['name'] = $user->name;

            $voucher = DealsUser::with('dealVoucher', 'dealVoucher.deal')->where('id_user', $user->id)
                ->where(function ($query) use ($outlet) {
                    $query->where('id_outlet', $outlet->id_outlet)
                        ->orWhereNull('id_outlet');
                })
                ->whereDate('voucher_expired_at', '>=', date("Y-m-d"))
                ->where(function ($q) {
                    $q->where('paid_status', 'Completed')
                        ->orWhere('paid_status', 'Free');
                })
                ->get();
            if (count($voucher) <= 0) {
                $result['vouchers'] = [];
            } else {
                // $arr = [];
                $voucher_name = [];
                foreach ($voucher as $index => $vou) {
                    array_push($voucher_name, ['name' => $vou->dealVoucher->deal->deals_title]);

                    /* if($index > 0){
                        $voucher_name[0] = $voucher_name[0]."\n".$vou->dealVoucher->deal->deals_title;
                    }else{
                    $voucher_name[0] = $vou->dealVoucher->deal->deals_title;
                    }  */
                }


                // array_push($arr, $voucher_name);

                $result['vouchers'] = $voucher_name;
            }

            $membership = UsersMembership::with('users_membership_promo_id')->where('id_user', $user->id)->orderBy('id_log_membership', 'DESC')->first();
            if (empty($membership)) {
                $result['customer_level'] = "";
                $result['promo_id'] = [];
            } else {
                $result['customer_level'] = $membership->membership_name;
                if ($membership->users_membership_promo_id) {
                    $result['promo_id'] = [];
                    foreach ($membership->users_membership_promo_id as $promoid) {
                        if ($promoid['promo_id']) {
                            $result['promo_id'][] = $promoid['promo_id'];
                        }
                    }
                } else {
                    $result['promo_id'] = [];
                }
            }

            $result['saldo'] = $user->balance;

            return response()->json(['status' => 'success', 'result' => $result]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => 'Input is incomplete']);
        }
    }

    public function checkVoucher(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['api_key']) && !empty($post['api_secret']) && !empty($post['store_code']) &&
        (!empty($post['qrcode']) || !empty($post['code']))){

            $api = $this->checkApi($post['api_key'], $post['api_secret']);
            if ($api['status'] != 'success') {
                return response()->json($api);
            }

            return CheckVoucher::check($post);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Input is incomplete']]);
        }
    }

    public function voidVoucher(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['api_key']) && !empty($post['api_secret']) &&
            !empty($post['store_code']) && !empty($post['voucher_code'])){

            $api = $this->checkApi($post['api_key'], $post['api_secret']);
            if ($api['status'] != 'success') {
                return response()->json($api);
            }

            $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
            if (empty($outlet)) {
                return response()->json(['status' => 'fail', 'messages' => 'Store not found']);
            }

            DB::beginTransaction();

            $voucher = DealsVoucher::join('deals_users', 'deals_vouchers.id_deals_voucher', 'deals_users.id_deals_voucher')
                ->leftJoin('transaction_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                ->leftJoin('transaction_vouchers as transaction_vouchers2', 'deals_vouchers.voucher_code', 'transaction_vouchers2.deals_voucher_invalid')
                ->where('deals_vouchers.voucher_code', $post['voucher_code'])
                ->select('deals_vouchers.*','deals_users.id_outlet', 'transaction_vouchers.id_deals_voucher as id_deals_voucher_transaction', 'transaction_vouchers2.deals_voucher_invalid as voucher_code_transaction')
                ->first();

            if (!$voucher) {
                return response()->json(['status' => 'fail', 'messages' => 'Voucher not found']);
            }elseif ($voucher['id_deals_voucher_transaction'] || $voucher['voucher_code_transaction']){
                return response()->json(['status' => 'fail', 'messages' => 'Void voucher failed, voucher has already been used.']);
            }

            if(isset($voucher['id_outlet']) && $voucher['id_outlet'] != $outlet['id_outlet']){
                $outletDeals = Outlet::find($voucher['deals_user'][0]['id_outlet']);
                if($outletDeals){
                    return response()->json(['status' => 'fail', 'messages' => 'Void voucher  '.$post['voucher_code'].'. Void vouchers can only be done at '.$outletDeals['outlet_name'].' outlets.']);
                }
                return response()->json(['status' => 'fail', 'messages' => 'Void voucher failed '.$post['voucher_code'].'. Void vouchers can only be done at '.$outlet['outlet_name'].' outlets.']);
            }

            //update voucher redeem
            foreach ($voucher['deals_user'] as $dealsUser) {
                $dealsUser->redeemed_at = null;
                $dealsUser->used_at = null;
                $dealsUser->voucher_hash = null;
                $dealsUser->voucher_hash_code = null;
                $dealsUser->id_outlet = null;
                $dealsUser->update();

                if (!$dealsUser) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'messages' => 'Void voucher failed ' . $post['voucher_code'] . '. Please contact team support.']);
                }
            }

            //update count deals
            $deals = Deal::find($voucher['id_deals']);
            $deals->deals_total_redeemed = $deals->deals_total_redeemed - 1;
            $deals->update();
            if (!$deals) {
                DB::rollBack();
                return response()->json(['status' => 'fail', 'messages' => 'Void voucher failed ' . $post['voucher_code'] . '. Please contact team support.']);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'messages' => 'Voucher ' . $post['voucher_code'] . ' was successfully voided']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => 'Input is incomplete']);
        }

    }

    public function syncOutlet(reqOutlet $request)
    {
        $post = $request->json()->all();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }
        $getBrand = Brand::pluck('code_brand')->toArray();
        $getBrandList = Brand::select('id_brand', 'code_brand')->get()->toArray();
        $successOutlet = [];
        $failedOutlet = [];
        $failedBrand = [];
        foreach ($post['store'] as $key => $value) {
            DB::beginTransaction();
            // search different brand
            $diffBrand = array_diff($value['brand_code'], $getBrand);
            if (!empty($diffBrand)) {
                $failedBrand[] = 'fail to sync outlet ' . $value['store_name'] . ', because code brand ' . implode(', ', $diffBrand) . ' not found';
                continue;
            }
            $cekOutlet = Outlet::where('outlet_code', strtoupper($value['store_code']))->first();
            if ($cekOutlet) {
                try {
                    $cekOutlet->outlet_name = $value['store_name'];
                    $cekOutlet->outlet_status = $value['store_status'];
                    $cekOutlet->save();
                } catch (\Exception $e) {
                    LogBackendError::logExceptionMessage("ApiPOS/syncOutlet=>" . $e->getMessage(), $e);
                    $failedOutlet[] = 'fail to sync, outlet ' . $value['store_name'];
                    continue;
                }
                $cekBrandOutlet = BrandOutlet::join('brands', 'brands.id_brand', 'brand_outlet.id_brand')->where('id_outlet', $cekOutlet->id_outlet)->pluck('code_brand')->toArray();
                // delete diff brand
                $deleteDiffBrand = array_diff($cekBrandOutlet, $value['brand_code']);
                if (!empty($deleteDiffBrand)) {
                    try {
                        BrandOutlet::join('brands', 'brands.id_brand', 'brand_outlet.id_brand')->where('id_outlet', $cekOutlet->id_outlet)->whereIn('brand_outlet.id_brand', $deleteDiffBrand)->delete();
                    } catch (\Exception $e) {
                        DB::rollback();
                        LogBackendError::logExceptionMessage("ApiPOS/syncOutlet=>" . $e->getMessage(), $e);
                        $failedOutlet[] = 'fail to sync, outlet ' . $value['store_name'];
                        continue;
                    }
                }
                $createDiffBrand = array_diff($value['brand_code'], $cekBrandOutlet);
                if (!empty($createDiffBrand)) {
                    try {
                        $brandOutlet = [];
                        foreach ($createDiffBrand as $valueBrand) {
                            $getIdBrand = $getBrandList[array_search($valueBrand, $getBrand)]['id_brand'];
                            $brandOutlet[] = [
                                'id_outlet' => $cekOutlet->id_outlet,
                                'id_brand' => $getIdBrand,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                        }
                        BrandOutlet::insert($brandOutlet);
                    } catch (Exception $e) {
                        DB::rollback();
                        LogBackendError::logExceptionMessage("ApiPOS/syncOutlet=>" . $e->getMessage(), $e);
                        $failedOutlet[] = 'fail to sync, outlet ' . $value['store_name'];
                        continue;
                    }
                }
            } else {
                try {
                    $save = Outlet::create([
                        'outlet_name'   => $value['store_name'],
                        'outlet_status' => $value['store_status'],
                        'outlet_code'   => $value['store_code']
                    ]);
                } catch (\Exception $e) {
                    LogBackendError::logExceptionMessage("ApiPOS/syncOutlet=>" . $e->getMessage(), $e);
                    $failedOutlet[] = 'fail to sync, outlet ' . $value['store_name'];
                    continue;
                }
                try {
                    $brandOutlet = [];
                    foreach ($value['brand_code'] as $valueBrand) {
                        $getIdBrand = $getBrandList[array_search($valueBrand, $getBrand)]['id_brand'];
                        $brandOutlet[] = [
                            'id_outlet' => $save->id_outlet,
                            'id_brand' => $getIdBrand,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                    BrandOutlet::insert($brandOutlet);
                } catch (Exception $e) {
                    DB::rollback();
                    LogBackendError::logExceptionMessage("ApiPOS/syncOutlet=>" . $e->getMessage(), $e);
                    $failedOutlet[] = 'fail to sync, outlet ' . $value['store_name'];
                    continue;
                }
            }
            $successOutlet[] = $value['store_name'];
            DB::commit();
        }
        // return success
        return response()->json([
            'status' => 'success',
            'result' => [
                'success_outlet' => $successOutlet,
                'failed_outlet' => $failedOutlet,
                'failed_brand' => $failedBrand
            ]
        ]);
    }

    public function syncMenuProcess($data, $flag)
    {
        $syncDatetime = date('d F Y h:i');
        $getBrand = Brand::pluck('code_brand')->toArray();
        $getBrandList = Brand::select('id_brand', 'code_brand')->get()->toArray();
        $outlet = Outlet::where('outlet_code', strtoupper($data['store_code']))->first();
        if ($outlet) {
            $countInsert = 0;
            $countUpdate = 0;
            $rejectedProduct = [];
            $updatedProduct = [];
            $insertedProduct = [];
            $failedProduct = [];
            foreach ($data['menu'] as $key => $menu) {
                if (!isset($menu['brand_code'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because brand_code not set';
                    continue;
                }
                if (!isset($menu['plu_id'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because plu_id not set';
                    continue;
                }
                if (!isset($menu['name'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because name not set';
                    continue;
                }
                if (!isset($menu['category'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because category not set';
                    continue;
                }
                if (!isset($menu['price'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because price not set';
                    continue;
                }
                if (!isset($menu['price_base'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because price_base not set';
                    continue;
                }
                if (!isset($menu['price_tax'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because price_tax not set';
                    continue;
                }
                if (!isset($menu['status'])) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because status not set';
                    continue;
                }
                $diffBrand = array_diff($menu['brand_code'], $getBrand);
                if (!empty($diffBrand)) {
                    $failedProduct[] = 'fail to sync product ' . $menu['name'] . ', because code brand ' . implode(', ', $diffBrand) . ' not found';
                    continue;
                }
                if (isset($menu['plu_id']) && isset($menu['name'])) {
                    DB::beginTransaction();
                    $product = Product::where('product_code', $menu['plu_id'])->first();
                    // update product
                    if ($product) {
                        // cek allow sync, jika 0 product tidak di update
                        if ($product->product_allow_sync == '1') {
                            $cekBrandProduct = BrandProduct::join('brands', 'brands.id_brand', 'brand_product.id_brand')->where('id_product', $product->id_product)->pluck('code_brand')->toArray();
                            // delete diff brand
                            $deleteDiffBrand = array_diff($cekBrandProduct, $menu['brand_code']);
                            if (!empty($deleteDiffBrand)) {
                                try {
                                    BrandProduct::join('brands', 'brands.id_brand', 'brand_product.id_brand')->where('id_product', $product->id_product)->whereIn('brand_product.id_brand', $deleteDiffBrand)->delete();
                                } catch (\Exception $e) {
                                    DB::rollback();
                                    LogBackendError::logExceptionMessage("ApiPOS/syncProduct=>" . $e->getMessage(), $e);
                                    $failedProduct[] = 'fail to sync, product ' . $menu['name'];
                                    continue;
                                }
                            }
                            $createDiffBrand = array_diff($menu['brand_code'], $cekBrandProduct);
                            if (!empty($createDiffBrand)) {
                                try {
                                    $brandProduct = [];
                                    foreach ($createDiffBrand as $menuBrand) {
                                        $getIdBrand = $getBrandList[array_search($menuBrand, $getBrand)]['id_brand'];
                                        $brandProduct[] = [
                                            'id_product' => $product->id_product,
                                            'id_brand' => $getIdBrand,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ];
                                    }
                                    BrandProduct::insert($brandProduct);
                                } catch (Exception $e) {
                                    DB::rollback();
                                    LogBackendError::logExceptionMessage("ApiPOS/syncProduct=>" . $e->getMessage(), $e);
                                    $failedProduct[] = 'fail to sync, product ' . $menu['name'];
                                    continue;
                                }
                            }
                            // cek name pos, jika beda product tidak di update
                            if (empty($product->product_name_pos) || $product->product_name_pos == $menu['name']) {
                                // update modifiers 
                                if (isset($menu['modifiers'])) {
                                    foreach ($menu['modifiers'] as $mod) {
                                        $dataProductMod['type'] = $mod['type'];
                                        if (isset($mod['text']))
                                            $dataProductMod['text'] = $mod['text'];
                                        else
                                            $dataProductMod['text'] = null;
                                        $updateProductMod = ProductModifier::updateOrCreate([
                                            'id_product' => $product->id_product,
                                            'code'  => $mod['code']
                                        ], $dataProductMod);
                                    }
                                }
                                // update price 
                                $productPrice = ProductPrice::where('id_product', $product->id_product)->where('id_outlet', $outlet->id_outlet)->first();
                                if ($productPrice) {
                                    $oldPrice =  $productPrice->product_price;
                                    $oldUpdatedAt =  $productPrice->updated_at;
                                } else {
                                    $oldPrice = null;
                                    $oldUpdatedAt = null;
                                }
                                $dataProductPrice['product_price'] = (int) round($menu['price']);
                                $dataProductPrice['product_price_base'] = round($menu['price_base'], 2);
                                $dataProductPrice['product_price_tax'] = round($menu['price_tax'], 2);
                                $dataProductPrice['product_status'] = $menu['status'];
                                try {
                                    $updateProductPrice = ProductPrice::updateOrCreate([
                                        'id_product' => $product->id_product,
                                        'id_outlet'  => $outlet->id_outlet
                                    ], $dataProductPrice);
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                    $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                }
                                //upload photo
                                // $imageUpload = [];
                                // if (isset($menu['photo'])) {
                                //     foreach ($menu['photo'] as $photo) {
                                //         $image = file_get_contents($photo['url']);
                                //         $img = base64_encode($image);
                                //         if (!file_exists('img/product/item/')) {
                                //             mkdir('img/product/item/', 0777, true);
                                //         }
                                //         $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);
                                //         if (isset($upload['status']) && $upload['status'] == "success") {
                                //             $orderPhoto = ProductPhoto::where('id_product', $product->id_product)->orderBy('product_photo_order', 'desc')->first();
                                //             if ($orderPhoto) {
                                //                 $orderPhoto = $orderPhoto->product_photo_order + 1;
                                //             } else {
                                //                 $orderPhoto = 1;
                                //             }
                                //             $dataPhoto['id_product'] = $product->id_product;
                                //             $dataPhoto['product_photo'] = $upload['path'];
                                //             $dataPhoto['product_photo_order'] = $orderPhoto;
                                //             try {
                                //                 $photo = ProductPhoto::create($dataPhoto);
                                //             } catch (\Exception $e) {
                                //                 DB::rollBack();
                                //                 LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                //                 $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                //             }
                                //             //add in array photo
                                //             $imageUpload[] = $photo['product_photo'];
                                //         } else {
                                //             DB::rollBack();
                                //             $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                //         }
                                //     }
                                // }
                                $countUpdate++;
                                // list updated product utk data log
                                $newProductPrice = ProductPrice::where('id_product', $product->id_product)->where('id_outlet', $outlet->id_outlet)->first();
                                $newUpdatedAt =  $newProductPrice->updated_at;
                                $updateProd['id_product'] = $product['id_product'];
                                $updateProd['plu_id'] = $product['product_code'];
                                $updateProd['product_name'] = $product['product_name'];
                                $updateProd['old_price'] = $oldPrice;
                                $updateProd['new_price'] = (int) round($menu['price']);
                                $updateProd['old_updated_at'] = $oldUpdatedAt;
                                $updateProd['new_updated_at'] = $newUpdatedAt;
                                // if (count($imageUpload) > 0) {
                                //     $updateProd['new_photo'] = $imageUpload;
                                // }
                                $updatedProduct[] = $updateProd;
                            } else {
                                // Add product to rejected product
                                $productPrice = ProductPrice::where('id_outlet', $outlet->id_outlet)->where('id_product', $product->id_product)->first();
                                $dataBackend['plu_id'] = $product->product_code;
                                $dataBackend['name'] = $product->product_name_pos;
                                if (empty($productPrice)) {
                                    $dataBackend['price'] = '';
                                } else {
                                    $dataBackend['price'] = number_format($productPrice->product_price, 0, ',', '.');
                                }
                                $dataRaptor['plu_id'] = $menu['plu_id'];
                                $dataRaptor['name'] = $menu['name'];
                                $dataRaptor['price'] = number_format($menu['price'], 0, ',', '.');
                                array_push($rejectedProduct, ['backend' => $dataBackend, 'raptor' => $dataRaptor]);
                            }
                        }
                    }
                    // insert product
                    else {
                        $create = Product::create(['product_code' => $menu['plu_id'], 'product_name_pos' => $menu['name'], 'product_name' => $menu['name']]);
                        if ($create) {
                            // update price
                            $dataProductPrice['product_price'] = (int) round($menu['price']);
                            $dataProductPrice['product_price_base'] = round($menu['price_base'], 2);
                            $dataProductPrice['product_price_tax'] = round($menu['price_tax'], 2);
                            $dataProductPrice['product_status'] = $menu['status'];
                            try {
                                $updateProductPrice = ProductPrice::updateOrCreate([
                                    'id_product' => $create['id_product'],
                                    'id_outlet'  => $outlet->id_outlet
                                ], $dataProductPrice);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                            }
                            try {
                                $brandProduct = [];
                                foreach ($menu['brand_code'] as $valueBrand) {
                                    $getIdBrand = $getBrandList[array_search($valueBrand, $getBrand)]['id_brand'];
                                    $brandProduct[] = [
                                        'id_product' => $create['id_product'],
                                        'id_brand' => $getIdBrand,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ];
                                }
                                BrandProduct::insert($brandProduct);
                            } catch (Exception $e) {
                                DB::rollback();
                                LogBackendError::logExceptionMessage("ApiPOS/syncProduct=>" . $e->getMessage(), $e);
                                $failedProduct[] = 'fail to sync, brand ' . $menu['name'];
                                continue;
                            }
                            // $imageUpload = [];
                            // if (isset($menu['photo'])) {
                            //     foreach ($menu['photo'] as $photo) {
                            //         $image = file_get_contents($photo['url']);
                            //         $img = base64_encode($image);
                            //         if (!file_exists('img/product/item/')) {
                            //             mkdir('img/product/item/', 0777, true);
                            //         }
                            //         $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);
                            //         if (isset($upload['status']) && $upload['status'] == "success") {
                            //             $dataPhoto['id_product'] = $product->id_product;
                            //             $dataPhoto['product_photo'] = $upload['path'];
                            //             $dataPhoto['product_photo_order'] = 1;
                            //             try {
                            //                 $photo = ProductPhoto::create($dataPhoto);
                            //             } catch (\Exception $e) {
                            //                 DB::rollBack();
                            //                 LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                            //                 $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                            //             }
                            //             //add in array photo
                            //             $imageUpload[] = $photo['product_photo'];
                            //         } else {
                            //             DB::rollBack();
                            //             $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                            //         }
                            //     }
                            // }
                            $countInsert++;
                            // list new product utk data log
                            $insertProd['id_product'] = $create['id_product'];
                            $insertProd['plu_id'] = $create['product_code'];
                            $insertProd['product_name'] = $create['product_name'];
                            $insertProd['price'] = (int) round($menu['price']);
                            // if (count($imageUpload) > 0) {
                            //     $updateProd['new_photo'] = $imageUpload;
                            // }
                            $insertedProduct[] = $insertProd;
                        }
                    }
                    DB::commit();
                }
            }
            if ($flag == 'partial') {
                if (count($rejectedProduct) > 0) {
                    $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, $rejectedProduct, null);
                }
                if (count($failedProduct) > 0) {
                    $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, null, $failedProduct);
                }
            }
            $hasil['new_product']['total'] = (string) $countInsert;
            $hasil['new_product']['list_product'] = $insertedProduct;
            $hasil['updated_product']['total'] = (string) $countUpdate;
            $hasil['updated_product']['list_product'] = $updatedProduct;
            $hasil['rejected_product']['list_product'] = $rejectedProduct;
            $hasil['failed_product']['list_product'] = $failedProduct;
            return [
                'status'    => 'success',
                'result'  => $hasil,
            ];
        } else {
            return [
                'status'    => 'fail',
                'messages'  => ['store_code ' . $data['store_code'] . ' isn\'t match']
            ];
        }
    }

    public function syncSendEmail($syncDatetime, $outlet_code, $outlet_name, $rejectedProduct = null, $failedProduct = null)
    {
        $emailSync = Setting::where('key', 'email_sync_menu')->first();
        if (!empty($emailSync) && $emailSync->value != null) {
            $emailSync = explode(',', $emailSync->value);
            foreach ($emailSync as $key => $to) {
                $subject = 'Rejected product from menu sync raptor';
                $content['sync_datetime'] = $syncDatetime;
                $content['outlet_code'] = $outlet_code;
                $content['outlet_name'] = $outlet_name;
                if ($rejectedProduct != null) {
                    $content['total_rejected'] = count($rejectedProduct);
                    $content['rejected_menu'] = $rejectedProduct;
                }
                if ($failedProduct != null) {
                    $content['total_failed'] = count($failedProduct);
                    $content['failed_menu'] = $failedProduct;
                }
                // get setting email
                $setting = array();
                $set = Setting::where('key', 'email_from')->first();
                if (!empty($set)) {
                    $setting['email_from'] = $set['value'];
                } else {
                    $setting['email_from'] = null;
                }
                $set = Setting::where('key', 'email_sender')->first();
                if (!empty($set)) {
                    $setting['email_sender'] = $set['value'];
                } else {
                    $setting['email_sender'] = null;
                }
                $set = Setting::where('key', 'email_reply_to')->first();
                if (!empty($set)) {
                    $setting['email_reply_to'] = $set['value'];
                } else {
                    $setting['email_reply_to'] = null;
                }
                $set = Setting::where('key', 'email_reply_to_name')->first();
                if (!empty($set)) {
                    $setting['email_reply_to_name'] = $set['value'];
                } else {
                    $setting['email_reply_to_name'] = null;
                }
                $set = Setting::where('key', 'email_cc')->first();
                if (!empty($set)) {
                    $setting['email_cc'] = $set['value'];
                } else {
                    $setting['email_cc'] = null;
                }
                $set = Setting::where('key', 'email_cc_name')->first();
                if (!empty($set)) {
                    $setting['email_cc_name'] = $set['value'];
                } else {
                    $setting['email_cc_name'] = null;
                }
                $set = Setting::where('key', 'email_bcc')->first();
                if (!empty($set)) {
                    $setting['email_bcc'] = $set['value'];
                } else {
                    $setting['email_bcc'] = null;
                }
                $set = Setting::where('key', 'email_bcc_name')->first();
                if (!empty($set)) {
                    $setting['email_bcc_name'] = $set['value'];
                } else {
                    $setting['email_bcc_name'] = null;
                }
                $set = Setting::where('key', 'email_logo')->first();
                if (!empty($set)) {
                    $setting['email_logo'] = $set['value'];
                } else {
                    $setting['email_logo'] = null;
                }
                $set = Setting::where('key', 'email_logo_position')->first();
                if (!empty($set)) {
                    $setting['email_logo_position'] = $set['value'];
                } else {
                    $setting['email_logo_position'] = null;
                }
                $set = Setting::where('key', 'email_copyright')->first();
                if (!empty($set)) {
                    $setting['email_copyright'] = $set['value'];
                } else {
                    $setting['email_copyright'] = null;
                }
                $set = Setting::where('key', 'email_disclaimer')->first();
                if (!empty($set)) {
                    $setting['email_disclaimer'] = $set['value'];
                } else {
                    $setting['email_disclaimer'] = null;
                }
                $set = Setting::where('key', 'email_contact')->first();
                if (!empty($set)) {
                    $setting['email_contact'] = $set['value'];
                } else {
                    $setting['email_contact'] = null;
                }
                $data = array(
                    'content' => $content,
                    'setting' => $setting
                );
                Mailgun::send('pos::email_sync_menu', $data, function ($message) use ($to, $subject, $setting) {
                    $message->to($to)->subject($subject)
                        ->trackClicks(true)
                        ->trackOpens(true);
                    if (!empty($setting['email_from']) && !empty($setting['email_sender'])) {
                        $message->from($setting['email_from'], $setting['email_sender']);
                    } else if (!empty($setting['email_from'])) {
                        $message->from($setting['email_from']);
                    }
                    if (!empty($setting['email_reply_to'])) {
                        $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                    }
                    if (!empty($setting['email_cc']) && !empty($setting['email_cc_name'])) {
                        $message->cc($setting['email_cc'], $setting['email_cc_name']);
                    }
                    if (!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])) {
                        $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                    }
                });
            }
        }
    }

    public function syncMenuReturn(reqMenu $request)
    {
        // call function syncMenu
        $url = env('API_URL') . 'api/v1/pos/menu/sync';
        $syncMenu = MyHelper::post($url, MyHelper::getBearerToken(), $request->json()->all());

        // return sesuai api raptor
        if (isset($syncMenu['status']) && $syncMenu['status'] == 'success') {
            $hasil['inserted'] = $syncMenu['result']['new_product']['total'];
            $hasil['updated'] = $syncMenu['result']['updated_product']['total'];
            return response()->json([
                'status'    => 'success',
                'result'  => [$hasil]
            ]);
        }
        return $syncMenu;
    }

    public function transaction(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['api_key']) && !empty($post['api_secret']) &&
            !empty($post['store_code']) && !empty($post['transactions'])){

            $api = $this->checkApi($post['api_key'], $post['api_secret']);
            if ($api['status'] != 'success') {
                return response()->json($api);
            }

            $checkOutlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
            if (empty($checkOutlet)) {
                return response()->json(['status' => 'fail', 'messages' => 'Store not found']);
            }

            $countTransaction = count($post['transactions']);
            $x = 10;

            $countTransactionFail = 0;
            $countTransactionSuccess = 0;
            $countTransactionDuplicate = 0;
            $detailTransactionFail = [];

            if($countTransaction <= $x){
                $config['point']    = Configs::where('config_name', 'point')->first()->is_active;
                $config['balance']  = Configs::where('config_name', 'balance')->first()->is_active;
                $settingPoint       = Setting::where('key', 'point_conversion_value')->first()->value;
                $transOriginal      = $post['transactions'];

                $result = array();

                $receipt = array_column($post['transactions'], 'trx_id');
                //exclude receipt number when already exist in outlet
                $checkReceipt = Transaction::select('transaction_receipt_number', 'id_transaction')->where('id_outlet', $checkOutlet['id_outlet'])
                                    ->whereIn('transaction_receipt_number', $receipt)
                                    ->where('trasaction_type', 'Offline')
                                    ->get();
                $convertTranscToArray = $checkReceipt->toArray();
                $receiptExist = $checkReceipt->pluck('transaction_receipt_number')->toArray();

                $validReceipt = array_diff($receipt,$receiptExist);

                $invalidReceipt = array_intersect($receipt,$receiptExist);
                foreach($invalidReceipt as $key => $invalid){
                    $countTransactionDuplicate++;
                    unset($post['transactions'][$key]);
                }

                //check possibility duplicate
                $receiptDuplicate = Transaction::where('id_outlet', '!=', $checkOutlet['id_outlet'])
                                    ->whereIn('transaction_receipt_number', $validReceipt)
                                    ->where('trasaction_type', 'Offline')
                                    ->select('transaction_receipt_number')
                                    ->get()->pluck('transaction_receipt_number')->toArray();

                $transactionDuplicate = TransactionDuplicate::where('id_outlet', '=', $checkOutlet['id_outlet'])
                                        ->whereIn('transaction_receipt_number', $validReceipt)
                                        ->select('transaction_receipt_number')
                                        ->get()->pluck('transaction_receipt_number')->toArray();

                $receiptDuplicate = array_intersect($receipt, $receiptDuplicate);
                $contentDuplicate = [];
                foreach($receiptDuplicate as $key => $receipt){
                    if(in_array($receipt, $transactionDuplicate)){
                        $countTransactionDuplicate++;
                        unset($post['transactions'][$key]);
                    }else{
                        $duplicate = $this->processDuplicate($post['transactions'][$key], $checkOutlet);
                        if(isset($duplicate['status']) && $duplicate['status'] == 'duplicate'){
                            $countTransactionDuplicate++;
                            $data = [
                                'trx' => $duplicate['trx'],
                                'duplicate' =>$duplicate['duplicate']
                            ];
                            $contentDuplicate[] = $data;
                            unset($post['transactions'][$key]);
                        }
                    }
                }

                $countSettingCashback = TransactionSetting::get();
                $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->where('fraud_settings_status','Active')->first();
                $fraudTrxWeek = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->where('fraud_settings_status','Active')->first();
                foreach ($post['transactions'] as $key => $trx) {
                    if(!empty($trx['date_time']) &&
                        isset($trx['total']) &&
                        isset($trx['service']) && isset($trx['tax']) &&
                        isset($trx['discount']) && isset($trx['grand_total']) &&
                        isset($trx['menu'])){

                        $insertTrx = $this->insertTransaction($checkOutlet, $trx, $config, $settingPoint, $countSettingCashback, $fraudTrxDay, $fraudTrxWeek);
                        if(isset($insertTrx['id_transaction'])){
                                $countTransactionSuccess++;
                                $result[] = $insertTrx;
                        }else{
                            $countTransactionFail++;
                            if(isset($trx['trx_id'])){
                                $id = $trx['trx_id'];
                            }else{
                                $id = 'trx_id does not exist';
                            }
                            array_push($detailTransactionFail, $id);
                            $data = [
                                'outlet_code' => $post['store_code'],
                                'request' => json_encode($trx),
                                'message_failed' => $insertTrx['messages'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            SyncTransactionFaileds::create($data);
                        }
                    }else{
                        $countTransactionFail++;
                        if(isset($trx['trx_id'])){
                            $id = $trx['trx_id'];
                        }else{
                            $id = 'trx_id does not exist';
                        }

                        array_push($detailTransactionFail, $id);
                        $data = [
                            'outlet_code' => $post['store_code'],
                            'request' => json_encode($trx),
                            'message_failed' => 'There is an incomplete input in the transaction list',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        SyncTransactionFaileds::create($data);
                    }
                }

                return response()->json([
                    'status'    => 'success',
                    'result'    => [
                        'transaction_success' => $countTransactionSuccess,
                        'transaction_failed' => $countTransactionFail,
                        'transaction_duplicate' => $countTransactionDuplicate,
                        'detail_transaction_failed' => $detailTransactionFail
                    ]
                ]);
            }else{
                $countDataTransToSave = $countTransaction / $x;
                $checkFloat = is_float($countDataTransToSave);
                $getDataFrom = 0;

                if($checkFloat === true){
                    $countDataTransToSave = (int)$countDataTransToSave + 1;
                }

                for($i=0;$i<$countDataTransToSave;$i++){
                    $dataTransToSave = array_slice($post['transactions'], $getDataFrom, $x);
                    $data = [
                        'outlet_code' => $post['store_code'],
                        'request_transaction' => json_encode($dataTransToSave),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    try{
                        $insertTransactionQueue = SyncTransactionQueues::create($data);

                        if(!$insertTransactionQueue){
                            $countTransactionFail = $countTransactionFail + count($dataTransToSave);
                            array_push($detailTransactionFail, array_column($dataTransToSave, 'trx_id'));
                        }else{
                            $countTransactionSuccess = $countTransactionSuccess + count($dataTransToSave);
                        }

                    }catch (Exception $e) {
                        $countTransactionFail = $countTransactionFail + count($dataTransToSave);
                        array_push($detailTransactionFail, array_column($dataTransToSave, 'trx_id'));
                    }

                    $getDataFrom = $getDataFrom + $countDataTransToSave;
                }

                return response()->json([
                    'status'    => 'success',
                    'result'    => [
                        'transaction_success' => $countTransactionSuccess,
                        'transaction_failed' => $countTransactionFail,
                        'transaction_duplicate' => $countTransactionDuplicate,
                        'detail_transaction_failed' => $detailTransactionFail
                    ]
                ]);
            }

        }else{
            return response()->json(['status' => 'fail', 'messages' => 'Input is incomplete']);
        }
    }

    function insertTransaction($outlet, $trx, $config, $settingPoint, $countSettingCashback, $fraudTrxDay, $fraudTrxWeek){
        DB::beginTransaction();
        try{
            if(!isset($trx['order_id'])){
                if(count($trx['menu']) >= 0 && isset($trx['trx_id'])){
                    $countTrxDay = 0;
                    $countTrxWeek = 0;

                    $dataTrx = [
                            'id_outlet'                   => $outlet['id_outlet'],
                            'transaction_date'            => date('Y-m-d H:i:s', strtotime($trx['date_time'])),
                            'transaction_receipt_number'  => $trx['trx_id'],
                            'trasaction_type'             => 'Offline',
                            'transaction_subtotal'        => $trx['total'],
                            'transaction_service'         => $trx['service'],
                            'transaction_discount'        => $trx['discount'],
                            'transaction_tax'             => $trx['tax'],
                            'transaction_grandtotal'      => $trx['grand_total'],
                            'transaction_point_earned'    => null,
                            'transaction_cashback_earned' => null,
                            'trasaction_payment_type'     => 'Offline',
                            'transaction_payment_status'  => 'Completed'
                    ];

                    if(!empty($trx['sales_type'])){
                        $dataTrx['sales_type']  = $trx['sales_type'];
                    }

                    $trxVoucher = [];
                    $pointBefore = 0;
                    $pointValue = 0;

                    if (isset($trx['member_uid'])) {
                        if(strlen($trx['member_uid']) < 35){
                            DB::rollback();
                            return ['status' => 'fail', 'messages' => 'Minimum length of member uid is 35'];
                        }
                        $qr         = MyHelper::readQR($trx['member_uid']);
                        $timestamp  = $qr['timestamp'];
                        $phoneqr    = $qr['phone'];
                        $user       = User::where('phone', $phoneqr)->with('memberships')->first();

                        if (empty($user)) {
                            DB::rollback();
                            return ['status' => 'fail', 'messages' => 'User not found'];
                        }elseif(isset($user['is_suspended']) && $user['is_suspended'] == '1'){
                            $user['id'] = null;
                            $dataTrx['membership_level']    = null;
                            $dataTrx['membership_promo_id'] = null;
                        }else{
                            //========= This process to check if user have fraud ============//
                            $geCountTrxDay = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                                ->where('transactions.id_user',$user['id'])
                                ->whereRaw('DATE(transactions.transaction_date) = "'.date('Y-m-d', strtotime($trx['date_time'])).'"')
                                ->where('transactions.transaction_payment_status','Completed')
                                ->whereNull('transaction_pickups.reject_at')
                                ->count();

                            $currentWeekNumber = date('W',strtotime($trx['date_time']));
                            $currentYear = date('Y',strtotime($trx['date_time']));
                            $dto = new DateTime();
                            $dto->setISODate($currentYear,$currentWeekNumber);
                            $start = $dto->format('Y-m-d');
                            $dto->modify('+6 days');
                            $end = $dto->format('Y-m-d');

                            $geCountTrxWeek = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                                ->where('id_user',$user['id'])
                                ->where('transactions.transaction_payment_status','Completed')
                                ->whereNull('transaction_pickups.reject_at')
                                ->whereRaw('Date(transactions.transaction_date) BETWEEN "'.$start.'" AND "'.$end.'"')
                                ->count();

                            $countTrxDay = $geCountTrxDay + 1;
                            $countTrxWeek = $geCountTrxWeek + 1;
                            //================================ End ================================//

                            if (count($user['memberships']) > 0) {
                                $dataTrx['membership_level']    = $user['memberships'][0]['membership_name'];
                                $dataTrx['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
                            }

                            //using voucher
                            if (!empty($trx['voucher'])) {
                                foreach ($trx['voucher'] as $keyV => $valueV) {
                                    $checkVoucher = DealsVoucher::join('deals_users', 'deals_vouchers.id_deals_voucher', 'deals_users.id_deals_voucher')
                                                        ->leftJoin('transaction_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                                                        ->where('voucher_code', $valueV['voucher_code'])
                                                        ->where('deals_users.id_outlet', $outlet['id_outlet'])
                                                        ->where('deals_users.id_user', $user['id'])
                                                        ->whereNotNull('deals_users.used_at')
                                                        ->whereNull('transaction_vouchers.id_transaction_voucher')
                                                        ->select('deals_vouchers.*')
                                                        ->first();

                                    if (empty($checkVoucher)) {
                                        // for invalid voucher
                                        $dataVoucher['deals_voucher_invalid'] = $valueV['voucher_code'];
                                    }else{
                                        $dataVoucher['id_deals_voucher'] =  $checkVoucher['id_deals_voucher'];
                                    }
                                    $trxVoucher[] = $dataVoucher;
                                }
                            }else{
                                if($config['point'] == '1'){
                                    if (isset($user['memberships'][0]['membership_name'])) {
                                        $level = $user['memberships'][0]['membership_name'];
                                        $percentageP = $user['memberships'][0]['benefit_point_multiplier'] / 100;
                                    } else {
                                        $level = null;
                                        $percentageP = 0;
                                    }

                                    $point = floor(app($this->pos)->count('point', $trx) * $percentageP);
                                    $dataTrx['transaction_point_earned'] = $point;
                                }

                                if($config['balance'] == '1'){
                                    if (isset($user['memberships'][0]['membership_name'])) {
                                        $level = $user['memberships'][0]['membership_name'];
                                        $percentageB = $user['memberships'][0]['benefit_cashback_multiplier'] / 100;
                                        $cashMax = $user['memberships'][0]['cashback_maximum'];
                                    } else {
                                        $level = null;
                                        $percentageB = 0;
                                    }

                                    $data = $trx;
                                    $data['total'] = $trx['grand_total'];
                                    $cashback = floor(app($this->pos)->count('cashback', $data) * $percentageB);

                                    //count some trx user
                                    $countUserTrx = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                                        ->where('id_user',$user['id'])
                                        ->where('transactions.transaction_payment_status','Completed')
                                        ->whereNull('transaction_pickups.reject_at')
                                        ->count();
                                    if ($countUserTrx < count($countSettingCashback)) {
                                        $cashback = $cashback * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;
                                        if ($cashback > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                                            $cashback = $countSettingCashback[$countUserTrx]['cashback_maximum'];
                                        }
                                    } else{
                                        if(isset($cashMax) && $cashback > $cashMax){
                                            $cashback = $cashMax;
                                        }
                                    }
                                    $dataTrx['transaction_cashback_earned'] = $cashback;
                                }
                            }
                        }
                        $dataTrx['id_user'] = $user['id'];
                    }

                    if(isset($qr['device'])){
                        $dataTrx['transaction_device_type'] = $qr['device'];
                    }
                    if(isset($trx['cashier'])){
                        $dataTrx['transaction_cashier'] = $trx['cashier'];
                    }

                    $createTrx = Transaction::create($dataTrx);
                    if (!$createTrx) {
                        DB::rollback();
                        return ['status' => 'fail', 'messages' => 'Transaction sync failed'];
                    }

                    $dataPayments = [];
                    if(!empty($trx['payments'])){
                        foreach ($trx['payments'] as $col => $pay) {
                            if(isset($pay['type']) && isset($pay['name'])
                                && isset($pay['nominal'])){
                                $dataPay = [
                                    'id_transaction' => $createTrx['id_transaction'],
                                    'payment_type'   => $pay['type'],
                                    'payment_bank'   => $pay['name'],
                                    'payment_amount' => $pay['nominal'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                array_push($dataPayments,$dataPay);
                            }else{
                                DB::rollback();
                                return ['status' => 'fail', 'messages' => 'There is an incomplete input in the payment list'];
                            }
                        }
                    }else{
                        $dataPayments = [
                            'id_transaction' => $createTrx['id_transaction'],
                            'payment_type'   => 'offline',
                            'payment_bank'   => null,
                            'payment_amount' => 10000,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    $insertPayments = TransactionPaymentOffline::insert($dataPayments);
                    if (!$insertPayments) {
                        DB::rollback();
                        return ['status' => 'fail', 'messages' => 'Failed insert transaction payments'];
                    }

                    $userTrxProduct = [];
                    $allMenuId = array_column($trx['menu'], 'plu_id');
                    $checkProduct = Product::select('id_product', 'product_code')->whereIn('product_code', $allMenuId)->get()->toArray();

                    $allProductCode = array_column($checkProduct, 'product_code');
                    foreach ($trx['menu'] as $row => $menu) {
                        if(!empty($menu['plu_id']) && !empty($menu['name'])
                            && isset($menu['price']) && isset($menu['qty'])){

                            $getIndexProduct = array_search($menu['plu_id'], $allProductCode);

                            if($getIndexProduct === false){
                                //create new product
                                $dataProduct['product_code']      = $menu['plu_id'];
                                $dataProduct['product_name']      = $menu['name'];
                                $dataProduct['product_name_pos'] = $menu['name'];

                                $newProduct = Product::create($dataProduct);
                                if (!$newProduct) {
                                    DB::rollback();
                                    return ['status' => 'fail', 'messages' => 'Failed create new product'];
                                }

                                $productPriceData['id_product']         = $newProduct['id_product'];
                                $productPriceData['id_outlet']             = $outlet['id_outlet'];
                                $productPriceData['product_price_base'] = $menu['price'];
                                $newProductPrice = ProductPrice::create($productPriceData);
                                if (!$newProductPrice) {
                                    DB::rollback();
                                    return ['status' => 'fail', 'messages' => 'Failed create new product'];
                                }

                                $product = $newProduct;
                            }else{
                                $product = $checkProduct[$getIndexProduct];
                            }
                            $dataProduct = [
                                'id_transaction'               => $createTrx['id_transaction'],
                                'id_product'                   => $product['id_product'],
                                'id_outlet'                    => $outlet['id_outlet'],
                                'id_user'                      => $createTrx['id_user'],
                                'transaction_product_qty'      => $menu['qty'],
                                'transaction_product_price'    => round($menu['price'], 2),
                                'transaction_product_subtotal' => $menu['qty'] * round($menu['price'], 2)
                            ];
                            if (isset($menu['open_modifier'])) {
                                $dataProduct['transaction_product_note'] = $menu['open_modifier'];
                            }

                            $createProduct = TransactionProduct::create($dataProduct);

                            // update modifiers
                            if (!empty($menu['modifiers'])) {

                                $allModCode = array_column($menu['modifiers'], 'code');
                                $detailMod = ProductModifier::select('id_product_modifier','type','text','code')
                                        ->whereIn('code', $allModCode)
                                        ->where('id_product', '=', $product['id_product'])->get()->toArray();

                                $allMenuModifier = array_column($detailMod, 'code');
                                foreach ($menu['modifiers'] as $mod) {
                                    $getIndexMod = array_search($mod['code'], $allMenuModifier);

                                    if ($getIndexMod !== false) {
                                        $id_product_modifier = $detailMod[$getIndexMod]['id_product_modifier'];
                                        $type = $detailMod[$getIndexMod]['type'];
                                        $text = $detailMod[$getIndexMod]['text'];
                                    } else {
                                        if (isset($mod['text'])) {
                                            $text = $mod['text'];
                                        } else {
                                            $text = null;
                                        }
                                        if (isset($mod['type'])) {
                                            $type = $mod['type'];
                                        } else {
                                            $type = "";
                                        }
                                        $newModifier = ProductModifier::create([
                                            'id_product' => $product['id_product'],
                                            'type' => $type,
                                            'code' => $mod['code'],
                                            'text' => $text
                                        ]);
                                        $id_product_modifier = $newModifier['id_product_modifier'];
                                    }
                                    $dataProductMod['id_transaction_product'] = $createProduct['id_transaction_product'];
                                    $dataProductMod['id_transaction'] = $createTrx['id_transaction'];
                                    $dataProductMod['id_product'] = $product['id_product'];
                                    $dataProductMod['id_product_modifier'] = $id_product_modifier;
                                    $dataProductMod['id_outlet'] = $outlet['id_outlet'];
                                    $dataProductMod['id_user'] = $createTrx['id_user'];
                                    $dataProductMod['type'] = $type;
                                    $dataProductMod['code'] = $mod['code'];
                                    $dataProductMod['text'] = $text;
                                    $dataProductMod['qty'] = $menu['qty'];
                                    $dataProductMod['datetime'] = $createTrx['created_at'];
                                    $dataProductMod['trx_type'] = $createTrx['trasaction_type'];
                                    $dataProductMod['sales_type'] = $createTrx['sales_type'];

                                    $updateProductMod = TransactionProductModifier::updateOrCreate([
                                        'id_transaction' => $createTrx['id_transaction'],
                                        'code'  => $mod['code']
                                    ], $dataProductMod);
                                }
                            }
                            if (!$createProduct) {
                                DB::rollback();
                                return ['status' => 'fail', 'messages' => 'Transaction product sync failed'];
                            }
                        }else{
                            DB::rollback();
                            return['status' => 'fail', 'messages' => 'There is an incomplete input in the menu list'];
                        }

                    }

                    if((($fraudTrxDay && $countTrxDay <= $fraudTrxDay['parameter_detail']) && ($fraudTrxWeek && $countTrxWeek <= $fraudTrxWeek['parameter_detail']))
                        || (!$fraudTrxDay && !$fraudTrxWeek)){
                        if ($createTrx['transaction_point_earned']) {
                            $dataLog = [
                                'id_user'                     => $createTrx['id_user'],
                                'point'                       => $createTrx['transaction_point_earned'],
                                'id_reference'                => $createTrx['id_transaction'],
                                'source'                      => 'Transaction',
                                'grand_total'                 => $createTrx['transaction_grandtotal'],
                                'point_conversion'            => $settingPoint,
                                'membership_level'            => $level,
                                'membership_point_percentage' => $percentageP * 100
                            ];

                            $insertDataLog = LogPoint::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLog);
                            if (!$insertDataLog) {
                                DB::rollback();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Point Failed'
                                ];
                            }

                            $pointValue = $insertDataLog->point;

                            //update user point
                            $user->points = $pointBefore + $pointValue;
                            $user->update();
                            if (!$user) {
                                DB::rollback();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Point Failed'
                                ];
                            }
                        }

                        if ($createTrx['transaction_cashback_earned']) {

                            $insertDataLogCash = app($this->balance)->addLogBalance($createTrx['id_user'], $createTrx['transaction_cashback_earned'], $createTrx['id_transaction'], 'Transaction', $createTrx['transaction_grandtotal']);
                            if (!$insertDataLogCash) {
                                DB::rollback();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => 'Insert Cashback Failed'
                                ];
                            }
                            $usere= User::where('id',$createTrx['id_user'])->first();
                            $send = app($this->autocrm)->SendAutoCRM('Transaction Point Achievement', $usere->phone,
                                [
                                    "outlet_name"       => $outlet['outlet_name'],
                                    "transaction_date"  => $createTrx['transaction_date'],
                                    'id_transaction'    => $createTrx['id_transaction'],
                                    'receipt_number'    => $createTrx['transaction_receipt_number'],
                                    'received_point'    => (string) $createTrx['transaction_cashback_earned']
                                ]
                            );
                            if($send != true){
                                DB::rollback();
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => 'Failed Send notification to customer'
                                ]);
                            }
                            $pointValue = $insertDataLogCash->balance;
                        }

                    }else{
                        if($countTrxDay > $fraudTrxDay['parameter_detail'] && $fraudTrxDay){
                            $fraudFlag = 'transaction day';
                        }elseif($countTrxWeek > $fraudTrxWeek['parameter_detail'] && $fraudTrxWeek){
                            $fraudFlag = 'transaction week';
                        }else{
                            $fraudFlag = NULL;
                        }

                        $updatePointCashback = Transaction::where('id_transaction', $createTrx['id_transaction'])
                            ->update([
                                'transaction_point_earned' => NULL,
                                'transaction_cashback_earned' => NULL,
                                'fraud_flag' => $fraudFlag
                            ]);

                        if(!$updatePointCashback){
                            DB::rollback();
                            return response()->json([
                                'status' => 'fail',
                                'messages' => ['Failed update Point and Cashback']
                            ]);
                        }
                    }

                    //insert voucher
                    foreach($trxVoucher as $dataTrxVoucher){
                        $dataTrxVoucher['id_transaction'] = $createTrx['id_transaction'];
                        $create = TransactionVoucher::create($dataTrxVoucher);
                    }

                    if (isset($user['phone'])) {
                        $checkMembership = app($this->membership)->calculateMembership($user['phone']);
                        $userData = User::find($user['id']);
                        //cek fraud detection transaction per day
                        if ($fraudTrxDay) {
                            $checkFraud = app($this->setting_fraud)->checkFraud($fraudTrxDay, $userData, null, $countTrxDay, $countTrxWeek, $trx['date_time'], 0, $trx['trx_id']);
                        }
                        //cek fraud detection transaction per week
                        if ($fraudTrxWeek) {
                            $checkFraud = app($this->setting_fraud)->checkFraud($fraudTrxWeek, $userData, null, $countTrxDay, $countTrxWeek, $trx['date_time'], 0, $trx['trx_id']);
                        }
                    }

                    DB::commit();
                    return [
                        'id_transaction'    => $createTrx->id_transaction,
                        'point_before'      => (int)$pointBefore,
                        'point_after'       => $pointBefore + $pointValue,
                        'point_value'       => $pointValue
                    ];
                }else{
                    DB::rollback();
                    return ['status' => 'fail', 'messages' => 'trx_id does not exist'];
                }
            }
        }catch (Exception $e) {
            DB::rollback();
            return ['status' => 'fail', 'messages' => $e];
        }
    }

    function processDuplicate($trx, $outlet){
        DB::beginTransaction();
        try{
            $trxDuplicate = Transaction::where('transaction_receipt_number',  $trx['trx_id'])
                ->with('user', 'outlet', 'productTransaction.product')
                ->whereNotIn('transactions.id_outlet', [$outlet['id_outlet']])
                ->where('transaction_date', date('Y-m-d H:i:s', strtotime($trx['date_time'])))
                ->where('transaction_grandtotal', $trx['grand_total'])
                ->where('transaction_subtotal', $trx['total'])
                ->where('trasaction_type', 'Offline');

            if(isset($trx['cashier'])){
                $trxDuplicate = $trxDuplicate->where('transaction_cashier', $trx['cashier']);
            }

            $trxDuplicate = $trxDuplicate->first();
            if($trxDuplicate){
                //cek detail productnya
                $statusDuplicate = true;

                $trx['product'] = [];
                $detailproduct = [];

                foreach ($trx['menu'] as $row => $menu) {
                    $productDuplicate = false;
                    foreach($trxDuplicate['productTransaction'] as $i => $dataProduct){
                        if($menu['plu_id'] == $dataProduct['product']['product_code']){
                            //cek jumlah quantity
                            if($menu['qty'] == $dataProduct['transaction_product_qty']){
                                //set status product duplicate true
                                $productDuplicate = true;
                                $menu['id_product'] = $dataProduct['id_product'];
                                $menu['product_name'] = $dataProduct['product']['product_name'];
                                $trx['product'][] = $menu;
                                $detailproduct[] = $dataProduct;
                                unset($trxDuplicate['productTransaction'][$i]);
                            }
                        }
                    }

                    //jika status product duplicate false maka detail product ada yg berbeda
                    if($productDuplicate == false){
                        $statusDuplicate = false;
                        break;
                    }
                }

                $trxDuplicate['product'] = $detailproduct;

                if($statusDuplicate == true){
                    //insert into table transaction_duplicates
                    if (isset($trx['member_uid'])) {
                        $qr = MyHelper::readQR($trx['member_uid']);
                        $timestamp = $qr['timestamp'];
                        $phoneqr = $qr['phone'];
                        $user      = User::where('phone', $phoneqr)->with('memberships')->first();
                        if ($user) {
                            $dataDuplicate['id_user'] = $user['id'];
                        }
                    }

                    $dataDuplicate['id_transaction'] = $trxDuplicate['id_transaction'];
                    $dataDuplicate['id_outlet_duplicate'] = $trxDuplicate['outlet']['id_outlet'];
                    $dataDuplicate['id_outlet'] = $outlet['id_outlet'];
                    $dataDuplicate['transaction_receipt_number'] = $trx['trx_id'];
                    $dataDuplicate['outlet_code_duplicate'] = $trxDuplicate['outlet']['outlet_code'];
                    $dataDuplicate['outlet_code'] = $outlet['outlet_code'];
                    $dataDuplicate['outlet_name_duplicate'] = $trxDuplicate['outlet']['outlet_name'];
                    $dataDuplicate['outlet_name'] = $outlet['outlet_name'];

                    if(isset($user['name'])){
                        $dataDuplicate['user_name'] = $user['name'];
                    }

                    if(isset($user['phone'])){
                        $dataDuplicate['user_phone'] = $user['phone'];
                    }

                    $dataDuplicate['transaction_cashier'] = $trx['cashier'];
                    $dataDuplicate['transaction_date'] = date('Y-m-d H:i:s',strtotime($trx['date_time']));
                    $dataDuplicate['transaction_subtotal'] = $trx['total'];
                    $dataDuplicate['transaction_tax'] = $trx['tax'];
                    $dataDuplicate['transaction_service'] = $trx['service'];
                    $dataDuplicate['transaction_grandtotal'] = $trx['grand_total'];
                    $dataDuplicate['sync_datetime_duplicate'] = $trxDuplicate['created_at'];
                    $dataDuplicate['sync_datetime'] = date('Y-m-d H:i:s');
                    $insertDuplicate = TransactionDuplicate::create($dataDuplicate);
                    if(!$insertDuplicate){
                        DB::rollback();
                        return ['status' => 'Transaction sync failed'];
                    }

                    //insert transaction duplicate product
                    $prodDuplicate = [];
                    foreach ($trx['product'] as $row => $menu) {
                        $dataTrxDuplicateProd['id_transaction_duplicate'] = $insertDuplicate['id_transaction_duplicate'];

                        $dataTrxDuplicateProd['id_product'] = $menu['id_product'];
                        $dataTrxDuplicateProd['transaction_product_code'] = $menu['plu_id'];
                        $dataTrxDuplicateProd['transaction_product_name'] = $menu['product_name'];
                        $dataTrxDuplicateProd['transaction_product_qty'] = $menu['qty'];
                        $dataTrxDuplicateProd['transaction_product_price'] = $menu['price'];
                        $dataTrxDuplicateProd['transaction_product_subtotal'] = $menu['qty'] * $menu['price'];
                        if(isset($menu['open_modifier'])){
                            $dataTrxDuplicateProd['transaction_product_note'] = $menu['open_modifier'];
                        }
                        $dataTrxDuplicateProd['created_at'] = date('Y-m-d H:i:s');
                        $dataTrxDuplicateProd['updated_at'] = date('Y-m-d H:i:s');

                        $prodDuplicate[] = $dataTrxDuplicateProd;
                    }

                    $insertTrxDuplicateProd = TransactionDuplicateProduct::insert($prodDuplicate);
                    if(!$insertTrxDuplicateProd){
                        DB::rollback();
                        return ['status' => 'Transaction sync failed'];
                    }

                    //insert payment
                    $payDuplicate = [];
                    if(!empty($trx['payments'])){
                        foreach($trx['payments'] as $pay){
                            $dataTrxDuplicatePay['id_transaction_duplicate'] = $insertDuplicate['id_transaction_duplicate'];
                            $dataTrxDuplicatePay['payment_name'] = $pay['name'];
                            $dataTrxDuplicatePay['payment_type'] = $pay['type'];
                            $dataTrxDuplicatePay['payment_amount'] = $pay['nominal'];
                            $dataTrxDuplicatePay['created_at'] = date('Y-m-d H:i:s');
                            $dataTrxDuplicatePay['updated_at'] = date('Y-m-d H:i:s');
                            $payDuplicate[] = $dataTrxDuplicatePay;
                        }

                    }else{
                        $dataTrxDuplicatePay = [
                            'id_transaction_duplicate' => $insertDuplicate['id_transaction_duplicate'],
                            'payment_name' => 'Offline',
                            'payment_type' => 'offline',
                            'payment_amount' => 10000,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $payDuplicate[] = $dataTrxDuplicatePay;
                    }

                    $insertTrxDuplicatePay = TransactionDuplicatePayment::create($dataTrxDuplicatePay);
                    if(!$insertTrxDuplicatePay){
                        DB::rollback();
                        return ['status' => 'Transaction sync failed'];
                    }

                    $trx['outlet_name'] = $outlet['outlet_name'];
                    $trx['outlet_code'] = $outlet['outlet_code'];
                    $trx['sync_datetime'] = $dataDuplicate['sync_datetime'];

                    DB::commit();
                    return [
                        'status' => 'duplicate',
                        'duplicate' => $trxDuplicate,
                        'trx' => $trx,
                    ];
                }
            }

            return ['status' => 'not duplicate'];
        }catch (Exception $e){
            DB::rollback();
            return ['status' => 'fail', 'messages' => $e];
        }
    }

    public function transactionRefund(reqTransactionRefund $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            DB::rollback();
            return response()->json($api);
        }

        $checkTrx = Transaction::where('transaction_receipt_number', $post['trx_id'])->first();
        if (empty($checkTrx)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => 'Transaction not found']);
        }

        //if use voucher, cannot refund
        $trxVou = TransactionVoucher::where('id_transaction', $checkTrx->id_transaction)->first();
        if ($trxVou) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => 'Transaction cannot be refund. This transaction use voucher']);
        }

        if ($checkTrx->id_user) {
            $user = User::where('id', $checkTrx->id_user)->first();
            if (empty($user)) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => 'User not found']);
            }
        }

        $checkTrx->transaction_payment_status = 'Cancelled';
        $checkTrx->void_date = date('Y-m-d H:i:s');
        $checkTrx->transaction_notes = $post['reason'];
        $checkTrx->update();
        if (!$checkTrx) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => 'Transaction refund sync failed1']);
        }

        $user = User::where('id', $checkTrx->id_user)->first();
        if ($user) {
            $point = LogPoint::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
            if (!empty($point)) {
                $point->delete();
                if (!$point) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => 'Transaction refund sync failed2']);
                }

                //update user point
                $sumPoint = LogPoint::where('id_user', $user['id'])->sum('point');
                $user->points = $sumPoint;
                $user->update();
                if (!$user) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => 'Update point failed'
                    ]);
                }
            }

            $balance = LogBalance::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
            if (!empty($balance)) {
                $balance->delete();
                if (!$balance) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => 'Transaction refund sync failed']);
                }

                //update user balance
                $sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
                $user->balance = $sumBalance;
                $user->update();
                if (!$user) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => 'Update cashback failed'
                    ]);
                }
            }
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
        }

        DB::commit();

        return response()->json(['status' => 'success']);
    }

    public static function checkApi($key, $secret)
    {
        $api_key = Setting::where('key', 'api_key')->first();
        if (empty($api_key)) {
            return ['status' => 'fail', 'messages' => 'api_key not found'];
        }

        $api_key = $api_key['value'];
        if ($api_key != $key) {
            return ['status' => 'fail', 'messages' => 'api_key isn\’t match'];
        }

        $api_secret = Setting::where('key', 'api_secret')->first();
        if (empty($api_secret)) {
            return ['status' => 'fail', 'messages' => 'api_secret not found'];
        }

        $api_secret = $api_secret['value'];
        if ($api_secret != $secret) {
            return ['status' => 'fail', 'messages' => 'api_secret isn\’t match'];
        }

        return ['status' => 'success'];
    }

    function count($value, $data)
    {
        if ($value == 'point') {
            $subtotal     = $data['total'];
            $service      = $data['service'];
            $discount     = $data['discount'];
            $tax          = $data['tax'];
            $pointFormula = $this->convertFormula('point');
            $value        = $this->pointValue();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $pointFormula) . ';'));
            return $count;
        }

        if ($value == 'cashback') {
            $subtotal        = $data['total'];
            $service         = $data['service'];
            $discount        = $data['discount'];
            $tax             = $data['tax'];
            $cashbackFormula = $this->convertFormula('cashback');
            $value           = $this->cashbackValue();
            // $max             = $this->cashbackValueMax();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $cashbackFormula) . ';'));

            // if ($count >= $max) {
            //     return $max;
            // } else {
            return $count;
            // }

        }
    }

    public function convertFormula($value)
    {
        $convert = $this->$value();
        return $convert;
    }

    public function point()
    {
        $point = $this->setting('point_acquisition_formula');

        $point = preg_replace('/\s+/', '', $point);
        return $point;
    }

    public function cashback()
    {
        $cashback = $this->setting('cashback_acquisition_formula');

        $cashback = preg_replace('/\s+/', '', $cashback);
        return $cashback;
    }

    public function setting($value)
    {
        $setting = Setting::where('key', $value)->first();

        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

    public function pointCount()
    {
        $point = $this->setting('point_acquisition_formula');
        return $point;
    }

    public function cashbackCount()
    {
        $cashback = $this->setting('cashback_acquisition_formula');
        return $cashback;
    }

    public function pointValue()
    {
        $point = $this->setting('point_conversion_value');
        return $point;
    }

    public function cashbackValue()
    {
        $cashback = $this->setting('cashback_conversion_value');
        return $cashback;
    }

    public function cashbackValueMax()
    {
        $cashback = $this->setting('cashback_maximum');
        return $cashback;
    }

    public function getLastTransaction(Request $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        $checkOutlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
        if (empty($checkOutlet)) {
            return response()->json(['status' => 'fail', 'messages' => 'Store not found']);
        }

        $trx = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->select('transactions.id_transaction', 'transaction_date', 'transaction_receipt_number', 'order_id')
            ->where('id_outlet', $checkOutlet['id_outlet'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->orderBy('transactions.id_transaction', 'DESC')
            ->limit(10)->get();

        foreach ($trx as $key => $dataTrx) {
            $qrCode = 'https://chart.googleapis.com/chart?chl=' . $dataTrx['order_id'] . '&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

            $trx[$key]['qrcode'] = $qrCode;
        }

        return response()->json(MyHelper::checkGet($trx));
    }

    public function syncOutletMenu(Request $request)
    {
        $post = $request->json()->all();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }
        $lastData = end($post['store']);
        foreach ($post['store'] as $key => $value) {
            $data[$key]['store_code']   = $value['store_code'];
            if ($value == $lastData) {
                $data[$key]['is_end']   = 1;
            } else {
                $data[$key]['is_end']   = 0;
            }
            $data[$key]['request']      = json_encode($value);
            $data[$key]['created_at']   = date('Y-m-d H:i:s');
            $data[$key]['updated_at']   = date('Y-m-d H:i:s');
        }
        DB::beginTransaction();
        try {
            $insertRequest = SyncMenuRequest::insert($data);
        } catch (\Exception $e) {
            DB::rollback();
            LogBackendError::logExceptionMessage("ApiPOS/syncOutletMenu=>" . $e->getMessage(), $e);
        }
        DB::commit();
        return response()->json(MyHelper::checkGet($insertRequest));
    }
    
    public function syncOutletMenuCron(Request $request)
    {
        $getRequest = SyncMenuRequest::get()->first()->toArray();
        $getRequest['request'] = json_decode($getRequest['request'], true);
        // $syncMenu = $this->syncMenuProcess($getRequest['request'], 'bulk');
        // if ($syncMenu['status'] == 'success') {
        //     SyncMenuResult::create(['result' => json_encode($syncMenu['result'])]);
        // } else {
        //     SyncMenuResult::create(['result' => json_encode($syncMenu['messages'])]);
        // }
        if ($getRequest['is_end'] == 1) {
            $getResult = SyncMenuResult::pluck('result');
            $totalReject    = 0;
            $totalFailed    = 0;
            $listFailed     = [];
            $listRejected     = [];
            foreach ($getResult as $value) {
                $data[] = json_decode($value, true);
                if (isset(json_decode($value, true)[0])) {
                    $result['fail'][] = json_decode($value, true)[0];
                }
                if (isset(json_decode($value, true)['rejected_product'])) {
                    $totalReject    = $totalReject + count(json_decode($value, true)['rejected_product']['list_product']);
                    foreach (json_decode($value, true)['rejected_product']['list_product'] as $valueRejected) {
                        array_push($listRejected, $valueRejected);
                    }
                }
                if (isset(json_decode($value, true)['failed_product'])) {
                    $totalFailed    = $totalFailed + count(json_decode($value, true)['failed_product']['list_product']);
                    foreach (json_decode($value, true)['failed_product']['list_product'] as $valueFailed) {
                        array_push($listFailed, $valueFailed);
                    }
                }
            }
            
            if (count($listRejected) > 0) {
                $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, $rejectedProduct, null);
            }
            if (count($listFailed) > 0) {
                $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, null, $failedProduct);
            }
        }
        SyncMenuRequest::where('id', $getRequest['id'])->delete();
    }
}
