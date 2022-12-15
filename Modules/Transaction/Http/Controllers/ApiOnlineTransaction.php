<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use App\Http\Models\ProductPhoto;
use App\Http\Models\Subdistricts;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\TransactionConsultationRecomendation;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentOvo;
use App\Jobs\DisburseJob;
use App\Jobs\FraudJob;
use App\Lib\Ovo;
use App\Lib\Shipper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductCategory;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandProduct;
use App\Http\Models\ProductModifier;
use App\Http\Models\User;
use App\Http\Models\UserAddress;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Merchant\Entities\Merchant;
use Modules\Product\Entities\ProductWholesaler;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariantGroupWholesaler;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionAdvanceOrder;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\UserOutlet;
use App\Http\Models\TransactionSetting;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\Configs;
use App\Http\Models\Holiday;
use App\Http\Models\OutletToken;
use App\Http\Models\UserLocationDetail;
use App\Http\Models\Deal;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\DealsUser;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\UserPromo;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Subscription\Entities\TransactionPaymentSubscription;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use Modules\Outlet\Entities\DeliveryOutlet;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request as RequestGuzzle;
use Guzzle\Http\Message\Response as ResponseGuzzle;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Transaction\Entities\TransactionProductConsultationRedeem;
use Modules\UserFeedback\Entities\UserFeedbackLog;
use DB;
use DateTime;
use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\GoSend;
use App\Lib\WeHelpYou;
use App\Lib\PushNotificationHelper;
use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;
use Modules\Transaction\Http\Requests\CheckTransaction;
use Modules\ProductVariant\Entities\ProductVariant;
use App\Http\Models\TransactionMultiplePayment;
use Modules\ProductBundling\Entities\Bundling;
use Modules\Xendit\Entities\TransactionPaymentXendit;

class ApiOnlineTransaction extends Controller
{
    public $saveImage = "img/payment/manual/";

