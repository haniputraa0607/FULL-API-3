<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Treatment;
use App\Http\Models\Consultation;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\Reservation;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;


class ApiReport extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    public function global(Request $request){
        $post = $request->json()->all();

        if ($post['date_start'] > $post['date_end']) {
            return response()->json([
                'status'    => 'fail', 
                'messages'    => 'Date start must be smaller than date end'
            ]);
        }

        $transaction = Transaction::with('user')->where('transaction_date', '>=', $post['date_start'])->where('transaction_date', '<=', $post['date_end']);

        $nominal = $transaction->sum('transaction_grandtotal');
        $total_transaction = $transaction->count();

        $group = $transaction->get()->groupBy(function($date) {
            return \Carbon\Carbon::parse($date->transaction_date)->format('d-M-y');
        });

        $groupCount = $group->map(function ($date) {
            return collect($date)->count();
        });

        if (count($group) == 0) {
            $average = 0;
        } else {
            $average = $nominal/count($group);
        }

        $point = LogPoint::orderBy('id_log_point');
        
        $total_redem = $point->count();
        $pointMin = LogPoint::where('source', 'voucher')->sum('point');
        $pointPlus = LogPoint::where('source', 'transaction')->sum('point');

        $nominal_redem = $pointPlus - $pointMin;
        $user = $transaction->select('id_user', DB::raw('SUM(transactions.transaction_grandtotal) as meh'))
                ->groupBy('id_user')
                ->orderBy('meh', 'DESC')
                ->take(10)->get();

        $product = TransactionProduct::with('product')->select('id_product', DB::raw('SUM(transaction_product_qty) as qty'), DB::raw('COUNT(id_product) as total'))
                ->groupBy('id_product')
                ->orderBy('qty', 'DESC')
                ->orderBy('total', 'DESC')
                ->take(10)->get();

        // $treatment = Reservation::with('treatment')->select('id_treatment', DB::raw('COUNT(id_treatment) as total'))
        //         ->groupBy('id_treatment')
        //         ->orderBy('total', 'DESC')
        //         ->take(10)->get();

        $data = [
            'nominal_transaction' => $nominal,
            'total_transaction'   => $total_transaction,
            'average_per_day'     => $average,
            'point_redem'         => $nominal_redem,
            'nominal_redem'       => $nominal_redem,
            'user'                => $user,
            'product'             => $product,
            // 'treatment'           => $treatment,
        ];

