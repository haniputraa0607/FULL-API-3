<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandProduct;
use Modules\Disburse\Entities\BankAccount;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantLogBalance;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Outlet\Entities\OutletApiKeySecret;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductWholesaler;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupWholesaler;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use DB;
use Image;

class ApiMerchantExternalManagementController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->merchant_management = "Modules\Merchant\Http\Controllers\ApiMerchantManagementController";
        $this->product_category = "Modules\Product\Http\Controllers\ApiCategoryController";
    }

    public static function checkApi($request)
    {
        $key = $request['api_key'] ?? null;
        $secret = $request['api_secret'] ?? null;
        $merchantCode = $request['merchant_code'] ?? null;

        if (empty($merchantCode)) {
            return ['status' => 'fail', 'messages' => ['merhant code can not be empty']];
        }

        if (empty($secret) || empty($key)) {
            return ['status' => 'fail', 'messages' => ['api_key and api_secret can not be empty']];
        }

        $idOutlet = Outlet::where('outlet_code', $merchantCode)->first()['id_outlet'] ?? null;
        if (empty($idOutlet)) {
            return ['status' => 'fail', 'messages' => ['merhant not found']];
        }

        $check = OutletApiKeySecret::where('api_key', $key)
                ->where('api_secret', $secret)
                ->where('id_outlet', $idOutlet)
                ->first();

        if (empty($check)) {
            return ['status' => 'fail', 'messages' => ['api_key and secret not found']];
        }
        unset($request['api_key']);
        unset($request['api_secret']);
        unset($request['merchant_code']);

        $idUser = Merchant::where('id_outlet', $idOutlet)->first()['id_user'] ?? null;
        $request['id_user'] = $idUser;
        $request['id_outlet'] = $idOutlet;
        return ['status' => 'success', 'result' => $request];
    }

    public function listProductCategory(Request $request)
    {
        $post = $request->json()->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->product_category)->listCategoryCustomerApps();
    }

    public function productList(Request $request)
    {
        $post = $request->json()->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productList($api['result']);
    }

    public function productCreate(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productCreate($api['result']);
    }

    public function productUpdate(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productUpdate($api['result']);
    }

    public function productDetail(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productDetail($api['result']);
    }

    public function productDelete(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productDelete($api['result']);
    }

    public function productPhotoDelete(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productPhotoDelete($api['result']);
    }

    public function variantDelete(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->variantDelete($api['result']);
    }

    public function productStockDetail(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productStockDetail($api['result']);
    }

    public function productStockUpdate(Request $request)
    {
        $post = $request->all();
        $api = $this->checkApi($post);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        return app($this->merchant_management)->productStockUpdate($api['result']);
    }
}