    public function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->notif         = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo       = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->plastic       = "Modules\Plastic\Http\Controllers\PlasticController";
        $this->voucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->subscription  = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->bundling      = "Modules\ProductBundling\Http\Controllers\ApiBundlingController";
        $this->merchant = "Modules\Merchant\Http\Controllers\ApiMerchantController";
        $this->promo_trx     = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
    }

    public function newTransaction(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        $fromRecipeDoctor = 0;
        if (!empty($post['id_transaction_consultation'])) {
            $trxRecipeCheck = $this->checkTransactionRecipe($post);

            if (!empty($trxRecipeCheck['items'])) {
                $fromRecipeDoctor = 1;
            } else {
                return response()->json(['status'    => 'fail', 'messages'  => ['Invalid item consultation']]);
            }
        }

        if (empty($post['items'])) {
            return response()->json(['status'    => 'fail', 'messages'  => ['Item can not be empty']]);
        }

        $user = User::with('memberships')->where('id', $user->id)->first();
        if (empty($user)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }

        if (isset($user['is_suspended']) && $user['is_suspended'] == '1') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami']
            ]);
        }

        if (isset($user['email'])) {
            $domain = substr($user['email'], strpos($user['email'], "@") + 1);
            if (
                !filter_var($user['email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false
            ) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Alamat email anda tidak valid, silahkan gunakan alamat email yang valid.']
                ]);
            }
        }

        if (count($user['memberships']) > 0) {
            $post['membership_level']    = $user['memberships'][0]['membership_name'];
            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
        } else {
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
        }

        if (empty($post['id_user_address'])) {
            return response()->json(['status'    => 'fail', 'messages'  => ['Alamat tidak boleh kosong, silahkan pilih alamat pengiriman.']]);
        }

        $address = UserAddress::leftJoin('cities', 'cities.id_city', 'user_addresses.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('id_user', $user->id)
            ->select('user_addresses.*', 'city_name', 'provinces.id_province', 'province_name')
            ->where('id_user_address', $post['id_user_address'])->first();

        if (empty($address)) {
            return response()->json(['status'    => 'fail', 'messages'  => ['Alamat tidak ditemukan.']]);
        }

        $itemsCheck = $this->checkDataTransaction($post['items'], 1, 0, 0, $address, $fromRecipeDoctor, $user->id);
        if (!empty($itemsCheck['error_messages'])) {
            return response()->json(['status'    => 'fail', 'messages'  => [$itemsCheck['error_messages']]]);
        }

        $transactionType = 'Delivery';
        $subtotal = $itemsCheck['subtotal'];
        $grandtotal = 0;
        $deliveryTotal = $itemsCheck['total_delivery'] ?? 0;
        $currentDate = date('Y-m-d H:i:s');
        $paymentType = null;
        $transactionStatus = 'Unpaid';
        $paymentStatus = 'Pending';
        if (isset($post['point_use']) && $post['point_use']) {
            $paymentType = 'Balance';
        }

        $grandTotal = $subtotal + $deliveryTotal;
        $itemsCheck['grandtotal'] = $grandTotal;

        $itemsCheck = app($this->promo_trx)->applyPromoCheckout($itemsCheck);
        if (!empty($itemsCheck['promo_code']['is_error']) && $itemsCheck['promo_code']['is_error']) {
            return response()->json(['status'    => 'fail', 'messages'  => [$itemsCheck['promo_code']['text'] ?? 'Promo tidak bisa digunakan']]);
        }
        $items = $itemsCheck['items'];
        $service = 0;
        $tax = 0;
        foreach ($items as $index => $value) {
            $sub = $value['items_subtotal'] - ($value['discount'] ?? 0);
            $sub = ($sub < 0 ? 0 : $sub);
            $dtTaxService = ['subtotal' => $sub];

            $serviceCalculate = round(app($this->setting_trx)->countTransaction('service', $dtTaxService));
            $service = $service + $serviceCalculate;

            $taxCalculate = round(app($this->setting_trx)->countTransaction('tax', $dtTaxService));
            $tax = $tax + $taxCalculate;

            $items[$index]['service'] = $serviceCalculate;
            $items[$index]['tax'] = $taxCalculate;
        }
        $grandTotal = $itemsCheck['grandtotal'] + $service + $tax;
        $itemsCheck['grandtotal'] = $grandTotal;
        $itemsCheck['grandtotal_text'] = 'Rp ' . number_format($grandTotal, 0, ",", ".");

        DB::beginTransaction();
        UserFeedbackLog::where('id_user', $request->user()->id)->delete();
        $dataTransactionGroup = [
            'id_user' => $user->id,
            'transaction_receipt_number' => 'TRX' . time() . rand() . substr($grandtotal, 0, 5),
            'transaction_subtotal' => $subtotal,
            'transaction_shipment' => $deliveryTotal,
            'transaction_service' => $service,
            'transaction_tax' => $tax,
            'transaction_grandtotal' => $itemsCheck['grandtotal'],
            'transaction_discount' => $itemsCheck['total_discount'] ?? 0,
            'transaction_payment_status' => $paymentStatus,
            'transaction_payment_type' => $paymentType,
            'transaction_group_date' => $currentDate
        ];

        $insertTransactionGroup = TransactionGroup::create($dataTransactionGroup);
        if (!$insertTransactionGroup) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Group Failed']
            ]);
        }

        UserFeedbackLog::where('id_user', $request->user()->id)->delete();
        foreach ($items as $data) {
            $outlet = Outlet::where('id_outlet', $data['id_outlet'])->where('outlet_status', 'Active')->first();
            if (empty($outlet)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet tidak ditemukan']
                ]);
            }

            $subtotal = $subtotal + $data['items_subtotal'];
            if (empty($post['point_use']) || (isset($post['point_use']) && !$post['point_use'])) {
                $earnedPoint = $this->countTranscationPoint(['subtotal' => (int)$data['items_subtotal']], $user);
            }
            $cashback = $earnedPoint['cashback'] ?? 0;
            $receiptNumber = 'TRX-' . $outlet['outlet_code'] . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-';
            $lastReceipt = Transaction::where('transaction_receipt_number', 'like', $receiptNumber . '%')->orderBy('transaction_receipt_number', 'desc')->first()['transaction_receipt_number'] ?? '';
            $lastReceipt = explode('-', $lastReceipt)[5] ?? 0;
            $lastReceipt = (int)$lastReceipt;
            $countReciptNumber = $lastReceipt + 1;
            $receiptNumber = $receiptNumber . sprintf("%04d", $countReciptNumber);

            $discount = $data['discount'] ?? 0;
            $discountDelivery = $data['discount_delivery'] ?? 0;
            $discountAll = $discount + $discountDelivery;

            $discountItem = $discount;
            $discountBill = 0;
            if (!empty($data['discount_bill_status'])) {
                $discountItem = 0;
                $discountBill = $discount;
            }
            $sub = ($data['subtotal_promo'] ?? $data['subtotal'] ?? 0);
            $subFinal = $sub + ($data['tax'] ?? 0) + ($data['service'] ?? 0);
            $maximumDateProcess = Setting::where('key', 'transaction_maximum_date_process')->first()['value'] ?? 3;
            $transaction = [
                'id_transaction_group'        => $insertTransactionGroup['id_transaction_group'],
                'id_promo_campaign_promo_code' => $data['id_promo_campaign_promo_code'] ?? null,
                'id_outlet'                   => $data['id_outlet'],
                'id_user'                     => $user->id,
                'id_transaction_consultation' => $post['id_transaction_consultation'] ?? null,
                'transaction_date'            => $currentDate,
                'transaction_receipt_number'  => $receiptNumber,
                'transaction_status'          => $transactionStatus,
                'trasaction_type'             => $transactionType,
                'transaction_subtotal'        => $data['items_subtotal'],
                'transaction_gross'           => $data['items_subtotal'],
                'transaction_shipment'        => $data['delivery_price']['shipment_price'] ?? 0,
                'transaction_grandtotal'      => $subFinal ?? 0,
                'transaction_tax'             => $data['tax'] ?? 0,
                'transaction_service'         => $data['service'] ?? 0,
                'transaction_point_earned'    => 0,
                'transaction_cashback_earned' => $cashback,
                'trasaction_payment_type'     => $paymentType,
                'transaction_payment_status'  => $paymentStatus,
                'membership_level'            => $post['membership_level'],
                'transaction_discount'        => ($discountAll > 0 ? -$discountAll : 0),
                'transaction_discount_item'  => $discountItem,
                'transaction_discount_bill'  => $discountBill,
                'transaction_discount_delivery'  => $discountDelivery,
                'transaction_maximum_date_process' => date('Y-m-d', strtotime($currentDate . ' + ' . $maximumDateProcess . ' days'))
            ];

            $newTopupController = new NewTopupController();
            $checkHashBefore = $newTopupController->checkHash('log_balances', $user->id);
            if (!$checkHashBefore) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Your previous transaction data is invalid']
                ]);
            }

            $insertTransaction = Transaction::create($transaction);

            if (!$insertTransaction) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
            }

            foreach ($data['items'] as $keyProduct => $valueProduct) {
                $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
                if (empty($checkProduct)) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Menu tidak ditemukan']
                    ]);
                }

                $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $data['id_outlet']])->first();
                if (!empty($checkDetailProduct) && $checkDetailProduct['product_detail_stock_status'] == 'Sold Out') {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product ' . $checkProduct['product_name'] . ' tidak tersedia dan akan terhapus dari cart.']
                    ]);
                }

                $idBrand = BrandProduct::where('id_product', $checkProduct['id_product'])->first()['id_brand'] ?? null;

                $variantSubtotal = $valueProduct['product_variant_price'] - $valueProduct['product_base_price'];
                $dataProduct = [
                    'id_transaction'               => $insertTransaction['id_transaction'],
                    'id_product'                   => $checkProduct['id_product'],
                    'type'                         => $checkProduct['product_type'],
                    'transaction_product_recipe_status' => $checkProduct['need_recipe_status'],
                    'id_product_variant_group'     => (empty($valueProduct['id_product_variant_group']) ? null : $valueProduct['id_product_variant_group']),
                    'id_brand'                     => $idBrand,
                    'id_outlet'                    => $insertTransaction['id_outlet'],
                    'id_user'                      => $insertTransaction['id_user'],
                    'transaction_product_qty'      => $valueProduct['qty'],
                    'transaction_product_price'    => $valueProduct['product_price'],
                    'transaction_product_price_base' => (!empty($valueProduct['product_price_before_discount']) ? $valueProduct['product_price_before_discount'] : $valueProduct['product_price']),
                    'transaction_product_subtotal' => $valueProduct['product_price_subtotal'],
                    'transaction_product_net' => $valueProduct['product_price_subtotal'],
                    'transaction_variant_subtotal' => ($variantSubtotal < 0 ? 0 : $variantSubtotal),
                    'transaction_product_note'     => $valueProduct['note'],
                    'transaction_product_wholesaler_minimum_qty'  => $valueProduct['wholesaler_minimum'] ?? null,
                    'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])) . ' ' . date('H:i:s'),
                    'updated_at'                   => date('Y-m-d H:i:s'),
                    'transaction_product_discount' => $valueProduct['total_discount'] ?? 0,
                    'transaction_product_discount_all' => $valueProduct['total_discount'] ?? 0,
                    'transaction_product_qty_discount' => $valueProduct['qty_discount'] ?? 0,
                    'transaction_product_base_discount' => $valueProduct['base_discount_each_item'] ?? 0
                ];

                $trx_product = TransactionProduct::create($dataProduct);
                if (!$trx_product) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Product Transaction Failed']
                    ]);
                }

                if (!empty($valueProduct['id_product_variant_group'])) {
                    $product_variant_group = ProductVariantGroup::where('product_variant_groups.id_product_variant_group', $valueProduct['id_product_variant_group'])
                                                ->select('product_variant_groups.id_product_variant_group', 'product_variant_groups.id_product', 'product_variant_groups.product_variant_group_price')
                                                ->first();
                    if (!$product_variant_group) {
                        DB::rollback();
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Product Variant Group not found'],
                            'product' => $checkProduct['product_name']
                        ]);
                    }
                    $variantTree = Product::getVariantTree($valueProduct['id_product'], $outlet);
                    $variants = Product::getVariantPrice($product_variant_group, $variantTree['variants_tree'] ?? []);
                    if (!$variants) {
                        $dataVariants = [];
                    } else {
                        $dataVariants = $variants;
                    }

                    $insert_variants = [];
                    foreach ($dataVariants as $id_product_variant => $product_variant_price) {
                        $insert_variants[] = [
                            'id_transaction_product' => $trx_product['id_transaction_product'],
                            'id_product_variant' => $id_product_variant,
                            'transaction_product_variant_price' => $product_variant_price,
                            'created_at'                   => date('Y-m-d H:i:s'),
                            'updated_at'                   => date('Y-m-d H:i:s')
                        ];
                    }
                    $trx_variants = TransactionProductVariant::insert($insert_variants);
                    if (!$trx_variants) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Insert Variant Transaction Failed']
                        ]);
                    }
                }
            }

            if (empty($data['delivery'])) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data delivery can not be empty']
                ]);
            }

            $shipmentCourier = null;
            $shipmentCourierCode = null;
            $shipmentCourierService = null;
            $shipmentInsuranceStatus = 0;
            $shipmentInsurancePrice = 0;
            $shipmentPrice = 0;
            $shipmentRateID = null;
            $estimated = null;

            //checkking shipment
            foreach ($data['available_delivery'] as $shipmentCheck) {
                foreach ($shipmentCheck['service'] as $service) {
                    if ($service['rate_id'] == $data['delivery']['rate_id']) {
                        $shipmentRateID = $service['rate_id'];
                        $shipmentCourier = $shipmentCheck['delivery_method'];
                        $shipmentCourierCode = $service['code'];
                        $shipmentCourierService = $shipmentCheck['delivery_name'] . ' ' . $service['service_name'];
                        $shipmentInsuranceStatus = (($data['delivery']['insurance_status'] ?? false) == true ? 1 : 0);
                        if ($service['must_use_insurance'] == true) {
                            $shipmentInsuranceStatus = 1;
                        }
                        $shipmentInsurancePrice = $service['insurance_fee'];
                        $shipmentPrice = $service['price'];
                        $estimated = $service['estimated'];
                        if ($shipmentInsuranceStatus == 1) {
                            $shipmentPrice = $shipmentPrice + $shipmentInsurancePrice;
                        }
                    }
                }
            }

            if (empty($shipmentRateID)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Delivery not available']
                ]);
            }

            $dataShipment = [
                'id_transaction'           => $insertTransaction['id_transaction'],
                'depart_name'              => $outlet['outlet_name'],
                'depart_phone'             => $outlet['outlet_phone'],
                'depart_address'           => $outlet['outlet_address'],
                'depart_id_city'           => $outlet['id_city'],
                'depart_id_subdistrict'    => $outlet['id_subdistrict'],
                'destination_name'         => $address['receiver_name'] ?? $user['name'],
                'destination_phone'        => $address['receiver_phone'] ?? $user['phone'],
                'destination_address'      => $address['address'],
                'destination_id_city'      => $address['id_city'],
                'destination_id_subdistrict' => $address['id_subdistrict'],
                'destination_description'  => $address['description'],
                'shipment_total_weight'    => $data['items_total_weight'] ?? 0,
                'shipment_total_height'    => $data['items_total_height'] ?? 0,
                'shipment_total_width'    => $data['items_total_width'] ?? 0,
                'shipment_total_length'    => $data['items_total_length'] ?? 0,
                'shipment_courier'         => $shipmentCourier,
                'shipment_courier_code'    => $shipmentCourierCode,
                'shipment_courier_service' => $shipmentCourierService,
                'shipment_insurance_price' => $shipmentInsurancePrice,
                'shipment_insurance_use_status' => $shipmentInsuranceStatus,
                'shipment_rate_id' => $shipmentRateID,
                'shipment_price' => $shipmentPrice,
                'shipment_courier_etd' => $estimated,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $insertShipment = TransactionShipment::create($dataShipment);
            if (!$insertShipment) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Shipment Transaction Failed']
                ]);
            }
        }

        $createDailyTrx = DailyTransactions::create([
            'id_transaction_group' => $insertTransactionGroup['id_transaction_group'],
            'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransactionGroup['transaction_group_date'])),
            'id_user'           => $user['id']
        ]);
        if (!$createDailyTrx) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Failed create daily transaction']
            ]);
        }

        $trxGroup = TransactionGroup::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->first();
        if ($paymentType == 'Balance' && isset($post['point_use']) && $post['point_use']) {
            $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
            $grandTotalNew = $trxGroup['transaction_grandtotal'];
            if ($currentBalance >= $grandTotalNew) {
                $grandTotalNew = 0;
            } else {
                $grandTotalNew = $grandTotalNew - $currentBalance;
            }

            $save = app($this->balance)->topUpGroup($user->id, $trxGroup);

            if (!isset($save['status'])) {
                DB::rollBack();
                return response()->json(['status' => 'fail', 'messages' => ['Transaction failed']]);
            }

            if ($save['status'] == 'fail') {
                DB::rollBack();
                return response()->json($save);
            }

            if ($grandTotalNew == 0) {
                $trxGroup->triggerPaymentCompleted();
            }
        } elseif ($insertTransactionGroup['transaction_grandtotal'] == 0 && !empty($itemsCheck['promo_code'])) {
            $trxGroup->triggerPaymentCompleted();
        }

        $transactionPromo = Transaction::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->get()->toArray();
        foreach ($transactionPromo as $trxPromo) {
            if ($trxPromo['id_promo_campaign_promo_code']) {
                app($this->promo_trx)->applyPromoNewTrx($trxPromo);
            }
        }

        DB::commit();
        $trxGroup = TransactionGroup::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->first();
        return MyHelper::checkCreate($trxGroup);
    }

    /**
     * Get info from given cart data
     * @param  CheckTransaction $request [description]
     * @return View                    [description]
     */
    public function checkTransaction(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        $fromRecipeDoctor = 0;
        if (!empty($post['id_transaction_consultation'])) {
            $post = $this->checkTransactionRecipe($post);
            $fromRecipeDoctor = 1;
        }

        if (empty($post['items'])) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Item can not be empty']
            ]);
        }

        $errorMsg = [];
        $address = UserAddress::leftJoin('subdistricts', 'subdistricts.id_subdistrict', 'user_addresses.id_subdistrict')
            ->leftJoin('districts', 'districts.id_district', 'subdistricts.id_district')
            ->leftJoin('cities', 'cities.id_city', 'districts.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('id_user', $user->id)
            ->orderBy('main_address', 'desc')
            ->select('user_addresses.*', 'city_name', 'provinces.id_province', 'province_name', 'districts.id_district', 'subdistrict_name', 'district_name');

        if (!empty($post['id_user_address'])) {
            $address = $address->where('id_user_address', $post['id_user_address']);
        } else {
            $address = $address->where('favorite', 1);
        }

        $address = $address->first();
        if (empty($address)) {
            $address = UserAddress::leftJoin('subdistricts', 'subdistricts.id_subdistrict', 'user_addresses.id_subdistrict')
                ->leftJoin('districts', 'districts.id_district', 'subdistricts.id_district')
                ->leftJoin('cities', 'cities.id_city', 'districts.id_city')
                ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                ->where('id_user', $user->id)
                ->orderBy('main_address', 'desc')
                ->select('user_addresses.*', 'city_name', 'provinces.id_province', 'province_name', 'districts.id_district', 'subdistrict_name', 'district_name')->first();
        }
        $mainAddress = $address;

        $itemsCheck = $this->checkDataTransaction($post['items'], 0, 0, 1, $mainAddress, $fromRecipeDoctor, $user->id);
        $items = $itemsCheck['items'];
        $subtotal = $itemsCheck['subtotal'];
        $delivery = $itemsCheck['total_delivery'] ?? 0;
        $checkOutStatus = $itemsCheck['available_checkout'];
        $popupConsultation = $itemsCheck['pupop_need_consultation'] ?? true;
        if (!empty($itemsCheck['error_messages'])) {
            $checkOutStatus = false;
        }
        $errorMsg[] = $itemsCheck['error_messages'];

        if (!empty($mainAddress)) {
            $mainAddress = [
                "id_user_address" => $mainAddress['id_user_address'],
                "address" => $mainAddress['address'],
                "postal_code" => $mainAddress['postal_code'],
                "id_province" => $mainAddress['id_province'],
                "province_name" => $mainAddress['province_name'],
                "id_city" => $mainAddress['id_city'],
                "city_name" => $mainAddress['city_name'],
                "id_district" => $mainAddress['id_district'],
                "district_name" => $mainAddress['district_name'],
                "id_subdistrict" => $mainAddress['id_subdistrict'],
                "subdistrict_name" => $mainAddress['subdistrict_name'],
                "receiver_name" => (empty($mainAddress['receiver_name']) ? $user->name : $mainAddress['receiver_name']),
                "receiver_phone" => (empty($mainAddress['receiver_phone']) ? $user->phone : $mainAddress['receiver_phone']),
                "receiver_email" => (empty($mainAddress['receiver_email']) ? $user->email : $mainAddress['receiver_email']),
                "main_address_status" => $mainAddress['favorite']
            ];
        } else {
            $errorMsg[] = 'Alamat tidak boleh kosong' .
            $checkOutStatus = false;
        }

        $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
        $summaryOrder = [
            [
                'name' => 'Subtotal',
                'is_discount' => 0,
                'value' => 'Rp ' . number_format($subtotal, 0, ",", ".")
            ],
            [
                'name' => 'Biaya Kirim',
                'is_discount' => 0,
                'value' => 'Rp ' . number_format($delivery, 0, ",", ".")
            ]
        ];

        $grandTotal = $subtotal + $delivery;

        $result = [
            'address' => $mainAddress,
            'point_use' => $post['point_use'] ?? false,
            'id_transaction_consultation' => $post['id_transaction_consultation'] ?? null,
            'items' => $items,
            'current_points' => $currentBalance,
            'summary_order' => $summaryOrder,
            'subtotal' => $subtotal,
            'total_delivery' => $delivery,
            'grandtotal' => $grandTotal,
            'grandtotal_text' => 'Rp ' . number_format($grandTotal, 0, ",", "."),
            'pupop_need_consultation' => $popupConsultation,
            'available_checkout' => $checkOutStatus,
            'error_messages' => implode('. ', array_unique($errorMsg))
        ];

        $fake_request = new Request(['show_all' => 0, 'from_check' => 1]);
        $result['available_payment'] = $this->availablePayment($fake_request)['result'] ?? [];
        $result = app($this->promo_trx)->applyPromoCheckout($result);

        $service = 0;
        $tax = 0;
        foreach ($result['items'] as $index => $value) {
            $sub = $value['items_subtotal'] - ($value['discount'] ?? 0);
            $sub = ($sub < 0 ? 0 : $sub);
            $dtTaxService = ['subtotal' => $sub];

            $serviceCalculate = round(app($this->setting_trx)->countTransaction('service', $dtTaxService));
            $service = $service + $serviceCalculate;

            $taxCalculate = round(app($this->setting_trx)->countTransaction('tax', $dtTaxService));
            $tax = $tax + $taxCalculate;

            $result['items'][$index]['service'] = $serviceCalculate;
            $result['items'][$index]['tax'] = $taxCalculate;
        }

        $service = round($service);
        $tax = round($tax);

        if ($service > 0) {
            $result['summary_order'][] = [
                'name' => 'Biaya Layanan',
                'is_discount' => 0,
                'value' => 'Rp ' . number_format($service, 0, ",", ".")
            ];
        }

        if ($tax > 0) {
            $result['summary_order'][] = [
                'name' => 'Pajak',
                'is_discount' => 0,
                'value' => 'Rp ' . number_format($tax, 0, ",", ".")
            ];
        }

        $grandTotal = $result['grandtotal'] + $service + $tax;
        $result['grandtotal'] = $grandTotal;
        $result['grandtotal_text'] = 'Rp ' . number_format($grandTotal, 0, ",", ".");

        $grandTotalNew = $result['grandtotal'];
        if (isset($post['point_use']) && $post['point_use']) {
            if ($currentBalance >= $grandTotalNew) {
                $usePoint = $grandTotalNew;
                $grandTotalNew = 0;
            } else {
                $usePoint = $currentBalance;
                $grandTotalNew = $grandTotalNew - $currentBalance;
            }

            $currentBalance -= $usePoint;

            if ($usePoint > 0) {
                $result['summary_order'][] = [
                    'name' => 'Point yang digunakan',
                    'value' => '- ' . number_format($usePoint, 0, ",", ".")
                ];
            } else {
                $result['available_checkout'] = false;
                $result['error_messages'] = 'Tidak bisa menggunakan point, Anda tidak memiliki cukup point.';
            }
        }

        $result['grandtotal'] = $grandTotalNew;
        $result['grandtotal_text'] = 'Rp ' . number_format($grandTotalNew, 0, ",", ".");
        $result['current_points'] = $currentBalance;

        return response()->json(MyHelper::checkGet($result));
    }

    public function checkBundlingProduct($post, $outlet, $subtotal_per_brand = [])
    {
        $error_msg = [];
        $subTotalBundling = 0;
        $totalItemBundling = 0;
        $itemBundlingDetail = [];
        $itemBundling = [];
        $errorBundlingName = [];
        $currentHour = date('H:i:s');
        foreach ($post['item_bundling'] ?? [] as $key => $bundling) {
            if ($bundling['bundling_qty'] <= 0) {
                $error_msg[] = $bundling['bundling_name'] . ' qty must not be below 0';
                unset($post['item_bundling'][$key]);
                continue;
            }
            $getBundling = Bundling::where('bundling.id_bundling', $bundling['id_bundling'])
                ->join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
                ->whereRaw('TIME_TO_SEC("' . $currentHour . '") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("' . $currentHour . '") <= TIME_TO_SEC(time_end)')
                ->whereRaw('NOW() >= start_date AND NOW() <= end_date')->first();
            if (empty($getBundling)) {
                $errorBundlingName[] = $bundling['bundling_name'];
                unset($post['item_bundling'][$key]);
                continue;
            }

            //check count product in bundling
            $getBundlingProduct = BundlingProduct::where('id_bundling', $bundling['id_bundling'])->select('id_product', 'bundling_product_qty')->get()->toArray();
            $arrBundlingQty = array_column($getBundlingProduct, 'bundling_product_qty');
            $arrBundlingIdProduct = array_column($getBundlingProduct, 'id_product');
            if (array_sum($arrBundlingQty) !== count($bundling['products'])) {
                $error_msg[] = MyHelper::simpleReplace(
                    'Jumlah product pada bundling %bundling_name% tidak sesuai',
                    [
                        'bundling_name' => $bundling['bundling_name']
                    ]
                );
                unset($post['item_bundling'][$key]);
                continue;
            }

            //check outlet available
            if ($getBundling['all_outlet'] == 0 && $getBundling['outlet_available_type'] == 'Selected Outlet') {
                $getBundlingOutlet = BundlingOutlet::where('id_bundling', $bundling['id_bundling'])->where('id_outlet', $post['id_outlet'])->count();

                if (empty($getBundlingOutlet)) {
                    $error_msg[] = MyHelper::simpleReplace(
                        'Bundling %bundling_name% tidak bisa digunakan di outlet %outlet_name%',
                        [
                            'bundling_name' => $bundling['bundling_name'],
                            'outlet_name' => $outlet['outlet_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue;
                }
            } elseif ($getBundling['all_outlet'] == 0 && $getBundling['outlet_available_type'] == 'Outlet Group Filter') {
                $brands = BrandProduct::whereIn('id_product', $arrBundlingIdProduct)->pluck('id_brand')->toArray();
                $availableBundling = app($this->bundling)->bundlingOutletGroupFilter($post['id_outlet'], $brands);
                if (empty($availableBundling)) {
                    $error_msg[] = MyHelper::simpleReplace(
                        'Bundling %bundling_name% tidak bisa digunakan di outlet %outlet_name%',
                        [
                            'bundling_name' => $bundling['bundling_name'],
                            'outlet_name' => $outlet['outlet_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue;
                }
            }

            $bundlingBasePrice = 0;
            $totalModPrice = 0;
            $totalPriceNoDiscount = 0;
            $products = [];
            $productsBundlingDetail = [];
            //check product from bundling
            foreach ($bundling['products'] as $keyProduct => $p) {
                $product = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                    ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                    ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                    ->where('bundling_product.id_bundling_product', $p['id_bundling_product'])
                    ->where('products.is_inactive', 0)
                    ->select(
                        'products.product_visibility',
                        'pgp.product_global_price',
                        'products.product_variant_status',
                        'bundling_product.*',
                        'bundling.bundling_promo_status',
                        'bundling.bundling_name',
                        'bundling.bundling_code',
                        'products.*'
                    )
                    ->first();

                if (empty($product)) {
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                $getProductDetail = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                $product['visibility_outlet'] = $getProductDetail['product_detail_visibility'] ?? null;
                $id_product_variant_group = $product['id_product_variant_group'] ?? null;

                if ($product['visibility_outlet'] == 'Hidden' || (empty($product['visibility_outlet']) && $product['product_visibility'] == 'Hidden')) {
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                if (isset($getProductDetail['product_detail_stock_status']) && $getProductDetail['product_detail_stock_status'] == 'Sold Out') {
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                $product['note'] = $p['note'] ?? '';
                if ($product['product_variant_status'] && !empty($product['id_product_variant_group'])) {
                    $checkAvailable = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                    if ($checkAvailable['product_variant_group_visibility'] == 'Hidden') {
                        $errorBundlingName[] = $bundling['bundling_name'];
                        unset($post['item_bundling'][$key]);
                        continue 2;
                    } else {
                        if ($outlet['outlet_different_price'] == 1) {
                            $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price'] ?? 0;
                        } else {
                            $price = $checkAvailable['product_variant_group_price'] ?? 0;
                        }
                    }
                } elseif (!empty($p['id_product'])) {
                    if ($outlet['outlet_different_price'] == 1) {
                        $price = ProductSpecialPrice::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price'] ?? 0;
                    } else {
                        $price = $product['product_global_price'];
                    }
                }

                $price = (float)$price ?? 0;
                if ($price <= 0) {
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $totalPriceNoDiscount = $totalPriceNoDiscount + $price;
                //calculate discount produk
                if (strtolower($product['bundling_product_discount_type']) == 'nominal') {
                    $calculate = ($price - $product['bundling_product_discount']);
                } else {
                    $discount = $price * ($product['bundling_product_discount'] / 100);
                    $discount = ($discount > $product['bundling_product_maximum_discount'] &&  $product['bundling_product_maximum_discount'] > 0 ? $product['bundling_product_maximum_discount'] : $discount);
                    $calculate = ($price - $discount);
                }
                $bundlingBasePrice = $bundlingBasePrice + $calculate;

                // get modifier
                $mod_price = 0;
                $modifiers = [];
                $removed_modifier = [];
                $missing_modifier = 0;
                foreach ($p['modifiers'] ?? [] as $key => $modifier) {
                    $id_product_modifier = is_numeric($modifier) ? $modifier : $modifier['id_product_modifier'];
                    $qty_product_modifier = is_numeric($modifier) ? 1 : $modifier['qty'];
                    $mod = ProductModifier::select(
                        'product_modifiers.id_product_modifier',
                        'code',
                        DB::raw('(CASE
                        WHEN product_modifiers.text_detail_trx IS NOT NULL 
                        THEN product_modifiers.text_detail_trx
                        ELSE product_modifiers.text
                    END) as text'),
                        'product_modifier_stock_status',
                        \DB::raw('coalesce(product_modifier_price, 0) as product_modifier_price'),
                        'modifier_type'
                    )
                        // product visible
                        ->leftJoin('product_modifier_details', function ($join) use ($post) {
                            $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                                ->where('product_modifier_details.id_outlet', $post['id_outlet']);
                        })
                        ->where(function ($q) {
                            $q->where(function ($q) {
                                $q->where(function ($query) {
                                    $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                                        ->orWhere(function ($q) {
                                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                                                ->where('product_modifiers.product_modifier_visibility', 'Visible');
                                        });
                                });
                            })->orWhere('product_modifiers.modifier_type', '=', 'Modifier Group');
                        })
                        ->where(function ($q) {
                            $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
                        })
                        ->groupBy('product_modifiers.id_product_modifier');
                    if ($outlet['outlet_different_price']) {
                        $mod->leftJoin('product_modifier_prices', function ($join) use ($post) {
                            $join->on('product_modifier_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
                            $join->where('product_modifier_prices.id_outlet', $post['id_outlet']);
                        });
                    } else {
                        $mod->leftJoin('product_modifier_global_prices', function ($join) use ($post) {
                            $join->on('product_modifier_global_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
                        });
                    }
                    $mod = $mod->find($id_product_modifier);
                    if (!$mod) {
                        $missing_modifier++;
                        continue;
                    }
                    $mod = $mod->toArray();
                    $scope = $mod['modifier_type'];
                    $mod['qty'] = $qty_product_modifier;
                    $mod['product_modifier_price'] = (int) $mod['product_modifier_price'];
                    if ($scope != 'Modifier Group') {
                        if ($mod['product_modifier_stock_status'] != 'Sold Out') {
                            $modifiers[] = $mod;
                        } else {
                            $removed_modifier[] = $mod['text'];
                        }
                    }
                    $mod_price += $mod['qty'] * $mod['product_modifier_price'];
                }

                if ($missing_modifier) {
                    $error_msg[] = MyHelper::simpleReplace(
                        '%missing_modifier% topping untuk produk %product_name% pada bundling %bundling_name% tidak tersedia',
                        [
                            'missing_modifier' => $missing_modifier,
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                if ($removed_modifier) {
                    $error_msg[] = MyHelper::simpleReplace(
                        'Topping %removed_modifier% untuk produk %product_name% pada bundling %bundling_name% tidak tersedia',
                        [
                            'removed_modifier' => implode(',', $removed_modifier),
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $product['selected_variant'] = [];
                $variants = [];
                if (!empty($id_product_variant_group)) {
                    $variants = ProductVariantGroup::join('product_variant_pivot as pvp', 'pvp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                        ->join('product_variants as pv', 'pv.id_product_variant', 'pvp.id_product_variant')
                        ->select('pv.id_product_variant', 'product_variant_name')
                        ->where('product_variant_groups.id_product_variant_group', $id_product_variant_group)
                        ->orderBy('pv.product_variant_order', 'asc')
                        ->get()->toArray();
                    $product['selected_variant'] = array_column($variants, 'id_product_variant');
                }

                $extraModifier = [];
                if (!empty($p['extra_modifiers'])) {
                    $extraModifier = ProductModifier::join('product_modifier_groups as pmg', 'pmg.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                                    ->join('product_modifier_group_pivots as pmgp', 'pmgp.id_product_modifier_group', 'pmg.id_product_modifier_group')
                                    ->select('product_modifiers.*', 'pmgp.id_product', 'pmgp.id_product_variant')
                                    ->whereIn('product_modifiers.id_product_modifier', $p['extra_modifiers'])
                                    ->where(function ($q) use ($product) {
                                        $q->whereIn('pmgp.id_product_variant', $product['selected_variant'])
                                            ->orWhere('pmgp.id_product', $product['id_product']);
                                    })
                                    ->get()->toArray();
                    foreach ($extraModifier as $m) {
                        $variants[] = [
                            'id_product_variant' => $m['id_product_modifier'],
                            'id_product_variant_group' => $m['id_product_modifier_group'],
                            'product_variant_name' => $m['text_detail_trx']
                        ];
                    }
                }

                if (isset($p['extra_modifiers']) && (count($p['extra_modifiers']) != count($extraModifier))) {
                    $variantsss = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->select('product_variant_name')->where('id_product_variant_group', $product['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                    $modifiersss = ProductModifier::whereIn('id_product_modifier', array_column($extraModifier, 'id_product_modifier'))->where('modifier_type', 'Modifier Group')->pluck('text')->toArray();
                    $error_msg[] = MyHelper::simpleReplace(
                        'Varian %variants% untuk %product_name% tidak tersedia pada bundling %bundling_name%',
                        [
                            'variants' => implode(', ', array_merge($variantsss, $modifiersss)),
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $totalModPrice = $totalModPrice + $mod_price;
                $product['variants'] = $variants;
                $products[] = [
                    "id_brand" => $product['id_brand'],
                    "id_product" => $product['id_product'],
                    "id_bundling_product" => $product['id_bundling_product'],
                    "id_product_variant_group" => $product['id_product_variant_group'],
                    "modifiers" => $modifiers,
                    "extra_modifiers" => array_column($extraModifier, 'id_product_modifier'),
                    "product_name" => $product['product_name'],
                    "note" => (!empty($product['note']) ? $product['note'] : ""),
                    "product_code" => $product['product_code'],
                    "selected_variant" => array_merge($product['selected_variant'], $p['extra_modifiers'] ?? []),
                    "variants" => $product['variants']
                ];

                $productsBundlingDetail[] = [
                    "product_qty" => 1,
                    "id_brand" => $product['id_brand'],
                    "id_product" => $product['id_product'],
                    "id_bundling_product" => $product['id_bundling_product'],
                    "id_product_variant_group" => $product['id_product_variant_group'],
                    "modifiers" => $modifiers,
                    "extra_modifiers" => array_column($extraModifier, 'id_product_modifier'),
                    "product_name" => $product['product_name'],
                    "note" => (!empty($product['note']) ? $product['note'] : ""),
                    "product_code" => $product['product_code'],
                    "selected_variant" => array_merge($product['selected_variant'], $p['extra_modifiers'] ?? []),
                    "variants" => $product['variants']
                ];

                if ($product['bundling_promo_status'] == 1) {
                    if (isset($subtotal_per_brand[$product['id_brand']])) {
                        $subtotal_per_brand[$product['id_brand']] += ($calculate  + $mod_price) * $bundling['bundling_qty'];
                    } else {
                        $subtotal_per_brand[$product['id_brand']] = ($calculate  + $mod_price) * $bundling['bundling_qty'];
                    }
                    $bundlingNotIncludePromo[] = $bundling['bundling_name'];
                }
            }

            if (!empty($products) && !empty($productsBundlingDetail)) {
                $itemBundling[] = [
                    "id_custom" => $bundling['id_custom'] ?? null,
                    "id_bundling" => $getBundling['id_bundling'],
                    "bundling_name" => $getBundling['bundling_name'],
                    "bundling_code" => $getBundling['bundling_code'],
                    "bundling_base_price" => $bundlingBasePrice,
                    "bundling_qty" => $bundling['bundling_qty'],
                    "bundling_price_total" =>  $bundlingBasePrice + $totalModPrice,
                    "products" => $products
                ];

                $productsBundlingDetail = $this->mergeBundlingProducts($productsBundlingDetail, $bundling['bundling_qty']);
                //check for same detail item bundling
                $itemBundlingDetail[] = [
                    "id_custom" => $bundling['id_custom'] ?? null,
                    "id_bundling" => $bundling['id_bundling'] ?? null,
                    'bundling_name' => $bundling['bundling_name'],
                    'bundling_qty' => $bundling['bundling_qty'],
                    'bundling_price_no_discount' => (int)$totalPriceNoDiscount * $bundling['bundling_qty'],
                    'bundling_subtotal' => $bundlingBasePrice * $bundling['bundling_qty'],
                    'bundling_sub_item' => '@' . MyHelper::requestNumber($bundlingBasePrice, '_CURRENCY'),
                    'bundling_sub_item_raw' => $bundlingBasePrice,
                    'bundling_sub_price_no_discount' => (int)$totalPriceNoDiscount,
                    "products" => $productsBundlingDetail
                ];

                $subTotalBundling = $subTotalBundling + (($bundlingBasePrice + $totalModPrice) * $bundling['bundling_qty']);
                $totalItemBundling = $totalItemBundling + $bundling['bundling_qty'];
            }
        }

        $mergeBundlingDetail = $this->mergeBundlingDetail($itemBundlingDetail);
        $mergeBundling = $this->mergeBundling($itemBundling);
        if (!empty($errorBundlingName)) {
            $error_msg[] = 'Product ' . implode(',', array_unique($errorBundlingName)) . ' tidak tersedia dan akan terhapus dari cart.';
        }

        return [
            'total_item_bundling' => $totalItemBundling,
            'subtotal_bundling' => $subTotalBundling,
            'item_bundling' => $mergeBundling,
            'item_bundling_detail' => $mergeBundlingDetail,
            'error_message' => $error_msg,
            'subtotal_per_brand' => $subtotal_per_brand,
            'bundling_not_include_promo' => implode(',', array_unique($bundlingNotIncludePromo ?? []))
        ];
    }

    public function checkBundlingIncludePromo($post)
    {
        $arr = [];
        foreach ($post['item_bundling'] ?? [] as $key => $bundling) {
            $getBundling = Bundling::where('bundling.id_bundling', $bundling['id_bundling'])
                ->join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')->first();

            if (!empty($getBundling)) {
                $getBundlingProduct = BundlingProduct::join('brand_product', 'brand_product.id_product', 'bundling_product.id_product')
                    ->where('bundling_product.id_bundling', $bundling['id_bundling'])
                    ->pluck('brand_product.id_brand')->toArray();

                foreach ($getBundlingProduct as $brand) {
                    if ($getBundling['bundling_promo_status'] == 1) {
                        $arr[] = [
                            'id_brand' => $brand,
                            'id_bundling' => $bundling['id_bundling']
                        ];
                    }
                }
            }
        }

        return $arr;
    }

    public function saveLocation($latitude, $longitude, $id_user, $id_transaction, $id_outlet)
    {

        $cek = UserLocationDetail::where('id_reference', $id_transaction)->where('activity', 'Transaction')->first();
        if ($cek) {
            return true;
        }

        $googlemap = MyHelper::get(env('GEOCODE_URL') . $latitude . ',' . $longitude . '&key=' . env('GEOCODE_KEY'));

        if (isset($googlemap['results'][0]['address_components'])) {
            $street = null;
            $route = null;
            $level1 = null;
            $level2 = null;
            $level3 = null;
            $level4 = null;
            $level5 = null;
            $country = null;
            $postal = null;
            $address = null;

            foreach ($googlemap['results'][0]['address_components'] as $data) {
                if ($data['types'][0] == 'postal_code') {
                    $postal = $data['long_name'];
                } elseif ($data['types'][0] == 'route') {
                    $route = $data['long_name'];
                } elseif ($data['types'][0] == 'administrative_area_level_5') {
                    $level5 = $data['long_name'];
                } elseif ($data['types'][0] == 'administrative_area_level_4') {
                    $level4 = $data['long_name'];
                } elseif ($data['types'][0] == 'administrative_area_level_3') {
                    $level3 = $data['long_name'];
                } elseif ($data['types'][0] == 'administrative_area_level_2') {
                    $level2 = $data['long_name'];
                } elseif ($data['types'][0] == 'administrative_area_level_1') {
                    $level1 = $data['long_name'];
                } elseif ($data['types'][0] == 'country') {
                    $country = $data['long_name'];
                }
            }

            if ($googlemap['results'][0]['formatted_address']) {
                $address = $googlemap['results'][0]['formatted_address'];
            }

            $outletCode = null;
            $outletName = null;

            $outlet = Outlet::find($id_outlet);
            if ($outlet) {
                $outletCode = $outlet['outlet_code'];
                $outletCode = $outlet['outlet_name'];
            }

            $logactivity = UserLocationDetail::create([
                'id_user' => $id_user,
                'id_reference' => $id_transaction,
                'id_outlet' => $id_outlet,
                'outlet_code' => $outletCode,
                'outlet_name' => $outletName,
                'activity' => 'Transaction',
                'action' => 'Completed',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'response_json' => json_encode($googlemap),
                'route' => $route,
                'street_address' => $street,
                'administrative_area_level_5' => $level5,
                'administrative_area_level_4' => $level4,
                'administrative_area_level_3' => $level3,
                'administrative_area_level_2' => $level2,
                'administrative_area_level_1' => $level1,
                'country' => $country,
                'postal_code' => $postal,
                'formatted_address' => $address
            ]);

            if ($logactivity) {
                return true;
            }
        }

        return false;
    }

    public function dataRedirect($id, $type, $success)
    {
        $button = '';

        $list = Transaction::where('transaction_receipt_number', $id)->first();
        if (empty($list)) {
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        $dataEncode = [
            'transaction_receipt_number'   => $id,
            'type' => $type,
        ];

        if (isset($success)) {
            $dataEncode['trx_success'] = $success;
            $button = 'LIHAT NOTA';
        }

        $title = 'Sukses';
        if ($list['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($list['transaction_payment_status'] == 'Terbayar') {
            $title = 'Terbayar';
        }

        if ($list['transaction_payment_status'] == 'Sukses') {
            $title = 'Sukses';
        }

        if ($list['transaction_payment_status'] == 'Gagal') {
            $title = 'Gagal';
        }

        $encode = json_encode($dataEncode);
        $base = base64_encode($encode);

        $send = [
            'button'                     => $button,
            'title'                      => $title,
            'payment_status'             => $list['transaction_payment_status'],
            'transaction_receipt_number' => $list['transaction_receipt_number'],
            'transaction_grandtotal'     => $list['transaction_grandtotal'],
            'type'                       => $type,
            'url'                        => env('VIEW_URL') . '/transaction/web/view/detail?data=' . $base
        ];

        return $send;
    }

    public function outletNotif($id_trx, $fromCron = false)
    {
        $trx = Transaction::where('id_transaction', $id_trx)->first();
        if ($trx['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $id_trx)->first();
        } else {
            $detail = TransactionShipment::where('id_transaction', $id_trx)->first();
        }

        $dataProduct = TransactionProduct::where('id_transaction', $id_trx)->with('product')->get();

        $count = count($dataProduct);
        $stringBody = "";
        $totalSemua = 0;

        foreach ($dataProduct as $key => $value) {
            $totalSemua += $value['transaction_product_qty'];
            $stringBody .= $value['product']['product_name'] . " - " . $value['transaction_product_qty'] . " pcs \n";
        }

        // return $stringBody;

        $outletToken = OutletToken::where('id_outlet', $trx['id_outlet'])->get();

        if (isset($detail['pickup_by'])) {
            if ($detail['pickup_by'] == 'Customer') {
                $type = 'Pickup';
                if (isset($detail['pickup_at'])) {
                    $type = $type . ' (' . date('H:i', strtotime($detail['pickup_at'])) . ' )';
                }
            } else {
                $type = 'Delivery';
            }
        } else {
            $type = 'Delivery';
        }

        $user = User::where('id', $trx['id_user'])->first();
        if (!empty($outletToken)) {
            if (env('PUSH_NOTIF_OUTLET') == 'fcm') {
                $tokens = $outletToken->pluck('token')->toArray();
                if (!empty($tokens)) {
                    $subject = $type . ' - Rp. ' . number_format($trx['transaction_grandtotal'], 0, ',', '.') . ' - ' . $totalSemua . ' pcs - ' . $detail['order_id'] . ' - ' . $user['name'];
                    $dataPush = ['type' => 'trx', 'id_reference' => $id_trx];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        // $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        if (!$fromCron) {
                            $dataPush += [
                                'push_notif_local' => 1,
                                'title_5mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 5 menit lagi'),
                                'msg_5mnt'         => str_replace($replacer[0], $replacer[1], 'Pesanan sudah siap kan?'),
                                'title_15mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 15 menit lagi'),
                                'msg_15mnt'         => str_replace($replacer[0], $replacer[1], 'Segera persiapkan pesanan'),
                                'pickup_time'       => $detail->pickup_at,
                            ];
                        } else {
                            $dataPush += [
                                'push_notif_local' => 0
                            ];
                        }
                    } else {
                        $dataPush += [
                            'push_notif_local' => 0
                        ];
                    }
                    $push = PushNotificationHelper::sendPush($tokens, $subject, $stringBody, null, $dataPush);
                }
            } else {
                $dataArraySend = [];

                foreach ($outletToken as $key => $value) {
                    $dataOutletSend = [
                        'to'    => $value['token'],
                        'title' => $type . ' - Rp. ' . number_format($trx['transaction_grandtotal'], 0, ',', '.') . ' - ' . $totalSemua . ' pcs - ' . $detail['order_id'] . ' - ' . $user['name'] . '',
                        'body'  => $stringBody,
                        'data'  => ['order_id' => $detail['order_id']]
                    ];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        // $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        if (!$fromCron) {
                            $dataOutletSend += [
                                'push_notif_local' => 1,
                                'title_5mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 5 menit lagi'),
                                'msg_5mnt'         => str_replace($replacer[0], $replacer[1], 'Pesanan sudah siap kan?'),
                                'title_15mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 15 menit lagi'),
                                'msg_15mnt'         => str_replace($replacer[0], $replacer[1], 'Segera persiapkan pesanan'),
                                'pickup_time'       => $detail->pickup_at,
                            ];
                        } else {
                            $dataOutletSend += [
                                'push_notif_local' => 0
                            ];
                        }
                    } else {
                        $dataOutletSend += [
                            'push_notif_local' => 0
                        ];
                    }
                    array_push($dataArraySend, $dataOutletSend);
                }

                $curl = $this->sendStatus('https://exp.host/--/api/v2/push/send', 'POST', $dataArraySend);
                if (!$curl) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Transaction failed']
                    ]);
                }
            }
        }

        return true;
    }

    public function sendStatus($url, $method, $data = null)
    {
        $client = new Client();

        $content = array(
            'headers' => [
                'host'            => 'exp.host',
                'accept'          => 'application/json',
                'accept-encoding' => 'gzip, deflate',
                'content-type'    => 'application/json'
            ],
            'json' => (array) $data
        );

        try {
            $response =  $client->request($method, $url, $content);
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            try {
                if ($e->getResponse()) {
                    $response = $e->getResponse()->getBody()->getContents();

                    $error = json_decode($response, true);

                    if (!$error) {
                        return $e->getResponse()->getBody();
                    } else {
                        return $error;
                    }
                } else {
                    return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
                }
            } catch (Exception $e) {
                return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
            }
        }
    }

    public function getrandomstring($length = 120)
    {

        global $template;
        settype($template, "string");

        $template = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

        settype($length, "integer");
        settype($rndstring, "string");
        settype($a, "integer");
        settype($b, "integer");

        for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
        }

        return $rndstring;
    }

    public function getrandomnumber($length)
    {

        global $template;
        settype($template, "string");

        $template = "0987654321";

        settype($length, "integer");
        settype($rndstring, "string");
        settype($a, "integer");
        settype($b, "integer");

        for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
        }

        return $rndstring;
    }

    public function checkPromoGetPoint($promo_source)
    {
        if (empty($promo_source)) {
            return 1;
        }

        if ($promo_source != 'promo_code' && $promo_source != 'voucher_online' && $promo_source != 'voucher_offline' && $promo_source != 'subscription') {
            return 0;
        }

        $config = app($this->promo)->promoGetCashbackRule();
        // $getData = Configs::whereIn('config_name',['promo code get point','voucher offline get point','voucher online get point'])->get()->toArray();

        // foreach ($getData as $key => $value) {
        //  $config[$value['config_name']] = $value['is_active'];
        // }

        if ($promo_source == 'promo_code') {
            if ($config['promo code get point'] == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        if ($promo_source == 'voucher_online') {
            if ($config['voucher online get point'] == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        if ($promo_source == 'voucher_offline') {
            if ($config['voucher offline get point'] == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        if ($promo_source == 'subscription') {
            if ($config['subscription get point'] == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        return 0;
    }
    public function cancelTransaction(Request $request)
    {
        if ($request->id) {
            $trx = TransactionGroup::where(['id_transaction_group' => $request->id, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        } else {
            $trx = TransactionGroup::where(['transaction_receipt_number' => $request->receipt_number, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        }
        if (!$trx) {
            return MyHelper::checkGet([], 'Transaction not found');
        }

        if ($trx->transaction_payment_status != 'Pending') {
            return MyHelper::checkGet([], 'Transaction cannot be canceled');
        }

        $payment_type = $trx->transaction_payment_type;
        if ($payment_type == 'Balance') {
            $multi_payment = TransactionMultiplePayment::select('type')->where('id_transaction_group', $trx->id_transaction_group)->pluck('type')->toArray();
            foreach ($multi_payment as $pm) {
                if ($pm != 'Balance') {
                    $payment_type = $pm;
                    break;
                }
            }
        }

        switch (strtolower($payment_type)) {
            case 'midtrans':
                $midtransStatus = Midtrans::status($trx['id_transaction_group']);
                if (
                    (($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? $midtransStatus['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || ($midtransStatus['status_code'] ?? false) == '404' ||
                    (!empty($midtransStatus['payment_type']) && $midtransStatus['payment_type'] == 'gopay' && $midtransStatus['transaction_status'] == 'pending')
                ) {
                    $connectMidtrans = Midtrans::expire($trx['transaction_receipt_number']);

                    if ($connectMidtrans) {
                        $trx->triggerPaymentCancelled();
                        return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
                return [
                    'status' => 'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
            case 'xendit':
                $dtXendit = TransactionPaymentXendit::where('id_transaction_group', $trx['id_transaction_group'])->first();
                if (empty($dtXendit->xendit_id)) {
                    $trx->triggerPaymentCancelled();
                    return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                } else {
                    $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit->xendit_id, $dtXendit->type);

                    $getStatus = $status['status'] ?? $status[0]['status'] ?? 0;
                    $getId = $status['id'] ?? $status[0]['id'] ?? null;
                    if ($status && $getStatus == 'PENDING' && !empty($getId)) {
                        $cancel = app('Modules\Xendit\Http\Controllers\XenditController')->expireInvoice($getId);

                        if ($cancel) {
                            $trx->triggerPaymentCancelled();
                            return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                        }
                    }
                }

                return [
                    'status' => 'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
        }
        return ['status' => 'fail', 'messages' => ["Cancel $payment_type transaction is not supported yet"]];
    }

    public function availablePayment(Request $request)
    {
        $availablePayment = config('payment_method');

        $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
        $payments = [];

        $config = [
            'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88')
        ];
        $last_status = [];
        foreach ($setting as $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if (is_array($payment['available_time'] ?? false)) {
                $available_time = $payment['available_time'];
                $current_time = time();
                if ($current_time < strtotime($available_time['start']) || $current_time > strtotime($available_time['end'])) {
                    $value['status'] = 0;
                }
            }

            if (!($payment['status'] ?? false) || (!$request->show_all && !($value['status'] ?? false))) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if (!is_numeric($payment['status'])) {
                $var = explode(':', $payment['status']);
                if (($config[$var[0]] ?? false) != ($var[1] ?? true)) {
                    $last_status[$var[0]] = $value['status'];
                    unset($availablePayment[$value['code']]);
                    continue;
                }
            }
            $payments[] = [
                'code'            => $value['code'],
                'payment_gateway' => (!empty($request->from_check) && $payment['payment_gateway'] == 'Midtrans' ? 'Xendit' : $payment['payment_gateway']),
                'payment_method'  => $payment['payment_method'],
                'logo'            => $payment['logo'],
                'text'            => $payment['text'],
                'status'          => (int) $value['status'] ? 1 : 0
            ];
            unset($availablePayment[$value['code']]);
        }
        foreach ($availablePayment as $code => $payment) {
            $status = 0;
            if (!$payment['status'] || !is_numeric($payment['status'])) {
                $var = explode(':', $payment['status']);
                if (($config[$var[0]] ?? false) != ($var[1] ?? true)) {
                    continue;
                }
                $status = (int) ($last_status[$var[0]] ?? 0);
            }
            if ($request->show_all || $status) {
                $payments[] = [
                    'code'            => $code,
                    'payment_gateway' => $payment['payment_gateway'],
                    'payment_method'  => $payment['payment_method'],
                    'logo'            => $payment['logo'],
                    'text'            => $payment['text'],
                    'status'          => $status
                ];
            }
        }
        return MyHelper::checkGet($payments);
    }
    /**
     * update available payment
     * @param
     * {
     *     payments: [
     *         {'code': 'xxx', status: 1}
     *     ]
     * }
     * @return [type]           [description]
     */
    public function availablePaymentUpdate(Request $request)
    {
        $availablePayment = config('payment_method');
        foreach ($request->payments as $key => $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment || !($payment['status'] ?? false)) {
                continue;
            }
            $payments[] = [
                'code'     => $value['code'],
                'status'   => $value['status'] ?? 0,
                'position' => $key + 1,
            ];
        }
        $update = Setting::updateOrCreate(['key' => 'active_payment_methods'], ['value_text' => json_encode($payments)]);
        return MyHelper::checkUpdate($update);
    }

    public function mergeBundlingProducts($items, $bundlinQty)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_brand' => $item['id_brand'],
                'id_product' => $item['id_product'],
                'id_product_variant_group' => ($item['id_product_variant_group'] ?? null) ?: null,
                'id_bundling_product' => $item['id_bundling_product'],
                'product_name' => $item['product_name'],
                'note' => $item['note'],
                'extra_modifiers' => $item['extra_modifiers'] ?? [],
                'variants' => array_map("unserialize", array_unique(array_map("serialize", array_map(function ($i) {
                    return [
                        'id_product_variant' => $i['id_product_variant'],
                        'product_variant_name' => $i['product_variant_name']
                    ];
                }, $item['variants'] ?? [])))),
                'modifiers' => array_map(function ($i) {
                    return [
                        "id_product_modifier" => $i['id_product_modifier'],
                        "code" => $i['code'],
                        "text" => $i['text'],
                        "product_modifier_price" => $i['product_modifier_price'] ,
                        "modifier_type" => $i['modifier_type'],
                        'qty' => $i['qty']
                    ];
                }, $item['modifiers'] ?? []),
            ];
            usort($new_item['modifiers'], function ($a, $b) {
                return $a['id_product_modifier'] <=> $b['id_product_modifier'];
            });
            $pos = array_search($new_item, $new_items);
            if ($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['product_qty'];
                $id_custom[] = $item['id_custom'] ?? 0;
            } else {
                $item_qtys[$pos] += $item['product_qty'];
            }
        }
        // update qty
        foreach ($new_items as $key => &$value) {
            $value['product_qty'] = $item_qtys[$key];
            foreach ($value['modifiers'] as &$mod) {
                $mod['product_modifier_price'] = $mod['product_modifier_price'] * $item_qtys[$key] * $bundlinQty;
            }
        }

        return $new_items;
    }

    public function mergeBundlingDetail($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_bundling' => $item['id_bundling'],
                'bundling_name' => $item['bundling_name'],
                'bundling_price_no_discount' => $item['bundling_price_no_discount'],
                'bundling_subtotal' => $item['bundling_subtotal'],
                'bundling_sub_item' => $item['bundling_sub_item'],
                'bundling_sub_item_raw' => $item['bundling_sub_item_raw'],
                'bundling_sub_price_no_discount' => $item['bundling_sub_price_no_discount'],
                'products' => array_map(function ($i) {
                    return [
                        "id_brand" => $i['id_brand'],
                        "id_product" => $i['id_product'],
                        "id_product_variant_group" => $i['id_product_variant_group'],
                        "id_bundling_product" => $i['id_bundling_product'] ,
                        "product_name" => $i['product_name'],
                        'product_code' =>  $i['product_code'] ?? "",
                        'note' => $i['note'],
                        'variants' => $i['variants'],
                        'modifiers' => $i['modifiers'],
                        'product_qty' => $i['product_qty'],
                        'extra_modifiers' => $i['extra_modifiers'] ?? []
                    ];
                }, $item['products'] ?? []),
            ];
            usort($new_item['products'], function ($a, $b) {
                return $a['id_product'] <=> $b['id_product'];
            });
            $pos = array_search($new_item, $new_items);
            if ($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['bundling_qty'];
                $id_custom[] = $item['id_custom'] ?? 0;
            } else {
                $item_qtys[$pos] += $item['bundling_qty'];
            }
        }

        // update qty
        foreach ($new_items as $key => &$value) {
            $value['bundling_qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
            $value['bundling_price_no_discount'] = $value['bundling_sub_price_no_discount'] * $item_qtys[$key];
            $value['bundling_subtotal'] = $value['bundling_sub_item_raw'] * $item_qtys[$key];
        }

        return $new_items;
    }

    public function mergeBundling($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_bundling' => $item['id_bundling'],
                'bundling_name' => $item['bundling_name'],
                'bundling_code' => $item['bundling_code'],
                'bundling_base_price' => $item['bundling_base_price'],
                'bundling_price_total' => $item['bundling_price_total'],
                'products' => array_map(function ($i) {
                    return [
                        "id_brand" => $i['id_brand'],
                        "id_product" => $i['id_product'],
                        "id_product_variant_group" => $i['id_product_variant_group'],
                        "id_bundling_product" => $i['id_bundling_product'] ,
                        "product_name" => $i['product_name'],
                        "product_code" => $i['product_code'],
                        'note' => $i['note'],
                        'variants' => $i['variants'],
                        'modifiers' => $i['modifiers'],
                        'extra_modifiers' => $i['extra_modifiers'] ?? []
                    ];
                }, $item['products'] ?? []),
            ];
            usort($new_item['products'], function ($a, $b) {
                return $a['id_product'] <=> $b['id_product'];
            });
            $pos = array_search($new_item, $new_items);
            if ($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['bundling_qty'];
                $id_custom[] = $item['id_custom'] ?? 0;
            } else {
                $item_qtys[$pos] += $item['bundling_qty'];
            }
        }

        // update qty
        foreach ($new_items as $key => &$value) {
            $value['bundling_qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
            $value['bundling_price_total'] = $value['bundling_price_total'] * $item_qtys[$key];
        }

        return $new_items;
    }

    public function getPlasticInfo($plastic, $outlet_plastic_used_status)
    {
        if ((isset($plastic['status']) && $plastic['status'] == 'success') && (isset($outlet_plastic_used_status) && $outlet_plastic_used_status == 'Active')) {
            $result['plastic'] = $plastic['result'];
            $result['plastic']['status'] = $outlet_plastic_used_status;
            $result['plastic']['item'] = array_values(
                array_filter($result['plastic']['item'], function ($item) {
                    return $item['total_used'] > 0;
                })
            );
        } else {
            $result['plastic'] = ['item' => [], 'plastic_price_total' => 0];
            $result['plastic']['status'] = $outlet_plastic_used_status;
        }

        return $result['plastic'];
    }

    public function triggerReversal(Request $request)
    {
        // cari transaksi yang pakai balance, atau split balance, sudah cancelled tapi balance nya tidak balik, & user nya ada
        $trxs = Transaction::select('transactions.id_transaction', 'transactions.id_user', 'transaction_receipt_number', 'transaction_grandtotal', 'log_bayar.balance as bayar', 'log_reversal.balance as reversal')
            ->join('transaction_multiple_payments', function ($join) {
                $join->on('transaction_multiple_payments.id_transaction', 'transactions.id_transaction')
                    ->where('transaction_multiple_payments.type', 'Balance');
            })
            ->join('log_balances as log_bayar', function ($join) {
                $join->on('log_bayar.id_reference', 'transactions.id_transaction')
                    ->whereIn('log_bayar.source', ['Transaction', 'Online Transaction'])
                    ->where('log_bayar.balance', '<', 0);
            })
            ->leftJoin('log_balances as log_reversal', function ($join) {
                $join->on('log_reversal.id_reference', 'transactions.id_transaction')
                    ->whereIn('log_reversal.source', ['Transaction Failed', 'Reversal'])
                    ->where('log_reversal.balance', '>', 0);
            })
            ->join('users', 'users.id', '=', 'transactions.id_user')
            ->where([
                'transaction_payment_status' => 'Cancelled'
            ]);
        $summary = [
            'all_with_point' => 0,
            'already_reversal' => 0,
            'new_reversal' => 0
        ];
        $reversal = [];
        foreach ($trxs->cursor() as $trx) {
            $summary['all_with_point']++;
            if ($trx->reversal) {
                $summary['already_reversal']++;
            } else {
                if (strtolower($request->request_type) == 'reversal') {
                    app($this->balance)->addLogBalance($trx->id_user, abs($trx->bayar), $trx->id_transaction, 'Reversal', $trx->transaction_grandtotal);
                }
                $summary['new_reversal']++;
                $reversal[] = [
                    'id_transaction' => $trx->id_transaction,
                    'receipt_number' => $trx->transaction_receipt_number,
                    'balance_nominal' => abs($trx->bayar),
                    'grandtotal' => $trx->transaction_grandtotal,
                ];
            }
        }
        return [
            'status' => 'success',
            'results' => [
                'type' => strtolower($request->request_type) == 'reversal' ? 'DO REVERSAL' : 'SHOW REVERSAL',
                'summary' => $summary,
                'new_reversal_detail' => $reversal
            ]
        ];
    }

    public function insertBundlingProduct($data, $trx, $outlet, $post, &$productMidtrans, &$userTrxProduct)
    {
        $type = $post['type'];
        $totalWeight = 0;
        foreach ($data as $itemBundling) {
            $dataItemBundling = [
                'id_transaction' => $trx['id_transaction'],
                'id_bundling' => $itemBundling['id_bundling'],
                'id_outlet' => $trx['id_outlet'],
                'transaction_bundling_product_base_price' => $itemBundling['transaction_bundling_product_base_price'],
                'transaction_bundling_product_subtotal' => $itemBundling['transaction_bundling_product_subtotal'],
                'transaction_bundling_product_qty' => $itemBundling['bundling_qty'],
                'transaction_bundling_product_total_discount' => $itemBundling['transaction_bundling_product_total_discount']
            ];

            $createTransactionBundling = TransactionBundlingProduct::create($dataItemBundling);

            if (!$createTransactionBundling) {
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Insert Bundling Product Failed']
                ];
            }

            foreach ($itemBundling['products'] as $itemProduct) {
                $checkProduct = Product::where('id_product', $itemProduct['id_product'])->first();
                if (empty($checkProduct)) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Menu tidak ditemukan ' . $itemProduct['product_name']]
                    ];
                }

                $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $trx['id_outlet']])->first();
                if (!empty($checkDetailProduct) && $checkDetailProduct['product_detail_stock_status'] == 'Sold Out') {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product ' . $checkProduct['product_name'] . ' tidak tersedia dan akan terhapus dari cart.']
                    ];
                }

                if (!isset($itemProduct['note'])) {
                    $itemProduct['note'] = null;
                }

                $productPrice = 0;

                $product = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                    ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                    ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                    ->where('bundling_product.id_bundling_product', $itemProduct['id_bundling_product'])
                    ->select(
                        'products.product_visibility',
                        'pgp.product_global_price',
                        'products.product_variant_status',
                        'bundling_product.*',
                        'bundling.bundling_name',
                        'bundling.bundling_code',
                        'products.*'
                    )
                    ->first();
                $getProductDetail = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                $product['visibility_outlet'] = $getProductDetail['product_detail_visibility'] ?? null;
                $id_product_variant_group = $product['id_product_variant_group'] ?? null;

                if ($product['visibility_outlet'] == 'Hidden' || (empty($product['visibility_outlet']) && $product['product_visibility'] == 'Hidden')) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product ' . $checkProduct['product_name'] . 'pada ' . $product['bundling_name'] . ' tidak tersedia']
                    ];
                }

                if ($product['product_variant_status'] && !empty($product['id_product_variant_group'])) {
                    $checkAvailable = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                    if ($checkAvailable['product_variant_group_visibility'] == 'Hidden') {
                        DB::rollback();
                        return [
                            'status'    => 'fail',
                            'product_sold_out_status' => true,
                            'messages'  => ['Product ' . $checkProduct['product_name'] . 'pada ' . $product['bundling_name'] . ' tidak tersedia']
                        ];
                    } else {
                        if ($outlet['outlet_different_price'] == 1) {
                            $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price'] ?? 0;
                        } else {
                            $price = $checkAvailable['product_variant_group_price'] ?? 0;
                        }
                    }
                } elseif (!empty($product['id_product'])) {
                    if ($outlet['outlet_different_price'] == 1) {
                        $price = ProductSpecialPrice::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price'] ?? 0;
                    } else {
                        $price = $product['product_global_price'];
                    }
                }

                $price = (float)$price ?? 0;
                //calculate discount produk
                if (strtolower($product['bundling_product_discount_type']) == 'nominal') {
                    $calculate = ($price - $product['bundling_product_discount']);
                } else {
                    $discount = $price * ($product['bundling_product_discount'] / 100);
                    $discount = ($discount > $product['bundling_product_maximum_discount'] &&  $product['bundling_product_maximum_discount'] > 0 ? $product['bundling_product_maximum_discount'] : $discount);
                    $calculate = ($price - $discount);
                }

                $dataProduct = [
                    'id_transaction'               => $trx['id_transaction'],
                    'id_product'                   => $checkProduct['id_product'],
                    'type'                         => $checkProduct['product_type'],
                    'id_product_variant_group'     => $itemProduct['id_product_variant_group'] ?? null,
                    'id_brand'                     => $itemProduct['id_brand'],
                    'id_outlet'                    => $trx['id_outlet'],
                    'id_user'                      => $trx['id_user'],
                    'transaction_product_qty'      => $itemProduct['product_qty'] * $itemBundling['bundling_qty'],
                    'transaction_product_bundling_qty' => $itemProduct['product_qty'],
                    'transaction_product_price'    => $itemProduct['transaction_product_price'],
                    'transaction_product_bundling_price' => $calculate,
                    'transaction_product_price_base' => null,
                    'transaction_product_price_tax'  => null,
                    'transaction_product_discount'   => 0,
                    'transaction_product_discount_all'   => $itemProduct['transaction_product_discount_all'],
                    'transaction_product_bundling_price'   => $itemProduct['transaction_product_bundling_price'],
                    'transaction_product_base_discount' => 0,
                    'transaction_product_qty_discount'  => 0,
                    'transaction_product_subtotal' => $itemProduct['transaction_product_subtotal'],
                    'transaction_product_net' => $itemProduct['transaction_product_net'],
                    'transaction_variant_subtotal' => $itemProduct['transaction_variant_subtotal'],
                    'transaction_product_note'     => $itemProduct['note'],
                    'id_transaction_bundling_product' => $createTransactionBundling['id_transaction_bundling_product'],
                    'id_bundling_product' => $itemProduct['id_bundling_product'],
                    'transaction_product_bundling_discount' => $itemProduct['transaction_product_bundling_discount'],
                    'transaction_product_bundling_charged_outlet' => $itemProduct['transaction_product_bundling_charged_outlet'],
                    'transaction_product_bundling_charged_central' => $itemProduct['transaction_product_bundling_charged_central'],
                    'created_at'                   => date('Y-m-d', strtotime($trx['transaction_date'])) . ' ' . date('H:i:s'),
                    'updated_at'                   => date('Y-m-d H:i:s')
                ];

                $trx_product = TransactionProduct::create($dataProduct);
                if (!$trx_product) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Insert Product Transaction Failed']
                    ];
                }
                if (strtotime($trx['transaction_date'])) {
                    $trx_product->created_at = strtotime($trx['transaction_date']);
                }
                $insert_modifier = [];
                $mod_subtotal = 0;
                $more_mid_text = '';
                $selectExtraModifier = ProductModifier::whereIn('id_product_modifier', $itemProduct['extra_modifiers'] ?? [])->get()->toArray();
                $mergetExtranAndModifier = array_merge($selectExtraModifier, $itemProduct['modifiers'] ?? []);
                if (isset($mergetExtranAndModifier)) {
                    foreach ($mergetExtranAndModifier as $modifier) {
                        $id_product_modifier = is_numeric($modifier) ? $modifier : $modifier['id_product_modifier'];
                        $qty_product_modifier = 1;
                        if (isset($modifier['qty'])) {
                            $qty_product_modifier = is_numeric($modifier) ? 1 : $modifier['qty'];
                        }

                        $mod = ProductModifier::select(
                            'product_modifiers.id_product_modifier',
                            'code',
                            DB::raw('(CASE
                        WHEN product_modifiers.text_detail_trx IS NOT NULL 
                        THEN product_modifiers.text_detail_trx
                        ELSE product_modifiers.text
                    END) as text'),
                            'product_modifier_stock_status',
                            \DB::raw('coalesce(product_modifier_price, 0) as product_modifier_price'),
                            'id_product_modifier_group',
                            'modifier_type'
                        )
                            // product visible
                            ->leftJoin('product_modifier_details', function ($join) use ($post) {
                                $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                                    ->where('product_modifier_details.id_outlet', $post['id_outlet']);
                            })
                            ->where(function ($query) {
                                $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                                    ->orWhere(function ($q) {
                                        $q->whereNull('product_modifier_details.product_modifier_visibility')
                                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                                    });
                            })
                            ->where(function ($q) {
                                $q->where(function ($q) {
                                    $q->where('product_modifier_stock_status', 'Available')->orWhereNull('product_modifier_stock_status');
                                })->orWhere('product_modifiers.modifier_type', '=', 'Modifier Group');
                            })
                            ->where(function ($q) {
                                $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
                            })
                            ->groupBy('product_modifiers.id_product_modifier');
                        if ($outlet['outlet_different_price']) {
                            $mod->leftJoin('product_modifier_prices', function ($join) use ($post) {
                                $join->on('product_modifier_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
                                $join->where('product_modifier_prices.id_outlet', $post['id_outlet']);
                            });
                        } else {
                            $mod->leftJoin('product_modifier_global_prices', function ($join) use ($post) {
                                $join->on('product_modifier_global_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
                            });
                        }
                        $mod = $mod->find($id_product_modifier);
                        if (!$mod) {
                            return [
                                'status' => 'fail',
                                'messages' => ['Modifier not found']
                            ];
                        }
                        $mod = $mod->toArray();
                        $insert_modifier[] = [
                            'id_transaction_product' => $trx_product['id_transaction_product'],
                            'id_transaction' => $trx['id_transaction'],
                            'id_product' => $checkProduct['id_product'],
                            'id_product_modifier' => $id_product_modifier,
                            'id_product_modifier_group' => $mod['modifier_type'] == 'Modifier Group' ? $mod['id_product_modifier_group'] : null,
                            'id_outlet' => $trx['id_outlet'],
                            'id_user' => $trx['id_user'],
                            'type' => $mod['type'] ?? '',
                            'code' => $mod['code'] ?? '',
                            'text' => $mod['text'] ?? '',
                            'qty' => $qty_product_modifier,
                            'transaction_product_modifier_price' => $mod['product_modifier_price'] * $qty_product_modifier,
                            'datetime' => $trx['transaction_date'] ?? date(),
                            'trx_type' => $type,
                            'created_at'                   => date('Y-m-d H:i:s'),
                            'updated_at'                   => date('Y-m-d H:i:s')
                        ];
                        $mod_subtotal += $mod['product_modifier_price'] * $qty_product_modifier;
                        if ($qty_product_modifier > 1) {
                            $more_mid_text .= ',' . $qty_product_modifier . 'x ' . $mod['text'];
                        } else {
                            $more_mid_text .= ',' . $mod['text'];
                        }
                    }
                }

                $trx_modifier = TransactionProductModifier::insert($insert_modifier);
                if (!$trx_modifier) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Insert Product Modifier Transaction Failed']
                    ];
                }
                $insert_variants = [];
                foreach ($itemProduct['trx_variants'] as $id_product_variant => $product_variant_price) {
                    $insert_variants[] = [
                        'id_transaction_product' => $trx_product['id_transaction_product'],
                        'id_product_variant' => $id_product_variant,
                        'transaction_product_variant_price' => $product_variant_price,
                        'created_at'                   => date('Y-m-d H:i:s'),
                        'updated_at'                   => date('Y-m-d H:i:s')
                    ];
                }

                $trx_variants = TransactionProductVariant::insert($insert_variants);
                $trx_product->transaction_modifier_subtotal = $mod_subtotal;
                $trx_product->save();
                $dataProductMidtrans = [
                    'id'       => $checkProduct['id_product'],
                    'price'    => $calculate + $mod_subtotal,
                    'name'     => $checkProduct['product_name'],
                    'quantity' => $itemBundling['bundling_qty'],
                ];
                array_push($productMidtrans, $dataProductMidtrans);
                $totalWeight += $checkProduct['product_weight'] * 1;

                $dataUserTrxProduct = [
                    'id_user'       => $trx['id_user'],
                    'id_product'    => $checkProduct['id_product'],
                    'product_qty'   => 1,
                    'last_trx_date' => $trx['transaction_date']
                ];
                array_push($userTrxProduct, $dataUserTrxProduct);
            }
        }

        return [
            'status'    => 'success'
        ];
    }

    public function syncDataSubtotal(Request $request)
    {
        $post = $request->json()->all();
        $dateStart = date('Y-m-d', strtotime($post['date_start']));
        $dateEnd = date('Y-m-d', strtotime($post['date_end']));

        $data = Transaction::whereDate('transaction_date', '>=', $dateStart)
            ->whereDate('transaction_date', '<=', $dateEnd)
            ->get()->toArray();

        foreach ($data as $dt) {
            $trxDiscount = $dt['transaction_discount'];
            $discountBill = 0;
            $totalDicountItem = [];
            $subtotalFinal = [];
            $prods = TransactionProduct::where('id_transaction', $dt['id_transaction'])->get()->toArray();

            foreach ($prods as $prod) {
                if (is_null($prod['id_transaction_bundling_product'])) {
                    $dtUpdateTrxProd = [
                        'transaction_product_net' => $prod['transaction_product_subtotal'] - $prod['transaction_product_discount'],
                        'transaction_product_discount_all' => $prod['transaction_product_discount']
                    ];
                    TransactionProduct::where('id_transaction_product', $prod['id_transaction_product'])->update($dtUpdateTrxProd);
                    array_push($totalDicountItem, $prod['transaction_product_discount']);
                    array_push($subtotalFinal, $prod['transaction_product_subtotal']);
                } else {
                    $bundlingQty = $prod['transaction_product_bundling_qty'];
                    if ($bundlingQty == 0) {
                        $bundlingQty = $prod['transaction_product_qty'];
                    }
                    $perItem = $prod['transaction_product_subtotal'] / $bundlingQty;
                    $productSubtotalFinal = $perItem * $prod['transaction_product_qty'];
                    $productSubtotalFinalNoDiscount = ($perItem + $prod['transaction_product_bundling_discount']) * $prod['transaction_product_qty'];
                    $discount = $prod['transaction_product_bundling_discount'] * $prod['transaction_product_qty'];
                    $dtUpdateTrxProd = [
                        'transaction_product_net' => $productSubtotalFinal,
                        'transaction_product_discount_all' => $discount
                    ];
                    array_push($totalDicountItem, $discount);
                    array_push($subtotalFinal, $productSubtotalFinalNoDiscount);
                    TransactionProduct::where('id_transaction_product', $prod['id_transaction_product'])->update($dtUpdateTrxProd);
                }
            }

            if (empty($totalDicountItem)) {
                $discountBill = $trxDiscount;
            }
            $dtUpdateTrx = [
                'transaction_gross' => array_sum($subtotalFinal),
                'transaction_discount_item' => array_sum($totalDicountItem),
                'transaction_discount_bill' => $discountBill
            ];
            Transaction::where('id_transaction', $dt['id_transaction'])->update($dtUpdateTrx);
        }

        return 'success';
    }

    public function listAvailableDelivery(Request $request)
    {
        $post = $request->json()->all();
        $setting  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
        $setting_default = Setting::where('key', 'default_delivery')->first()->value ?? null;
        $delivery = [];

        foreach ($setting as $value) {
            if (!empty($post['all'])) {
                if (!empty($value['logo'])) {
                    $value['logo'] = $value['logo'];
                }

                $delivery[] = $value;
            } elseif ($value['show_status'] == 1) {
                if (!empty($value['logo'])) {
                    $value['logo'] = $value['logo'];
                }

                $delivery[] = $value;
            }
        }

        usort($delivery, function ($a, $b) {
            return $a['position'] - $b['position'];
        });

        $result = [
            'default_delivery' => $setting_default,
            'delivery' => $delivery
        ];
        return MyHelper::checkGet($result);
    }

    public function availableDeliveryUpdate(Request $request)
    {
        $post = $request->json()->all();
        $availableDelivery  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
        $dtDelivery = $post['delivery'] ?? [];
        foreach ($availableDelivery as $key => $value) {
            $check = array_search($value['delivery_method'], array_column($dtDelivery, 'delivery_method'));
            if ($check !== false) {
                $availableDelivery[$key]['delivery_name'] = $dtDelivery[$check]['delivery_name'];

                foreach ($value['service'] as $index => $s) {
                    $checkService = array_search($s['code'], array_column($dtDelivery[$check]['service'], 'code'));
                    if ($checkService !== false) {
                        $availableDelivery[$key]['service'][$index]['service_name'] = $dtDelivery[$check]['service'][$checkService]['service_name'];
                        $availableDelivery[$key]['service'][$index]['available_status'] = $dtDelivery[$check]['service'][$checkService]['available_status'];
                        $availableDelivery[$key]['service'][$index]['drop_counter_status'] = $dtDelivery[$check]['service'][$checkService]['drop_counter_status'];
                    }
                }
            }
        }

        $update = Setting::where('key', 'available_delivery')->update(['value_text' => json_encode($availableDelivery)]);
        return MyHelper::checkUpdate($update);
    }

    public function mergeNewDelivery($data = [])
    {
        $jsonDecode = json_decode($data);
        if (isset($jsonDecode->data->partners) && !empty($jsonDecode->data->partners)) {
            $availableDelivery  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
            $dataDelivery = (array)$jsonDecode->data->partners;
            foreach ($dataDelivery as $val) {
                if (empty($val)) {
                    continue;
                }

                $check = array_search('wehelpyou_' . $val->courier, array_column($availableDelivery, 'code'));
                if ($check === false) {
                    $availableDelivery[] = [
                        "code" => 'wehelpyou_' . $val->courier,
                        "delivery_name" => ucfirst($val->courier),
                        "delivery_method" => "wehelpyou",
                        "show_status" => 1,
                        "available_status" => 1,
                        "logo" => "",
                        "position" => count($availableDelivery) + 1
                    ];
                }
            }
            $update = Setting::where('key', 'available_delivery')->update(['value_text' => json_encode($availableDelivery)]);
        }
        return true;
    }

    public function setGrandtotalListDelivery($listDelivery, $grandtotal)
    {
        foreach ($listDelivery as $key => $delivery) {
            $listDelivery[$key]['total_payment'] = $grandtotal + $delivery['price'];
        }
        return $listDelivery;
    }

    public function getActiveCourier($listDelivery, $courier)
    {
        foreach ($listDelivery as $delivery) {
            if (
                (empty($courier) && $delivery['disable'] == 0)
                || $delivery['courier'] == $courier
            ) {
                return $delivery;
                break;
            }
        }

        return null;
    }

    public function getCourierName(string $courier)
    {
        foreach ($this->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] as $delivery) {
            if (strpos($delivery['code'], $courier) !== false) {
                $courier = $delivery['delivery_name'];
                break;
            }
        }
        return $courier;
    }

    public function countTranscationPoint($post, $user)
    {
        $post['point'] = app($this->setting_trx)->countTransaction('point', $post);
        $post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);

        $countUserTrx = Transaction::where('id_user', $user['id'])->where('transaction_payment_status', 'Completed')->count();

        $countSettingCashback = TransactionSetting::get();

        if ($countUserTrx < count($countSettingCashback)) {
            $post['cashback'] = $post['cashback'] * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

            if ($post['cashback'] > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                $post['cashback'] = $countSettingCashback[$countUserTrx]['cashback_maximum'];
            }
        } else {
            $maxCash = Setting::where('key', 'cashback_maximum')->first();

            if (count($user['memberships']) > 0) {
                $post['point'] = $post['point'] * ($user['memberships'][0]['benefit_point_multiplier']) / 100;
                $post['cashback'] = $post['cashback'] * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;

                if ($user['memberships'][0]['cashback_maximum']) {
                    $maxCash['value'] = $user['memberships'][0]['cashback_maximum'];
                }
            }

            $statusCashMax = 'no';

            if (!empty($maxCash) && !empty($maxCash['value'])) {
                $statusCashMax = 'yes';
                $totalCashMax = $maxCash['value'];
            }

            if ($statusCashMax == 'yes') {
                if ($totalCashMax < $post['cashback']) {
                    $post['cashback'] = $totalCashMax;
                }
            } else {
                $post['cashback'] = $post['cashback'];
            }
        }
        return [
            'point' => $post['point'] ?? 0,
            'cashback' => $post['cashback'] ?? 0
        ];
    }

    public function showListDelivery($showDelivery, $listDelivery)
    {
        if (empty($listDelivery) || $showDelivery != 1) {
            return $showDelivery;
        }

        $showList = 0;
        foreach ($listDelivery as $val) {
            if ($val['disable']) {
                continue;
            }

            $showList = 1;
            break;
        }

        return $showList;
    }

    public function showListDeliveryPickup($showDelivery, $id_outlet)
    {
        if ($showDelivery != 1) {
            return $showDelivery;
        }

        $listDelivery = $this->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
        $delivery_outlet = DeliveryOutlet::where('id_outlet', $id_outlet)->get();
        $outletSetting = [];
        foreach ($delivery_outlet as $val) {
            $outletSetting[$val['code']] = $val;
        }

        $showList = 0;
        foreach ($listDelivery as $val) {
            if (
                $val['show_status'] != 1
                || $val['available_status'] != 1
                || empty($outletSetting[$val['code']])
                || (isset($outletSetting[$val['code']]) && ($outletSetting[$val['code']]['available_status'] != 1 || $outletSetting[$val['code']]['show_status'] != 1))
            ) {
                continue;
            }

            $showList = 1;
            break;
        }

        return $showList;
    }

    public function cartTransaction(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post)) {
            $itemsCheck = $this->checkDataTransaction($post, 0, 1, 0, [], 0, $request->user()->id);
            $items = $itemsCheck['items'];
            $subtotal = $itemsCheck['subtotal'];
            $dtNeedRecipe = $itemsCheck['data_need_recipe'];
            if (!empty($dtNeedRecipe)) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => $itemsCheck['error_messages'] ?? 'Produk membutuhkan resep',
                    'data_need_recipe' => $dtNeedRecipe
                ]);
            }

            return response()->json(MyHelper::checkGet([
                'items' => $items,
                'subtotal' => 'Rp ' . number_format((int)$subtotal, 0, ",", ".")
            ]));
        } else {
            return response()->json(['status'    => 'fail', 'messages'  => ['Item can not be empty']]);
        }
    }

    public function checkDataTransaction($post, $from_new = 0, $from_cart = 0, $from_check = 0, $dtAddress = [], $fromRecipeDoctor = 0, $id_user = null)
    {
        $items = $this->mergeProducts($post);

        $availableCheckout = true;
        $canBuyStatus = true;
        $subtotal = 0;
        $taxTotal = 0;
        $deliveryPrice = 0;
        $errorMsg = [];
        $weight = [];
        $needRecipeData = [];
        $needRecipeStatus = 0;

        foreach ($items as $index => $value) {
            $errorMsgSubgroup = [];
            $merchant = Merchant::where('id_outlet', $value['id_outlet'])->first();
            $checkOutlet = Outlet::where('id_outlet', $value['id_outlet'])->where('outlet_status', 'Active')->where('outlet_is_closed', 0)->first();
            if (!empty($checkOutlet)) {
                $productSubtotal = 0;
                $weightProduct = 0;
                $dimentionProduct = 0;
                foreach ($value['items'] as $key => $item) {
                    $idWholesaler = null;
                    $idWholesalerVariant = null;
                    $error = '';
                    $product = Product::select('need_recipe_status', 'product_weight', 'product_width', 'product_length', 'product_height', 'id_merchant', 'product_category_name', 'products.id_product_category', 'id_product', 'product_code', 'product_name', 'product_description', 'product_code', 'product_variant_status')
                        ->leftJoin('product_categories', 'product_categories.id_product_category', 'products.id_product_category')
                        ->where('product_visibility', 'Visible')
                        ->where('id_product', $item['id_product'])->first();

                    if (empty($product)) {
                        if (!empty($from_new)) {
                            $errorMsg[] = 'Produk tidak valid';
                        }

                        unset($value['items'][$key]);
                        continue;
                    } else {
                        $product = $product->toArray();
                    }

                    $product['product_price'] = 0;
                    $productGlobalPrice = ProductGlobalPrice::where('id_product', $item['id_product'])->first();
                    if ($productGlobalPrice) {
                        $product['product_price'] = $productGlobalPrice['product_global_price'];
                        $product['product_price_discount'] = $productGlobalPrice['global_price_discount_percent'];
                        $product['product_price_before_discount'] = $productGlobalPrice['global_price_before_discount'];
                    }

                    $product['stock_item'] = 0;
                    $product['variants'] = '';
                    $variantPriceFinal = 0;
                    if ($product['product_variant_status'] && !empty($item['id_product_variant_group'])) {
                        //check visibility
                        $check = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])->where('id_outlet', $value['id_outlet'])->first();
                        if ($check['product_variant_group_visibility' == 'Hidden']) {
                            if (!empty($from_new)) {
                                $errorMsg[] = 'Produk tidak valid';
                            }
                            unset($value['items'][$key]);
                            continue;
                        }
                        $variants = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')
                            ->where('product_variant_visibility', 'Visible')->where('id_product_variant_group', $item['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                        $product['variants'] = implode(', ', $variants);
                        $product['stock_item'] = $check['product_variant_group_stock_item'] ?? 0;
                        $variantPrice = ProductVariantGroup::where('id_product_variant_group', $item['id_product_variant_group'])->first();
                        $variantPriceFinal = $variantPrice['product_variant_group_price'] ?? 0;
                        $product['product_price'] = $variantPrice['product_variant_group_price'] ?? 0;
                        $product['product_price_discount'] = $variantPrice['variant_group_price_discount_percent'];
                        $product['product_price_before_discount'] = $variantPrice['variant_group_price_before_discount'];

                        //check wholesaler
                        $wholesalerVariant = ProductVariantGroupWholesaler::where('id_product_variant_group', $item['id_product_variant_group'])
                            ->where('variant_wholesaler_minimum', '<=', $item['qty'])
                            ->orderBy('variant_wholesaler_minimum', 'desc')
                            ->first();

                        if (!empty($wholesalerVariant)) {
                            $idWholesalerVariant = $wholesalerVariant['id_product_variant_group_wholesaler'];
                            $variantPriceFinal = $wholesalerVariant['variant_wholesaler_unit_price'];
                            $product['product_price'] = $wholesalerVariant['variant_wholesaler_unit_price'];
                            $product['product_price_discount'] = $wholesalerVariant['variant_wholesaler_unit_price_discount_percent'];
                            $product['product_price_before_discount'] = $wholesalerVariant['variant_wholesaler_unit_price_before_discount'];
                            $product['wholesaler_minimum'] = $wholesalerVariant['variant_wholesaler_minimum'];
                        }
                    } else {
                        $product['stock_item'] = ProductDetail::where('id_product', $item['id_product'])->where('id_outlet', $value['id_outlet'])->first()['product_detail_stock_item'] ?? 0;
                    }

                    if (!$product['product_variant_status']) {
                        //check wholesaler
                        $wholesaler = ProductWholesaler::where('id_product', $product['id_product'])
                            ->where('product_wholesaler_minimum', '<=', $item['qty'])
                            ->orderBy('product_wholesaler_minimum', 'desc')
                            ->first();

                        if (!empty($wholesaler)) {
                            $idWholesaler = $wholesaler['id_product_wholesaler'];
                            $product['product_price'] = $wholesaler['product_wholesaler_unit_price'];
                            $product['product_price_discount'] = $wholesaler['wholesaler_unit_price_discount_percent'];
                            $product['product_price_before_discount'] = $wholesaler['wholesaler_unit_price_before_discount'];
                            $product['wholesaler_minimum'] = $wholesaler['product_wholesaler_minimum'];
                        }
                    }

                    $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first()['product_photo'] ?? null;
                    $product['image'] = (empty($image) ? config('url.storage_url_api') . 'img/default.jpg' : config('url.storage_url_api') . $image);

                    if (empty($productGlobalPrice['product_global_price'])) {
                        $error = 'Harga produk tidak valid';
                    }

                    if ($product['need_recipe_status'] == 1) {
                        $idUser = request()->user()->id;
                        if (!empty($item['id_transaction_consultation'])) {
                            $checkRecipe = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                                ->whereNotNull('completed_at')
                                ->where('transaction_consultation_recomendations.id_transaction_consultation', $item['id_transaction_consultation'])
                                ->where('product_type', 'Drug')->where('id_product', $product['id_product'])->get()->toArray();
                        } else {
                            $checkRecipe = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                                ->whereNotNull('completed_at')
                                ->whereNotIn('consultation_status', ['canceled'])
                                ->where('id_user', $idUser)->where('product_type', 'Drug')->where('id_product', $product['id_product'])
                                ->get()->toArray();
                        }

                        if (empty($checkRecipe)) {
                            $canBuyStatus = false;
                            $error = 'Produk membutuhkan resep, silahkan lakukan konsultasi terlebih dahulu';
                            if (!empty($from_cart)) {
                                $errorMsg[] = 'Produk ' . $product['product_name'] . ' membutuhkan resep';
                                $needRecipeData = [
                                    'id_product' => $product['id_product'],
                                    'product_name' => $product['product_name'],
                                    'product_code' => $product['product_code']
                                ];
                                unset($value['items'][$key]);
                                continue;
                            }
                        } else {
                            $qtyCanBuy = 0;
                            foreach ($checkRecipe as $valueRecipe) {
                                $maxQty = ($valueRecipe['recipe_redemption_limit'] ?? 0) * ($valueRecipe['qty_product'] ?? 0);
                                $calculate = $maxQty - $valueRecipe['qty_product_redeem'];
                                $qtyCanBuy = $qtyCanBuy + $calculate;
                            }

                            if ($item['qty'] > $qtyCanBuy) {
                                $canBuyStatus = false;
                                $error = 'Produk membutuhkan resep, silahkan lakukan konsultasi terlebih dahulu';
                                if (!empty($from_cart)) {
                                    $errorMsg[] = 'Produk ' . $product['product_name'] . ' membutuhkan resep';
                                    $needRecipeData = [
                                        'id_product' => $product['id_product'],
                                        'product_name' => $product['product_name'],
                                        'product_code' => $product['product_code']
                                    ];
                                    unset($value['items'][$key]);
                                    continue;
                                }
                            }
                        }
                    }

                    if ($item['qty'] > $product['stock_item']) {
                        $error = 'Jumlah item yang Anda pilih melebihi batas maksimal stock';
                    }

                    if ($item['qty'] < 1) {
                        $error = 'Jumlah item tidak valid';
                    }

                    if ($product['product_price'] <= 0) {
                        $error = 'Produk tidak valid';
                    }

                    if (!empty($product['product_weight'])) {
                        $w = ($product['product_weight'] * $item['qty']) / 1000;
                        $weight[] = $w;
                        $weightProduct = $weightProduct + $w;
                        $dimentionProduct = $dimentionProduct + ($product['product_width'] * $product['product_height'] * $product['product_length'] * $item['qty']);
                    } else {
                        $error = 'Produk tidak valid';
                    }

                    if ($id_user == $merchant['id_user']) {
                        $error = 'Tidak bisa membeli produk sendiri';
                    }

                    $totalPrice = (int)$product['product_price'] * $item['qty'];
                    $productSubtotal = $productSubtotal + (int)$totalPrice;
                    $value['items'][$key] = [
                        "id_product" => $item['id_product'],
                        "product_category_name" => $product['product_category_name'],
                        "product_name" => $product['product_name'],
                        "product_base_price" => (int)$productGlobalPrice['product_global_price'],
                        "product_price" => (int)$product['product_price'],
                        "product_price_text" => 'Rp ' . number_format((int)$product['product_price'], 0, ",", "."),
                        "product_discount" => $product['product_price_discount'],
                        "product_price_before_discount" => $product['product_price_before_discount'],
                        "product_price_before_discount_text" => 'Rp ' . number_format($product['product_price_before_discount'], 0, ",", "."),
                        "product_price_subtotal" => (int)$totalPrice,
                        "product_price_subtotal_text" => 'Rp ' . number_format((int)$totalPrice, 0, ",", "."),
                        "product_variant_price" => (int)$variantPriceFinal,
                        "variants" => $product['variants'],
                        "id_product_variant_group" => $item['id_product_variant_group'],
                        "note" => $item['note'],
                        "qty" => $item['qty'],
                        "current_stock" => $product['stock_item'],
                        "id_custom" => $item['id_custom'],
                        "id_product_wholesaler" => $idWholesaler ?? null,
                        "id_product_variant_group_wholesaler" => $idWholesalerVariant ?? null,
                        "wholesaler_minimum" => $product['wholesaler_minimum'] ?? null,
                        "need_recipe_status" => $product['need_recipe_status'],
                        "can_buy_status" => $canBuyStatus,
                        "image" => $product['image'],
                        "error_message" => $error
                    ];

                    $idBrand = BrandProduct::where('id_product', $item['id_product'])->first()['id_brand'] ?? null;
                    if (!empty($idBrand)) {
                        $value['items'][$key]['id_brand'] = $idBrand;
                    }

                    if (!empty($error)) {
                        $errorMsg[] = $error;
                    }

                    if ($item['qty'] > $product['stock_item']) {
                        $availableCheckout = false;
                    }

                    if ($from_check == 1 && !empty($error)) {
                        unset($value['items'][$key]);
                        continue;
                    }
                }

                if (!empty($value['items'])) {
                    $subtotal = $subtotal + $productSubtotal;

                    $s = round($dimentionProduct ** (1 / 3), 0);
                    $items[$index]['outlet_name'] = $checkOutlet['outlet_name'];
                    $items[$index]['items_total_height'] = (int)$s;
                    $items[$index]['items_total_width'] = (int) $s;
                    $items[$index]['items_total_length'] = (int) $s;
                    $items[$index]['items_total_weight'] = (int) ceil($weightProduct);
                    $items[$index]['items_subtotal'] = $productSubtotal;
                    $items[$index]['items_subtotal_text'] = 'Rp ' . number_format($productSubtotal, 0, ",", ".");
                    $items[$index]['items'] = array_values($value['items']);

                    if ($from_cart == 0) {
                        $subdistrictOutlet = Subdistricts::where('id_subdistrict', $checkOutlet['id_subdistrict'])
                            ->join('districts', 'districts.id_district', 'subdistricts.id_district')->first();
                        if (empty($subdistrictOutlet)) {
                            $errorMsg[] = 'Address toko tidak valid';
                            $errorMsgSubgroup[] = 'Address toko tidak valid';
                            if ($from_new == 1) {
                                unset($value['items'][$key]);
                                continue;
                            }
                        }
                        $latOutlet = $subdistrictOutlet['subdistrict_latitude'];
                        $lngOutlet = $subdistrictOutlet['subdistrict_longitude'];

                        $subdistrictCustomer = Subdistricts::where('id_subdistrict', $dtAddress['id_subdistrict'])
                                    ->join('districts', 'districts.id_district', 'subdistricts.id_district')->first();
                        if (empty($subdistrictCustomer)) {
                            $errorMsg[] = 'Address tidak valid';
                            $errorMsgSubgroup[] = 'Address tidak valid';
                            if ($from_new == 1) {
                                unset($value['items'][$key]);
                                continue;
                            }
                        }
                        $latCustomer = $subdistrictCustomer['subdistrict_latitude'];
                        $lngCustomer = $subdistrictCustomer['subdistrict_longitude'];

                        $dtDeliveryPrice = [
                            "cod" =>  false,
                            "for_order" => true,
                            "destination" => [
                                "area_id" => $subdistrictCustomer['id_subdistrict_external'],
                                "lat" => $latCustomer,
                                "lng" => $lngCustomer,
                                "suburb_id" => $subdistrictCustomer['id_district_external']
                            ],
                            "origin" => [
                                "area_id" => $subdistrictOutlet['id_subdistrict_external'],
                                "lat" => $latOutlet,
                                "lng" => $lngOutlet,
                                "suburb_id" => $subdistrictOutlet['id_district_external']
                            ],
                            "weight" => (int) ceil($weightProduct),
                            "height" => (int)$s,
                            "width" => (int)$s,
                            "length" => (int)$s,
                            "item_value" => $productSubtotal,
                            "limit" => 100,
                            "sort_by" => [
                                "final_price"
                            ],
                        ];

                        $availableDelivery = app($this->merchant)->availableDelivery($value['id_outlet'], 1);
                        $shipper = new Shipper();
                        $getDeliveryPrice = $shipper->sendRequest('Get Price', 'POST', 'pricing/domestic', $dtDeliveryPrice);
                        $deliveryPriceList = $shipper->listPrice($getDeliveryPrice['response']['data'] ?? [], $availableDelivery);

                        $items[$index]['available_delivery'] = $deliveryPriceList;
                        $items[$index]['delivery'] = $value['delivery'] ?? [];

                        if (!empty($value['delivery']['rate_id'])) {
                            foreach ($deliveryPriceList as $shipmentCheck) {
                                foreach ($shipmentCheck['service'] as $service) {
                                    if ($service['rate_id'] == $value['delivery']['rate_id']) {
                                        $shipmentRateID = $service['rate_id'];
                                        $shipmentCode = $service['code'];
                                        $shipmentCourier = $shipmentCheck['delivery_method'];
                                        $shipmentCourierService = $shipmentCheck['delivery_name'] . ' ' . $service['service_name'];
                                        $shipmentInsuranceStatus = (($value['delivery']['insurance_status'] ?? false) == true ? 1 : 0);
                                        if ($service['must_use_insurance'] == true) {
                                            $shipmentInsuranceStatus = 1;
                                        }
                                        $shipmentInsurancePrice = $service['insurance_fee'];
                                        $shipmentPrice = $service['price'];
                                        if ($shipmentInsuranceStatus == 1) {
                                            $shipmentPrice = $shipmentPrice + $shipmentInsurancePrice;
                                        }
                                    }
                                }
                            }
                            $deliveryPrice = $deliveryPrice + ($shipmentPrice ?? 0);
                        }

                        $items[$index]['subtotal'] = $productSubtotal;
                        $items[$index]['subtotal_text'] = 'Rp ' . number_format($productSubtotal, 0, ",", ".");

                        if (!empty($shipmentRateID)) {
                            $items[$index]['delivery_price'] = [
                                'rate_id' => $shipmentRateID,
                                'shipment_code' => $shipmentCode,
                                'shipment_courier' => $shipmentCourier,
                                'shipment_courier_service' => $shipmentCourierService,
                                'shipment_insurance_status' => $shipmentInsurancePrice,
                                'shipment_price' => $shipmentPrice,
                                'shipment_price_text' => 'Rp ' . number_format($shipmentPrice, 0, ",", ".")
                            ];

                            $items[$index]['subtotal'] = $productSubtotal + $shipmentPrice;
                            $items[$index]['subtotal_text'] = 'Rp ' . number_format(($productSubtotal + $shipmentPrice), 0, ",", ".");
                        }

                        if (empty($deliveryPriceList)) {
                            $errorMsg[] = 'Kurir tidak available';
                            $errorMsgSubgroup[] = 'Kurir tidak available';
                            if ($from_new == 1) {
                                unset($value['items'][$key]);
                                continue;
                            }
                        }
                        $items[$index]['error_messages'] = implode('. ', array_unique($errorMsgSubgroup));
                    }
                } else {
                    if (empty($errorMsg)) {
                        $errorMsg[] = 'Stock produk habis';
                    }
                    unset($items[$index]);
                    continue;
                }
            } else {
                $errorMsg[] = 'Outlet tidak ditemukan';
                unset($items[$index]);
                continue;
            }
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $taxTotal,
            'items' => array_values($items),
            'total_delivery' => $deliveryPrice,
            'available_checkout' => $availableCheckout,
            'error_messages' => implode('. ', array_unique($errorMsg)),
            'weight' => array_sum($weight),
            'pupop_need_consultation' => ($canBuyStatus ? false : true),
            'data_need_recipe' => $needRecipeData
        ];
    }

    public function mergeProducts($items)
    {
        $tmp = [];
        foreach ($items as $value) {
            if (!empty($value['id_outlet'])) {
                $tmp[$value['id_outlet']]['id_outlet'] = $value['id_outlet'] ?? null;
                $tmp[$value['id_outlet']]['delivery'] = $value['delivery'] ?? '';
                $tmp[$value['id_outlet']]['items'] = array_merge($tmp[$value['id_outlet']]['items'] ?? [], $value['items']);
            }
        }
        $items = array_values($tmp);

        // create unique array
        foreach ($items as $index => $val) {
            $new_items = [];
            $item_qtys = [];
            $id_custom = [];

            foreach ($val['items'] as $item) {
                $new_item = [
                    'id_product' => $item['id_product'],
                    'id_product_variant_group' => $item['id_product_variant_group'] ?? null,
                    'id_product_variant_group_wholesaler' => $item['id_product_variant_group_wholesaler'] ?? null,
                    'id_product_wholesaler' => $item['id_product_wholesaler'] ?? null,
                    'note' => $item['note'],
                    'id_transaction_consultation' => $item['id_transaction_consultation'] ?? null
                ];
                $pos = array_search($new_item, $new_items);
                if ($pos === false) {
                    $new_items[] = $new_item;
                    $item_qtys[] = $item['qty'];
                    $id_custom[] = $item['id_custom'] ?? 0;
                } else {
                    $item_qtys[$pos] += $item['qty'];
                }
            }

            // update qty
            foreach ($new_items as $key => &$value) {
                $value['qty'] = $item_qtys[$key];
                $value['id_custom'] = $id_custom[$key];
            }

            $items[$index]['items'] = $new_items;
        }

        return $items;
    }

    public function updateStockProduct($id_transaction, $action)
    {
        $transaction = Transaction::where('id_transaction', $id_transaction)->first();
        $transactionProducts = TransactionProduct::where('id_transaction', $id_transaction)->get()->toArray();
        foreach ($transactionProducts as $product) {
            if (!empty($product['id_product_variant_group'])) {
                $currentStock = ProductVariantGroupDetail::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $product['id_outlet'])->first();
                if ($action == 'book') {
                    $stockItem = $currentStock['product_variant_group_stock_item'] - $product['transaction_product_qty'];
                    $statusStock = $currentStock['product_variant_group_stock_status'];
                    if ($stockItem <= 0) {
                        $statusStock = 'Sold Out';
                    }
                } else {
                    $stockItem = $currentStock['product_variant_group_stock_item'] + $product['transaction_product_qty'];
                    $statusStock = 'Available';
                }


                ProductVariantGroupDetail::where('id_product_variant_group_detail', $currentStock['id_product_variant_group_detail'])
                    ->update(['product_variant_group_stock_status' => $statusStock, 'product_variant_group_stock_item' => $stockItem]);
            } else {
                $currentStock = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $product['id_outlet'])->first();
                if ($action == 'book') {
                    $stockItem = $currentStock['product_detail_stock_item'] - $product['transaction_product_qty'];
                    $statusStock = $currentStock['product_detail_stock_status'];
                    if ($stockItem <= 0) {
                        $statusStock = 'Sold Out';
                    }
                } else {
                    $stockItem = $currentStock['product_detail_stock_item'] + $product['transaction_product_qty'];
                    $statusStock = 'Available';
                }

                ProductDetail::where('id_product_detail', $currentStock['id_product_detail'])
                    ->update(['product_detail_stock_status' => $statusStock, 'product_detail_stock_item' => $stockItem]);
            }

            $checkProduct = Product::where('id_product', $product['id_product'])->first();
            if ($action == 'book' && $checkProduct['need_recipe_status'] == 1) {
                if (!empty($transaction['id_transaction_consultation'])) {
                    $getQtyDrug = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                        ->whereNotNull('completed_at')
                        ->where('transaction_consultation_recomendations.id_transaction_consultation', $transaction['id_transaction_consultation'])
                        ->where('product_type', 'Drug')->where('id_product', $checkProduct['id_product'])->get()->toArray();
                } else {
                    $getQtyDrug = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                        ->whereNotNull('completed_at')
                        ->whereNotIn('consultation_status', ['canceled'])
                        ->where('id_user', $product['id_user'])
                        ->where('product_type', 'Drug')->where('id_product', $checkProduct['id_product'])
                        ->orderBy('recipe_redemption_limit', 'desc')
                        ->get()->toArray();
                }

                if (!empty($getQtyDrug)) {
                    $qtyTrxProduct = $product['transaction_product_qty'];

                    foreach ($getQtyDrug as $value) {
                        if ($qtyTrxProduct <= 0) {
                            continue;
                        }
                        $maxQty = ($value['recipe_redemption_limit'] ?? 0) * ($value['qty_product'] ?? 0);
                        $calculateQty = $maxQty - $value['qty_product_redeem'];
                        $finalQty = ($calculateQty >= $qtyTrxProduct ? $qtyTrxProduct : $calculateQty);
                        $curretQtyDrug = $value['qty_product_redeem'];
                        $updateReedemQty = $curretQtyDrug + $finalQty;
                        $currentQtyCounter = $value['qty_product_counter'];
                        $updateQtyCounter = ($finalQty > $currentQtyCounter ? 0 : $currentQtyCounter - $finalQty);

                        TransactionConsultationRecomendation::where('id_transaction_consultation_recomendation', $value['id_transaction_consultation_recomendation'])
                            ->update(['qty_product_redeem' => $updateReedemQty, 'qty_product_counter' => $updateQtyCounter]);
                        TransactionProductConsultationRedeem::updateOrCreate([
                                'id_transaction_product' => $product['id_transaction_product'],
                                'id_transaction_consultation_recomendation' => $value['id_transaction_consultation_recomendation']
                            ], [
                                'qty' => $finalQty,
                                'created_at' => date('Y-m-d H:i:s'),
                                'update_at' => date('Y-m-d H:i:s'),
                            ]);

                        //update counter redeem recipe
                        $dataRecomend = TransactionConsultationRecomendation::where('id_transaction_consultation', $value['id_transaction_consultation'])
                            ->where('product_type', 'Drug')->get()->toArray();
                        $sumOriginalQty = 0;
                        $sumAllRedeem = 0;
                        foreach ($dataRecomend as $rec) {
                            $sumOriginalQty = $sumOriginalQty + $rec['qty_product'];
                            $sumAllRedeem = $sumAllRedeem + $rec['qty_product_redeem'];
                        }

                        $totalRedeem = (int)($sumAllRedeem / $sumOriginalQty);
                        TransactionConsultation::where('id_transaction_consultation', $value['id_transaction_consultation'])->update(['recipe_redemption_counter' => $totalRedeem]);

                        if ($totalRedeem < $value['recipe_redemption_limit']) {
                            $qtyAllDrug = TransactionConsultationRecomendation::where('id_transaction_consultation', $value['id_transaction_consultation'])->get()->toArray();
                            foreach ($qtyAllDrug as $dt) {
                                TransactionConsultationRecomendation::where('id_transaction_consultation_recomendation', $dt['id_transaction_consultation_recomendation'])->update(['qty_product_counter' => $dt['qty_product']]);
                            }
                        }

                        $qtyTrxProduct = $qtyTrxProduct - $finalQty;
                    }
                }
            } elseif ($action == 'cancel') {
                $getFromProductConsultation = TransactionProductConsultationRedeem::where('id_transaction_product', $product['id_transaction_product'])->get()->toArray();
                foreach ($getFromProductConsultation as $con) {
                    $getQtyDrug = TransactionConsultation::join('transaction_consultation_recomendations', 'transaction_consultation_recomendations.id_transaction_consultation', 'transaction_consultations.id_transaction_consultation')
                        ->where('id_transaction_consultation_recomendation', $con['id_transaction_consultation_recomendation'])->first();

                    if (!empty($getQtyDrug)) {
                        $minusQtyRedeem = $getQtyDrug['qty_product_redeem'] - $con['qty'];
                        $minusQtyRedeem = ($minusQtyRedeem < 0 ? 0 : $minusQtyRedeem);
                        $currentQtyCounter = $getQtyDrug['qty_product_counter'];
                        $updateQtyCounter = ($con['qty'] > $getQtyDrug['qty_product'] ? $getQtyDrug['qty_product'] : $currentQtyCounter + $con['qty']);

                        $dtUpdate = [
                            'qty_product_redeem' => $minusQtyRedeem,
                            'qty_product_counter' => $updateQtyCounter
                        ];

                        TransactionConsultationRecomendation::where('id_transaction_consultation_recomendation', $getQtyDrug['id_transaction_consultation_recomendation'])
                            ->update($dtUpdate);

                        $dataRecomend = TransactionConsultationRecomendation::where('id_transaction_consultation', $getQtyDrug['id_transaction_consultation'])
                            ->where('product_type', 'Drug')->get()->toArray();
                        $sumOriginalQty = 0;
                        $sumAllRedeem = 0;
                        foreach ($dataRecomend as $rec) {
                            $sumOriginalQty = $sumOriginalQty + $rec['qty_product'];
                            $sumAllRedeem = $sumAllRedeem + $rec['qty_product_redeem'];
                        }

                        $totalRedeem = (int)($sumAllRedeem / $sumOriginalQty);
                        $minusRedeem = ($totalRedeem < 0 ? 0 : $totalRedeem);
                        TransactionConsultation::where('id_transaction_consultation', $getQtyDrug['id_transaction_consultation'])
                            ->update(['recipe_redemption_counter' => $minusRedeem]);
                    }

                    TransactionProductConsultationRedeem::where('id_transaction_product_consultation_redeem', $con['id_transaction_product_consultation_redeem'])->delete();
                }
            }
        }

        return true;
    }

    public function rejectPayment($data)
    {
        $user = User::where('id', $data['id_user'])->first();
        $multiple = TransactionMultiplePayment::where('id_transaction_group', $data['id_transaction_group'])->get()->toArray();
        $trxGroup = TransactionGroup::where('id_transaction_group', $data['id_transaction_group'])->first();
        if ($multiple) {
            foreach ($multiple as $pay) {
                if ($pay['type'] == 'Balance') {
                    $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                    if ($payBalance) {
                        $refund = app($this->balance)->addLogBalance($user->id, (int)$payBalance['balance_nominal'], $trxGroup['id_transaction_group'], 'Rejected Order Group', $payBalance['balance_nominal']);
                        if ($refund == false) {
                            return false;
                        }
                    }
                } else {
                    $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                    if ($payMidtrans) {
                        $doRefundPayment = MyHelper::setting('refund_midtrans');
                        if ($doRefundPayment) {
                            $refund = Midtrans::refund($payMidtrans['vt_transaction_id'], ['reason' => $post['reason'] ?? '']);

                            if ($refund['status'] != 'success') {
                                $data->update(['failed_void_reason' => $refund['messages'] ?? []]);
                                $data->update(['need_manual_void' => 1]);
                                $order2 = clone $data;
                                $order2->payment_method = 'Midtrans';
                                $order2->payment_detail = $payMidtrans['payment_type'];
                                $order2->manual_refund = $payMidtrans['gross_amount'];
                                $order2->payment_reference_number = $payMidtrans['vt_transaction_id'];
                                if ($shared['reject_batch'] ?? false) {
                                    $shared['void_failed'][] = $order2;
                                } else {
                                    $variables = [
                                        'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                    ];
                                    app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $user->phone, $variables, null, true);
                                }

                                return false;
                            }
                        }
                    }
                }
            }
        } else {
            $payMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $data['id_transaction_group'])->first();
            if ($payMidtrans) {
                $doRefundPayment = MyHelper::setting('refund_midtrans');
                if ($doRefundPayment) {
                    $refund = Midtrans::refund($payMidtrans['vt_transaction_id'], ['reason' => $post['reason'] ?? '']);
                    if ($refund['status'] != 'success') {
                        $data->update(['need_manual_void' => 1]);
                        $order2 = clone $data;
                        $order2->payment_method = 'Midtrans';
                        $order2->payment_detail = $payMidtrans['payment_type'];
                        $order2->manual_refund = $payMidtrans['gross_amount'];
                        $order2->payment_reference_number = $payMidtrans['vt_transaction_id'];
                        if ($shared['reject_batch'] ?? false) {
                            $shared['void_failed'][] = $order2;
                        } else {
                            $variables = [
                                'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                            ];
                            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $user->phone, $variables, null, true);
                        }

                        return false;
                    }
                }
            } else {
                $payBalance = TransactionPaymentBalance::where('id_transaction_group', $data['id_transaction_group'])->first();
                if ($payBalance) {
                    $refund = app($this->balance)->addLogBalance($user->id, (int)$payBalance['balance_nominal'], $trxGroup['id_transaction_group'], 'Rejected Order Group', $payBalance['balance_nominal']);
                    if ($refund == false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function checkTransactionRecipe($post)
    {
        $dt = [
            'id_transaction_consultation' => $post['id_transaction_consultation'],
            'point_use' => $post['point_use'] ?? true,
        ];

        if (!empty($post['id_user_address'])) {
            $dt['id_user_address'] = $post['id_user_address'];
        }

        $consultationProduct = TransactionConsultationRecomendation::where('id_transaction_consultation', $post['id_transaction_consultation'])
                            ->where('product_type', 'Drug')
                            ->get()->toArray();

        $productOutlet = [];
        foreach ($consultationProduct as $product) {
            $productOutlet[$product['id_outlet']][] = [
                "id_custom" => rand(pow(10, 4 - 1), pow(10, 4) - 1),
                "id_product" => $product['id_product'],
                'id_transaction_consultation' => $post['id_transaction_consultation'],
                "qty" => (int)$product['qty_product'],
                "id_product_variant_group" => $product['id_product_variant_group'],
                "id_product_variant_group_wholesaler" => null,
                "id_product_wholesaler" => null,
                "note" => ""
            ];
        }

        $items = [];
        foreach ($productOutlet as $key => $value) {
            $items[] = [
                'id_outlet' => $key,
                'items' => $value
            ];
        }

        $dt['items'] = $items;

        return $dt;
    }
}
