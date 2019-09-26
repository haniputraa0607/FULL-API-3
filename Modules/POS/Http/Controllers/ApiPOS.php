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

use Modules\POS\Http\Controllers\CheckVoucher;

use DB;

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
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]);
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

            $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], ["outlet_name" => $outlet['outlet_name'], "id_reference" => $check['transaction_receipt_number'] . ',' . $outlet['id_outlet'], "transaction_date" => $check['transaction_date']]);

            return response()->json(['status' => 'success', 'result' => $transactions]);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Invalid Order ID']]);
        }
        return response()->json(['status' => 'success', 'message' => 'API is not ready yet. Stay tuned!', 'result' => $post]);
    }

    public function checkMember(reqMember $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
        if (empty($outlet)) {
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]);
        }

        $qr = MyHelper::readQR($post['uid']);
        $timestamp = $qr['timestamp'];
        $phoneqr = $qr['phone'];

        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', $timestamp)) {
            return response()->json(['status' => 'fail', 'messages' => ['Mohon refresh qrcode dan ulangi scan member']]);
        }

        $user = User::where('phone', $phoneqr)->first();
        if (empty($user)) {
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
        }

        //suspend
        if (isset($user['is_suspended']) && $user['is_suspended'] == '1') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Maaf, akun Anda sedang di-suspend']
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
    }

    public function checkVoucher(reqVoucher $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return CheckVoucher::check($post);
    }

    public function voidVoucher(voidVoucher $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        DB::beginTransaction();

        $voucher = DealsVoucher::with('deals_user')->where('voucher_code', $post['voucher_code'])->first();
        if (!$voucher) {
            return response()->json(['status' => 'fail', 'messages' => ['Voucher tidak ditemukan']]);
        }

        // if($voucher['deals_user'][0]){
        //     return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher '.$post['voucher_code'].'. Voucher sudah digunakan.']]); 
        // }

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
                return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher ' . $post['voucher_code'] . '. Segera hubungi team support']]);
            }
        }

        //update count deals
        $deals = Deal::find($voucher['id_deals']);
        $deals->deals_total_redeemed = $deals->deals_total_redeemed - 1;
        $deals->update();
        if (!$deals) {
            DB::rollBack();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher ' . $post['voucher_code'] . '. Segera hubungi team support']]);
        }

        DB::commit();
        return response()->json(['status' => 'success', 'messages' => ['Void Voucher ' . $post['voucher_code'] . ' telah berhasil']]);
    }

    public function syncOutlet(reqOutlet $request)
    {
        $post = $request->json()->all();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }
        DB::beginTransaction();
        foreach ($post['store'] as $key => $value) {
            $dataOutlet[$key]['outlet_name'] = $value['store_name'];
            $dataOutlet[$key]['outlet_code'] = strtoupper($value['store_code']);
            $dataOutlet[$key]['outlet_status'] = strtoupper($value['store_status']);
            $dataBrand[$key]['code_brand'] = strtoupper($value['brand_code']);
            $dataBrandOutlet[$key]['outlet_code'] = strtoupper($value['store_code']);
            $dataBrandOutlet[$key]['code_brand'] = strtoupper($value['brand_code']);
        }
        foreach (array_unique($dataOutlet, SORT_REGULAR) as $key => $value) {
            $cekOutlet = Outlet::where('outlet_code', strtoupper($value['outlet_code']))->first();
            if ($cekOutlet) {
                $update = Outlet::where('outlet_code', strtoupper($value['outlet_code']))->update($value);
                if (!$update) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['fail to sync']
                    ]);
                }
            } else {
                $save = Outlet::create($value);
                if (!$save) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['fail to sync']
                    ]);
                }
            }
        }
        foreach (array_unique($dataBrand, SORT_REGULAR) as $key => $value) {
            $cekBrand = Brand::where('code_brand', strtoupper($value['code_brand']))->first();
            if (!$cekBrand) {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to sync, brand ' . $value['code_brand']]
                ]);
            }
        }
        foreach ($dataBrandOutlet as $key => $value) {
            $getId['id_outlet'] = Outlet::where('outlet_code', $value['outlet_code'])->first()->id_outlet;
            $getId['id_brand'] = Brand::where('code_brand', $value['code_brand'])->first()->id_brand;
            $save = BrandOutlet::updateOrCreate($getId);
            if (!$save) {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to sync']
                ]);
            }
        }
        // return success
        DB::commit();
        return response()->json([
            'status' => 'success'
        ]);
    }

    public function syncMenu(Request $request)
    {
        $post = $request->json()->all();

        $syncDatetime = date('d F Y h:i');

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
        if ($outlet) {
            $countInsert = 0;
            $countUpdate = 0;
            $rejectedProduct = [];
            $updatedProduct = [];
            $insertedProduct = [];
            $failedProduct = [];

            foreach ($post['menu'] as $key => $menu) {
                dd($menu['brand_code']);
                if (isset($menu['plu_id']) && isset($menu['name'])) {
                    DB::beginTransaction();
                    $product = Product::where('product_code', $menu['plu_id'])->first();

                    // return response()->json($menu);
                    // update product
                    if ($product) {
                        // cek allow sync, jika 0 product tidak di update
                        if ($product->product_allow_sync == '1') {

                            foreach ($menu['brand_code'] as $valueBrand) {
                                $brand = Brand::where('code_brand', $valueBrand)->first();
                                if (!$brand) {
                                    DB::rollBack();
                                    $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                }
                                try {
                                    BrandProduct::create(
                                        [
                                            'id_product' => $product->id_product,
                                            'id_brand'  => $brand->id_brand
                                        ]
                                    );
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
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
                                $imageUpload = [];
                                if (isset($menu['photo'])) {
                                    foreach ($menu['photo'] as $photo) {
                                        $image = file_get_contents($photo['url']);
                                        $img = base64_encode($image);
                                        if (!file_exists('img/product/item/')) {
                                            mkdir('img/product/item/', 0777, true);
                                        }

                                        $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);

                                        if (isset($upload['status']) && $upload['status'] == "success") {
                                            $orderPhoto = ProductPhoto::where('id_product', $product->id_product)->orderBy('product_photo_order', 'desc')->first();
                                            if ($orderPhoto) {
                                                $orderPhoto = $orderPhoto->product_photo_order + 1;
                                            } else {
                                                $orderPhoto = 1;
                                            }
                                            $dataPhoto['id_product'] = $product->id_product;
                                            $dataPhoto['product_photo'] = $upload['path'];
                                            $dataPhoto['product_photo_order'] = $orderPhoto;

                                            try {
                                                $photo = ProductPhoto::create($dataPhoto);
                                            } catch (\Exception $e) {
                                                DB::rollBack();
                                                LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                                $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                            }

                                            //add in array photo
                                            $imageUpload[] = $photo['product_photo'];
                                        } else {
                                            DB::rollBack();
                                            $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                        }
                                    }
                                }

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
                                if (count($imageUpload) > 0) {
                                    $updateProd['new_photo'] = $imageUpload;
                                }

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
                        foreach ($menu['brand_code'] as $valueBrand) {
                            $brand = Brand::where('code_brand', $valueBrand)->first();
                            if (!$brand) {
                                DB::rollBack();
                                $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                            }
                            try {
                                BrandProduct::create(
                                    [
                                        'id_product' => $create->id_product,
                                        'id_brand'  => $brand->id_brand
                                    ]
                                );
                            } catch (\Exception $e) {
                                DB::rollBack();
                                LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                            }
                        }
                        if ($create) {
                            // update price
                            $dataProductPrice['product_price'] = (int) round($menu['price']);
                            $dataProductPrice['product_price_base'] = round($menu['price_base'], 2);
                            $dataProductPrice['product_price_tax'] = round($menu['price_tax'], 2);
                            $dataProductPrice['product_status'] = $menu['status'];

                            try {
                                $updateProductPrice = ProductPrice::updateOrCreate([
                                    'id_product' => $create->id_product,
                                    'id_outlet'  => $outlet->id_outlet
                                ], $dataProductPrice);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                            }

                            $imageUpload = [];
                            if (isset($menu['photo'])) {
                                foreach ($menu['photo'] as $photo) {
                                    $image = file_get_contents($photo['url']);
                                    $img = base64_encode($image);
                                    if (!file_exists('img/product/item/')) {
                                        mkdir('img/product/item/', 0777, true);
                                    }

                                    $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);

                                    if (isset($upload['status']) && $upload['status'] == "success") {
                                        $dataPhoto['id_product'] = $product->id_product;
                                        $dataPhoto['product_photo'] = $upload['path'];
                                        $dataPhoto['product_photo_order'] = 1;

                                        try {
                                            $photo = ProductPhoto::create($dataPhoto);
                                        } catch (\Exception $e) {
                                            DB::rollBack();
                                            LogBackendError::logExceptionMessage("ApiPOS/syncMenu=>" . $e->getMessage(), $e);
                                            $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                        }

                                        //add in array photo
                                        $imageUpload[] = $photo['product_photo'];
                                    } else {
                                        DB::rollBack();
                                        $failedProduct[] = ['product_code' => $menu['plu_id'], 'product_name' => $menu['name']];
                                    }
                                }
                            }

                            $countInsert++;

                            // list new product utk data log
                            $insertProd['id_product'] = $create['id_product'];
                            $insertProd['plu_id'] = $create['product_code'];
                            $insertProd['product_name'] = $create['product_name'];
                            $insertProd['price'] = (int) round($menu['price']);
                            if (count($imageUpload) > 0) {
                                $updateProd['new_photo'] = $imageUpload;
                            }

                            $insertedProduct[] = $insertProd;
                        }
                    }
                    DB::commit();
                }
            }

            // send email rejected product
            if (count($rejectedProduct) > 0) {
                $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, $rejectedProduct, null);
            }
            if (count($failedProduct) > 0) {
                $this->syncSendEmail($syncDatetime, $outlet->outlet_code, $outlet->outlet_name, null, $failedProduct);
            }

            $hasil['new_product']['total'] = (string) $countInsert;
            $hasil['new_product']['list_product'] = $insertedProduct;
            $hasil['updated_product']['total'] = (string) $countUpdate;
            $hasil['updated_product']['list_product'] = $updatedProduct;
            $hasil['failed_product']['list_product'] = $failedProduct;

            return response()->json([
                'status'    => 'success',
                'result'  => $hasil,
            ]);
        } else {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['store_code isn\'t match']
            ]);
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

    public function transaction(reqTransaction $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            DB::rollback();
            return response()->json($api);
        }

        $checkOutlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first();
        if (empty($checkOutlet)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]);
        }

        $result = array();
        foreach ($post['transactions'] as $key => $trx) {
            if (isset($trx['order_id'])) {
                if (!empty($trx['order_id'])) {
                    $trx = Transaction::join('transaction_pickups', 'transactions.id_transaction', '=', 'transaction_pickups.id_transaction')
                        ->where('transaction_pickups.order_id', '=', $trx['order_id'])
                        ->whereDate('transactions.transaction_date', '=', date("Y-m-d"))
                        ->first();
                    if ($trx) {
                        $r = [
                            'id_transaction'    => $trx['id_transaction']
                        ];
                        array_push($result, $r);
                    } else {
                        if (count($post['transactions']) == 1)
                            return response()->json(['status' => 'fail', 'messages' => ['Order ID not found']]);
                    }
                }
            } else {
                if (count($trx['menu']) <= 0) {
                    continue;
                }
                if (!$trx['trx_id']) {
                    continue;
                }
                if (isset($trx['member_uid'])) {
                    $qr = MyHelper::readQR($trx['member_uid']);
                    $timestamp = $qr['timestamp'];
                    $phoneqr = $qr['phone'];
                    $user      = User::where('phone', $phoneqr)->with('memberships')->first();
                    if (empty($user)) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
                    }

                    //suspend
                    if (isset($user['is_suspended']) && $user['is_suspended'] == '1') {
                        $user['id'] = null;
                        $post['membership_level']    = null;
                        $post['membership_promo_id'] = null;
                    } else {

                        if (count($user['memberships']) > 0) {
                            $post['membership_level']    = $user['memberships'][0]['membership_name'];
                            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
                        } else {
                            $post['membership_level']    = null;
                            $post['membership_promo_id'] = null;
                        }
                    }
                } else {

                    //transaction with voucher but non member
                    if (isset($trx['voucher']) && !empty($trx['voucher'])) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transactions with vouchers must be with member UID', 'trx id : ' . $trx['trx_id'] . ' does not have a member UID']]);
                    }

                    $user['id'] = null;
                    $post['membership_level']    = null;
                    $post['membership_promo_id'] = null;
                }

                $trx['total'] = round($trx['total']);
                $trx['discount'] = round($trx['discount']);
                $trx['tax'] = round($trx['tax']);

                if (!isset($trx['sales_type'])) {
                    $trx['sales_type'] = null;
                }

                $dataTrx = [
                    'id_outlet'                   => $checkOutlet['id_outlet'],
                    'id_user'                     => $user['id'],
                    'transaction_date'            => date('Y-m-d H:i:s', strtotime($trx['date_time'])),
                    'transaction_receipt_number'  => $trx['trx_id'],
                    'trasaction_type'             => 'Offline',
                    'sales_type'                  => $trx['sales_type'],
                    'transaction_subtotal'        => $trx['total'],
                    'transaction_service'         => $trx['service'],
                    'transaction_discount'        => $trx['discount'],
                    'transaction_tax'             => $trx['tax'],
                    'transaction_grandtotal'      => $trx['grand_total'],
                    'transaction_point_earned'    => null,
                    'transaction_cashback_earned' => null,
                    'membership_level'            => $post['membership_level'],
                    'membership_promo_id'         => $post['membership_promo_id'],
                    'trasaction_payment_type'     => 'Offline',
                    'transaction_payment_status'  => 'Completed'
                ];

                if (isset($qr['device'])) {
                    $dataTrx['transaction_device_type'] = $qr['device'];
                }

                if (isset($trx['cashier'])) {
                    $dataTrx['transaction_cashier'] = $trx['cashier'];
                }

                //cek jika transaksi sudah pernah di sync, data tidak akan diproses
                $cektransaction = Transaction::where(['transaction_receipt_number' => $trx['trx_id'], 'id_outlet' => $checkOutlet['id_outlet']])->first();
                if ($cektransaction) {
                    $r = ['id_transaction'    => $cektransaction->id_transaction];
                    array_push($result, $r);
                    continue;
                }

                $createTrx = Transaction::updateOrCreate(['transaction_receipt_number' => $trx['trx_id'], 'id_outlet' => $checkOutlet['id_outlet']], $dataTrx);

                if (!$createTrx) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
                }

                $checkPay = TransactionPaymentOffline::where('id_transaction', $createTrx['id_transaction'])->get();
                if (count($checkPay) > 0) {
                    $deletePay = TransactionPaymentOffline::where('id_transaction', $createTrx['id_transaction'])->delete();
                    if (!$deletePay) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
                    }
                }

                $statusGet = 0;

                foreach ($trx['payments'] as $col => $pay) {
                    $paymentSpecial = SpecialMembership::where('payment_method', $pay['name'])->first();
                    if (!empty($paymentSpecial)) {
                        $paymentUse = $paymentSpecial;
                        $statusGet = 1;
                    }

                    $dataPay = [
                        'id_transaction' => $createTrx['id_transaction'],
                        'payment_type'   => $pay['type'],
                        'payment_bank'   => $pay['name'],
                        'payment_amount' => $pay['nominal']
                    ];

                    $createPay = TransactionPaymentOffline::create($dataPay);
                    if (!$createPay) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
                    }
                }

                $checkProd = TransactionProduct::where('id_transaction', $createTrx['id_transaction'])->get();
                if (count($checkProd) > 0) {
                    $checkProd = TransactionProduct::where('id_transaction', $createTrx['id_transaction'])->delete();
                }

                foreach ($trx['menu'] as $row => $menu) {
                    $checkProduct = Product::where('product_code', $menu['plu_id'])->first();
                    if (empty($checkProduct)) {
                        //create new product
                        $dataProduct['product_code']      = $menu['plu_id'];
                        $dataProduct['product_name']      = $menu['name'];
                        $dataProduct['product_name_pos'] = $menu['name'];

                        $newProduct = Product::create($dataProduct);
                        if (!$newProduct) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
                        }

                        $productPriceData['id_product']         = $newProduct['id_product'];
                        $productPriceData['id_outlet']             = $checkOutlet['id_outlet'];
                        $productPriceData['product_price_base'] = $menu['price'];
                        $newProductPrice = ProductPrice::create($productPriceData);
                        if (!$newProductPrice) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
                        }

                        $checkProduct = $newProduct;
                    }

                    $dataProduct = [
                        'id_transaction'               => $createTrx['id_transaction'],
                        'id_product'                   => $checkProduct['id_product'],
                        'id_outlet'                    => $checkOutlet['id_outlet'],
                        'id_user'                      => $createTrx['id_user'],
                        'transaction_product_qty'      => $menu['qty'],
                        'transaction_product_price'    => round($menu['price'], 2),
                        'transaction_product_subtotal' => $menu['qty'] * round($menu['price'], 2)
                    ];
                    if (isset($menu['open_modifier'])) {
                        $dataProduct['transaction_product_note'] = $menu['open_modifier'];
                    }

                    $createProduct = TransactionProduct::create($dataProduct);
                    // 	$createProduct = TransactionProduct::updateOrCreate(['id_transaction' => $createTrx['id_transaction'], 'id_product' => $checkProduct['id_product']], $dataProduct);

                    // update modifiers 
                    if (isset($menu['modifiers'])) {
                        if (!empty($menu['modifiers'])) {
                            foreach ($menu['modifiers'] as $mod) {
                                $detailMod = ProductModifier::where('id_product', '=', $checkProduct['id_product'])
                                    ->where('code', '=', $mod['code'])
                                    ->first();
                                if ($detailMod) {
                                    $id_product_modifier = $detailMod['id_product_modifier'];
                                    $type = $detailMod['type'];
                                    $text = $detailMod['text'];
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
                                        'id_product' => $checkProduct['id_product'],
                                        'type' => $mod['type'],
                                        'code' => $mod['code'],
                                        'text' => $text
                                    ]);
                                    $id_product_modifier = $newModifier['id_product_modifier'];
                                }
                                $dataProductMod['id_transaction_product'] = $createProduct['id_transaction_product'];
                                $dataProductMod['id_transaction'] = $createTrx['id_transaction'];
                                $dataProductMod['id_product'] = $checkProduct['id_product'];
                                $dataProductMod['id_product_modifier'] = $id_product_modifier;
                                $dataProductMod['id_outlet'] = $checkOutlet['id_outlet'];
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
                    }
                    if (!$createProduct) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transaction product sync failed']]);
                    }
                }

                $pointBefore = 0;
                $pointValue = 0;

                if (isset($trx['member_uid'])) {
                    //insert voucher
                    $idDealVouUsed = [];
                    if (!empty($trx['voucher'])) {
                        foreach ($trx['voucher'] as $keyV => $valueV) {
                            $checkUsed = DealsVoucher::where('voucher_code', $valueV['voucher_code'])->with(['deals_user', 'deal'])->first();
                            if (empty($checkUsed)) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' not found']
                                ]);
                            }

                            foreach ($checkUsed['deals_user'] as $valueDealUser) {
                                //cek voucher user
                                if ($valueDealUser->id_user != $user['id']) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' not valid']
                                    ]);
                                }

                                //cek voucher sudah di invalidate
                                if ($valueDealUser->redeemed_at == null) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' not valid']
                                    ]);
                                }

                                //cek voucher outlet
                                if ($valueDealUser->id_outlet !=  $checkOutlet['id_outlet']) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' cannot be used at this store.']
                                    ]);
                                }
                            }

                            $checkVoucherUsed = TransactionVoucher::whereNotIn('id_transaction', [$createTrx->id_transaction])->where('id_deals_voucher', $checkUsed['id_deals_voucher'])->first();
                            if ($checkVoucherUsed) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' has been used']
                                ]);
                            }

                            //create transaction voucher
                            $trxVoucher['id_transaction'] = $createTrx->id_transaction;
                            $trxVoucher['id_deals_voucher'] =  $checkUsed['id_deals_voucher'];

                            $insertTrxVoucher = TransactionVoucher::updateOrCreate(['id_transaction' => $createTrx->id_transaction, 'id_deals_voucher' => $checkUsed['id_deals_voucher']], $trxVoucher);
                            if (!$insertTrxVoucher) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Create voucher transaction failed']
                                ]);
                            }

                            $idDealVouUsed[] = $insertTrxVoucher->id_deals_voucher;

                            // 			foreach ($checkUsed['deals_user'] as $keyU => $valueU) {
                            // 				$valueU->used_at = $createTrx->transaction_date;
                            // 				$valueU->update();
                            // 				if (!$valueU) {
                            // 					DB::rollback();
                            // 					return response()->json([
                            // 						'status'    => 'fail',
                            // 						'messages'  => ['Voucher '.$valueV['voucher_code'].' not valid']
                            // 					]);
                            // 				}
                            // 			}

                            $checkUsed['deal']->deals_total_used =  $checkUsed['deal']->deals_total_used + 1;
                            $checkUsed['deal']->update();
                            if (!$checkUsed['deal']) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Voucher ' . $valueV['voucher_code'] . ' not valid']
                                ]);
                            }
                        }
                    }

                    //delete voucher not used if transction update
                    $trxVouNotUsed = TransactionVoucher::where('id_transaction', $createTrx->id_transaction)->whereNotIn('id_deals_voucher', $idDealVouUsed)->get();
                    if (count($trxVouNotUsed) > 0) {
                        foreach ($trxVouNotUsed as $notUsed) {

                            $notUsed['deals_voucher']['deals_user'][0]->used_at = null;
                            $notUsed['deals_voucher']['deals_user'][0]->save();
                            if (!$notUsed['deals_voucher']['deals_user'][0]) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Update voucher transaction failed']
                                ]);
                            }

                            $notUsed['deals_voucher']['deal']->deals_total_used = $notUsed['deals_voucher']['deal']->deals_total_used - 1;
                            $notUsed['deals_voucher']['deal']->save();
                            if (!$notUsed['deals_voucher']['deal']) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Update voucher transaction failed']
                                ]);
                            }
                        }

                        $delTrxVou = TransactionVoucher::where('id_transaction', $createTrx->id_transaction)->whereNotIn('id_deals_voucher', $idDealVouUsed)->delete();
                        if (!$delTrxVou) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Update voucher transaction failed']
                            ]);
                        }
                    }

                    if ($createTrx['transaction_payment_status'] == 'Completed') {
                        //get last point 
                        $pointBefore = LogBalance::where('id_user', $user['id'])->whereNotIn('id_log_balance', function ($q) use ($createTrx) {
                            $q->from('log_balances')
                                ->where('source', 'Transaction')
                                ->where('id_reference', $createTrx->id_transaction)
                                ->select('id_log_balance');
                        })->sum('balance');

                        //cek jika menggunakan voucher tidak dapat point / cashback
                        if (count($idDealVouUsed) > 0) {
                            $point = null;
                            $cashback = null;

                            //delete log point
                            $delLog = LogPoint::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->get();
                            if (count($delLog) > 0) {
                                $delLog = LogPoint::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->delete();
                                if (!$delLog) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Update point failed']
                                    ]);
                                }
                            }

                            //delete log balance
                            $delLog = LogBalance::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->get();
                            if (count($delLog) > 0) {
                                $delLog = LogBalance::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->delete();
                                if (!$delLog) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Update cashback failed']
                                    ]);
                                }
                            }
                        } else {
                            if ($statusGet == 1) {
                                if (!empty($user['memberships'][0]['membership_name'])) {
                                    $level = $paymentUse->special_membership_name;
                                    $percentageP = $paymentUse->benefit_point_multiplier / 100;
                                    $percentageB = $paymentUse->benefit_cashback_multiplier / 100;
                                    $cashMax = $paymentUse->cashback_maximum;
                                } else {
                                    $level = null;
                                    $percentageP = 1;
                                    $percentageB = 1;

                                    $getSet = Setting::where('key', 'cashback_maximum')->first();
                                    if ($getSet) {
                                        $cashMax = (int) $getSet->value;
                                    }
                                }

                                $point = floor($this->count('point', $trx) * $percentageP);

                                $datatrx = $trx;
                                $datatrx['total'] = $trx['grand_total'];
                                $cashback = floor($this->count('cashback', $datatrx) * $percentageB);

                                if (isset($cashMax) && $cashback > $cashMax) {
                                    $cashback = $cashMax;
                                }

                                //update point & cashback earned
                                $configPoint = Configs::where('config_name', 'point')->first();
                                if ($configPoint && isset($configPoint['is_active']) && $configPoint['is_active'] == '1') {
                                    $createTrx->transaction_point_earned = $point;
                                } else {
                                    $createTrx->transaction_point_earned = null;
                                }
                                $configBalance = Configs::where('config_name', 'balance')->first();
                                if ($configBalance && isset($configBalance['is_active']) && $configBalance['is_active'] == '1') {
                                    $createTrx->transaction_cashback_earned = $cashback;
                                } else {
                                    $createTrx->transaction_cashback_earned = null;
                                }

                                $createTrx->update();
                                if (!$createTrx) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Point Failed']
                                    ]);
                                }

                                if ($createTrx['transaction_point_earned']) {
                                    $settingPoint = Setting::where('key', 'point_conversion_value')->first();

                                    $dataLog = [
                                        'id_user'                     => $createTrx['id_user'],
                                        'point'                       => $createTrx['transaction_point_earned'],
                                        'id_reference'                => $createTrx['id_transaction'],
                                        'source'                      => 'Transaction',
                                        'grand_total'                 => $createTrx['transaction_grandtotal'],
                                        'point_conversion'            => $settingPoint['value'],
                                        'membership_level'            => $level,
                                        'membership_point_percentage' => $percentageP * 100
                                    ];

                                    $insertDataLog = LogPoint::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLog);
                                    if (!$insertDataLog) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Point Failed']
                                        ]);
                                    }

                                    $pointValue = $insertDataLog->point;

                                    //update user point
                                    $user->points = $pointBefore + $pointValue;
                                    $user->update();
                                    if (!$user) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Point Failed']
                                        ]);
                                    }
                                }

                                if ($createTrx['transaction_cashback_earned']) {

                                    $insertDataLogCash = app($this->balance)->addLogBalance($createTrx['id_user'], $createTrx['transaction_cashback_earned'], $createTrx['id_transaction'], 'Transaction', $createTrx['transaction_grandtotal']);
                                    if (!$insertDataLogCash) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Cashback Failed']
                                        ]);
                                    }

                                    $pointValue = $insertDataLogCash->balance;
                                }

                                $createTrx->special_memberships = 1;
                                $createTrx->save();
                                if (!$createTrx) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Transaction sync failed']
                                    ]);
                                }
                            } else {
                                if (!empty($user['memberships'][0]['membership_name'])) {
                                    $level = $user['memberships'][0]['membership_name'];
                                    $percentageP = $user['memberships'][0]['benefit_point_multiplier'] / 100;
                                    $percentageB = $user['memberships'][0]['benefit_cashback_multiplier'] / 100;
                                    $cashMax = $user['memberships'][0]['cashback_maximum'];
                                } else {
                                    $level = null;
                                    $percentageP = 0;
                                    $percentageB = 0;

                                    $getSet = Setting::where('key', 'cashback_maximum')->first();
                                    if ($getSet) {
                                        $cashMax = (int) $getSet->value;
                                    }
                                }

                                $point = floor($this->count('point', $trx) * $percentageP);

                                $datatrx = $trx;
                                $datatrx['total'] = $trx['grand_total'];
                                $cashback = floor($this->count('cashback', $datatrx) * $percentageB);

                                //count some trx user
                                $countUserTrx = Transaction::where('id_user', $user['id'])->count();

                                $countSettingCashback = TransactionSetting::get();

                                // return $countSettingCashback;
                                if ($countUserTrx < count($countSettingCashback)) {
                                    // return $countUserTrx;
                                    $cashback = $cashback * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

                                    if ($cashback > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                                        $cashback = $countSettingCashback[$countUserTrx]['cashback_maximum'];
                                    }
                                } else {
                                    if (isset($cashMax) && $cashback > $cashMax) {
                                        $cashback = $cashMax;
                                    }
                                }

                                //update point & cashback earned
                                $configPoint = Configs::where('config_name', 'point')->first();
                                if ($configPoint && isset($configPoint['is_active']) && $configPoint['is_active'] == '1') {
                                    $createTrx->transaction_point_earned = $point;
                                } else {
                                    $createTrx->transaction_point_earned = null;
                                }
                                $configBalance = Configs::where('config_name', 'balance')->first();
                                if ($configBalance && isset($configBalance['is_active']) && $configBalance['is_active'] == '1') {
                                    $createTrx->transaction_cashback_earned = $cashback;
                                } else {
                                    $createTrx->transaction_cashback_earned = null;
                                }
                                $createTrx->update();
                                if (!$createTrx) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Point Failed']
                                    ]);
                                }

                                if ($createTrx['transaction_point_earned']) {
                                    $settingPoint = Setting::where('key', 'point_conversion_value')->first();

                                    $dataLog = [
                                        'id_user'                     => $createTrx['id_user'],
                                        'point'                       => $createTrx['transaction_point_earned'],
                                        'id_reference'                => $createTrx['id_transaction'],
                                        'source'                      => 'Transaction',
                                        'grand_total'                 => $createTrx['transaction_grandtotal'],
                                        'point_conversion'            => $settingPoint['value'],
                                        'membership_level'            => $level,
                                        'membership_point_percentage' => $percentageP * 100
                                    ];

                                    $insertDataLog = LogPoint::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLog);
                                    if (!$insertDataLog) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Point Failed']
                                        ]);
                                    }

                                    $pointValue = $insertDataLog->point;

                                    //update user point
                                    $user->points = $pointBefore + $pointValue;
                                    $user->update();
                                    if (!$user) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Point Failed']
                                        ]);
                                    }
                                }

                                if ($createTrx['transaction_cashback_earned']) {

                                    $insertDataLogCash = app($this->balance)->addLogBalance($createTrx['id_user'], $createTrx['transaction_cashback_earned'], $createTrx['id_transaction'], 'Transaction', $createTrx['transaction_grandtotal']);
                                    if (!$insertDataLogCash) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Cashback Failed']
                                        ]);
                                    }

                                    $pointValue = $insertDataLogCash->balance;
                                }

                                if (isset($user['phone'])) {
                                    $checkMembership = app($this->membership)->calculateMembership($user['phone']);

                                    //update count transaction
                                    if (date('Y-m-d', strtotime($createTrx['transaction_date'])) == date('Y-m-d')) {
                                        $updateCountTrx = User::where('id', $user['id'])->update([
                                            'count_transaction_day' => $user['count_transaction_day'] + 1,
                                        ]);

                                        if (!$updateCountTrx) {
                                            DB::rollback();
                                            return response()->json([
                                                'status'    => 'fail',
                                                'messages'  => ['Update User Count Transaction Failed']
                                            ]);
                                        }

                                        $userData = User::find($user['id']);

                                        //cek fraud detection transaction per day
                                        $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->first();
                                        if ($fraudTrxDay && $fraudTrxDay['parameter_detail'] != null) {
                                            if ($userData['count_transaction_day'] >= $fraudTrxDay['parameter_detail']) {
                                                //send fraud detection to admin
                                                $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraudTrxDay['id_fraud_setting'], $userData, $createTrx['id_transaction'], null);
                                            }
                                        }
                                    }

                                    if (date('Y-m-d', strtotime($createTrx['transaction_date'])) >= date('Y-m-d', strtotime(' - 6 days')) && date('Y-m-d', strtotime($createTrx['transaction_date'])) <= date('Y-m-d')) {
                                        $updateCountTrx = User::where('id', $user['id'])->update([
                                            'count_transaction_week' => $user['count_transaction_week'] + 1,
                                        ]);

                                        if (!$updateCountTrx) {
                                            DB::rollback();
                                            return response()->json([
                                                'status'    => 'fail',
                                                'messages'  => ['Update User Count Transaction Failed']
                                            ]);
                                        }

                                        //cek fraud detection transaction per week (last 7 days)
                                        $userData = User::find($user['id']);

                                        $fraudTrx = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->first();
                                        if ($fraudTrx && $fraudTrx['parameter_detail'] != null) {
                                            if ($userData['count_transaction_week'] >= $fraudTrx['parameter_detail']) {
                                                //send fraud detection to admin
                                                $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraudTrx['id_fraud_setting'], $userData, $createTrx['id_transaction'], $lastDeviceId = null);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $r = [
                    'id_transaction'    => $createTrx->id_transaction,
                    'point_before'      => (int) $pointBefore,
                    'point_after'       => $pointBefore + $pointValue,
                    'point_value'       => $pointValue
                ];
                array_push($result, $r);
            }
        }
        DB::commit();
        return response()->json([
            'status'    => 'success',
            'result'    => $result
        ]);
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
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        //if use voucher, cannot refund
        $trxVou = TransactionVoucher::where('id_transaction', $checkTrx->id_transaction)->first();
        if ($trxVou) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction cannot be refund. This transaction use voucher']]);
        }

        if ($checkTrx->id_user) {
            $user = User::where('id', $checkTrx->id_user)->first();
            if (empty($user)) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
            }
        }

        $checkTrx->transaction_payment_status = 'Cancelled';
        $checkTrx->void_date = date('Y-m-d H:i:s');
        $checkTrx->transaction_notes = $post['reason'];
        $checkTrx->update();
        if (!$checkTrx) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed1']]);
        }

        $user = User::where('id', $checkTrx->id_user)->first();
        if ($user) {
            $point = LogPoint::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
            if (!empty($point)) {
                $point->delete();
                if (!$point) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed2']]);
                }

                //update user point
                $sumPoint = LogPoint::where('id_user', $user['id'])->sum('point');
                $user->points = $sumPoint;
                $user->update();
                if (!$user) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Update point failed']
                    ]);
                }
            }

            $balance = LogBalance::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
            if (!empty($balance)) {
                $balance->delete();
                if (!$balance) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed3']]);
                }

                //update user balance
                $sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
                $user->balance = $sumBalance;
                $user->update();
                if (!$user) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Update cashback failed']
                    ]);
                }
            }
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
        }

        DB::commit();

        return response()->json(['status' => 'success']);
    }

    function checkApi($key, $secret)
    {
        $api_key = Setting::where('key', 'api_key')->first();
        if (empty($api_key)) {
            return ['status' => 'fail', 'messages' => ['api_key not found']];
        }

        $api_key = $api_key['value'];
        if ($api_key != $key) {
            return ['status' => 'fail', 'messages' => ['api_key isn\’t match']];
        }

        $api_secret = Setting::where('key', 'api_secret')->first();
        if (empty($api_secret)) {
            return ['status' => 'fail', 'messages' => ['api_secret not found']];
        }

        $api_secret = $api_secret['value'];
        if ($api_secret != $secret) {
            return ['status' => 'fail', 'messages' => ['api_secret isn\’t match']];
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
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]);
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

    public function syncOutletMenu(reqBulkMenu $request)
    {
        $post = $request->json()->all();

        $syncDatetime = date('d F Y h:i');

        $apikey = Setting::where('key', 'api_key')->first()->value;
        $apisecret = Setting::where('key', 'api_secret')->first()->value;
        if ($post['api_key'] != $apikey) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Api key doesn\'t match.']
            ]);
        }
        if ($post['api_secret'] != $apisecret) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Api secret doesn\'t match.']
            ]);
        }

        DB::beginTransaction();
        $hasil = [];
        $isReject = false;
        foreach ($post['store'] as $dataoutlet) {
            $outlet = Outlet::where('outlet_code', strtoupper($dataoutlet['store_code']))->first();
            //update
            if ($outlet) {
                if (isset($dataoutlet['store_name'])) {
                    $outlet->outlet_name = $dataoutlet['store_name'];
                }
                if (isset($dataoutlet['store_address'])) {
                    $outlet->outlet_address = $dataoutlet['store_address'];
                }
                if (isset($dataoutlet['store_phone'])) {
                    $outlet->outlet_phone = $dataoutlet['store_phone'];
                }
                $outlet->save();
            }
            //insert
            else {
                $dataInsert['outlet_code'] = strtoupper($dataoutlet['store_code']);
                if (!isset($dataoutlet['store_name'])) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Store with code ' . $dataoutlet['store_code'] . ' not found.', 'Add store_name to create a new data store.']
                    ]);
                }
                $dataInsert['outlet_name'] = $dataoutlet['store_name'];
                if (isset($dataoutlet['store_address'])) {
                    $outlet->outlet_address = $dataoutlet['store_address'];
                }
                if (isset($dataoutlet['store_phone'])) {
                    $outlet->outlet_phone = $dataoutlet['store_phone'];
                }
                $dataInsert['outlet_status'] = 'Inactive';
                $outlet = Outlet::create($dataInsert);
            }

            if (!$outlet) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to sync']
                ]);
            }

            if ($outlet) {
                $countInsert = 0;
                $countUpdate = 0;
                $rejectedProduct = [];
                $updatedProduct = [];
                $insertedProduct = [];

                $idProduct = [];
                foreach ($dataoutlet['menu'] as $key => $menu) {
                    $product = Product::where('product_code', $menu['plu_id'])->first();
                    // return response()->json($menu);
                    // update product
                    if ($product) {
                        // cek allow sync, jika 0 product tidak di update
                        if ($product->product_allow_sync == '1') {

                            // cek name pos, jika beda product tidak di update
                            if (empty($product->product_name_pos) || $product->product_name_pos == $menu['name']) {
                                $update = $product->update(['product_name_pos' => $menu['name']]);
                                if ($update) {
                                    // update modifiers 
                                    if (isset($menu['modifiers'])) {
                                        if (!empty($menu['modifiers'])) {
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

                                    $updateProductPrice = ProductPrice::updateOrCreate([
                                        'id_product' => $product->id_product,
                                        'id_outlet'  => $outlet->id_outlet
                                    ], $dataProductPrice);

                                    if (!$updateProductPrice) {
                                        DB::rollBack();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Something went wrong.']
                                        ]);
                                    } else {

                                        //upload photo
                                        $imageUpload = [];
                                        if (isset($menu['photo'])) {
                                            foreach ($menu['photo'] as $photo) {
                                                $image = file_get_contents($photo['url']);
                                                $img = base64_encode($image);
                                                if (!file_exists('img/product/item/')) {
                                                    mkdir('img/product/item/', 0777, true);
                                                }

                                                $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);

                                                if (isset($upload['status']) && $upload['status'] == "success") {
                                                    $orderPhoto = ProductPhoto::where('id_product', $product->id_product)->orderBy('product_photo_order', 'desc')->first();
                                                    if ($orderPhoto) {
                                                        $orderPhoto = $orderPhoto->product_photo_order + 1;
                                                    } else {
                                                        $orderPhoto = 1;
                                                    }
                                                    $dataPhoto['id_product'] = $product->id_product;
                                                    $dataPhoto['product_photo'] = $upload['path'];
                                                    $dataPhoto['product_photo_order'] = $orderPhoto;

                                                    $photo = ProductPhoto::create($dataPhoto);
                                                    if (!$photo) {
                                                        DB::rollBack();
                                                        $result = [
                                                            'status'   => 'fail',
                                                            'messages' => ['fail upload image']
                                                        ];

                                                        return response()->json($result);
                                                    }

                                                    //add in array photo
                                                    $imageUpload[] = $photo['product_photo'];
                                                } else {
                                                    DB::rollBack();
                                                    $result = [
                                                        'status'   => 'fail',
                                                        'messages' => ['fail upload image']
                                                    ];

                                                    return response()->json($result);
                                                }
                                            }
                                        }

                                        $countUpdate++;

                                        // list updated product utk data log
                                        $newProductPrice = ProductPrice::where('id_product', $product->id_product)->where('id_outlet', $outlet->id_outlet)->first();
                                        $newUpdatedAt =  $newProductPrice->updated_at;

                                        $updateProd['id_product'] = $product['id_product'];
                                        $updateProd['plu_id'] = $product['product_code'];
                                        $updateProd['product_name'] = $product['product_name'];
                                        $updateProd['old_price'] = $oldPrice;
                                        $updateProd['new_price'] = (int) $menu['price'];
                                        $updateProd['old_updated_at'] = $oldUpdatedAt;
                                        $updateProd['new_updated_at'] = $newUpdatedAt;
                                        if (count($imageUpload) > 0) {
                                            $updateProd['new_photo'] = $imageUpload;
                                        }

                                        $updatedProduct[] = $updateProd;
                                    }
                                } else {
                                    DB::rollBack();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  =>  ['Something went wrong.']
                                    ]);
                                }
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
                                $isReject = true;
                            }
                        }
                        array_push($idProduct, $product->id_product);
                        // $idProduct[] = $product->id_product; 
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

                            $updateProductPrice = ProductPrice::updateOrCreate([
                                'id_product' => $create->id_product,
                                'id_outlet'  => $outlet->id_outlet
                            ], $dataProductPrice);

                            if (!$updateProductPrice) {
                                DB::rollBack();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Something went wrong.']
                                ]);
                            } else {

                                //upload photo
                                $imageUpload = [];
                                if (isset($menu['photo'])) {
                                    foreach ($menu['photo'] as $photo) {
                                        $image = file_get_contents($photo['url']);
                                        $img = base64_encode($image);
                                        if (!file_exists('img/product/item/')) {
                                            mkdir('img/product/item/', 0777, true);
                                        }

                                        $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);

                                        if (isset($upload['status']) && $upload['status'] == "success") {
                                            $dataPhoto['id_product'] = $product->id_product;
                                            $dataPhoto['product_photo'] = $upload['path'];
                                            $dataPhoto['product_photo_order'] = 1;

                                            $photo = ProductPhoto::create($dataPhoto);
                                            if (!$photo) {
                                                DB::rollBack();
                                                $result = [
                                                    'status'   => 'fail',
                                                    'messages' => ['fail upload image']
                                                ];

                                                return response()->json($result);
                                            }

                                            //add in array photo
                                            $imageUpload[] = $photo['product_photo'];
                                        } else {
                                            DB::rollBack();
                                            $result = [
                                                'status'   => 'fail',
                                                'messages' => ['fail upload image']
                                            ];

                                            return response()->json($result);
                                        }
                                    }
                                }

                                $countInsert++;

                                // list new product utk data log
                                $insertProd['id_product'] = $create['id_product'];
                                $insertProd['plu_id'] = $create['product_code'];
                                $insertProd['product_name'] = $create['product_name'];
                                $insertProd['price'] = (int) $menu['price'];
                                if (count($imageUpload) > 0) {
                                    $updateProd['new_photo'] = $imageUpload;
                                }

                                $insertedProduct[] = $insertProd;
                            }

                            array_push($idProduct, $create->id_product);
                            // $idProduct = $create->id_product;
                        }
                    }
                }

                //update inactive
                $inactive = ProductPrice::where('id_outlet', $outlet->id_outlet)->whereNotIn('id_product', $idProduct)->update(['product_status' => 'Inactive']);
                $hasil[] = [
                    "outlet"  => [
                        "id_outlet" => $outlet->id_outlet,
                        "outlet_code" => $outlet->outlet_code,
                        "outlet_name" => $outlet->outlet_name
                    ],
                    "new_product" => [
                        "total" => $countInsert,
                        "list_product" => $insertedProduct
                    ],
                    "updated_product" => [
                        "total" => $countUpdate,
                        "list_product" => $updatedProduct
                    ],
                    "rejected_product" => [
                        "total" => count($rejectedProduct),
                        "list_product" => $rejectedProduct,
                    ],
                    "inactive_product" => $inactive
                ];
            } else {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['store_code ' . $dataoutlet['store_code'] . ' isn\'t match']
                ]);
            }
        }

        DB::commit();

        // send email rejected product
        if ($isReject == true) {

            $emailSync = Setting::where('key', 'email_sync_menu')->first();
            if (!empty($emailSync) && $emailSync->value != null) {
                $emailSync = explode(',', $emailSync->value);
                foreach ($emailSync as $key => $to) {

                    $subject = 'Rejected product from outlet menu sync raptor';

                    $content['sync_datetime'] = $syncDatetime;
                    $content['data'] = $hasil;

                    // get setting email
                    $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                    $setting = array();
                    foreach ($getSetting as $key => $value) {
                        $setting[$value['key']] = $value['value'];
                    }

                    $data = array(
                        'content' => $content,
                        'setting' => $setting
                    );

                    Mailgun::send('pos::email_sync_outlet_menu', $data, function ($message) use ($to, $subject, $setting) {
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

        return response()->json([
            'status'    => 'success',
            'result'  => $hasil,
        ]);
    }

    public function syncOutletMenuReturn(reqBulkMenu $request)
    {
        // call function syncMenu
        $url = env('API_URL') . 'api/v1/pos/outlet/menu/sync';
        $syncMenu = MyHelper::post($url, MyHelper::getBearerToken(), $request->json()->all());

        // return sesuai api raptor
        if (isset($syncMenu['status']) && $syncMenu['status'] == 'success') {
            $hasil = [];
            foreach ($syncMenu['result'] as $result) {
                $hasil[] = [
                    "store_code" => $result['outlet']['outlet_code'],
                    "inserted" => $result['new_product']['total'],
                    "updated" => $result['updated_product']['total'],
                    "rejected" => $result['rejected_product']['total']
                ];
            }
            return response()->json([
                'status'    => 'success',
                'result'  => $hasil
            ]);
        }
        return $syncMenu;
    }
}
