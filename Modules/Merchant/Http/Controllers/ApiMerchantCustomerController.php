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
use App\Http\Models\City;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\Favorite\Entities\Favorite;

class ApiMerchantCustomerController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->management_merchant = "Modules\Merchant\Http\Controllers\ApiMerchantManagementController";
    }
    public function list(Request $request)
    {
        $idUser = $request->user()->id;
        $post = $request->json()->all();

        if (!empty($post['id_outlet'])) {
            $idMerchant = Merchant::where('id_outlet', $post['id_outlet'])->first()['id_merchant'] ?? null;
            if (empty($idMerchant)) {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }
        }

        $list = Outlet::join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
                ->leftjoin('products', 'products.id_merchant', 'merchants.id_merchant')
                ->leftjoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
            ->where('outlet_status', 'Active')
            ->groupBy('merchants.id_merchant');
        if (!empty($post['filter_sorting'])) {
            $sorting = $post['filter_sorting'];
            if ($sorting == 'Sesuaikan' && !empty($post['name'])) {
                $list = $list->select(
                    'merchants.id_merchant',
                    'merchants.created_at',
                    'outlets.*',
                    DB::raw('MATCH (outlet_name) AGAINST ("' . $post['name'] . '" IN BOOLEAN MODE) AS relate'),
                    DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                    DB::raw('
                            sum(products.product_count_transaction) as product_count_transaction
                        ')
                )
                    ->orderBy('relate', 'desc');
            } elseif ($sorting == 'Terlaris') {
                 $list = $list->select(
                     'merchants.id_merchant',
                     'merchants.created_at',
                     'outlets.*',
                     DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                     DB::raw('
                            sum(products.product_count_transaction) as product_count_transaction
                        '),
                 )->orderby('product_count_transaction', 'desc');
            } elseif ($sorting == 'Terbaru') {
                $list = $list->select(
                    'merchants.id_merchant',
                    'merchants.created_at',
                    'outlets.*',
                    DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                    DB::raw('
                            sum(products.product_count_transaction) as product_count_transaction
                        '),
                )->orderBy('merchants.created_at', 'desc');
            } else {
                $list = $list->select(
                    'merchants.id_merchant',
                    'merchants.created_at',
                    'outlets.*',
                    DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                    DB::raw('
                            sum(products.product_count_transaction) as product_count_transaction
                        ')
                );
            }
        } else {
             $list = $list->select(
                 'merchants.id_merchant',
                 'merchants.created_at',
                 'outlets.*',
                 DB::raw('
                            count(
                            products.id_product
                            ) as product
                        '),
                 DB::raw('
                            sum(products.product_count_transaction) as product_count_transaction
                        ')
             );
        }
        if (!empty($post['name'])) {
            if (strpos($post['name'], " ") !== false) {
                $list = $list->whereRaw('MATCH (outlet_name) AGAINST ("' . $post['name'] . '" IN BOOLEAN MODE)');
            } else {
                $list = $list->where('outlet_name', 'like', '%' . $post['name'] . '%');
            }
            $list = $list->where('outlet_is_closed', 0);
        }
        if (isset($post['city']) && $post['city'] != null) {
            $list = $list->wherein('id_city', $post['city']);
        }
          $list = $list->get()->toArray();
          $data = array();
        foreach ($list as $key => $value) {
               $data[] = array(
                    'id_merchant' => $value['id_merchant'],
                    'id_outlet' => $value['id_outlet'],
                    'outlet_name' => $value['outlet_name'],
                    'product' => $value['product'],
                    'outlet_is_closed' => $value['outlet_is_closed'],
                    'outlet_image_cover' => $value['url_outlet_image_cover'],
                    'outlet_image_logo_portrait' => $value['url_outlet_image_logo_portrait'],
                    'sold' => app($this->management_merchant)->productCount($value['product_count_transaction']),
                );
        }

        return response()->json(MyHelper::checkGet($data));
    }
    public function list2(Request $request)
    {
        $post = $request->json()->all();
        $get = Outlet::join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
                ->leftjoin('products', 'products.id_merchant', 'merchants.id_merchant')
                ->leftjoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->where('merchant_status', 'Active')
                ->where('outlet_status', 'Active')
                ->select(
                    'merchants.id_merchant',
                    'outlets.*',
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
        $data = array();
        foreach ($get as $value) {
            $data[] = array(
                'id_merchant' => $value['id_merchant'],
                'id_outlet' => $value['id_outlet'],
                'outlet_name' => $value['outlet_name'],
                'outlet_is_closed' => $value['outlet_is_closed'],
                'product' => $value['product'],
                'price' => $value['average_price'],
                'rating' => $value['total_rating'],
                'outlet_image_cover' => $value['url_outlet_image_cover'],
                'outlet_image_logo_portrait' => $value['url_outlet_image_logo_portrait'],
            );
        }
        return response()->json(MyHelper::checkGet($data));
    }
    public function product(Request $request)
    {
        $post = $request->json()->all();
        $get = Product::join('merchants', 'merchants.id_merchant', 'products.id_merchant')
                ->leftjoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                ->leftjoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->leftjoin('transaction_products', 'transaction_products.id_product', 'products.id_product')
                ->leftjoin('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                ->where('merchant_status', 'Active')
                ->where('outlet_status', 'Active')
                ->where('outlet_is_closed', 0)
                ->select(
                    'products.id_product',
                    'product_name',
                    'product_global_price',
                    'total_rating',
                    DB::raw('
                            floor(total_rating) as rating
                        '),
                    DB::raw('
                            sum(CASE WHEN
                                transactions.transaction_payment_status = "Completed" THEN transaction_products.transaction_product_qty ELSE 0
                            END
                            ) as total_penjualan
                        '),
                )
                ->groupby('products.id_product');
        if (isset($post['name']) && $post['name'] != null) {
            $get = $get->where('product_name', 'like', '%' . $post['name'] . '%');
        }
        if (isset($post['city']) && $post['city'] != null) {
            $get = $get->wherein('id_city', $post['city']);
        }
        if (isset($post['range']) && $post['range'] != null) {
            $get = $get->whereBetween('product_global_price', $post['range']);
        }

        if (isset($post['order_by']) && $post['order_by'] != null) {
            if ($post['order_by'] == 'Rating Tertinggi') {
                $get = $get->orderby('total_rating', 'desc');
            } elseif ($post['order_by'] == 'Rating Terendah') {
                 $get = $get->orderby('total_rating', 'asc');
            } elseif ($post['order_by'] == 'Terlaris') {
                 $get = $get->orderby('total_penjualan', 'desc');
            } elseif ($post['order_by'] == 'Terbaru') {
                 $get = $get->orderby('products.created_at', 'asc');
            } elseif ($post['order_by'] == 'Harga Tertinggi') {
                 $get = $get->orderby('product_global_price', 'desc');
            } elseif ($post['order_by'] == 'Harga Terendah') {
                 $get = $get->orderby('product_global_price', 'asc');
            }
        }
                $get = $get->get();
        if (isset($post['rating']) && $post['rating'] != null) {
            $get = $get->wherein('rating', $post['rating']);
        }
        if (isset($post['sell']) && $post['sell'] != null) {
            if (isset($post['sell']['operator'])) {
                if ($post['sell']['operator'] == 'between') {
                    if (isset($post['sell']['start']) && isset($post['sell']['end'])) {
                        $get = $get->whereBetween('total_penjualan', [$post['sell']['start'],$post['sell']['end']]);
                    }
                } else {
                    if (isset($post['sell']['value'])) {
                        $get = $get->where('total_penjualan', $post['sell']['operator'], $post['sell']['value']);
                    }
                }
            }
        }
                $get = $get->values();
        return response()->json(MyHelper::checkGet($get));
    }
    public function city()
    {
        $get = City::join('outlets', 'outlets.id_city', 'cities.id_city')
                ->join('merchants', 'merchants.id_outlet', 'outlets.id_outlet')
                ->orderby('city_type', 'desc')
                ->select('cities.id_city', 'city_name', 'city_type')
                ->groupby('cities.id_city')
                ->get();
        return response()->json(MyHelper::checkGet($get));
    }
    public function filter_sorting()
    {
        $get = array(
            'Sesuaikan',
            'Terlaris',
            'Terbaru',
        );
        return response()->json(MyHelper::checkGet($get));
    }
    public function order_by()
    {
        $get = array(
            'Rating Tertinggi',
            'Rating Terendah',
            'Terlaris',
            'Terbaru',
            'Harga Tertinggi',
            'Harga Terendah',
        );
        return response()->json(MyHelper::checkGet($get));
    }
    public function promo()
    {
        $get = PromoCampaign::where(array(
                    'promo_campaign_visibility' => "Visible",
                    'step_complete' => 1
                ))
                ->where('date_start', '<=', date('Y-m-d H:i:s'))
                ->where('date_end', '>=', date('Y-m-d H:i:s'))
                ->groupby('promo_title')
                ->distinct()
                ->select('id_promo_campaign', 'promo_title')
                ->get();
        $get = array(
           "status" => "success",
            "result" => []
        );
        return $get;
        return response()->json(MyHelper::checkGet($get));
    }
}
