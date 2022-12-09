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

class ApiMerchantCustomerController extends Controller
{
    public function list(Request $request)
    {
        $post = $request->json()->all();
        $get = Merchant::join('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                ->leftjoin('products', 'products.id_merchant', 'merchants.id_merchant')
                ->leftjoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->where('merchant_status', 'Active')
                ->select(
                    'merchants.id_merchant',
                    'outlet_name',
                    'outlet_image_logo_landscape',
                    'outlet_image_logo_portrait',
                    DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                    DB::raw('
                            floor(avg(
                            product_global_price.product_global_price
                            )) as average_price
                        '),
                    DB::raw('
                            floor(avg(
                            products.total_rating
                            )) as rating
                        ')
                )
                ->groupby('merchants.id_merchant');
        if (isset($post['name']) && $post['name'] != null) {
            $get = $get->where('outlet_name', 'like', '%' . $post['name'] . '%');
        }
        if (isset($post['city']) && $post['city'] != null) {
            $get = $get->wherein('id_city', $post['city']);
        }
                $get = $get->get();
        return response()->json(MyHelper::checkGet($get));
    }
}
