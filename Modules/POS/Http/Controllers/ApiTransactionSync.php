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
use App\Http\Models\TransactionDuplicate;
use App\Http\Models\TransactionDuplicatePayment;
use App\Http\Models\TransactionDuplicateProduct;
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
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\LogBackendError;
use App\Http\Models\SyncTransactionFaileds;
use App\Http\Models\SyncTransactionQueues;
use App\Lib\MyHelper;
use App\Lib\MailQueue as Mail;

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
use Exception;

use DB;
use DateTime;

class ApiTransactionSync extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->balance    = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";

        $this->pos = "Modules\POS\Http\Controllers\ApiPos";
    }

    public function transaction(){
        $log = MyHelper::logCron('Sync Transaction');
        try {
            $x = 10;
            $getDataQueue = SyncTransactionQueues::Orderby('created_at', 'asc')->limit($x)->get()->toArray();

            foreach($getDataQueue as $key => $trans){
                $checkOutlet = Outlet::where('outlet_code', $trans['outlet_code'])->first();

                $config['point']    = Configs::where('config_name', 'point')->first()->is_active;
                $config['balance']  = Configs::where('config_name', 'balance')->first()->is_active;
                $settingPoint       = Setting::where('key', 'point_conversion_value')->first()->value;
                $dataTrans = json_decode($trans['request_transaction']);
                $countDataTrans = count($dataTrans);
                $checkSuccess = 0;
                $checkDuplicate = 0;

                $receipt = array_column($dataTrans, 'trx_id');
                $checkReceipt = Transaction::select('transaction_receipt_number', 'id_transaction')->where('id_outlet', $checkOutlet['id_outlet'])
                                    ->whereIn('transaction_receipt_number', $receipt)
                                    ->where('trasaction_type', 'Offline')
                                    ->get();
                $convertTranscToArray = $checkReceipt->toArray();
                $receiptExist = $checkReceipt->pluck('transaction_receipt_number')->toArray();

                $validReceipt = array_diff($receipt,$receiptExist);

                $invalidReceipt = array_intersect($receipt,$receiptExist);
                foreach($invalidReceipt as $key => $invalid){
                    $checkDuplicate++;
                    unset($dataTrans[$key]);
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
                        $checkDuplicate++;
                        unset($dataTrans[$key]);
                    }else{
                        $duplicate = $this->processDuplicate($dataTrans[$key], $checkOutlet);
                        if(isset($duplicate['status']) && $duplicate['status'] == 'duplicate'){
                            $checkDuplicate++;
                            $data = [
                                'trx' => $duplicate['trx'],
                                'duplicate' =>$duplicate['duplicate']
                            ];
                            $contentDuplicate[] = $data;
                            unset($dataTrans[$key]);
                        }
                    }
                }

                $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->where('fraud_settings_status','Active')->first();
                $fraudTrxWeek = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->where('fraud_settings_status','Active')->first();
                $countSettingCashback = TransactionSetting::get();
                foreach ($dataTrans as $key => $trx) {
                    if(!empty($trx->date_time) &&
                        isset($trx->total) && isset($trx->service) &&
                        isset($trx->tax) && isset($trx->discount) && isset($trx->grand_total) &&
                        isset($trx->menu)){

                        $insertTrx = $this->insertTransaction($checkOutlet, $trx, $config, $settingPoint, $countSettingCashback, $fraudTrxDay, $fraudTrxWeek);
                        if(isset($insertTrx['id_transaction'])){
                                $checkSuccess++;
                                $result[] = $insertTrx;

                        }else{
                            $data = [
                                'outlet_code' => $trans['outlet_code'],
                                'request' => json_encode($trx),
                                'message_failed' => $insertTrx['messages'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            SyncTransactionFaileds::create($data);
                        }
                    }else{
                        if(isset($trx->trx_id)){
                            $id = $trx->trx_id;
                        }else{
                            $id = 'trx_id does not exist';
                        }
                        $data = [
                            'outlet_code' => $trans['outlet_code'],
                            'request' => json_encode($trx),
                            'message_failed' => 'There is an incomplete input in the transaction list',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        SyncTransactionFaileds::create($data);
                    }
                }

                $newTranAndDuplicate = $checkSuccess + $checkDuplicate;
                if($countDataTrans == $checkSuccess || $countDataTrans == $checkDuplicate ||
                    $countDataTrans == $newTranAndDuplicate){
                    SyncTransactionQueues::where('id_sync_transaction_queues', $trans['id_sync_transaction_queues'])->delete();
                }
            }
            $log->success();
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    function insertTransaction($outlet, $trx, $config, $settingPoint, $countSettingCashback, $fraudTrxDay, $fraudTrxWeek){
        $trx = (array)$trx;
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
                            return ['status' => 'fail', 'messages' => ['User not found']];
                        }elseif(isset($user['is_suspended']) && $user['is_suspended'] == '1'){
                            $user['id'] = null;
                            $dataTrx['membership_level']    = null;
                            $dataTrx['membership_promo_id'] = null;
                        }else{
                            if (count($user['memberships']) > 0) {
                                $dataTrx['membership_level']    = $user['memberships'][0]['membership_name'];
                                $dataTrx['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
                            }

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

                            //using voucher
                            if (!empty($trx['voucher'])) {
                                foreach ($trx['voucher'] as $keyV => $valueV) {
                                    $checkVoucher = DealsVoucher::join('deals_users', 'deals_voucher.id_deals_voucher', 'deals_users.id_deals_voucher')
                                                                                    ->leftJoin('transaction_vouchers', 'deals_voucher.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                                                                                    ->where('voucher_code', $valueV->voucher_code)
                                                                                    ->where('deals_users.id_outlet', $outlet['id_outlet'])
                                                                                    ->where('deals_users.id_user', $user['id'])
                                                                                    ->whereNotNull('deals_users.used_at')
                                                                                    ->whereNull('transaction_vouchers.id_transaction_voucher')
                                                                                    ->first();
                                    if (empty($checkVoucher)) {
                                            // for invalid voucher
                                            $dataVoucher['deals_voucher_invalid'] = $valueV->voucher_code;
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
                    }

                    $dataTrx['id_user'] = $user['id'];
                    if(isset($qr['device'])){
                        $dataTrx['transaction_device_type'] = $qr['device'];
                    }
                    if(isset($trx['cashier'])){
                        $dataTrx['transaction_cashier'] = $trx['cashier'];
                    }

                    $createTrx = Transaction::create($dataTrx);
                    if (!$createTrx) {
                        DB::rollback();
                        return ['status' => 'fail', 'messages' => ['Transaction sync failed']];
                    }

                    $dataPayments = [];
                    if(!empty($trx['payments'])){
                        foreach ($trx['payments'] as $col => $pay) {
                            $pay = (array)$pay;
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
                                return ['status' => 'fail', 'messages' => ['There is an incomplete input in the payment list']];
                            }
                        }
                    }else{
                        $dataPayments = [
                            'id_transaction' => $createTrx['id_transaction'],
                            'payment_type'   => 'offline',
                            'payment_bank'   => 'null',
                            'payment_amount' => 10000,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    $insertPayments = TransactionPaymentOffline::insert($dataPayments);
                    if (!$insertPayments) {
                        DB::rollback();
                        return ['status' => 'fail', 'messages' => ['Failed insert transaction payments']];
                    }

                    $userTrxProduct = [];
                    $allMenuId = array_column($trx['menu'], 'plu_id');
                    $checkProduct = Product::select('id_product', 'product_code')->whereIn('product_code', $allMenuId)->get()->toArray();

                    $allProductCode = array_column($checkProduct, 'product_code');
                    foreach ($trx['menu'] as $row => $menu) {
                        $menu = (array)$menu;
                        if(!empty($menu['plu_id']) && !empty($menu['name'])
                            && isset($menu['price']) && isset($menu['qty']) ){

                            $getIndexProduct = array_search($menu['plu_id'], $allProductCode);

                            if($getIndexProduct === false){
                                //create new product
                                $dataProduct['product_code']      = $menu['plu_id'];
                                $dataProduct['product_name']      = $menu['name'];
                                $dataProduct['product_name_pos'] = $menu['name'];

                                $newProduct = Product::create($dataProduct);
                                if (!$newProduct) {
                                    DB::rollback();
                                    return ['status' => 'fail', 'messages' => ['Failed create new product']];
                                }

                                $productPriceData['id_product']         = $newProduct['id_product'];
                                $productPriceData['id_outlet']             = $outlet['id_outlet'];
                                $productPriceData['product_price_base'] = $menu['price'];
                                $newProductPrice = ProductPrice::create($productPriceData);
                                if (!$newProductPrice) {
                                    DB::rollback();
                                    return ['status' => 'fail', 'messages' => ['Failed create new product']];
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
                                    $getIndexMod = array_search($mod->code, $allMenuModifier);

                                    if ($getIndexMod !== false) {
                                        $id_product_modifier = $detailMod[$getIndexMod]['id_product_modifier'];
                                        $type = $detailMod[$getIndexMod]['type'];
                                        $text = $detailMod[$getIndexMod]['text'];
                                    } else {
                                        if (isset($mod->text)) {
                                            $text = $mod->text;
                                        } else {
                                            $text = null;
                                        }
                                        if (isset($mod->type)) {
                                            $type = $mod->type;
                                        } else {
                                            $type = "";
                                        }
                                        $newModifier = ProductModifier::create([
                                            'id_product' => $product['id_product'],
                                            'type' => $type,
                                            'code' => $mod->code,
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
                                    $dataProductMod['code'] = $mod->code;
                                    $dataProductMod['text'] = $text;
                                    $dataProductMod['qty'] = $menu['qty'];
                                    $dataProductMod['datetime'] = $createTrx['created_at'];
                                    $dataProductMod['trx_type'] = $createTrx['trasaction_type'];
                                    $dataProductMod['sales_type'] = $createTrx['sales_type'];

                                    $updateProductMod = TransactionProductModifier::updateOrCreate([
                                        'id_transaction' => $createTrx['id_transaction'],
                                        'code'  => $mod->code
                                    ], $dataProductMod);
                                }
                            }
                            if (!$createProduct) {
                                DB::rollback();
                                return ['status' => 'fail', 'messages' => ['Transaction product sync failed']];
                            }
                        }else{
                            DB::rollback();
                            return['status' => 'fail', 'messages' => ['There is an incomplete input in the menu list']];
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
                                    'messages'  => ['Insert Point Failed']
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
                                    'messages'  => ['Insert Point Failed']
                                ];
                            }
                        }

                        if ($createTrx['transaction_cashback_earned']) {

                            $insertDataLogCash = app($this->balance)->addLogBalance($createTrx['id_user'], $createTrx['transaction_cashback_earned'], $createTrx['id_transaction'], 'Offline Transaction', $createTrx['transaction_grandtotal']);
                            if (!$insertDataLogCash) {
                                DB::rollback();
                                return [
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Cashback Failed']
                                ];
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
                            return [
                                'status' => 'fail',
                                'messages' => ['Failed update Point and Cashback']
                            ];
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
                    return ['status' => 'fail', 'messages' => ['trx_id does not exist']];
                }
            }
        }catch (Exception $e) {
            DB::rollback();
            return ['status' => 'fail', 'messages' => $e];
        }
    }

    function processDuplicate($trx, $outlet){
        $trx = (array)$trx;
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
                    $menu = (array)$menu;
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
                            $pay = (array)$pay;
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

}
