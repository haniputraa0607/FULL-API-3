<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\InboxGlobal;
use App\Http\Models\InboxGlobalRead;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\Subdistricts;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Jobs\DisburseJob;
use App\Lib\Shipper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankAccountOutlet;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;
use Modules\InboxGlobal\Http\Requests\MarkedInbox;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantInbox;
use Modules\Merchant\Entities\MerchantLogBalance;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Outlet\Entities\DeliveryOutlet;
use DB;
use App\Http\Models\Transaction;
use Modules\Merchant\Entities\MerchantGrading;
use Modules\Merchant\Entities\UserResellerMerchant;
use Modules\Merchant\Http\Requests\UserReseller\Register;
use Illuminate\Support\Facades\Auth;

class ApiBeUserResellerMerchantController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }

    public function candidate(Request $request)
    {
        $post = $request->all();
        $employee = UserResellerMerchant::where(array(
            "user_reseller_merchants.reseller_merchant_status" => "Pending",
            ))->join('users', 'users.id', 'user_reseller_merchants.id_user')
              ->join('merchants', 'merchants.id_merchant', 'user_reseller_merchants.id_merchant')
              ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
              ->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant_grading', 'user_reseller_merchants.id_merchant_grading');
        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }
            if ($rule == 'and') {
                foreach ($post['conditions'] as $condition) {
                    if (isset($condition['subject'])) {
                        $employee = $employee->where($condition['subject'], $condition['parameter']);
                    }
                }
            } else {
                $employee = $employee->where(function ($q) use ($post) {
                    foreach ($post['conditions'] as $condition) {
                        if (isset($condition['subject'])) {
                            if ($condition['operator'] == 'like') {
                                 $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            } else {
                                 $q->orWhere($condition['subject'], $condition['parameter']);
                            }
                        }
                    }
                });
            }
        }
            $employee = $employee->orderBy('user_reseller_merchants.created_at', 'desc')
                        ->select('user_reseller_merchants.*', 'users.name as user_name', 'outlets.outlet_name as outlet', 'merchant_gradings.grading_name as grading')
                        ->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
    }
    public function candidateDetail(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_user_reseller_merchant']) && !empty($post['id_user_reseller_merchant'])) {
             $detail = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->join('users', 'users.id', 'user_reseller_merchants.id_user')
              ->join('merchants', 'merchants.id_merchant', 'user_reseller_merchants.id_merchant')
              ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
              ->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant_grading', 'user_reseller_merchants.id_merchant_grading')
              ->select('user_reseller_merchants.*', 'users.name as user_name', 'outlets.outlet_name as outlet', 'merchant_gradings.grading_name as grading', 'merchants.reseller_status', 'merchants.auto_grading')
              ->first();
            if (!$detail) {
                return response()->json(MyHelper::checkGet($detail));
            }
             $detail['gradings'] = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant', 'user_reseller_merchants.id_merchant')
              ->select('merchant_gradings.id_merchant_grading', 'merchant_gradings.grading_name')
              ->get();
             $approved = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->join('users', 'users.id', 'user_reseller_merchants.id_approved')->select('users.name')->first();
             $detail['approved'] = $approved->name ?? '';
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function candidateUpdate(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_user_reseller_merchant']) && !empty($post['id_user_reseller_merchant'])) {
            $detail = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
            ))->join('users', 'users.id', 'user_reseller_merchants.id_user')
              ->join('merchants', 'merchants.id_merchant', 'user_reseller_merchants.id_merchant')
              ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
              ->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant_grading', 'user_reseller_merchants.id_merchant_grading')
              ->select('user_reseller_merchants.*', 'users.name as user_name', 'outlets.outlet_name as outlet', 'merchant_gradings.grading_name as grading')
              ->update([
                  'reseller_merchant_status' => $post['reseller_merchant_status'],
                  'notes' => $post['notes'],
                  'id_approved' => Auth::user()->id
              ]);
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function index(Request $request)
    {
        $post = $request->all();
        $employee = UserResellerMerchant::where(
            "user_reseller_merchants.reseller_merchant_status",
            '!=',
            "Pending",
        )->join('users', 'users.id', 'user_reseller_merchants.id_user')
              ->join('merchants', 'merchants.id_merchant', 'user_reseller_merchants.id_merchant')
              ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
              ->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant_grading', 'user_reseller_merchants.id_merchant_grading');
        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }
            if ($rule == 'and') {
                foreach ($post['conditions'] as $condition) {
                    if (isset($condition['subject'])) {
                        $employee = $employee->where($condition['subject'], $condition['parameter']);
                    }
                }
            } else {
                $employee = $employee->where(function ($q) use ($post) {
                    foreach ($post['conditions'] as $condition) {
                        if (isset($condition['subject'])) {
                            if ($condition['operator'] == 'like') {
                                 $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            } else {
                                 $q->orWhere($condition['subject'], $condition['parameter']);
                            }
                        }
                    }
                });
            }
        }
            $employee = $employee->orderBy('user_reseller_merchants.created_at', 'desc')
                        ->select('user_reseller_merchants.*', 'users.name as user_name', 'outlets.outlet_name as outlet', 'merchant_gradings.grading_name as grading')
                        ->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
    }
    public function detail(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_user_reseller_merchant']) && !empty($post['id_user_reseller_merchant'])) {
             $detail = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->join('users', 'users.id', 'user_reseller_merchants.id_user')
              ->join('merchants', 'merchants.id_merchant', 'user_reseller_merchants.id_merchant')
              ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
              ->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant_grading', 'user_reseller_merchants.id_merchant_grading')
              ->select('user_reseller_merchants.*', 'users.name as user_name', 'outlets.outlet_name as outlet', 'merchant_gradings.grading_name as grading', 'merchants.reseller_status', 'merchants.auto_grading')
              ->first();
            if (!$detail) {
                return response()->json(MyHelper::checkGet($detail));
            }
             $detail['gradings'] = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->leftjoin('merchant_gradings', 'merchant_gradings.id_merchant', 'user_reseller_merchants.id_merchant')
              ->select('merchant_gradings.id_merchant_grading', 'merchant_gradings.grading_name')
              ->get();
             $approved = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
             ))->join('users', 'users.id', 'user_reseller_merchants.id_approved')->select('users.name')->first();
             $detail['approved'] = $approved->name ?? '';
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function update(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['id_user_reseller_merchant']) && !empty($post['id_user_reseller_merchant'])) {
            $detail = UserResellerMerchant::where(array(
            "id_user_reseller_merchant" => $request->id_user_reseller_merchant,
            ))->update($post);
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
