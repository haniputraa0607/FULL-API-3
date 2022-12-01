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

class ApiUserResellerMerchantController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }

    public function register(Register $request)
    {
        $post = $request->json()->all();
        $post['id_user'] = Auth::user()->id;
        $get = UserResellerMerchant::where(array(
                    'id_merchant' => $post['id_merchant'],
                    'id_user' => $post['id_user'],
                ))->where('reseller_merchant_status', 'Rejected')->orwhere('reseller_merchant_status', 'Inactive')->first();
        if ($get) {
            $get = UserResellerMerchant::where(array(
                    'id_merchant' => $post['id_merchant'],
                    'id_user' => $post['id_user'],
                ))->where('reseller_merchant_status', 'Rejected')->orwhere('reseller_merchant_status', 'Inactive')
                    ->update([
                        'notes_user' => $post['notes_user'],
                        'reseller_merchant_status' => 'Pending'
                    ]);
        } else {
            $get = UserResellerMerchant::create($post);
        }
        return response()->json(MyHelper::checkUpdate($get));
    }
}
