<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Subdistricts;
use App\Http\Models\Districts;
use App\Http\Models\Deal;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionProductModifier;
use App\Lib\Shipper;
use Illuminate\Pagination\Paginator;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\Province;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\Courier;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Setting;
use App\Http\Models\StockLog;
use App\Http\Models\UserAddress;
use App\Http\Models\ManualPayment;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\ManualPaymentTutorial;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentBalance;
use Modules\Disburse\Entities\MDR;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentManual;
use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\ShopeePay\Entities\DealsPaymentShopeePay;
use App\Http\Models\UserTrxProduct;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Transaction\Entities\TransactionShipmentTrackingUpdate;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Http\Requests\RuleUpdate;

use Modules\Transaction\Http\Requests\TransactionDetail;
use Modules\Transaction\Http\Requests\TransactionHistory;
use Modules\Transaction\Http\Requests\TransactionFilter;
use Modules\Transaction\Http\Requests\TransactionNew;
use Modules\Transaction\Http\Requests\TransactionShipping;
use Modules\Transaction\Http\Requests\GetProvince;
use Modules\Transaction\Http\Requests\GetCity;
use Modules\Transaction\Http\Requests\GetSub;
use Modules\Transaction\Http\Requests\GetAddress;
use Modules\Transaction\Http\Requests\GetNearbyAddress;
use Modules\Transaction\Http\Requests\AddAddress;
use Modules\Transaction\Http\Requests\UpdateAddress;
use Modules\Transaction\Http\Requests\DeleteAddress;
use Modules\Transaction\Http\Requests\ManualPaymentCreate;
use Modules\Transaction\Http\Requests\ManualPaymentEdit;
use Modules\Transaction\Http\Requests\ManualPaymentUpdate;
use Modules\Transaction\Http\Requests\ManualPaymentDetail;
use Modules\Transaction\Http\Requests\ManualPaymentDelete;
use Modules\Transaction\Http\Requests\MethodSave;
use Modules\Transaction\Http\Requests\MethodDelete;
use Modules\Transaction\Http\Requests\ManualPaymentConfirm;
use Modules\Transaction\Http\Requests\ShippingGoSend;
use Modules\Transaction\Entities\TransactionGroup;

use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\Xendit\Entities\TransactionPaymentXendit;

use App\Lib\MyHelper;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use Illuminate\Support\Facades\Log;
use Modules\Quest\Entities\Quest;