        return response()->json(MyHelper::checkGet($data));

    }

    public function customerSummary(Request $request) {
        $post = $request->json()->all();
		$all_user = User::count();
		
		$all_super_admin = User::where('level', 'Super Admin')->count();
		$all_admin = User::where('level', 'Admin')->count();
		$all_verified = User::where('level', 'Customer')->where('phone_verified', '1')->count();
		$all_not_verified = User::where('level', 'Customer')->where('phone_verified', '0')->count();
		$all_customer = User::where('level', 'Customer')->count();
		$all_doctor = User::where('level', 'Doctor')->count();
		
        $new_customer = User::where('level', 'Customer')
                              ->where('created_at', '<=', date('Y-m-d H:i:s'))
                              ->where('created_at', '>=', date('Y-m-d', strtotime("- 7 days")))
                              ->count();

        if (!empty($linked_customer)) {
            foreach ($linked_customer as $key => $link) {
                if (is_null($link['user_natasha'])) {
                    unset($linked_customer[$key]);
                }
            }

            $count_link_customer = $linked_customer->count();
        } else {
            $count_link_customer = 0;
        }

        $android_customer = User::where('level', 'Customer')->where('android_device', '!=', '')->count();
        $ios_customer = User::where('level', 'Customer')->where('ios_device', '!=', '')->count();

        $male_customer = User::where('level', 'Customer')->where('gender', 'Male')->count();

        $female_customer = User::where('level', 'Customer')->where('gender', 'Female')->count();

        if (!isset($post['cust'])) {
            $dataCustomer = [];
        } else {
            $dataCustomer = User::where('level', 'Customer');

            if (isset($post['status'])) {
                if ($post['status'] == 'active') {
                    $dataCustomer = $dataCustomer->where('is_suspended', 0);
                } else {
                    $dataCustomer = $dataCustomer->where('is_suspended', 1);
                }
            }

            if (isset($post['from'])) {
                if ($post['from'] == 'apps') {
                    
                } else {

                }
            }

            if (isset($post['gender'])) {
                if ($post['gender'] == 'male') {
                    $dataCustomer = $dataCustomer->where('gender', 'Male');
                } else {
                    $dataCustomer = $dataCustomer->where('gender', 'Female');
                }
            }

            if (isset($post['city'])) {
                $dataCustomer = $dataCustomer->where('id_city', $post['city']);
            }

            if (isset($age_start) && isset($age_end)) {
                $dataCustomer = $dataCustomer->whereYear('birthday', '>=', date('Y-m-d', strtotime('-'.$age_end.' years'))->whereYear('birthday', '<=', date('Y-m-d', strtotime('-'.$age_start.' years'))));
            }

            if (isset($post['regis_date_start']) && isset($post['regis_date_end'])) {
                $dataCustomer = $dataCustomer->where('created_at', '>=', $post['regis_date_start'].' 00:00:00')->where('created_at', '<=', $post['regis_date_end'].' 00:00:00');
            }

            if (isset($post['device'])) {
                if ($post['device'] == 'ios') {
                    $dataCustomer = $dataCustomer->where('ios_device', '!=', '');
                } else {
                    $dataCustomer = $dataCustomer->where('android_device', '!=', '');
                }
            }

            if (isset($post['name'])) {
                $dataCustomer = $dataCustomer->where('name', 'like', '%'.$post['name'].'%');
            }

            if (isset($post['email'])) {
                $dataCustomer = $dataCustomer->where('email', 'like', '%'.$post['email'].'%');
            }

            if (isset($post['phone'])) {
                $dataCustomer = $dataCustomer->where('phone', 'like', '%'.$post['phone'].'%');
            }

            if (isset($post['point_start']) && isset($post['point_end'])) {
                $dataCustomer = $dataCustomer->where('points', '>=', $post['point_start'])->where('points', '<=', $post['point_end']);
            }

            if (isset($post['id_membership']) && $post['id_membership'] != 0) {
                $dataCustomer = $dataCustomer->where('id_membership', $post['id_membership']);
            }

            $dataCustomer = $dataCustomer->orderBy('created_at', 'DESC')->get()->toArray();
        }

        $teens = 0;
        $young_adult = 0;
        $adult = 0;
        $old = 0;
        $customers = User::where('level', 'Customer')->get();
        foreach ($customers as $key => $customer) {
            // count age
            $birthday = new \DateTime($customer->birthday);
            $today    = new \DateTime(date('Y-m-d'));
            $age      =  $birthday->diff($today)->y;    // get difference in year

            if ($age >= 11 && $age <= 17) {
                $teens++;
            }
            else if ($age >= 18 && $age <= 24) {
                $young_adult++;
            }
            else if ($age >= 25 && $age <= 34) {
                $adult++;
            }
            else if ($age >= 35 && $age <= 100) {
                $old++;
            }
        }
        $customer_age['teens'] = $teens;
        $customer_age['young_adult'] = $young_adult;
        $customer_age['adult'] = $adult;
        $customer_age['old'] = $old;


        $data = [
            'new_customer'    => $new_customer,
            'all_user'        => $all_user,
            'all_super_admin' => $all_super_admin,
            'all_admin'       => $all_admin,
            'all_customer'    => $all_customer,
            'all_doctor'      => $all_doctor,
            'all_verified'    => $all_verified,
            'all_not_verified'=> $all_not_verified,
            'linked_cust'     => $count_link_customer,
            'android_cust'    => $android_customer,
            'ios_customer'    => $ios_customer,
            'male_customer'   => $male_customer,
            'female_customer' => $female_customer,
            'data_customer'   => $dataCustomer,
            'customer_age'    => $customer_age
        ];

        return response()->json(MyHelper::checkGet($data));
    }

    public function customerDetail(DetailReport $request) {
        $id = $request->json('phone');
        $type = $request->json('type');
        $log = [];
        $user = User::with('city.province', 'memberships')->where('phone', $id)->first();
        if($type == 'log'){
            $dataType = LogActivitiesApps::where('phone', $id)->orderBy('id_log_activities_apps', 'DESC')->paginate(10)->toArray();
        }elseif($type == 'transactions'){
            $dataType = Transaction::where('id_user', $user->id)->orderBy('transaction_date', 'DESC')->paginate(10)->toArray();
        }elseif($type == 'point'){
            $dataType = LogPoint::with('transaction')->where('id_user', $user->id)->orderBy('id_log_point', 'DESC')->paginate(10)->toArray();
        }
       
        foreach ($user->point as $key => $value) {
            $value->detail_product = $value->detailProduct;
        }

        if (empty($user)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'User not found'
            ]);
        }
       
        $countVoucher = 0;
        $countTrx = 0;
        if($type == 'point'){
            $countVoucher = LogPoint::where(['id_user' => $user['id'], 'source' => 'voucher'])->get()->count();
            $countTrx = LogPoint::where(['id_user' => $user['id'], 'source' => 'transaction'])->get()->count();
        }

        $data = [
            'user'    => $user,
            $type     => $dataType,
            'trx'     => $countTrx,
            'voucher' => $countVoucher
        ];

        return response()->json([
            'status'    => 'success',
            'result'    => $data
        ]);
    }

    public function product(Request $request) {
        $post = $request->json()->all();
        $product = Product::orderBy('id_product');

        $start = $post['date_start'].' 00:00:00';
        $end = $post['date_end'].' 23:59:59';

        if (empty($product->get())) {
            return response()->json([
                'status'    => 'fail',
                'messages'   => 'Product is empty'
            ]);
        }

        $list = $product->get()->toArray();
        $select = $product->first();

        $total = DB::select(DB::raw("SELECT SUM(transaction_products.transaction_product_qty) as qty, transaction_products.id_transaction_product, transaction_products.transaction_product_qty, transactions.transaction_date, transactions.transaction_payment_status, products.product_name FROM transaction_products, transactions, products WHERE transaction_products.id_transaction = transactions.id_transaction AND transaction_products.id_product = products.id_product AND transactions.transaction_payment_status = 'Success' AND transactions.transaction_date >= '".$start."' AND transactions.transaction_date <= '".$end."' AND transaction_products.id_product = '".$select['id_product']."' GROUP BY CAST(transactions.transaction_date AS DATE)"));

        $recuring = DB::select(DB::raw("SELECT COUNT(transaction_products.id_transaction_product) as qty, transaction_products.id_transaction_product, transaction_products.transaction_product_qty, transactions.transaction_date, transactions.transaction_payment_status, products.product_name FROM transaction_products, transactions, products WHERE transaction_products.id_transaction = transactions.id_transaction AND transaction_products.id_product = products.id_product AND transactions.transaction_date >= '".$start."' AND transactions.transaction_date <= '".$end."' AND transactions.transaction_payment_status = 'Success' AND transaction_products.id_product = '".$select['id_product']."' GROUP BY CAST(transactions.transaction_date AS DATE)"));

        $data = [
            'total_product' => $total,
            'recur_product' => $recuring,
            'list'          => $list,
            'select'        => $select
        ];

        return response()->json([
            'status'    => 'success',
            'result'    => $data
        ]);
    }

    public function productDetail(Request $request) {
        $post = $request->json()->all();
        // return $post;
        $product = Product::orderBy('id_product');

        $start = $post['date_start'].' 00:00:00';
        $end = $post['date_end'].' 23:59:59';

        if (empty($product->get())) {
            return response()->json([
                'status'    => 'fail',
                'messages'   => 'Product is empty'
            ]);
        }

        $list = $product->get()->toArray();
        $select = $product->where('id_product', $post['id_product'])->first();

        if (empty($select)) {
            return response()->json([
                'status'    => 'fail',
                'messages'   => 'Product not found'
            ]);
        }

        $total = DB::select(DB::raw("SELECT SUM(transaction_products.transaction_product_qty) as qty, transaction_products.id_transaction_product, transaction_products.transaction_product_qty, transactions.transaction_date, transactions.transaction_payment_status, products.product_name FROM transaction_products, transactions, products WHERE transaction_products.id_transaction = transactions.id_transaction AND transaction_products.id_product = products.id_product AND transactions.transaction_payment_status = 'Success' AND transactions.transaction_date >= '".$start."' AND transactions.transaction_date <= '".$end."' AND transaction_products.id_product = '".$select['id_product']."' GROUP BY CAST(transactions.transaction_date AS DATE)"));
        // return $total;

        $recuring = DB::select(DB::raw("SELECT COUNT(transaction_products.id_transaction_product) as qty, transaction_products.id_transaction_product, transaction_products.transaction_product_qty, transactions.transaction_date, transactions.transaction_payment_status, products.product_name FROM transaction_products, transactions, products WHERE transaction_products.id_transaction = transactions.id_transaction AND transaction_products.id_product = products.id_product AND transactions.transaction_date >= '".$start."' AND transactions.transaction_date <= '".$end."' AND transactions.transaction_payment_status = 'Success' AND transaction_products.id_product = '".$select['id_product']."' GROUP BY CAST(transactions.transaction_date AS DATE)"));

        $data = [
            'total_product' => $total,
            'recur_product' => $recuring,
            'list'          => $list,
            'select'        => $select
        ];

        return response()->json([
            'status'    => 'success',
            'result'    => $data
        ]);
    }
}