class ApiTransaction extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->shopeepay      = 'Modules\ShopeePay\Http\Controllers\ShopeePayController';
        $this->xendit         = 'Modules\Xendit\Http\Controllers\XenditController';
        $this->shipper         = 'Modules\Transaction\Http\Controllers\ApiShipperController';
    }

    public function transactionRule() {
        $settingTotal = Setting::where('key', 'transaction_grand_total_order')->first();
        $settingService = Setting::where('key', 'transaction_service_formula')->first();
        $settingServiceValue = Setting::where('key', 'service')->first();

        $settingDiscount = Setting::where('key', 'transaction_discount_formula')->first();
        $settingPercent = Setting::where('key', 'discount_percent')->first();
        $settingNom = Setting::where('key', 'discount_nominal')->first();

        $settingTax = Setting::where('key', 'transaction_tax_formula')->first();
        $settingTaxValue = Setting::where('key', 'tax')->first();

        $settingPoint = Setting::where('key', 'point_acquisition_formula')->first();
        $settingPointValue = Setting::where('key', 'point_conversion_value')->first();

        $settingCashback = Setting::where('key', 'cashback_acquisition_formula')->first();
        $settingCashbackValue = Setting::where('key', 'cashback_conversion_value')->first();
        $settingCashbackMax = Setting::where('key', 'cashback_maximum')->first();

        $settingOutlet = Setting::where('key', 'default_outlet')->first();

        $outlet = Outlet::get()->toArray();

        if (!$settingTotal || !$settingService || !$settingServiceValue || !$settingDiscount || !$settingTax || !$settingTaxValue || !$settingPoint || !$settingPointValue || !$settingCashback || !$settingCashbackValue || !$settingOutlet) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Data setting not found']
            ]);
        }

        $data = [
            'grand_total'   => explode(',', $settingTotal['value']),
            'service'       => [
                'data'  => explode(' ', $settingService['value']),
                'value' => $settingServiceValue['value']
            ],
            'discount'      => [
                'data'    => explode(' ', $settingDiscount['value']),
                'percent' => $settingPercent['value'],
                'nominal' => $settingNom['value'],
            ],
            'tax'       => [
                'data'  => explode(' ', $settingTax['value']),
                'value' => $settingTaxValue['value']
            ],
            'point'       => [
                'data'  => explode(' ', $settingPoint['value']),
                'value' => $settingPointValue['value']
            ],
            'cashback'       => [
                'data'  => explode(' ', $settingCashback['value']),
                'value' => $settingCashbackValue['value'],
                'max' => $settingCashbackMax['value'],
            ],
            'outlet'        => $outlet,
            'default_outlet' => $settingOutlet,
        ];

        return response()->json(MyHelper::checkGet($data));
    }

    public function transactionRuleUpdate(RuleUpdate $request) {
        $post = $request->json()->all();
        DB::beginTransaction();
        if ($post['key'] == 'grand_total') {
            $merge = implode(',', $post['item']);

            $save = Setting::where('key', 'transaction_grand_total_order')->first();
            if (!$save) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $save->value = $merge;
            $save->save();

            DB::commit();
            return response()->json(MyHelper::checkUpdate($save));
        } elseif ($post['key'] == 'service') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_service_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateService = Setting::where('key', 'service')->first();
            if (!$updateService) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateService->value = $post['value']/100;
            $updateService->save();
            if (!$updateService) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateService));

        } elseif ($post['key'] == 'courier') {
            $dataResult = [];

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_delivery_standard')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));

        } elseif ($post['key'] == 'delivery') {
            $updateMinValue = Setting::where('key', 'transaction_delivery_min_value')->first();
            $updateMaxDis = Setting::where('key', 'transaction_delivery_max_distance')->first();
            $updateDelPrice = Setting::where('key', 'transaction_delivery_price')->first();
            $updateDelPricing = Setting::where('key', 'transaction_delivery_pricing')->first();

            if (!$updateMinValue || !$updateMaxDis || !$updateDelPrice || !$updateDelPricing) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateMinValue->value = $post['min_value'];
            $updateMaxDis->value = $post['max_distance'];
            $updateDelPrice->value = $post['delivery_price'];
            $updateDelPricing->value = $post['delivery_pricing'];

            $updateMinValue->save();
            $updateMaxDis->save();
            $updateDelPrice->save();
            $updateDelPricing->save();

            if (!$updateMinValue || !$updateMaxDis || !$updateDelPrice || !$updateDelPricing) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateMinValue));

        } elseif ($post['key'] == 'discount') {
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_discount_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $checkPercent = Setting::where('key', 'discount_percent')->first();
            if (!$checkPercent) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $checkNominal = Setting::where('key', 'discount_nominal')->first();
            if (!$checkNominal) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $checkPercent->value = $post['percent'];
            $checkPercent->save();
            if (!$checkPercent) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }


            $checkNominal->value = $post['nominal'];
            $checkNominal->save();
            if (!$checkNominal) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));

        } elseif ($post['key'] == 'tax') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_tax_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateTax = Setting::where('key', 'tax')->first();
            if (!$updateTax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateTax->value = $post['value']/100;
            $updateTax->save();
            if (!$updateTax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateTax));

        } elseif ($post['key'] == 'point') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'point_acquisition_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updatePoint = Setting::where('key', 'point_conversion_value')->first();
            if (!$updatePoint) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updatePoint->value = $post['value'];
            $updatePoint->save();
            if (!$updatePoint) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updatePoint));

        } elseif ($post['key'] == 'cashback') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'cashback_acquisition_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateCashback = Setting::where('key', 'cashback_conversion_value')->first();
            if (!$updateCashback) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateCashback->value = $post['value']/100;
            $updateCashback->save();
            if (!$updateCashback) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateCashbackMax = Setting::where('key', 'cashback_maximum')->first();
            if (!$updateCashbackMax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateCashbackMax->value = $post['max'];
            $updateCashbackMax->save();
            if (!$updateCashbackMax) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateCashback));

        } elseif ($post['key'] == 'outlet') {
            $update = Setting::where('key', 'default_outlet')->first();
            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $post['value'];
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));
        }

    }

    public function internalCourier() {
        $setting = Setting::where('key', 'transaction_delivery_standard')->orWhere('key', 'transaction_delivery_min_value')->orWhere('key', 'transaction_delivery_max_distance')->orWhere('key', 'transaction_delivery_pricing')->orWhere('key', 'transaction_delivery_price')->get()->toArray();

        return response()->json(MyHelper::checkGet($setting));
    }

    public function manualPaymentList() {
        $list = ManualPayment::with('manual_payment_methods')->get()->toArray();

        return response()->json(MyHelper::checkGet($list));
    }

    public function manualPaymentCreate(ManualPaymentCreate $request) {
        $post = $request->json()->all();

        DB::beginTransaction();
        if (isset($post['manual_payment_logo'])) {
            $decoded = base64_decode($post['manual_payment_logo']);

            // cek extension
            $ext = MyHelper::checkExtensionImageBase64($decoded);

            // set picture name
            $pictName = mt_rand(0, 1000).''.time().''.$ext;

            // path
            $upload = $this->saveImage.$pictName;

            $img = Image::make($decoded);
            $img->save($upload);

            if ($img) {
                $data['manual_payment_logo'] = $upload;
            } else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ]);
            }

            // $save = MyHelper::uploadPhotoStrict($post['manual_payment_logo'], $this->saveImage, 300, 300);

            // if (isset($save['status']) && $save['status'] == "success") {
            //     $data['manual_payment_logo'] = $save['path'];
            // }
            // else {
            //     DB::rollback();
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['fail upload image']
            //     ]);
            // }
        }

        if (isset($post['is_virtual_account'])) {
            $data['is_virtual_account'] = $post['is_virtual_account'];
        }

        if (isset($post['manual_payment_name'])) {
            $data['manual_payment_name'] = $post['manual_payment_name'];
        }

        if (isset($post['account_number'])) {
            $data['account_number'] = $post['account_number'];
        }

        if (isset($post['account_name'])) {
            $data['account_name'] = $post['account_name'];
        }

        $save = ManualPayment::create($data);

        if (!$save) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Create manual payment failed']
            ]);
        }

        DB::commit();
        return response()->json(MyHelper::checkCreate($save));
    }

    public function manualPaymentEdit(ManualPaymentEdit $request) {
        $id = $request->json('id');

        $list = ManualPayment::with('manual_payment_methods')->where('id_manual_payment', $id)->first();

        if (count($list['manual_payment_methods']) > 0) {
            $method = [];

            foreach ($list['manual_payment_methods'] as $value) {
                array_push($method, $value['payment_method_name']);
            }

            $list['method'] = implode(',', $method);
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function manualPaymentUpdate(ManualPaymentUpdate $request) {
        $post = $request->json()->all();

        DB::beginTransaction();
        if (isset($post['post']['manual_payment_logo'])) {
            $decoded = base64_decode($post['post']['manual_payment_logo']);

            // cek extension
            $ext = MyHelper::checkExtensionImageBase64($decoded);

            // set picture name
            $pictName = mt_rand(0, 1000).''.time().''.$ext;

            // path
            $upload = $this->saveImage.$pictName;

            $img = Image::make($decoded);
            $img->save($upload);

            if ($img) {
                $data['manual_payment_logo'] = $upload;
            } else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ]);
            }
            // $save = MyHelper::uploadPhotoStrict($post['post']['manual_payment_logo'], $this->saveImage, 300, 300);

            // if (isset($save['status']) && $save['status'] == "success") {
            //     $data['manual_payment_logo'] = $save['path'];
            // }
            // else {
            //     DB::rollback();
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['fail upload image']
            //     ]);
            // }
        }

        if (isset($post['post']['is_virtual_account'])) {
            $data['is_virtual_account'] = $post['post']['is_virtual_account'];
        }

        if (isset($post['post']['manual_payment_name'])) {
            $data['manual_payment_name'] = $post['post']['manual_payment_name'];
        }

        if (isset($post['post']['account_number'])) {
            $data['account_number'] = $post['post']['account_number'];
        }

        if (isset($post['post']['account_name'])) {
            $data['account_name'] = $post['post']['account_name'];
        }

        $save = ManualPayment::where('id_manual_payment', $post['id'])->update($data);
        // return $save;
        if (!$save) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Update manual payment failed']
            ]);
        }

        // $old = explode(',', $post['post']['method_name_old']);
        // $new = explode(',', $post['post']['method_name_new']);
        // // return $old;
        // // return response()->json($old[0]);

        // foreach ($old as $key => $o) {
        //     if (!in_array($o, $new)) {
        //         $delete = ManualPaymentMethod::where('payment_method_name', $o)->delete();
        //         // return $delete;

        //         if (!$delete) {
        //             DB::rollback();
        //             return response()->json([
        //                 'status'    => 'fail',
        //                 'messages'  => ['Update manual payment failed1']
        //             ]);
        //         }
        //     }
        // }

        // foreach ($new as $row => $n) {
        //     if (!in_array($n, $old)) {
        //         $data = [
        //             'id_manual_payment' => $post['id'],
        //             'payment_method_name'   => $n
        //         ];

        //         $insert = ManualPaymentMethod::create($data);

        //         if (!$insert) {
        //             DB::rollback();
        //             return response()->json([
        //                 'status'    => 'fail',
        //                 'messages'  => ['Update manual payment failed']
        //             ]);
        //         }
        //     }
        // }

        DB::commit();
        return response()->json(MyHelper::checkCreate($save));
    }

    public function manualPaymentDetail(ManualPaymentDetail $request) {
        $id = $request->json('id');

        $detail = ManualPayment::with('manual_payment_methods.manual_payment_tutorials')->where('id_manual_payment', $id)->first();

        if (!empty($detail['manual_payment_methods'])) {
            $detail['old_id'] = implode(',', array_column($detail['manual_payment_methods']->toArray(), 'id_manual_payment_method'));
        }
        // return $detail;
        return response()->json(MyHelper::checkGet($detail));
    }

    public function manualPaymentDelete(ManualPaymentDelete $request) {
        $id = $request->json('id');
        $check = ManualPayment::with('manual_payment_methods.transaction_payment_manuals.transaction')->where('id_manual_payment', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Delete manual payment failed']
            ]);
        }

        foreach ($check['manual_payment_methods'] as $key => $value) {
            if (count($value['transaction_payment_manuals']) > 0) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['This payment is already in use']
                ]);
            }
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));
    }

    public function manualPaymentMethod(MethodSave $request) {
        $post = $request->json()->all();
        // return $post;
        $check = explode(',', $post['old_id']);

        DB::beginTransaction();
        if (!isset($post['method_name'])) {
            $delete = ManualPaymentMethod::where('id_manual_payment', $post['id'])->delete();

            if (!$delete) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Failed']
                ]);
            }
        } else {
            foreach ($post['method_name'] as $key => $value) {
                $data = [
                    'id_manual_payment'   => $post['id'],
                    'payment_method_name' => $value,
                ];

                if (in_array($post['id_method'][$key], $check)) {
                    $method = ManualPaymentMethod::with('manual_payment_tutorials')->where('id_manual_payment_method', $post['id_method'][$key])->first();
                    $insert = $method->update($data);

                    if (count($method['manual_payment_tutorials']) > 0) {
                        $delete = ManualPaymentTutorial::where('id_manual_payment_method', $post['id_method'][$key])->delete();

                        if (!$delete) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed']
                            ]);
                        }
                    }

                    $id = $method['id_manual_payment_method'];
                } else {
                    $insert = ManualPaymentMethod::create($data);
                    $id = $insert['id_manual_payment_method'];
                }

                if (!$insert) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed']
                    ]);
                }

                if (isset($post['tutorial_'.$key.''])) {
                    foreach ($post['tutorial_'.$key.''] as $row => $tutorial) {
                        $dataTutor = [
                            'id_manual_payment_method' => $id,
                            'payment_tutorial'         => $tutorial,
                            'payment_tutorial_no'      => $row+1
                        ];

                        $insert = ManualPaymentTutorial::create($dataTutor);

                        if (!$insert) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Failed']
                            ]);
                        }
                    }
                }
            }

            if (isset($post['old_id'])) {
                foreach ($check as $tes => $value) {
                    if (!in_array($value, $post['id_method'])) {
                        $delete = ManualPaymentMethod::where('id_manual_payment_method', $value)->delete();

                        if (!$delete) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Failed']
                            ]);
                        }
                    }
                }
            }
        }

        DB::commit();

        return response()->json([
            'status'    => 'success',
            'messages'  => ['Success']
        ]);
    }

    public function manualPaymentMethodDelete(MethodDelete $request) {
        $id = $request->json('id');

        $check = ManualPaymentTutorial::where('id_manual_payment_tutorial', $id)->first();

        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tutorial Not Found']
            ]);
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));

    }

    public function pointUser(Request $request) {
        $point = LogPoint::with('user')->paginate(10);
        return response()->json(MyHelper::checkGet($point));
    }

    public function pointUserFilter(Request $request) {
        $post = $request->json()->all();

        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $query = LogPoint::select('log_points.*',
            'users.*')
            ->leftJoin('users','log_points.id_user','=','users.id')
            ->where('log_points.created_at', '>=', $start)
            ->where('log_points.created_at', '<=', $end)
            ->orderBy('log_points.id_log_point', 'DESC')
            ->groupBy('log_points.id_log_point');
        // ->orderBy('transactions.id_transaction', 'DESC');

        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(isset($con['subject'])){
                    $var = $con['subject'];
                    if ($post['rule'] == 'and') {
                        if ($con['operator'] == 'like') {
                            $query = $query->where($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        }
                    } else {
                        if ($con['operator'] == 'like') {
                            $query = $query->orWhere($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10);

        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function balanceUserFilter(Request $request) {
        $post = $request->json()->all();

        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $query = LogBalance::select(
            'log_balances.*',
            'users.name',
            'users.phone'
        )
            ->leftJoin('users','log_balances.id_user','=','users.id')
            ->where('log_balances.created_at', '>=', $start)
            ->where('log_balances.created_at', '<=', $end)
            ->orderBy('log_balances.id_log_balance', 'DESC')
            ->groupBy('log_balances.id_log_balance');
        // ->orderBy('transactions.id_transaction', 'DESC');

        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(isset($con['subject'])){
                    if ($con['subject'] == 'balance') {
                        $var = 'log_balances.balance';
                    } else {
                        $var = $con['subject'];
                    }

                    if ($post['rule'] == 'and') {
                        if ($con['operator'] == 'like') {
                            $query = $query->where($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        }
                    } else {
                        if ($con['operator'] == 'like') {
                            $query = $query->orWhere($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10)->toArray();

        if ($akhir) {

            $akhir['data'] = $query->paginate(10)
                ->each(function($q){
                    $q->setAppends([
                        'get_reference'
                    ]);
                })
                ->toArray();

            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function balanceUser(Request $request) {

        $balance = LogBalance::with('user')
            ->orderBy('id_log_balance', 'desc')
            ->paginate(10)
            ->toArray();

        if ($balance) {
            $balance['data'] = LogBalance::with('user')
                ->orderBy('id_log_balance', 'desc')
                ->paginate(10)
                ->each(function($q){
                    $q->setAppends([
                        'get_reference'
                    ]);
                })
                ->toArray();
        }

        return response()->json(MyHelper::checkGet($balance));
    }

    public function manualPaymentListUnpay(Request $request) {
        $list = TransactionPaymentManual::with('transaction', 'manual_payment_method')->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function transactionList(Request $request){
        $post = $request->json()->all();

        $filterCode = [
            1 => 'Rejected',
            2 => 'Unpaid',
            3 => 'Pending',
            4 => 'On Progress',
            5 => 'On Delivery',
            6 => 'Completed'
        ];

        $codeIndo = [
            'Rejected' => [
                'code' => 1,
                'text' => 'Dibatalkan'
            ],
            'Unpaid' => [
                'code' => 2,
                'text' => 'Belum dibayar'
            ],
            'Pending' => [
                'code' => 3,
                'text' => 'Pending'
            ],
            'On Progress' => [
                'code' => 4,
                'text' => 'Diproses'
            ],
            'On Delivery' => [
                'code' => 5,
                'text' => 'Dikirim'
            ],
            'Completed' => [
                'code' => 6,
                'text' => 'Selesai'
            ]
        ];

        $list = Transaction::join('transaction_groups', 'transaction_groups.id_transaction_group', 'transactions.id_transaction_group')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->where('trasaction_type', 'Delivery')
            ->orderBy('transaction_date', 'desc')
            ->select('transaction_groups.transaction_receipt_number as transaction_receipt_number_group', 'transactions.*', 'outlets.*', 'users.*');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $list->whereDate('transactions.transaction_date', '>=', $start_date)
                ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(($row['operator'] == '=' || $row['operator'] == 'like') && empty($row['parameter'])){
                        continue;
                    }

                    if(isset($row['subject'])){
                        $subject = $row['subject'];
                        if($subject == 'transaction_receipt_number'){
                            $subject = 'transactions.transaction_receipt_number';
                        }elseif($subject == 'transaction_group_receipt_number'){
                            $subject = 'transaction_groups.transaction_receipt_number';
                        }

                        if($row['operator'] == '=' || empty($row['parameter'])){
                            $list->where($subject, (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                        }else{
                            $list->where($subject, 'like', '%'.$row['parameter'].'%');
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(($row['operator'] == '=' || $row['operator'] == 'like') && empty($row['parameter'])){
                            continue;
                        }

                        if(isset($row['subject'])){
                            $subject = $row['subject'];
                            if($subject == 'transaction_receipt_number'){
                                $subject = 'transactions.transaction_receipt_number';
                            }elseif($subject == 'transaction_group_receipt_number'){
                                $subject = 'transaction_groups.transaction_receipt_number';
                            }

                            if($row['operator'] == '=' || empty($row['parameter'])){
                                $subquery->orWhere($subject, (empty($row['parameter']) ? $row['operator'] : $row['parameter']));
                            }else{
                                $subquery->orWhere($subject, 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(25)->toArray();

        foreach ($list['data']??[] as $key=>$value){
            $list['data'][$key] = [
                'id_outlet' => $value['id_outlet'],
                'outlet_code' => $value['outlet_code'],
                'outlet_name' => $value['outlet_name'],
                'user_name' => $value['name'],
                'user_phone' => $value['phone'],
                'user_email' => $value['email'],
                'id_transaction' => $value['id_transaction'],
                'id_transaction_group' => $value['id_transaction_group'],
                'transaction_date' => $value['transaction_date'],
                'transaction_group_receipt_number' => $value['transaction_receipt_number_group'],
                'transaction_receipt_number' => $value['transaction_receipt_number'],
                'transaction_status_code' => $codeIndo[$value['transaction_status']]['code']??'',
                'transaction_status_text' => $codeIndo[$value['transaction_status']]['text']??'',
                'transaction_grandtotal' => $value['transaction_grandtotal']
            ];
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function transactionFilter(TransactionFilter $request) {
        $post = $request->json()->all();
        // return $post;
        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $delivery = false;
        if(strtolower($post['key']) == 'delivery'){
            $post['key'] = 'pickup order';
            $delivery = true;
        }
        $query = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')->select('transactions.*',
            'transaction_pickups.*',
            'transaction_pickup_go_sends.*',
            'transaction_products.*',
            'users.*',
            'products.*',
            'product_categories.*',
            'outlets.outlet_code', 'outlets.outlet_name')
            ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('transaction_pickup_go_sends','transaction_pickups.id_transaction_pickup','=','transaction_pickup_go_sends.id_transaction_pickup')
            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
            ->leftJoin('users','transactions.id_user','=','users.id')
            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
            ->leftJoin('product_categories','products.id_product_category','=','product_categories.id_product_category')
            ->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end)
            ->with('user')
            ->orderBy('transactions.id_transaction', 'DESC')
            ->groupBy('transactions.id_transaction');
        // ->orderBy('transactions.id_transaction', 'DESC');
        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if($delivery){
                $query->where('pickup_by','<>','Customer');
            }else{
                $query->where('pickup_by','Customer');
            }
        }
        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if (isset($con['subject'])) {
                    if ($con['subject'] == 'receipt') {
                        $var = 'transactions.transaction_receipt_number';
                    } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                        $var = 'users.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                        $var = 'products.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_category') {
                        $var = 'product_categories.product_category_name';
                    } elseif ($con['subject'] == 'order_id') {
                        $var = 'transaction_pickups.order_id';
                    }

                    if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                        $var = 'outlets.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }
                    if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category', 'order_id'])) {
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }

                    if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                        $var = 'products.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                        if ($con['subject'] == 'grand_total') {
                            $var = 'transactions.transaction_grandtotal';
                        } else {
                            $var = 'transactions.transaction_tax';
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'transaction_status') {
                        if ($post['rule'] == 'and') {
                            if($con['operator'] == 'pending'){
                                $query = $query->whereNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->where('transaction_pickups.pickup_by', 'Customer');
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNotNull('transaction_pickups.taken_by_system_at');
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->whereNotNull('transaction_pickups.receive_at')
                                    ->whereNull('transaction_pickups.ready_at');
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNull('transaction_pickups.taken_at');
                            }else{
                                $query = $query->whereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        } else {
                            if($con['operator'] == 'pending'){
                                $query = $query->orWhereNotNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                });
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                });
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                });
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.receive_at')
                                        ->whereNull('transaction_pickups.ready_at');
                                });
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->orWhere(function ($q) {
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNull('transaction_pickups.taken_at');
                                });
                            }else{
                                $query = $query->orWhereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        }
                    }

                    if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'pickup_by'])) {
                        switch ($con['subject']) {
                            case 'status':
                                $var = 'transactions.transaction_payment_status';
                                break;

                            case 'courier':
                                $var = 'transactions.transaction_courier';
                                break;

                            case 'id_product':
                                $var = 'products.id_product';
                                break;

                            case 'id_outlet':
                                $var = 'outlets.id_outlet';
                                break;

                            case 'pickup_by':
                                $var = 'transaction_pickups.pickup_by';
                                break;

                            default:
                                continue 2;
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, '=', $con['operator']);
                        } else {
                            $query = $query->orWhere($var, '=', $con['operator']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10);
        // return $akhir;
        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function exportTransaction($filter, $statusReturn = null, $filter_type='admin') {
        $post = $filter;

        $delivery = false;
        if(strtolower($post['key']) == 'delivery'){
            $post['key'] = 'pickup order';
            $delivery = true;
        }

        $query = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
            ->select('transaction_pickups.*','transactions.*','users.*','outlets.outlet_code', 'outlets.outlet_name', 'payment_type', 'payment_method', 'transaction_payment_midtrans.gross_amount', 'transaction_payment_ipay88s.amount', 'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay')
            ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('users','transactions.id_user','=','users.id')
            ->orderBy('transactions.transaction_date', 'asc');

        $query = $query->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction');

        $settingMDRAll = [];
        if(isset($post['detail']) && $post['detail'] == 1){
            $settingMDRAll = MDR::get()->toArray();
            $query->leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                ->join('transaction_products','transaction_products.id_transaction','=','transactions.id_transaction')
                ->leftJoin('transaction_balances','transaction_balances.id_transaction','=','transactions.id_transaction')
                ->join('products', 'products.id_product', 'transaction_products.id_product')
                ->join('brands', 'brands.id_brand', 'transaction_products.id_brand')
                ->leftJoin('product_categories','products.id_product_category','=','product_categories.id_product_category')
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->leftJoin('cities as c', 'c.id_city', 'users.id_city')
                ->join('provinces', 'cities.id_province', 'provinces.id_province')
                ->leftJoin('transaction_bundling_products','transaction_products.id_transaction_bundling_product','=','transaction_bundling_products.id_transaction_bundling_product')
                ->leftJoin('bundling','bundling.id_bundling','=','transaction_bundling_products.id_bundling')
                ->leftJoin('rule_promo_payment_gateway','rule_promo_payment_gateway.id_rule_promo_payment_gateway','=','disburse_outlet_transactions.id_rule_promo_payment_gateway')
                ->leftJoin('promo_payment_gateway_transactions as promo_pg', 'promo_pg.id_transaction', 'transactions.id_transaction')
                ->with(['transaction_payment_subscription', 'vouchers', 'promo_campaign', 'point_refund', 'point_use', 'subscription_user_voucher.subscription_user.subscription'])
                ->orderBy('transaction_products.id_transaction_bundling_product', 'asc')
                ->addSelect('promo_pg.total_received_cashback',  'rule_promo_payment_gateway.name as promo_payment_gateway_name',
                    'transaction_bundling_products.transaction_bundling_product_base_price', 'transaction_bundling_products.transaction_bundling_product_qty', 'transaction_bundling_products.transaction_bundling_product_total_discount', 'transaction_bundling_products.transaction_bundling_product_subtotal', 'bundling.bundling_name', 'disburse_outlet_transactions.bundling_product_fee_central', 'transaction_products.*', 'products.product_code', 'products.product_name', 'product_categories.product_category_name',
                    'brands.name_brand', 'cities.city_name', 'c.city_name as user_city', 'provinces.province_name',
                    'disburse_outlet_transactions.fee_item', 'disburse_outlet_transactions.payment_charge', 'disburse_outlet_transactions.discount', 'disburse_outlet_transactions.subscription',
                    'disburse_outlet_transactions.point_use_expense',
                    'disburse_outlet_transactions.fee_promo_payment_gateway_outlet', 'disburse_outlet_transactions.fee_promo_payment_gateway_central',
                    'disburse_outlet_transactions.income_outlet', 'disburse_outlet_transactions.discount_central', 'disburse_outlet_transactions.subscription_central');
        }

        if(isset($post['date_start']) && !empty($post['date_start'])
            && isset($post['date_end']) && !empty($post['date_end'])){
            $start = date('Y-m-d', strtotime($post['date_start']));
            $end = date('Y-m-d', strtotime($post['date_end']));
        }else{
            $start = date('Y-m-01 00:00:00');
            $end = date('Y-m-d 23:59:59');
        }

        $query = $query->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end);

        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if($delivery){
                $query->where('pickup_by','<>','Customer');
            }else{
                $query->where('pickup_by','Customer');
            }
        }

        if($filter_type == 'admin'){
            $query = $this->filterExportTransactionForAdmin($query,$post);
        }else{
            $query = app('Modules\Franchise\Http\Controllers\ApiTransactionFranchiseController')->filterTransaction($query,$post);
        }

        if($statusReturn == 1){
            $columnsVariant = '';
            $addAdditionalColumnVariant = '';
            $getVariant = ProductVariant::whereNull('id_parent')->get()->toArray();
            $getAllVariant = ProductVariant::select('id_product_variant', 'id_parent')->get()->toArray();
            foreach ($getVariant as $v){
                $columnsVariant .= '<td style="background-color: #dcdcdc;" width="10">'.$v['product_variant_name'].'</td>';
                $addAdditionalColumnVariant .= '<td></td>';
            }
            if($filter_type == 'admin') {
                $query->whereNull('reject_at');
            }

            $dataTrxDetail = '';
            $cek = '';
            $get = $query->get()->toArray();
            $count = count($get);
            $tmpBundling = '';
            $htmlBundling = '';
            foreach ($get as $key=>$val) {
                $payment = '';
                if(!empty($val['payment_type'])){
                    $payment = $val['payment_type'];
                }elseif(!empty($val['payment_method'])){
                    $payment = $val['payment_method'];
                }elseif(!empty($val['id_transaction_payment_shopee_pay'])){
                    $payment = 'Shopeepay';
                }

                $variant = [];
                $productCode = $val['product_code'];
                if(!empty($val['id_product_variant_group'])){
                    $getProductVariantGroup = ProductVariantGroup::where('id_product_variant_group', $val['id_product_variant_group'])->first();
                    $productCode = $getProductVariantGroup['product_variant_group_code']??'';
                }

                $modifierGroup = TransactionProductModifier::where('id_transaction_product', $val['id_transaction_product'])
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->select('text', 'transaction_product_modifier_price')->get()->toArray();
                $modifierGroupText = array_column($modifierGroup, 'text');
                $modifierGroupPrice = array_sum(array_column($modifierGroup, 'transaction_product_modifier_price'));

                if(isset($post['detail']) && $post['detail'] == 1){

                    $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->where('transaction_product_modifiers.id_transaction_product', $val['id_transaction_product'])
                        ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                        ->select('product_modifiers.text', 'transaction_product_modifiers.transaction_product_modifier_price')->get()->toArray();

                    $addAdditionalColumn = '';
                    $promoName = '';
                    $promoType = '';
                    $promoCode = '';

                    $promoName2 = '';
                    $promoType2 = '';
                    $promoCode2 = '';
                    if(count($val['vouchers']) > 0){
                        $getDeal = Deal::where('id_deals', $val['vouchers'][0]['id_deals'])->first();
                        if($getDeal['promo_type'] == 'Discount bill' || $getDeal['promo_type'] == 'Discount delivery'){
                            $promoName2 = $getDeal['deals_title'];
                            $promoType2 = 'Deals';
                            $promoCode2 = $val['vouchers'][0]['voucher_code'];
                        }else{
                            $promoName = $getDeal['deals_title'];
                            $promoType = 'Deals';
                            $promoCode = $val['vouchers'][0]['voucher_code'];
                        }

                    }elseif (!empty($val['promo_campaign'])){
                        if($val['promo_campaign']['promo_type'] == 'Discount bill' || $val['promo_campaign']['promo_type'] == 'Discount delivery'){
                            $promoName2 = $val['promo_campaign']['promo_title'];
                            $promoType2 = 'Promo Campaign';
                            $promoCode2 = $val['promo_campaign']['promo_code'];
                        }else{
                            $promoName = $val['promo_campaign']['promo_title'];
                            $promoType = 'Promo Campaign';
                            $promoCode = $val['promo_campaign']['promo_code'];
                        }
                    }elseif(isset($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'])){
                        $promoName2 = htmlspecialchars($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title']);
                        $promoType2 = 'Subscription';
                    }

                    $promoName = htmlspecialchars($promoName);
                    $status = $val['transaction_payment_status'];
                    if(!is_null($val['reject_at'])){
                        $status = 'Reject';
                    }

                    $poinUse = '';
                    if(isset($val['point_use']) && !empty($val['point_use'])){
                        $poinUse = $val['point_use']['balance'];
                    }

                    $pointRefund = '';
                    if(isset($val['point_refund']) && !empty($val['point_refund'])){
                        $pointRefund = $val['point_refund']['balance'];
                    }

                    $paymentRefund = '';
                    if($val['reject_type'] == 'payment'){
                        $paymentRefund = $val['amount']??$val['gross_amount'];
                    }

                    $paymentCharge = 0;
                    if((int)$val['point_use_expense'] > 0){
                        $paymentCharge = $val['point_use_expense'];
                    }

                    if((int)$val['payment_charge'] > 0){
                        $paymentCharge = $val['payment_charge'];
                    }

                    $html = '';
                    $sameData = '';
                    $sameData .= '<td>'.$val['outlet_code'].'</td>';
                    $sameData .= '<td>'.htmlspecialchars($val['outlet_name']).'</td>';
                    $sameData .= '<td>'.$val['province_name'].'</td>';
                    $sameData .= '<td>'.$val['city_name'].'</td>';
                    $sameData .= '<td>'.$val['transaction_receipt_number'].'</td>';
                    $sameData .= '<td>'.$status.'</td>';
                    $sameData .= '<td>'.date('d M Y', strtotime($val['transaction_date'])).'</td>';
                    $sameData .= '<td>'.date('H:i:s', strtotime($val['transaction_date'])).'</td>';

                    //for check additional column
                    if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                        $addAdditionalColumn = "<td></td>";
                    }

                    if(!empty($val['id_transaction_bundling_product'])){
                        $totalModPrice = 0;
                        for($j=0;$j<$val['transaction_product_bundling_qty'];$j++){
                            $priceMod = 0;
                            $textMod = '';
                            if(!empty($mod)){
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            $htmlBundling .= '<td>'.$val['name_brand'].'</td>';
                            $htmlBundling .= '<td>'.$val['product_category_name'].'</td>';
                            if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                                $htmlBundling .= '<td>'.$productCode.'</td>';
                            }
                            $htmlBundling .= '<td>'.$val['product_name'].'</td>';
                            $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                                ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            foreach ($getTransactionVariant as $k=>$gtV){
                                $getTransactionVariant[$k]['main_parent'] = $this->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            }
                            foreach ($getVariant as $v){
                                $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                                if($search !== false){
                                    $htmlBundling .= '<td>'.$getTransactionVariant[$search]['product_variant_name'].'</td>';
                                }else{
                                    $htmlBundling .= '<td></td>';
                                }
                            }
                            $totalModPrice = $totalModPrice + $priceMod;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.implode(",",$modifierGroupText).'</td>';
                            $htmlBundling .= '<td>'.$textMod.'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>'.$priceMod.'</td>';
                            $htmlBundling .= '<td>'.htmlspecialchars($val['transaction_product_note']).'</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.$priceMod.'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>'.($priceMod).'</td>';
                            $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';

                            $totalMod = count($mod);
                            if($totalMod > 1){
                                for($i=1;$i<$totalMod;$i++){
                                    $totalModPrice = $totalModPrice + $mod[$i]['transaction_product_modifier_price']??0;
                                    $htmlBundling .= '<tr>';
                                    $htmlBundling .= $sameData;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumn;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumnVariant;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['text']??''.'</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price']??(int)'0'.'</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $htmlBundling .= '<td>0</td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                                    if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                                    }
                                    $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $htmlBundling .= '</tr>';
                                }
                            }
                        }

                        if($key == ($count-1) || (isset($get[$key+1]) && $val['id_transaction_bundling_product'] != $get[$key+1]['id_transaction_bundling_product'])){
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            $htmlBundling .= '<td>Paket</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumn;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumnVariant;
                            $htmlBundling .= '<td>'.$val['bundling_name'].'</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td>'.$val['transaction_bundling_product_total_discount'].'</td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']-$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';
                            for ($bun = 1;$bun<=$val['transaction_bundling_product_qty'];$bun++){
                                $html .= $htmlBundling;
                            }
                            $htmlBundling = "";
                        }

                        $tmpBundling = $val['id_transaction_bundling_product'];
                    }else{
                        for($j=0;$j<$val['transaction_product_qty'];$j++){
                            $priceMod = 0;
                            $textMod = '';
                            if(!empty($mod)){
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td>'.$val['name_brand'].'</td>';
                            $html .= '<td>'.$val['product_category_name'].'</td>';
                            if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                                $html .= '<td>'.$productCode.'</td>';
                            }
                            $html .= '<td>'.$val['product_name'].'</td>';
                            $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                                ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            foreach ($getTransactionVariant as $k=>$gtV){
                                $getTransactionVariant[$k]['main_parent'] = $this->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            }
                            foreach ($getVariant as $v){
                                $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                                if($search !== false){
                                    $html .= '<td>'.$getTransactionVariant[$search]['product_variant_name'].'</td>';
                                }else{
                                    $html .= '<td></td>';
                                }
                            }
                            $priceProd = $val['transaction_product_price']+(float)$val['transaction_variant_subtotal']+$modifierGroupPrice;
                            $html .= '<td></td>';
                            $html .= '<td>'.implode(",",$modifierGroupText).'</td>';
                            $html .= '<td>'.$textMod.'</td>';
                            $html .= '<td>'.$priceProd.'</td>';
                            $html .= '<td>'.$priceMod.'</td>';
                            $html .= '<td>'.htmlspecialchars($val['transaction_product_note']).'</td>';
                            if(!empty($val['transaction_product_qty_discount'])&& $val['transaction_product_qty_discount'] > $j){
                                $html .= '<td>'.$promoName.'</td>';
                                $html .= '<td>'.$promoCode.'</td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                                $html .= '<td>'.$val['transaction_product_base_discount'].'</td>';
                                $html .= '<td>'.(($priceProd+$priceMod)-$val['transaction_product_base_discount']).'</td>';
                            }else{
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';

                            $totalMod = count($mod);
                            if($totalMod > 1){
                                for($i=1;$i<$totalMod;$i++){
                                    $html .= '<tr>';
                                    $html .= $sameData;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumn;
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumnVariant;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.$mod[$i]['text']??''.'</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.$mod[$i]['transaction_product_modifier_price']??(int)'0'.'</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.($mod[$i]['transaction_product_modifier_price']??0).'</td>';
                                    $html .= '<td>0</td>';
                                    $html .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                    if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $html .= '<td></td><td></td><td></td><td></td>';
                                    }
                                    $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $html .= '</tr>';
                                }
                            }
                        }
                    }

                    $sub = 0;
                    if($key == ($count-1) || (isset($get[$key+1]['transaction_receipt_number']) && $val['transaction_receipt_number'] != $get[$key+1]['transaction_receipt_number'])){
                        //for product plastic
                        $productPlastics = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                            ->where('id_transaction', $val['id_transaction'])->where('type', 'Plastic')
                            ->get()->toArray();

                        foreach ($productPlastics as $plastic){
                            for($j=0;$j<$plastic['transaction_product_qty'];$j++){
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>'.$plastic['product_name']??''.'</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td></td><td></td><td></td><td></td>';
                                if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        }

                        if(!empty($val['transaction_payment_subscription'])) {
                            $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                                ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                                ->where('subscription_user_vouchers.id_subscription_user_voucher', $val['transaction_payment_subscription']['id_subscription_user_voucher'])
                                ->groupBy('subscriptions.id_subscription')->select('subscriptions.*', 'subscription_user_vouchers.voucher_code')->first();

                            if($getSubcription){
                                $sub  = abs($val['transaction_payment_subscription']['subscription_nominal'])??0;
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>'.htmlspecialchars($getSubcription['subscription_title']).'(subscription)</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.abs($val['transaction_payment_subscription']['subscription_nominal']??0).'</td>';
                                $html .= '<td>'.(-$val['transaction_payment_subscription']['subscription_nominal']??0).'</td>';
                                $html .= '<td></td><td></td><td></td><td></td>';
                                if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        }elseif(!empty($promoName2)){
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>'.htmlspecialchars($promoName2).'('.$promoType2.')'.'</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td>'.abs(abs($val['transaction_discount'])??0).'</td>';
                            $html .= '<td>'.(-abs($val['transaction_discount'])??0).'</td>';
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $deliveryPrice = $val['transaction_shipment'];
                        if($val['transaction_shipment_go_send']){
                            $deliveryPrice = $val['transaction_shipment_go_send'];
                        }
                        if(!empty($deliveryPrice)) {
                            $discountDelivery = 0;
                            $promoDiscountDelivery = '';
                            if(abs($val['transaction_discount_delivery']) > 0){
                                $promoDiscountDelivery = ' ('.(empty($promoName) ? $promoName2 : $promoName).')';
                                $discountDelivery = abs($val['transaction_discount_delivery']);
                            }

                            if(isset($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']) && !empty($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription'])){
                                $promoDiscountDelivery = ' ('.$val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']['subscription_title'].')';
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>Delivery'.$promoDiscountDelivery.'</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '<td>'.($deliveryPrice??0).'</td>';
                            $html .= '<td>'.$discountDelivery.'</td>';
                            $html .= '<td>'.($deliveryPrice-$discountDelivery??0).'</td>';
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $promoNamePaymentGateway = (empty($val['promo_payment_gateway_name']) ? "": $val['promo_payment_gateway_name']);
                        $nominalPromoPaymentGateway =  $val['total_received_cashback'];
                        if(!empty($promoNamePaymentGateway)) {
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>'.htmlspecialchars($promoNamePaymentGateway).'(Promo Payment Gateway)</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td>'.$nominalPromoPaymentGateway.'</td>';
                            $html .= '<td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $html .= '<tr>';
                        $html .= $sameData;
                        $html .= '<td></td>';
                        $html .= '<td></td>';
                        $html .= $addAdditionalColumn;
                        $html .= '<td>Fee</td>';
                        $html .= $addAdditionalColumnVariant;
                        $html .= '<td></td>';
                        $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                        $html .= '<td>'.($val['transaction_grandtotal']-$sub).'</td>';
                        $html .= '<td>'.(float)$val['fee_item'].'</td>';
                        $html .= '<td>'.(float)$paymentCharge.'</td>';
                        if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                            $html .= '<td>' . (float)$val['discount_central'] . '</td>';
                            $html .= '<td>' . (float)$val['subscription_central'] . '</td>';
                            $html .= '<td>' . (float)$val['bundling_product_fee_central'] . '</td>';
                            $html .= '<td>' . (float)$val['fee_promo_payment_gateway_central'] . '</td>';
                        }
                        $html .= '<td>'.(float)$val['income_outlet'].'</td>';
                        $html .= '<td>'.$payment.'</td>';
                        $html .= '<td>'.abs($poinUse).'</td>';
                        $html .= '<td>'.$val['transaction_cashback_earned'].'</td>';
                        $html .= '<td>'.$pointRefund.'</td>';
                        $html .= '<td>'.$paymentRefund.'</td>';
                        $html .= '<td>'.(!empty($deliveryPrice)  ? 'Delivery' : $val['trasaction_type']).'</td>';
                        $html .= '<td>'.($val['receive_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['receive_at']))).'</td>';
                        $html .= '<td>'.($val['ready_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['ready_at']))).'</td>';
                        $html .= '<td>'.($val['taken_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['taken_at']))).'</td>';
                        $html .= '<td>'.($val['arrived_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['arrived_at']))).'</td>';
                        $html .= '</tr>';
                    }
                }
                $dataTrxDetail .= $html;
            }
            return [
                'list' => $dataTrxDetail,
                'add_column' => $columnsVariant
            ];
        }else{
            return $query;
        }
    }

    public function filterExportTransactionForAdmin($query, $post){
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(is_object($con)){
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] == 'receipt') {
                        $var = 'transactions.transaction_receipt_number';
                    } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                        $var = 'users.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                        $var = 'products.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_category') {
                        $var = 'product_categories.product_category_name';
                    }

                    if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                        $var = 'outlets.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }
                    if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category'])) {
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }

                    if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                        $var = 'products.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                        if ($con['subject'] == 'grand_total') {
                            $var = 'transactions.transaction_grandtotal';
                        } else {
                            $var = 'transactions.transaction_tax';
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'transaction_status') {
                        if ($post['rule'] == 'and') {
                            if($con['operator'] == 'pending'){
                                $query = $query->whereNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->where('transaction_pickups.pickup_by', 'Customer');
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNotNull('transaction_pickups.taken_by_system_at');
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->whereNotNull('transaction_pickups.receive_at')
                                    ->whereNull('transaction_pickups.ready_at');
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNull('transaction_pickups.taken_at');
                            }else{
                                $query = $query->whereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        } else {
                            if($con['operator'] == 'pending'){
                                $query = $query->orWhereNotNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                });
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                });
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                });
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.receive_at')
                                        ->whereNull('transaction_pickups.ready_at');
                                });
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->orWhere(function ($q) {
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNull('transaction_pickups.taken_at');
                                });
                            }else{
                                $query = $query->orWhereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        }
                    }

                    if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'pickup_by'])) {
                        switch ($con['subject']) {
                            case 'status':
                                $var = 'transactions.transaction_payment_status';
                                break;

                            case 'courier':
                                $var = 'transactions.transaction_courier';
                                break;

                            case 'id_product':
                                $var = 'products.id_product';
                                break;

                            case 'id_outlet':
                                $var = 'outlets.id_outlet';
                                break;

                            case 'pickup_by':
                                $var = 'transaction_pickups.pickup_by';
                                break;

                            default:
                                continue 2;
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, '=', $con['operator']);
                        } else {
                            $query = $query->orWhere($var, '=', $con['operator']);
                        }
                    }
                }
            }
        }

        return $query;
    }

    function returnExportYield($filter){
        $query = $this->exportTransaction($filter);
        $post = $filter;
        $forCheck = '';

        foreach ($query->cursor() as $val) {
            $payment = '';
            if(!empty($val['payment_type'])){
                $payment = $val['payment_type'];
            }elseif(!empty($val['payment_method'])){
                $payment = $val['payment_method'];
            }elseif(!empty($val['id_transaction_payment_shopee_pay'])){
                $payment = 'Shopeepay';
            }

            if(isset($post['detail']) && $post['detail'] == 1){

                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->where('transaction_product_modifiers.id_transaction_product', $val['id_transaction_product'])
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->select('product_modifiers.text')->get()->toArray();

                $promoName = '';
                $promoType = '';
                $promoCode = '';
                if(count($val['vouchers']) > 0){
                    $getDeal = Deal::where('id_deals', $val['vouchers'][0]['id_deals'])->first();
                    $promoName = $getDeal['deals_title'];
                    $promoType = 'Deals';
                    $promoCode = $val['vouchers'][0]['voucher_code'];
                }elseif (!empty($val['promo_campaign'])){
                    $promoName = $val['promo_campaign']['promo_title'];
                    $promoType = 'Promo Campaign';
                    $promoCode = $val['promo_campaign']['promo_code'];
                }elseif (isset($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'])){
                    $promoName = $val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'];
                    $promoType = 'Subscription';
                    $promoCode = '';
                }

                $paymentStatus = $val['transaction_payment_status'];
                $status = '';
                if(empty($val['receive_at'])){
                    $status = 'Pending';
                }elseif(!empty($val['receive_at']) && empty($val['ready_at'])){
                    $status = 'Received';
                }elseif(!empty($val['ready_at']) && empty($val['taken_at'])){
                    $status = 'Ready';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] == 'Customer'){
                    $status = 'Taken by Customer';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] != 'Customer'){
                    $status = 'Taken by Driver';
                }elseif(!empty($val['taken_by_system_at'])){
                    $status = 'Taken by System';
                }elseif(!empty($val['reject_at'])){
                    $status = 'Reject';
                }

                $poinUse = '';
                if(isset($val['point_use']) && !empty($val['point_use'])){
                    $poinUse = $val['point_use']['balance'];
                }

                $pointRefund = '';
                if(isset($val['point_refund']) && !empty($val['point_refund'])){
                    $pointRefund = $val['point_refund']['balance'];
                }
                $paymentRefund = '';
                if($val['reject_type'] == 'payment'){
                    $paymentRefund = $val['amount']??$val['gross_amount'];
                }

                $paymentCharge = 0;
                if((int)$val['point_use_expense'] > 0){
                    $paymentCharge = $val['point_use_expense'];
                }

                if((int)$val['payment_charge'] > 0){
                    $paymentCharge = $val['payment_charge'];
                }
                $taken = '';
                if(!empty($val['ready_at'])){
                    $taken = date('d M Y H:i', strtotime($val['ready_at']));
                }elseif(!empty($val['taken_by_system_at'])){
                    $taken = date('d M Y H:i', strtotime($val['taken_by_system_at']));
                }

                $deliveryPrice = $val['transaction_shipment'];
                if(!empty($val['transaction_shipment_go_send'])){
                    $deliveryPrice = $val['transaction_shipment_go_send'];
                }

                $dt = [
                    'Name' => $val['name'],
                    'Phone' => $val['phone'],
                    'Gender' => $val['gender'],
                    'Date of birth' => ($val['birthday'] == null ? '' : date('d M Y', strtotime($val['birthday']))),
                    'Customer City' => $val['user_city'],
                    'Outlet Code' => $val['outlet_code'],
                    'Outlet Name' => htmlspecialchars($val['outlet_name']),
                    'Province' => $val['province_name'],
                    'City' => $val['city_name'],
                    'Receipt number' => $val['transaction_receipt_number'],
                    'Payment Status' => $paymentStatus,
                    'Transaction Status' => $status,
                    'Transaction Date' => date('d M Y', strtotime($val['transaction_date'])),
                    'Transaction Time' => date('H:i:s', strtotime($val['transaction_date'])),
                    'Customer latitude' =>$val['latitude'],
                    'Customer longitude' =>$val['longitude'],
                    'Customer distance' => $val['distance_customer'],
                    'Brand' => $val['name_brand'],
                    'Category' => $val['product_category_name'],
                    'Items' => $val['product_code'].'-'.$val['product_name'],
                    'Modifier' => implode(",", array_column($mod, 'text')),
                    'Qty' => $val['transaction_product_qty'],
                    'Notes' => $val['transaction_product_note'],
                    'Promo Type' => $promoType,
                    'Promo Name' => $promoName,
                    'Promo Code' => $promoCode,
                    'Gross Sales' => $val['transaction_grandtotal'],
                    'Discounts' => $val['transaction_product_discount'],
                    'Delivery Fee' => $deliveryPrice??'0',
                    'Discount Delivery' => $val['transaction_discount_delivery']??'0',
                    'Subscription' => abs($val['transaction_payment_subscription']['subscription_nominal']??0),
                    'Total Fee (fee item+fee discount deliver+fee payment+fee promo+fee subscription) ' => ($paymentCharge == 0? '' : (float)($val['fee_item'] + $paymentCharge + $val['discount'] + $val['subscription'])),
                    'Fee Payment Gateway' =>(float)$paymentCharge,
                    'Net Sales (income outlet)' => (float)$val['income_outlet'],
                    'Payment' => $payment,
                    'Point Use' => $poinUse,
                    'Point Cashback' => $val['transaction_cashback_earned'],
                    'Point Refund' => $pointRefund,
                    'Refund' => $paymentRefund,
                    'Sales Type' => (!empty($deliveryPrice) ? 'Delivery' : $val['trasaction_type']),
                    'Received Time' =>  ($val['receive_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['receive_at']))),
                    'Ready Time' =>  ($val['ready_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['ready_at']))),
                    'Taken Time' =>  $taken,
                    'Arrived Time' =>  ($val['arrived_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['arrived_at'])))
                ];
            }else{
                $paymentStatus = $val['transaction_payment_status'];
                $status = '';
                if(empty($val['receive_at'])){
                    $status = 'Pending';
                }elseif(!empty($val['receive_at']) && empty($val['ready_at'])){
                    $status = 'Received';
                }elseif(!empty($val['ready_at']) && empty($val['taken_at'])){
                    $status = 'Ready';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] == 'Customer'){
                    $status = 'Taken by Customer';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] != 'Customer') {
                    $status = 'Taken by Driver';
                }elseif(!empty($val['taken_by_system_at'])){
                    $status = 'Taken by System';
                }elseif(!empty($val['reject_at'])){
                    $status = 'Reject';
                }

                $deliveryPrice = $val['transaction_shipment'];
                if(!empty($val['transaction_shipment_go_send'])){
                    $deliveryPrice = $val['transaction_shipment_go_send'];
                }

                $dt = [
                    'Name' => $val['name'],
                    'Phone' => $val['phone'],
                    'Email' => $val['email'],
                    'Transaction Date' => date('d M Y', strtotime($val['transaction_date'])),
                    'Transaction Time' => date('H:i', strtotime($val['transaction_date'])),
                    'Payment Status' => $paymentStatus,
                    'Transaction Status' => $status,
                    'Outlet Code' => $val['outlet_code'],
                    'Outlet Name' => htmlspecialchars($val['outlet_name']),
                    'Gross Sales' => number_format($val['transaction_grandtotal']),
                    'Receipt number' => $val['transaction_receipt_number'],
                    'Point Received' => number_format($val['transaction_cashback_earned']),
                    'Payments' => $payment,
                    'Transaction Type' => (!empty($deliveryPrice) ? 'Delivery' : $val['trasaction_type']),
                    'Delivery Fee' => number_format($deliveryPrice)??'-'
                ];
            }

            yield $dt;
        }
    }

    function getKeyVariant($arr, $id)
    {
        foreach ($arr as $key => $val) {
            if ($val['id_product_variant'] === $id) {
                return $key;
            }
        }
        return null;
    }

    function getParentVariant($arr, $id)
    {
        $key = $this->getKeyVariant($arr, $id);
        if ($arr[$key]['id_parent'] == 0)
        {
            return $id;
        }
        else
        {
            return $this->getParentVariant($arr, $arr[$key]['id_parent']);
        }
    }


    public function transactionDetail(TransactionDetail $request){
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $codeIndo = [
            'Rejected' => [
                'code' => 1,
                'text' => 'Dibatalkan'
            ],
            'Unpaid' => [
                'code' => 2,
                'text' => 'Belum dibayar'
            ],
            'Pending' => [
                'code' => 3,
                'text' => 'Pending'
            ],
            'On Progress' => [
                'code' => 4,
                'text' => 'Diproses'
            ],
            'On Delivery' => [
                'code' => 5,
                'text' => 'Dikirim'
            ],
            'Completed' => [
                'code' => 6,
                'text' => 'Selesai'
            ]
        ];

        $transaction = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->where(['transactions.id_transaction' => $id])
            ->leftJoin('transaction_shipments','transaction_shipments.id_transaction','=','transactions.id_transaction')
            ->leftJoin('cities','transaction_shipments.destination_id_city','=','cities.id_city')
            ->leftJoin('provinces','provinces.id_province','=','cities.id_province');

        if(empty($request->json('admin'))){
            $transaction = $transaction->where('id_user', $request->user()->id);
        }

        $transaction = $transaction->first();

        if(empty($transaction)){
            return response()->json(MyHelper::checkGet($transaction));
        }
        $transactionProducts = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                            ->where('id_transaction', $id)
                            ->with(['variants'=> function($query){
                                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')
                                    ->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
                            }])
                            ->select('transaction_products.*', 'products.product_name')->get()->toArray();

        $products = [];
        foreach ($transactionProducts as $value){
            $image = ProductPhoto::where('id_product', $value['id_product'])->orderBy('product_photo_order', 'asc')->first()['url_product_photo']??config('url.storage_url_api').'img/default.jpg';
            $products[] = [
                'id_product' => $value['id_product'],
                'product_name' => $value['product_name'],
                'product_qty' => $value['transaction_product_qty'],
                'need_recipe_status' =>  $value['transaction_product_recipe_status'],
                'product_base_price' => 'Rp '. number_format((int)$value['transaction_product_price'],0,",","."),
                'product_total_price' => 'Rp '. number_format((int)$value['transaction_product_subtotal'],0,",","."),
                'discount_all' => (int)$value['transaction_product_discount_all'],
                'discount_all_text' => 'Rp '.number_format((int)$value['transaction_product_discount_all'],0,",","."),
                'discount_each_product' => (int)$value['transaction_product_base_discount'],
                'discount_each_product_text' => 'Rp '.number_format((int)$value['transaction_product_base_discount'],0,",","."),
                'note' => $value['transaction_product_note'],
                'variants' => implode(', ', array_column($value['variants'], 'product_variant_name')),
                'image' => $image
            ];
        }

        $paymentDetail = [
            [
                'text' => 'Subtotal',
                'value' => 'Rp '. number_format((int)$transaction['transaction_subtotal'],0,",",".")
            ],
            [
                'text' => 'Biaya Kirim',
                'value' => 'Rp '. number_format((int)$transaction['transaction_shipment'],0,",",".")
            ]
        ];

        if(!empty($transaction['transaction_discount'])){
            $codePromo = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->first()['promo_code']??'';
            $paymentDetail[] = [
                'text' => 'Discount'.(!empty($transaction['transaction_discount_delivery'])? ' Biaya Kirim':'').(!empty($codePromo) ?' ('.$codePromo.')' : ''),
                'value' => '-Rp '. number_format((int)abs($transaction['transaction_discount']),0,",",".")
            ];
        }

        $grandTotal = $transaction['transaction_grandtotal'];
        $trxPaymentBalance = TransactionPaymentBalance::where('id_transaction', $transaction['id_transaction'])->first()['balance_nominal']??0;

        if(!empty($trxPaymentBalance)){
            $paymentDetail[] = [
                'text' => 'Point yang digunakan',
                'value' => '-'.number_format($trxPaymentBalance,0,",",".")
            ];
            $grandTotal = $grandTotal - $trxPaymentBalance;
        }

        $trxPaymentMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $transaction['id_transaction_group'])->first();
        $trxPaymentXendit = TransactionPaymentXendit::where('id_transaction_group', $transaction['id_transaction_group'])->first();

        if(!empty($trxPaymentMidtrans)){
            $paymentMethod = $trxPaymentMidtrans['payment_type'].(!empty($trxPaymentMidtrans['bank']) ? ' ('.$trxPaymentMidtrans['bank'].')':'');
            $paymentLogo = config('payment_method.midtrans_'.strtolower($paymentMethod).'.logo');
        }elseif(!empty($trxPaymentXendit)){
            $paymentMethod = $trxPaymentXendit['type'];
            $paymentLogo = config('payment_method.xendit_'.strtolower($paymentMethod).'.logo');
        }

        $district = Districts::join('subdistricts', 'subdistricts.id_district', 'districts.id_district')
            ->where('id_subdistrict', $transaction['depart_id_subdistrict'])->first();
        $address = [
            'destination_name' => $transaction['destination_name'],
            'destination_phone' => $transaction['destination_phone'],
            'destination_address' => $transaction['destination_address'],
            'destination_description' => $transaction['destination_description'],
            'destination_province' => $transaction['province_name'],
            'destination_city' => $transaction['city_name'],
            'destination_district' => $district['district_name'],
            'destination_subdistrict' => $district['subdistrict_name']
        ];

        $tracking = [];
        $trxTracking = TransactionShipmentTrackingUpdate::where('id_transaction', $id)->orderBy('tracking_date_time', 'desc')->orderBy('id_transaction_shipment_tracking_update', 'desc')->get()->toArray();
        foreach ($trxTracking as $value){
            $tracking[] = [
                'date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($value['tracking_date_time'])), true),
                'description' => $value['tracking_description']
            ];
        }

        $result = [
            'id_transaction' => $id,
            'transaction_receipt_number' => $transaction['transaction_receipt_number'],
            'transaction_status_code' => $codeIndo[$transaction['transaction_status']]['code']??'',
            'transaction_status_text' => $codeIndo[$transaction['transaction_status']]['text']??'',
            'transaction_date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_date'])), true),
            'transaction_products' => $products,
            'address' => $address,
            'transaction_grandtotal' => 'Rp '. number_format($grandTotal,0,",","."),
            'outlet_name' => $transaction['outlet_name'],
            'outlet_logo' => (empty($transaction['outlet_image_logo_portrait']) ? config('url.storage_url_api').'img/default.jpg': config('url.storage_url_api').$transaction['outlet_image_logo_portrait']),
            'delivery' => [
                'delivery_id' => $transaction['order_id'],
                'pickup_code' => $transaction['shipment_pickup_code'],
                'pickup_date_start' => $transaction['shipment_pickup_time_start'],
                'pickup_date_end' => $transaction['shipment_pickup_time_end'],
                'delivery_method' => strtoupper($transaction['shipment_courier']),
                'delivery_service' => ucfirst($transaction['shipment_courier_service']),
                'delivery_price' => 'Rp '. number_format((int)$transaction['transaction_shipment'],0,",","."),
                'delivery_tracking' => $tracking,
                'estimated' => $transaction['shipment_courier_etd']
            ],
            'user' => User::where('id', $transaction['id_user'])->select('name', 'email', 'phone')->first(),
            'payment' => $paymentMethod??'',
            'payment_logo' => $paymentLogo??'',
            'payment_detail' => $paymentDetail,
            'point_receive' => (!empty($transaction['transaction_cashback_earned'] && $transaction['transaction_status'] != 'Rejected') ? 'Mendapatkan +'.number_format((int)$transaction['transaction_cashback_earned'],0,",",".").' Points Dari Transaksi ini' : '')
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    // api/transaction/item
    // api order lagi
    public function transactionDetailTrx(Request $request) {
        $trid = $request->json('id_transaction');
        $rn = $request->json('request_number');
        $trx = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
            ->select('transactions.id_transaction', 'transactions.id_user', 'transactions.id_outlet', 'outlets.outlet_code', 'pickup_by', 'pickup_type', 'pickup_at', 'id_transaction_pickup')->where([
                'transactions.id_transaction' => $trid,
                'id_user' => $request->user()->id
            ])->first();
        if(!$trx){
            return [
                'status'=>'fail',
                'messages'=>['Transaction not found']
            ];
        }
        $id_transaction = $trx['id_transaction'];
        $pts = TransactionProduct::select(DB::raw('
            0 as id_custom,
            transaction_products.id_product,
            id_transaction_product,
            id_product_variant_group,
            id_brand,
            transaction_products.id_outlet,
            outlets.outlet_code,
            outlets.outlet_different_price,
            transaction_product_qty as qty,
            products.product_name,
            products.product_code,
            transaction_products.transaction_product_note as note,
            transaction_products.transaction_product_price
            '))
            ->join('products','products.id_product','=','transaction_products.id_product')
            ->join('outlets','outlets.id_outlet','=','transaction_products.id_outlet')
            ->where('transaction_products.type', 'product')
            ->whereNull('id_transaction_bundling_product')
            ->where(['id_transaction'=>$id_transaction])
            ->with(['modifiers'=>function($query){
                $query->select('id_transaction_product','product_modifiers.code','transaction_product_modifiers.id_product_modifier','qty','product_modifiers.text', 'transaction_product_modifier_price', 'modifier_type')->join('product_modifiers','product_modifiers.id_product_modifier','=','transaction_product_modifiers.id_product_modifier');
            },'variants'=>function($query){
                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
            }])->get()->toArray();

        $id_outlet = $trx['id_outlet'];
        $total_mod_price = 0;
        foreach ($pts as &$pt) {
            if ($pt['outlet_different_price']) {
                $pt['product_price'] = ProductSpecialPrice::select('product_special_price')->where([
                    'id_outlet' => $pt['id_outlet'],
                    'id_product' => $pt['id_product']
                ])->pluck('product_special_price')->first()?:$pt['transaction_product_price'];
            } else {
                $pt['product_price'] = ProductGlobalPrice::select('product_global_price')->where('id_product',$pt['id_product'])->pluck('product_global_price')->first()?:$pt['transaction_product_price'];
            }
            $pt['extra_modifiers'] = [];
            foreach ($pt['modifiers'] as $key => &$modifier) {
                if ($pt['outlet_different_price']) {
                    $price = ProductModifierPrice::select('product_modifier_price')->where([
                        'id_product_modifier'=>$modifier['id_product_modifier'],
                        'id_outlet' => $id_outlet
                    ])->pluck('product_modifier_price')->first()?:$modifier['transaction_product_modifier_price'];
                } else {
                    $price = ProductModifierGlobalPrice::select('product_modifier_price')->where('id_product_modifier', $modifier['id_product_modifier'])->pluck('product_modifier_price')->first()?:$modifier['transaction_product_modifier_price'];
                }
                $total_mod_price+=$price*$modifier['qty'];
                $modifier['product_modifier_price'] = MyHelper::requestNumber($price,$rn);
                unset($modifier['transaction_product_modifier_price']);
                if ($modifier['modifier_type'] == 'Modifier Group') {
                    $pt['variants'][] = [
                        'id_transaction_product' => $pt['id_transaction_product'],
                        'id_product_variant' => $modifier['id_product_modifier'],
                        'product_variant_name' => $modifier['text'],
                        'product_variant_price' => (double) $price,
                    ];
                    $pt['extra_modifiers'][] = $modifier['id_product_modifier'];
                    unset($pt['modifiers'][$key]);
                }
            }
            $pt['modifiers'] = array_values($pt['modifiers']);
            if ($pt['id_product_variant_group']) {
                if ($pt['outlet_different_price']) {
                    $product_price = ProductVariantGroupSpecialPrice::select('product_variant_group_price')->where('id_product_variant_group', $pt['id_product_variant_group'])->first();
                } else {
                    $product_price = ProductVariantGroup::select('product_variant_group_price')->where('id_product_variant_group', $pt['id_product_variant_group'])->first();
                }
                $pt['selected_variant'] = Product::getVariantParentId($pt['id_product_variant_group'], Product::getVariantTree($pt['id_product'], $pt)['variants_tree'] ?? [], $pt['extra_modifiers']);
                if (!$product_price) {
                    $pt['product_price'] = $pt['product_price'] + array_sum(array_column($pt['variants'], 'transaction_product_variant_price'));
                } else {
                    $pt['product_price'] = $product_price->product_variant_group_price;
                }
            } else {
                $pt['selected_variant'] = [];
            }
            $order = array_flip($pt['selected_variant']);
            usort($pt['variants'], function ($a, $b) use ($order) {
                return ($order[$a['id_product_variant']]??999) <=> ($order[$b['id_product_variant']]??999);
            });
            $pt['product_price_total'] = MyHelper::requestNumber(($total_mod_price + $pt['product_price'])*$pt['qty'],$rn);
            $pt['product_price'] = MyHelper::requestNumber($pt['product_price'],$rn);
            $pt['note'] = $pt['note']?:'';
            unset($pt['transaction_product_price']);
        }

        //item bundling
        $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
            ->where('id_transaction', $id_transaction)->get()->toArray();
        $itemBundling = [];
        foreach ($getBundling as $key=>$bundling){
            $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();
            $basePriceBundling = 0;
            $products = [];
            foreach ($bundlingProduct as $bp){
                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.code', 'transaction_product_modifiers.qty', 'transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as text',
                        DB::raw('FLOOR(transaction_product_modifier_price * '.$bp['transaction_product_bundling_qty'].' * '.$bundling['transaction_bundling_product_qty'].') as product_modifier_price'))->get()->toArray();
                $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('product_variants.id_product_variant', 'product_variants.product_variant_name',  DB::raw('FLOOR(transaction_product_variant_price) as product_variant_price'))->get()->toArray();
                $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as product_variant_price')->get()->toArray();
                $variants = array_merge($variantPrice, $variantNoPrice);
                $extraMod = array_column($variantNoPrice, 'id_product_variant');

                for ($i=1;$i<=$bp['transaction_product_bundling_qty'];$i++){
                    $products[] = [
                        'id_brand' => $bp['id_brand'],
                        'id_bundling' => $bundling['id_bundling'],
                        'id_bundling_product' => $bp['id_bundling_product'],
                        'id_product' => $bp['id_product'],
                        'id_product_variant_group' => $bp['id_product_variant_group'],
                        'note' => $bp['transaction_product_note'],
                        'product_code' => $bp['product_code'],
                        'product_name' => $bp['product_name'],
                        'note' => $bp['transaction_product_note'],
                        'extra_modifiers' => $extraMod,
                        'variants' => $variants,
                        'modifiers' => $mod
                    ];
                }
                $productPrice = $bp['transaction_product_price'] + $bp['transaction_variant_subtotal'];
                $basePriceBundling = $basePriceBundling + ($productPrice * $bp['transaction_product_bundling_qty']);
            }

            $itemBundling[] = [
                'id_custom' => $key+1,
                'id_bundling' => $bundling['id_bundling'],
                'bundling_name' => $bundling['bundling_name'],
                'bundling_qty' => $bundling['transaction_bundling_product_qty'],
                'bundling_code' =>  $bundling['bundling_code'],
                'bundling_base_price' => (int)$bundling['transaction_bundling_product_base_price'],
                'bundling_price_no_discount' => $basePriceBundling * $bundling['transaction_bundling_product_qty'],
                'bundling_price_total' => (int)$bundling['transaction_bundling_product_subtotal'],
                'products' => $products
            ];
        }

        if(empty($pts) && empty($getBundling)){
            return MyHelper::checkGet([]);
        }

        $result = [
            'id_outlet' => $trx->id_outlet,
            'outlet_code' => $trx->outlet_code,
            'item' => $pts,
            'item_bundling' => $itemBundling
        ];
        if ($trx->pickup_by == 'Customer') {
            $result += [
                'transaction_type' => 'Pickup Order',
                'pickup_type' => $trx->pickup_type
            ];
            if ($trx->pickup_type == 'set time') {
                $result += [
                    'pickup_at' => date('H:i',strtotime($trx->pickup_at))
                ];
            }
        } else {
        	if ($trx->pickup_by == 'GO-SEND') {
            	$address = TransactionPickupGoSend::where('id_transaction_pickup',$trx->id_transaction_pickup)->first();
	            $result += [
	                'transaction_type' => 'Delivery Order',
	                'courier' => 'gosend',
	                'destination' => [
	                    'name' => $address->destination_address_name?:$address->destination_short_address,
	                    'short_address' => $address->destination_short_address,
	                    'address' => $address->destination_address,
	                    'description' => $address->destination_note,
	                    'latitude' => $address->destination_latitude,
	                    'longitude' => $address->destination_longitude,
	                ]
	            ];
        	} else {
        		$address = TransactionPickupWehelpyou::where('id_transaction_pickup', $trx->id_transaction_pickup)->first();
	            $result += [
	                'transaction_type' => 'Delivery Order',
	                'courier' => $address->courier,
	                'destination' => [
	                    'name' => $address->address_name ?: ($address->short_address ?: $address->receiver_address),
	                    'short_address' => $address->short_address ?: $address->receiver_address,
	                    'address' => $address->receiver_address,
	                    'description' => $address->receiver_notes,
	                    'latitude' => $address->receiver_latitude,
	                    'longitude' => $address->receiver_longitude,
	                ]
	            ];
        	}

            if (!$result['destination']['name']) {
                $ua = UserAddress::where(['id_user' => $trx->id_user, 'latitude'=>$address->destination_latitude, 'longitude' => $address->destination_longitude])->first();
                if ($ua) {
                    $result['destination']['name'] = $ua->name?:$ua->short_address;
                    $result['destination']['short_address'] = $ua->short_address;
                }
            }
        }
        return MyHelper::checkGet($result);
    }

    public function transactionPointDetail(Request $request) {
        $id     = $request->json('id');
        $select = [];
        $data   = LogPoint::where('id_log_point', $id)->first();

        if ($data['source'] == 'Transaction') {
            $select = Transaction::with('outlet')->where('id_transaction', $data['id_reference'])->first();

            $data['date'] = $select['transaction_date'];
            $data['type'] = 'trx';
            $data['outlet'] = $select['outlet']['outlet_name'];
            if ($select['trasaction_type'] == 'Offline') {
                $data['online'] = 0;
            } else {
                $data['online'] = 1;
            }

        } else {
            $select = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $data['id_reference'])->first();
            $data['type']   = 'voucher';
            $data['date']   = date('Y-m-d H:i:s', strtotime($select['claimed_at']));
            $data['outlet'] = $select['outlet']['outlet_name'];
            $data['online'] = 1;
        }

        $data['detail'] = $select;
        return response()->json(MyHelper::checkGet($data));
    }

    public function transactionBalanceDetail(Request $request) {
        $id     = $request->json('id');
        $select = [];
        $data   = LogBalance::where('id_log_balance', $id)->first();
        // dd($data);
        $statusTrx = ['Online Transaction', 'Transaction', 'Transaction Failed', 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Rejected Order Ovo', 'Reversal'];
        if (in_array($data['source'], $statusTrx)) {
            $select = Transaction::select(DB::raw('transactions.*,sum(transaction_products.transaction_product_qty) item_total'))->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')->with('outlet')->where('transactions.id_transaction', $data['id_reference'])->groupBy('transactions.id_transaction')->first();

            $data['date'] = $select['transaction_date'];
            $data['type'] = 'trx';
            $data['item_total'] = $select['item_total'];
            $data['outlet'] = $select['outlet']['outlet_name'];
            if ($select['trasaction_type'] == 'Offline') {
                $data['online'] = 0;
            } else {
                $data['online'] = 1;
            }
            $data['detail'] = $select;

            $result = [
                'type'                          => $data['type'],
                'id_log_balance'                => $data['id_log_balance'],
                'id_transaction'                => $data['detail']['id_transaction'],
                'transaction_receipt_number'    => $data['detail']['transaction_receipt_number'],
                'transaction_date'              => date('d M Y H:i', strtotime($data['detail']['transaction_date'])),
                'balance'                       => MyHelper::requestNumber($data['balance'], '_POINT'),
                'transaction_grandtotal'        => MyHelper::requestNumber($data['detail']['transaction_grandtotal'], '_CURRENCY'),
                'transaction_cashback_earned'   => MyHelper::requestNumber($data['detail']['transaction_cashback_earned'], '_POINT'),
                'name'                          => $data['detail']['outlet']['outlet_name'],
                'title'                         => 'Total Payment'
            ];
        } elseif ($data['source'] == 'Quest Benefit') {
            $quest = Quest::find($data['id_reference']);
            $result = [
                'type'                          => 'quest',
                'id_log_balance'                => $data['id_log_balance'],
                'id_quest'                      => $data['id_reference'],
                'transaction_date'              => date('d M Y H:i', strtotime($data['created_at'])),
                'balance'                       => '+' . MyHelper::requestNumber($data['balance'], '_POINT'),
                'title'                         => $quest['name'] ?? 'Misi tidak diketahui',
            ];
        } else {
            $select = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $data['id_reference'])->first();
            $data['type']   = 'voucher';
            $data['date']   = date('Y-m-d H:i:s', strtotime($select['claimed_at']));
            $data['outlet'] = $select['outlet']['outlet_name'];
            $data['online'] = 1;
            $data['detail'] = $select;

            $usedAt = '';
            $status = 'UNUSED';
            if($data['detail']['used_at'] != null){
                $usedAt = date('d M Y H:i', strtotime($data['detail']['used_at']));
                $status = 'USED';
            }

            $price = 0;
            if($data['detail']['voucher_price_cash'] != NULL){
                $price = MyHelper::requestNumber($data['detail']['voucher_price_cash'],'_CURRENCY');
            }elseif($data['detail']['voucher_price_point'] != NULL){
                $price = MyHelper::requestNumber($data['detail']['voucher_price_point'],'_POINT').' points';
            }

            $result = [
                'type'                          => $data['type'],
                'id_log_balance'                => $data['id_log_balance'],
                'id_deals_user'                 => $data['detail']['id_deals_user'],
                'status'                        => $status,
                'used_at'                       => $usedAt,
                'transaction_receipt_number'    => implode('', [strtotime($data['date']), $data['detail']['id_deals_user']]),
                'transaction_date'              => date('d M Y H:i', strtotime($data['date'])),
                'balance'                       => MyHelper::requestNumber($data['balance'], '_POINT'),
                'transaction_grandtotal'        => $price,
                'transaction_cashback_earned'   => null,
                'name'                          => 'Buy Voucher',
                'title'                         => $data['detail']['dealVoucher']['deal']['deals_title']
            ];
        }

        return response()->json(MyHelper::checkGet($result));
    }

    public function setting($value) {
        $setting = Setting::where('key', $value)->first();

        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

    public function transactionHistory(TransactionHistory $request) {
        if($request->json('phone') == "") {
            $data = $request->user();
            $id   = $data['id'];
        } else {
            $user = User::where('phone', $request->json('phone'))->get->first();
            $id = $user['id'];
        }

        $transaction = Transaction::where('id_user', $id)->with('user', 'productTransaction', 'user.city', 'user.city.province', 'productTransaction.product', 'productTransaction.product.category', 'productTransaction.product.photos', 'productTransaction.product.discount')->get()->toArray();

        return response()->json(MyHelper::checkGet($transaction));
    }

    public function getProvince(GetProvince $request) {
        $id_province = $request->json('id_province');
        if (isset($id_province)) {
            $province = Province::where('id_province', $id_province)->orderBy('id_province', 'ASC');
        } else {
            $province = Province::orderBy('id_province', 'ASC');
        }

        $province = $province->with('cities')->get();

        return response()->json(MyHelper::checkGet($province));

    }

    public function getCity(GetCity $request) {
        $id_city = $request->json('id_city');
        if (isset($id_city)) {
            $city = City::where('id_city', $id_city)->orderBy('id_city', 'ASC');
        } else {
            $city = City::orderBy('id_city', 'ASC');
        }

        $city = $city->with('province')->get();

        return response()->json(MyHelper::checkGet($city));

    }

    public function getSubdistrict(GetSub $request) {
        $id_city = $request->json('id_city');
        $id_subdistrict = $request->json('id_subdistrict');

        $subdistrict = MyHelper::urlTransaction('https://pro.rajaongkir.com/api/subdistrict?city='.$id_city.'&id='.$id_subdistrict, 'GET', '', 'application/json');

        if ($subdistrict->rajaongkir->status->code == 200) {
            $subdistrict = $subdistrict->rajaongkir->results;
        }

        return response()->json(MyHelper::checkGet($subdistrict));
    }

    public function getNearbyAddress(GetNearbyAddress $request) {
        $id = $request->user()->id;
        $distance = Setting::select('value')->where('key','history_address_max_distance')->pluck('value')->first()?:50;
        $maxmin = MyHelper::getRadius($request->json('latitude'),$request->json('longitude'),$distance);
        $latitude = $request->json('latitude');
        $longitude = $request->json('longitude');

        $gmaps = $this->getListLocation($request);

        if($gmaps['status'] === 'OK'){
        	if ($gmaps['send_gmaps_data'] ?? false) {
            	MyHelper::sendGmapsData($gmaps['results']);
        	}
            $gmaps = $gmaps['results'];
        }else{
            $gmaps = [];
        };

        $maxmin = MyHelper::getRadius($latitude,$longitude,$distance);
        $user_address = UserAddress::select('id_user_address','short_address','address','latitude','longitude','description','favorite')->where('id_user',$id)
            ->whereBetween('latitude',[$maxmin['latitude']['min'],$maxmin['latitude']['max']])
            ->whereBetween('longitude',[$maxmin['longitude']['min'],$maxmin['longitude']['max']])
            ->take(10);

        if($keyword = $request->json('keyword')){
            $user_address->where(function($query) use ($keyword) {
                $query->where('name',$keyword);
                $query->orWhere('address',$keyword);
                $query->orWhere('short_address',$keyword);
            });
        }

        $user_address = $user_address->get()->toArray();

        $saved = array_map(function($i){
            return [
                'latitude' => $i['latitude'],
                'longitude' => $i['longitude']
            ];
        },$user_address);

        foreach ($gmaps as $key => &$gmap){
            $coor = [
                'latitude' => number_format($gmap['geometry']['location']['lat'],8),
                'longitude' => number_format($gmap['geometry']['location']['lng'],8)
            ];
            if(in_array($coor, $saved)){
                unset($gmaps[$key]);
            }
            $gmap = [
                'id_user_address' => 0,
                'short_address' => $gmap['name'],
                'address' => $gmap['vicinity']??'',
                'latitude' => $coor['latitude'],
                'longitude' => $coor['longitude'],
                'description' => '',
                'favorite' => 0
            ];
        }

        // mix history and gmaps
        $user_address = array_merge($user_address,$gmaps);

        // reorder based on distance
        usort($user_address,function(&$a,&$b) use ($latitude,$longitude){
            return MyHelper::count_distance($latitude,$longitude,$a['latitude'],$a['longitude']) <=> MyHelper::count_distance($latitude,$longitude,$b['latitude'],$b['longitude']);
        });

        $selected_address = null;
        foreach ($user_address as $key => $addr) {
            if ($addr['favorite']) {
                $selected_address = $addr;
                break;
            }
            if ($addr['id_user_address']) {
                $selected_address = $addr;
                continue;
            }
            if ($key == 0) {
                $selected_address = $addr;
            }
        }

        if(!$selected_address){
            $selected_address = $user_address[0]??null;
        }
        // apply limit;
        // $max_item = Setting::select('value')->where('key','history_address_max_item')->pluck('value')->first()?:10;
        // $user_address = array_splice($user_address,0,$max_item);
        $result = [];
        if($user_address){
            $result = [
                'default' => $selected_address,
                'nearby' => $user_address
            ];
        }
        return response()->json(MyHelper::checkGet($result, 'Lokasi tidak ditemukan'));
    }

    public function getDefaultAddress (GetNearbyAddress $request) {
        $id = $request->user()->id;
        $distance = Setting::select('value')->where('key','history_address_max_distance')->pluck('value')->first()?:50;
        $maxmin = MyHelper::getRadius($request->json('latitude'),$request->json('longitude'),$distance);
        $latitude = $request->json('latitude');
        $longitude = $request->json('longitude');

        $maxmin = MyHelper::getRadius($latitude,$longitude,$distance);
        $user_address = UserAddress::select('id_user_address','short_address','address','latitude','longitude','description','favorite')->where('id_user',$id)
            ->whereBetween('latitude',[$maxmin['latitude']['min'],$maxmin['latitude']['max']])
            ->whereBetween('longitude',[$maxmin['longitude']['min'],$maxmin['longitude']['max']])
            ->take(10);

        if($keyword = $request->json('keyword')){
            $user_address->where(function($query) use ($keyword) {
                $query->where('name',$keyword);
                $query->orWhere('address',$keyword);
                $query->orWhere('short_address',$keyword);
            });
        }

        $user_address = $user_address->get()->toArray();
        if (!$user_address) {
            $gmaps = $this->getListLocation($request);

            if($gmaps['status'] === 'OK'){
            	if ($gmaps['send_gmaps_data'] ?? false) {
	            	MyHelper::sendGmapsData($gmaps['results']);
	        	}
                $gmaps = $gmaps['results'];
            }else{
                return MyHelper::checkGet([]);
            };

            foreach ($gmaps as $key => &$gmap){
                $coor = [
                    'latitude' => number_format($gmap['geometry']['location']['lat'],8),
                    'longitude' => number_format($gmap['geometry']['location']['lng'],8)
                ];
                $gmap = [
                    'id_user_address' => 0,
                    'short_address' => $gmap['name'],
                    'address' => $gmap['vicinity']??'',
                    'latitude' => $coor['latitude'],
                    'longitude' => $coor['longitude'],
                    'description' => '',
                    'favorite' => 0
                ];
            }
            // mix history and gmaps
            $user_address = array_merge($user_address,$gmaps);
        }

        // reorder based on distance
        usort($user_address,function(&$a,&$b) use ($latitude,$longitude){
            return MyHelper::count_distance($latitude,$longitude,$a['latitude'],$a['longitude']) <=> MyHelper::count_distance($latitude,$longitude,$b['latitude'],$b['longitude']);
        });

        foreach ($user_address as $key => $addr) {
            if ($addr['favorite']) {
                $selected_address = $addr;
                break;
            }
            if ($addr['id_user_address']) {
                $selected_address = $addr;
                continue;
            }
            if ($key == 0) {
                $selected_address = $addr;
            }
        }

        if(!$selected_address){
            $selected_address = $user_address[0]??null;
        }
        // apply limit;
        // $max_item = Setting::select('value')->where('key','history_address_max_item')->pluck('value')->first()?:10;
        // $user_address = array_splice($user_address,0,$max_item);
        $result = [];
        if($user_address){
            $result = [
                'default' => $selected_address
            ];
        }
        return response()->json(MyHelper::checkGet($result));
    }

    public function getListLocation($request)
    {
        $key_maps = env('LOCATION_PRIMARY_KEY');
        $locationUrl = env('LOCATION_PRIMARY_URL');
        if (env('LOCATION_PRIMARY_KEY_TOTAL')) {
            $weekNow = date('W') % env('LOCATION_PRIMARY_KEY_TOTAL');
            $key_maps = env('LOCATION_PRIMARY_KEY'.$weekNow, $key_maps);
        }

    	$param = [
            'key'		=> $key_maps,
            'location'	=> sprintf('%s,%s',$request->json('latitude'),$request->json('longitude')),
            'rankby'	=> 'distance'
        ];

        if($request->json('keyword')){
            $param['keyword'] = $request->json('keyword');
        }

        $gmaps = MyHelper::get($locationUrl.'?'.http_build_query($param));

    	if ($gmaps['status'] !== 'OK'
    		|| ($gmaps['status'] === 'OK' && count($gmaps['results']) < env('LOCATION_MIN_TOTAL'))
    	) {
	    	// get place from google maps . max 20
	        $key_maps = env('LOCATION_SECONDARY_KEY');
        	$locationUrl = env('LOCATION_SECONDARY_URL');
	        if (env('LOCATION_SECONDARY_KEY_TOTAL')) {
	            $weekNow = date('W') % env('LOCATION_SECONDARY_KEY_TOTAL');
	            $key_maps = env('LOCATION_SECONDARY_KEY'.$weekNow, $key_maps);
	        }
	        $param = [
	            'key'=>$key_maps,
	            'location'=>sprintf('%s,%s',$request->json('latitude'),$request->json('longitude')),
	            'rankby'=>'distance'
	        ];
	        if($request->json('keyword')){
	            $param['keyword'] = $request->json('keyword');
	        }
        	$gmaps = MyHelper::get($locationUrl.'?'.http_build_query($param));
    	}

    	$gmaps['send_gmaps_data'] = (strpos($locationUrl, 'google') !== false) ? true : false;

        return $gmaps;
    }

    public function getAddress(GetAddress $request) {
        $id = $request->user()->id;

        if (!$id) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }

        $address = UserAddress::join('subdistricts', 'subdistricts.id_subdistrict', 'user_addresses.id_subdistrict')
            ->join('districts', 'districts.id_district', 'subdistricts.id_district')
            ->join('cities', 'cities.id_city', 'districts.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->select('id_user_address', 'receiver_name', 'receiver_phone', 'receiver_email',
                'provinces.id_province', 'province_name',
                'cities.id_city', 'city_name',
                'districts.id_district', 'district_name',
                'subdistricts.id_subdistrict', 'subdistrict_name',
                'address', 'postal_code', 'favorite as main_address')
            ->where('id_user', $id)
            ->orderBy('id_user_address', 'DESC')->get()->toArray();

        return response()->json(MyHelper::checkGet($address));
    }


    public function detailAddress(GetAddress $request) {
        $id = $request->user()->id;
        $address = UserAddress::join('subdistricts', 'subdistricts.id_subdistrict', 'user_addresses.id_subdistrict')
            ->join('districts', 'districts.id_district', 'subdistricts.id_district')
            ->join('cities', 'cities.id_city', 'districts.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->select('id_user_address', 'receiver_name', 'receiver_phone', 'receiver_email',
                'provinces.id_province', 'province_name',
                'cities.id_city', 'city_name',
                'districts.id_district', 'district_name',
                'subdistricts.id_subdistrict', 'subdistrict_name',
                'address', 'postal_code', 'favorite as main_address')
            ->where(['id_user'=> $id,'id_user_address'=>$request->id_user_address])->first();

        return response()->json(MyHelper::checkGet($address));
    }

    public function addAddress(AddAddress $request) {
        $post = $request->json()->all();

        $phone = $post['receiver_phone'];
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Format nomor telepon tidak valid']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        if(!empty($post['main_address'])){
            UserAddress::where('id_user', $request->user()->id)->update(['favorite' => 0]);
        }

        $subdis = Subdistricts::where('id_subdistrict', $post['id_subdistrict'])->first();
        $idCity = Districts::where('id_district',$subdis['id_district']??null)->first()['id_city']??null;

        $create = UserAddress::create([
            'id_user' => $request->user()->id,
            'name' => isset($post['name']) ? $post['name'] : " ",
            'receiver_name' => $post['receiver_name'],
            'receiver_phone' => $phone,
            'receiver_email' => $post['receiver_email'],
            'address' => $post['address'],
            "id_city" => (empty($idCity) ? $post['id_city'] : $idCity),
            "id_subdistrict" => $post['id_subdistrict'],
            'postal_code' => (empty($subdis['subdistrict_postal_code']) ? $post['postal_code'] : $subdis['subdistrict_postal_code']),
            'favorite' => $post['main_address']??0
        ]);

        return response()->json(MyHelper::checkCreate($create));
    }

    public function updateAddress (UpdateAddress $request) {
        $post = $request->json()->all();
        $data['id_user'] = $request->user()->id;

        if (empty($data['id_user'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User not found']
            ]);
        }

        $phone = $post['receiver_phone'];
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Format nomor telepon tidak valid']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        if(!empty($post['main_address'])){
            UserAddress::where('id_user', $request->user()->id)->update(['favorite' => 0]);
        }

        $dtUpdate = [
            'receiver_name' => $post['receiver_name'],
            'receiver_phone' => $phone,
            'receiver_email' => $post['receiver_email'],
            'address' => $post['address'],
            "id_city" => (empty($idCity) ? $post['id_city'] : $idCity),
            "id_subdistrict" => $post['id_subdistrict'],
            'postal_code' => (empty($subdis['subdistrict_postal_code']) ? $post['postal_code'] : $subdis['subdistrict_postal_code']),
            'favorite' => $post['main_address']??0
        ];

        $update = UserAddress::where('id_user_address', $post['id_user_address'])->update($dtUpdate);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function deleteAddress (DeleteAddress $request) {
        $id = $request->json('id_user_address');

        $check = UserAddress::where('id_user_address', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address not found']
            ]);
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));
    }

    public function getCourier(Request $request) {
        $courier = Courier::orderBy('id_courier', 'ASC')->get();

        return response()->json(MyHelper::checkGet($courier));
    }

    public function getShippingFee(TransactionShipping $request) {
        $post = $request->json()->all();

        if (isset($post['from'])) {
            $from = $post['from'];
        }

        if (isset($post['fromType'])) {
            $fromType = $post['fromType'];
        }

        if (isset($post['to'])) {
            $to = $post['to'];
        }

        if (isset($post['toType'])) {
            $toType = $post['toType'];
        }

        if (isset($post['weight'])) {
            $weight = $post['weight'];
        }

        if (isset($post['courier'])) {
            $courier = $post['courier'];
        }

        $data = "origin=".$from."&originType=".$fromType."&destination=".$to."&destinationType=".$toType."&weight=".$weight."&courier=".$courier;

        $shiping = MyHelper::urlTransaction('http://pro.rajaongkir.com/api/cost', 'POST', $data, 'application/x-www-form-urlencoded');

        if (isset($shiping->rajaongkir->status->code) && $shiping->rajaongkir->status->code == 200) {
            if (!empty($shiping->rajaongkir->results[0]->costs)) {
                $data = [
                    'status'    => 'success',
                    'result'    => $shiping->rajaongkir->results[0]->costs
                ];
            } else {
                $data = [
                    'status'      => 'empty',
                    'messages'    => ['Maaf, pengiriman ke kota tersebut belum tersedia']
                ];
            }

        } elseif (isset($shiping->rajaongkir->status->code) && $shiping->rajaongkir->status->code == 400) {
            $data = [
                'status'    => 'fail',
                'messages'    => [$shiping->rajaongkir->status->description]
            ];
        } else {
            $data = [
                'status'    => 'error',
                'messages'    => ['Data invalid!!']
            ];
        }

        return response()->json($data);
    }

    public function transactionVoid(Request $request) {
        $id = $request->json('transaction_receipt_number');

        $transaction = Transaction::where('transaction_receipt_number', $id)->first();
        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction not found !!']
            ]);
        }

        MyHelper::updateFlagTransactionOnline($transaction, 'cancel');

        $transaction->void_date = date('Y-m-d H:i:s');
        $transaction->save();

        if (!$transaction) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Void transaction failure !!']
            ]);
        }

        return response()->json([
            'status'    => 'success',
            'messages'  => ['Void transaction success']
        ]);
    }

    public function transactionFinish(Request $request) {
        $result = $request->input('result_data');
        $result = json_decode($result);
        echo $result->status_message . '<br>';
        echo 'RESULT <br><pre>';
        var_dump($result);
        echo '</pre>' ;
    }

    public function transactionApprove(Request $request) {
        $json_result = file_get_contents('php://input');
        $result = json_decode($json_result);

        $url = 'https://api.sandbox.midtrans.com/v2/'.$result->order_id.'/status';
    }

    public function transactionCancel(Request $request) {
        return 'cancel';
    }

    public function transactionError(Request $request) {
        return 'error';
    }

    public function transactionNotif(Request $request) {
        $json_result = file_get_contents('php://input');
        $result = json_decode($json_result);

        DB::beginTransaction();
        $checkTransaction = Transaction::where('transaction_receipt_number', $result->order_id)->first();

        if (!$checkTransaction) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Receipt number not available']
            ]);
        }

        if (count($checkTransaction) > 0) {
            $url = 'https://api.sandbox.midtrans.com/v2/'.$result->order_id.'/status';

            $getStatus = $this->getToken(false, $url, false);

            if ($getStatus->status_code != 200) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Cannot access this transaction']
                ]);
            }

            if (!empty($getStatus)) {
                $masked_card        = isset($getStatus['masked_card']) ? $getStatus['masked_card'] : null;
                $approval_code      = isset($getStatus['approval_code']) ? $getStatus['approval_code'] : null;
                $bank               = isset($getStatus['bank']) ? $getStatus['bank'] : null;
                $eci                = isset($getStatus['eci']) ? $getStatus['eci'] : null;
                $transaction_time   = isset($getStatus['transaction_time']) ? $getStatus['transaction_time'] : null;
                $payment_type       = isset($getStatus['payment_type']) ? $getStatus['payment_type'] : null;
                $signature_key      = isset($getStatus['signature_key']) ? $getStatus['signature_key'] : null;
                $status_code        = isset($getStatus['status_code']) ? $getStatus['status_code'] : null;
                $vt_transaction_id  = isset($getStatus['vt_transaction_id']) ? $getStatus['vt_transaction_id'] : null;
                $transaction_status = isset($getStatus['transaction_status']) ? $getStatus['transaction_status'] : null;
                $fraud_status       = isset($getStatus['fraud_status']) ? $getStatus['fraud_status'] : null;
                $status_message     = isset($getStatus['status_message']) ? $getStatus['status_message'] : null;

                if ($getStatus->status_code == 200) {
                    if ($transaction_status == 'capture') {
                        $checkTransaction->transaction_payment_status = 'Success';

                        if (!empty($checkTransaction->id_user)) {
                            $dataPoint = [
                                'id_user'      => $checkTransaction->id_user,
                                'point'        => $checkTransaction->transaction_point_earned,
                                'id_reference' => $checkTransaction->id_transaction,
                                'source'       => 'transaction'
                            ];

                            $insertPoint = PointLog::create($dataPoint);

                            if (!$insertPoint) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['insert point failed']
                                ]);
                            }
                        }
                    } else {
                        $checkTransaction->transaction_payment_status = ucwords($transaction_status);
                    }

                    $checkTransaction->transaction_payment_method = $payment_type;
                    $checkTransaction->save();

                    if (!$checkTransaction) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Update status payment failed']
                        ]);
                    }
                }


                $dataPayment = [
                    'id_transaction'     => $checkTransaction->id_transaction,
                    'masked_card'        => $masked_card,
                    'approval_code'      => $approval_code,
                    'bank'               => $bank,
                    'eci'                => $eci,
                    'transaction_time'   => $transaction_time,
                    'gross_amount'       => $getStatus->gross_amount,
                    'order_id'           => $getStatus->order_id,
                    'payment_type'       => $payment_type,
                    'signature_key'      => $signature_key,
                    'status_code'        => $status_code,
                    'vt_transaction_id'  => $vt_transaction_id,
                    'transaction_status' => $transaction_status,
                    'fraud_status'       => $fraud_status,
                    'status_message'     => $status_message,
                ];

                $insertPayment = TransactionPayment::create($dataPayment);

                if (!$insertPayment) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Transaction payment cannot be create']
                    ]);
                }

                DB::commit();
                return $response->json([
                    'status'    => 'success',
                    'result'    => $dataPayment
                ]);

            }
        }
    }

    public function sendTransaction($data) {
        $tes = MyHelper::curlData('http://localhost/natasha-api/public/api/transaction/tes2', $data);
    }

    public function testing() {
        $testing = MyHelper::logCount('089674657270', 'point');
        return $testing;
    }

    public function insertUserTrxProduct($data){
        foreach ($data as $key => $value) {
            # code...
            $check = UserTrxProduct::where('id_user', $value['id_user'])->where('id_product', $value['id_product'])->first();

            if(empty($check)){
                $insertData = UserTrxProduct::create($value);
            }else{
                $value['product_qty'] = $check->product_qty + $value['product_qty'];
                $insertData = $check->update($value);
            }

            if(!$insertData){
                return 'fail';
            }
        }
        return 'success';
    }

    public function shippingCostGoSend(ShippingGosend $request){
        $post = $request->json()->all();

        $outlet = Outlet::find($post['id_outlet']);
        if(!$outlet){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet not found.']]);
        }

        $origin['latitude'] = $outlet['latitude'];
        $origin['longitude'] = $outlet['longitude'];
        $shipping = GoSend::getPrice($origin, $post['destination']);

        if(isset($shipping['Instant']['price']['total_price'])){
            $shippingCost = $shipping['Instant']['price']['total_price'];
            $shippingFree = null;
            $isFree = '0';
            $setting = Setting::where('key', 'like', '%free_delivery%')->get();
            if($setting){
                $freeDev = [];
                foreach($setting as $dataSetting){
                    $freeDev[$dataSetting['key']] = $dataSetting['value'];
                }

                if(isset($freeDev['free_delivery_type'])){
                    if($freeDev['free_delivery_type'] == 'free' || isset($freeDev['free_delivery_nominal'])){
                        if(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'total item' && isset($freeDev['free_delivery_min_item'])){
                            if($post['total_item'] >= $freeDev['free_delivery_min_item']){
                                $isFree = '1';
                            }
                        }elseif(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'subtotal' && isset($freeDev['free_delivery_min_subtotal'])){
                            if($post['subtotal'] >= $freeDev['free_delivery_min_subtotal']){
                                $isFree = '1';
                            }
                        }

                        if($isFree == '1'){
                            if($freeDev['free_delivery_type'] == 'free'){
                                $shippingFree = 'FREE';
                            }else{
                                $shippingFree = $freeDev['free_delivery_nominal'];
                            }
                        }
                    }
                }

            }

            $result['shipping_cost_go_send'] = $shippingCost;

            if($shippingFree != null){
                if($shippingFree == 'FREE'){
                    $result['shipping_cost_discount'] = $shippingCost;
                    $result['is_free'] = 'yes';
                    $result['shipping_cost'] = 'FREE';
                }else{
                    if($shippingFree > $shippingCost){
                        $result['shipping_cost_discount'] = $shippingCost;
                        $result['is_free'] = 'no';
                        $result['shipping_cost'] = 0;
                    }else{
                        $result['shipping_cost_discount'] = (int)$shippingFree;
                        $result['is_free'] = 'no';
                        $result['shipping_cost'] = $shippingCost - $shippingFree;
                    }
                }
            }else{
                $result['shipping_cost_discount'] = 0;
                $result['is_free'] = 'no';
                $result['shipping_cost'] = $shippingCost;
            }

            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);
        }else{
            if(isset($shipping['status']) && $shipping['status'] == 'fail'){
                return response()->json($shipping);
            }
            return response()->json([
                'status' => 'fail',
                'messages' => [$shipping]
            ]);
        }

    }

    public function updateStatusInvalidTrx(Request $request){
        $post = $request->json()->all();
        $update = Transaction::where('id_transaction', $request['id_transaction'])->update(['transaction_flag_invalid' => $request['transaction_flag_invalid']]);

        if($request->user()->id){
            $insertLog = [
                'id_transaction' => $request['id_transaction'],
                'tansaction_flag' => $request['transaction_flag_invalid'],
                'updated_by' => $request->user()->id,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            LogInvalidTransaction::create($insertLog);
        }

        return MyHelper::checkUpdate($update);
    }

    public function logInvalidFlag(Request $request){
        $post = $request->json()->all();

        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
                ->join('users', 'users.id', 'log_invalid_transactions.updated_by')
                ->groupBy('log_invalid_transactions.id_transaction');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $list->where('transactions.transaction_flag_invalid', $row['operator']);
                        }

                        if($row['subject'] == 'receipt_number'){
                            if($row['operator'] == '='){
                                $list->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $list->where('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'updated_by'){
                            if($row['operator'] == '='){
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', $row['parameter']);
                                });
                            }else{
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                $subquery->orWhere('transactions.transaction_flag_invalid', $row['operator']);
                            }

                            if($row['subject'] == 'receipt_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'updated_by'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', $row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);

        return MyHelper::checkGet($list);
    }

    public function detailInvalidFlag(Request $request){
        $post = $request->json()->all();
        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
            ->leftJoin('users', 'users.id', 'log_invalid_transactions.updated_by')
            ->where('log_invalid_transactions.id_transaction', $request['id_transaction'])
            ->select(DB::raw('DATE_FORMAT(log_invalid_transactions.updated_date, "%d %M %Y %H:%i") as updated_date'), 'users.name', 'log_invalid_transactions.tansaction_flag', 'transactions.transaction_receipt_number')
            ->get()->toArray();

        return MyHelper::checkGet($list);
    }

    public function retryRefund($id_transaction, &$errors = [], $manualRetry = false)
    {
        $trx = Transaction::where('transactions.id_transaction', $id_transaction)
            ->join('transaction_multiple_payments', function($join) {
            $join->on('transaction_multiple_payments.id_transaction_group', 'transactions.id_transaction_group')
                ->whereIn('type', ['Midtrans', 'Shopeepay', 'Xendit']);
        })->first();
        if (!$trx) {
            $errors[] = 'Transaction Not Found';
            return false;
        }
        $result = true;
        switch ($trx->type) {
            case 'Midtrans':
                $payMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $trx['id_transaction_group'])->first();
                if (!$payMidtrans) {
                    $errors[] = 'Model TransactionPaymentMidtran not found';
                    return false;
                }
                if($trx['refund_requirement'] > 0){
                    $refund = Midtrans::refundPartial($payMidtrans['vt_transaction_id'],['reason' => 'refund transaksi', 'amount' => (int)$trx['refund_requirement']]);
                }else{
                    $refund = Midtrans::refund($payMidtrans['vt_transaction_id'],['reason' => 'refund transaksi']);
                }

                if ($refund['status'] != 'success') {
                    Transaction::where('id_transaction', $id_transaction)->update(['failed_void_reason' => implode(', ', $refund['messages'] ?? [])]);
                    $errors = $refund['messages'] ?? [];
                    $result = false;
                } else {
                    Transaction::where('id_transaction', $id_transaction)->update(['need_manual_void' => 0]);
                }
                break;
            case 'Xendit':
                $payXendit = TransactionPaymentXendit::where('id_transaction_group', $trx['id_transaction_group'])->first();
                if (!$payXendit) {
                    $errors[] = 'Model TransactionPaymentXendit not found';
                    return false;
                }
                if($trx['refund_requirement'] > 0){
                    $refund = app($this->xendit)->refund($trx['id_transaction_group'], 'trx', [
                        'amount' => (int) $trx['refund_requirement'],
                        'reason' => 'Retry rejected from merchant'
                    ], $errors2);
                }else{
                    $refund = app($this->xendit)->refund($trx['id_transaction_group'], 'trx', [], $errors2);
                }

                if (!$refund) {
                    Transaction::where('id_transaction', $id_transaction)->update(['failed_void_reason' => implode(', ', $errors2 ?: [])]);
                    $errors = $errors2;
                    $result = false;
                } else {
                    Transaction::where('id_transaction', $id_transaction)->update(['need_manual_void' => 0]);
                }
                break;

            default:
                $errors[] = 'Unkown payment type '.$trx->type;
                return false;
        }
        return $result;
    }

    public function retry(Request $request)
    {
        $retry = $this->retryRefund($request->id_transaction, $errors);
        if ($retry) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'fail',
                'messages' => $errors ?? ['Something went wrong']
            ];
        }
    }

    public function listPaymentDetailOutlet(Request $request){
        $post = $request->json()->all();

        if(empty($post['id_outlet'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $listTransaction = Transaction::join('transaction_payment_midtrans', 'transaction_payment_midtrans.id_transaction_group', 'transactions.id_transaction_group')
                        ->where('id_outlet', $post['id_outlet']);

        if(!empty($post['search_key'])){
            $listTransaction = $listTransaction->where('transaction_receipt_number', 'like', '%'.$post['search_key'].'%');
        }
        $listTransaction = $listTransaction->orderBy('transaction_date', 'desc')->paginate(15);
        return response()->json(MyHelper::checkGet($listTransaction));
    }

    public function transactionGroupDetail(Request $request){
        $trxGroup = TransactionGroup::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();

        $idtransaction = Transaction::where('id_transaction_group', $trxGroup['id_transaction_group'])->pluck('id_transaction')->toArray();

        if(empty($idtransaction)){
            return response()->json(MyHelper::checkGet([]));
        }
        $transactionProducts = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereIn('id_transaction', $idtransaction)
            ->with(['variants'=> function($query){
                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')
                    ->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
            }])
            ->select('transaction_products.*', 'products.product_name')->get()->toArray();

        $products = [];
        foreach ($transactionProducts as $value){
            $image = ProductPhoto::where('id_product', $value['id_product'])->orderBy('product_photo_order', 'asc')->first()['url_product_photo']??config('url.storage_url_api').'img/default.jpg';
            $products[] = [
                'id_product' => $value['id_product'],
                'product_name' => $value['product_name'],
                'product_qty' => $value['transaction_product_qty'],
                'need_recipe_status' =>  $value['transaction_product_recipe_status'],
                'product_base_price' => 'Rp '. number_format((int)$value['transaction_product_price'],0,",","."),
                'product_total_price' => 'Rp '. number_format((int)$value['transaction_product_subtotal'],0,",","."),
                'note' => $value['transaction_product_note'],
                'variants' => implode(', ', array_column($value['variants'], 'product_variant_name')),
                'image' => $image
            ];
        }

        $paymentDetail = [
            [
                'text' => 'Subtotal',
                'value' => 'Rp '. number_format((int)$trxGroup['transaction_subtotal'],0,",",".")
            ],
            [
                'text' => 'Biaya Kirim',
                'value' => 'Rp '. number_format((int)$trxGroup['transaction_shipment'],0,",",".")
            ]
        ];

        $grandTotal = $trxGroup['transaction_grandtotal'];
        $trxPaymentBalance = TransactionPaymentBalance::where('id_transaction_group', $trxGroup['id_transaction_group'])->first()['balance_nominal']??0;
        $trxCount = Transaction::where('id_transaction_group', $trxGroup['id_transaction_group'])->count();
        $balance = (int)$trxPaymentBalance/$trxCount;
        if(!empty($trxPaymentBalance)){
            $grandTotal = $grandTotal - $balance;
            $paymentDetail[] = [
                'text' => 'Point yang digunakan',
                'value' => '-'.number_format($balance,0,",",".")
            ];
        }

        $trxPaymentMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $trxGroup['id_transaction_group'])->first();
        $trxPaymentXendit = TransactionPaymentXendit::where('id_transaction_group', $trxGroup['id_transaction_group'])->first();

        if(!empty($trxPaymentMidtrans)){
            $paymentMethod = $trxPaymentMidtrans['payment_type'].(!empty($trxPaymentMidtrans['bank']) ? ' ('.$trxPaymentMidtrans['bank'].')':'');
        }elseif(!empty($trxPaymentXendit)){
            $paymentMethod = $trxPaymentXendit['type'];
        }

        $transaction = Transaction::where(['transactions.id_transaction' => $idtransaction[0]])
            ->leftJoin('transaction_shipments','transaction_shipments.id_transaction','=','transactions.id_transaction')
            ->leftJoin('cities','transaction_shipments.destination_id_city','=','cities.id_city')
            ->leftJoin('provinces','provinces.id_province','=','cities.id_province')->first();

        $address = [
            'destination_name' => $transaction['destination_name'],
            'destination_phone' => $transaction['destination_phone'],
            'destination_address' => $transaction['destination_address'],
            'destination_description' => $transaction['destination_description'],
            'destination_province' => $transaction['province_name'],
            'destination_city' => $transaction['city_name'],
        ];

        $result = [
            'receipt_number_group' => $transaction['transaction_receipt_number'],
            'transaction_products' => $products,
            'payment_detail' => $paymentDetail,
            'address' => $address,
            'transaction_grandtotal' => 'Rp '. number_format($grandTotal,0,",","."),
            'payment' => $paymentMethod??''
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function orderReceived(Request $request){
        $transaction = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->where('transaction_receipt_number', $request->json('transaction_receipt_number'))
            ->where('id_user', $request->user()->id)->first();

        if(empty($transaction)){
            return response()->json(MyHelper::checkGet($transaction));
        }

        if($transaction['transaction_status'] == 'Completed'){
            return response()->json(['status' => 'success']);
        }

        $received = app($this->shipper)->completedTransaction($transaction);

        if($received){
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['failed to update data']]);
        }
    }
}
