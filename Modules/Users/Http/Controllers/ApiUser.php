<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Models\UsersDeviceLogin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserFeature;
use App\Http\Models\UserDevice;
use App\Http\Models\UserLocation;
use App\Http\Models\Level;
use App\Http\Models\Doctor;
use App\Http\Models\UserOutlet;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\LogActivitiesBE;
use App\Http\Models\UserInbox;
use App\Http\Models\LogPoint;
use App\Http\Models\UserNotification;
use App\Http\Models\Transaction;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\Setting;
use App\Http\Models\Feature;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\LogBalance;
use Modules\Favorite\Entities\Favorite;

use Modules\Users\Http\Requests\users_list;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone;
use Modules\Users\Http\Requests\users_phone_pin;
use Modules\Users\Http\Requests\users_phone_pin_new;
use Modules\Users\Http\Requests\users_phone_pin_new_admin;
use Modules\Users\Http\Requests\users_new;
use Modules\Users\Http\Requests\users_create;
use Modules\Users\Http\Requests\users_profile;
use Modules\Users\Http\Requests\users_profile_admin;
use Modules\Users\Http\Requests\users_notification;
use Modules\Users\Entities\UserFraud;
use Modules\Users\Entities\UserExtraToken;

use Modules\Balance\Http\Controllers\BalanceController;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Auth;

class ApiUser extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');
        $this->home     = "Modules\Users\Http\Controllers\ApiHome";
        if(\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->membership  = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->inbox  = "Modules\InboxGlobal\Http\Controllers\ApiInbox";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
    }

    function LogActivityFilter($rule='and', $conditions = null, $order_field='id', $order_method='asc', $skip=0, $take=999999999999){

        if($order_field == 'id'){
            $order_field_apps = 'id_log_activities_apps';
            $order_field_be = 'id_log_activities_be';
        }else{
            $order_field_apps = $order_field;
            $order_field_be = $order_field;
        }

        $queryApps = DB::table(env('DB2_DATABASE').'.log_activities_apps as t_log_activities')
            ->leftJoin(env('DB_DATABASE').'.users as t_users','t_users.phone','=','t_log_activities.phone')
            ->select('t_log_activities.*',
                't_users.name',
                't_users.email'
            )->orderBy($order_field_apps, $order_method);
        $queryBe = DB::table(env('DB2_DATABASE').'.log_activities_be as t_log_activities')
            ->leftJoin(env('DB_DATABASE').'.users as t_users','t_users.phone','=','t_log_activities.phone')
            ->select('t_log_activities.*',
                't_users.name',
                't_users.email'
            )->orderBy($order_field_be, $order_method);

        if($conditions != null){
            foreach($conditions as $condition){
                if(isset($condition['subject'])){
                    if($condition['subject'] == 'name' || $condition['subject'] == 'email'){
                        $var = "t_users.".$condition['subject'];
                    }
                    if($condition['subject'] == 'phone' || $condition['subject'] == 'url' || $condition['subject'] == 'subject' || $condition['subject'] == 'request' || $condition['subject'] == 'response' || $condition['subject'] == 'ip' || $condition['subject'] == 'useragent'){
                        $var = "t_log_activities.".$condition['subject'];
                    }
                    if($condition['subject'] == 'response_status'){
                        $var = "t_log_activities.".$condition['subject'];
                    }

                    if($rule == 'and'){
                        if($condition['operator'] == 'like') {
                            $queryApps = $queryApps->where($var, 'like', '%' . $condition['parameter'] . '%');
                            $queryBe = $queryBe->where($var, 'like', '%' . $condition['parameter'] . '%');
                        }else {
                            $queryApps = $queryApps->where($var, '=', $condition['parameter']);
                            $queryBe = $queryBe->where($var, '=', $condition['parameter']);
                        }
                    } else {
                        if($condition['operator'] == 'like') {
                            $queryApps = $queryApps->orWhere($var, 'like', '%' . $condition['parameter'] . '%');
                            $queryBe = $queryBe->orWhere($var, 'like', '%' . $condition['parameter'] . '%');
                        }
                        else {
                            $queryApps = $queryApps->orWhere($var, '=', $condition['parameter']);
                            $queryBe = $queryBe->orWhere($var, '=', $condition['parameter']);
                        }
                    }
                }
            }
        }

        $resultCountApps = $queryApps->count();
        $resultCountBe = $queryBe->count();
        $resultApps = $queryApps->skip($skip)->take($take)->get()->toArray();
        $resultBe = $queryBe->skip($skip)->take($take)->get()->toArray();
        
        foreach ($resultApps as $keyApps => $valApps) {
            $resultApps[$keyApps]->id_log_activities_apps = MyHelper::encSlug($valApps->id_log_activities_apps);
        }
        foreach ($resultBe as $keyBe => $valBe) {
            $resultBe[$keyBe]->id_log_activities_apps = MyHelper::encSlug($valBe->id_log_activities_be);
        }
        
        if($resultApps || $resultBe){
            $response = ['status'	=> 'success',
                'result'	=> [
                    'mobile' => [
                        'data' => $resultApps,
                        'total' => $resultCountApps
                    ],
                    'be' => [
                        'data' => $resultBe,
                        'total' => $resultCountBe
                    ]
                ]
            ];
        } else{
            $response = [
                'status'	=> 'fail',
                'messages'	=> ['Log Activity Not Found']
            ];
        }

        return $response;
    }

    function UserFilter($conditions = null, $order_field='id', $order_method='asc', $skip=0, $take=99999999999, $keyword=null,$columns=null,$objOnly=false){
        $prevResult = [];
        $finalResult = [];

        if($conditions != null){
            $key = 0;
            foreach ($conditions as $key => $cond) {
                $query = User::leftJoin('cities','cities.id_city','=','users.id_city')
                    ->leftJoin('provinces','provinces.id_province','=','cities.id_province')
                    ->orderBy($order_field, $order_method);

                if($cond != null){
                    $scanTrx = false;
                    $scanProd = false;
                    $scanTag = false;
                    $notTrx = false;
                    $userTrxProduct = false;
                    $exceptUserTrxProduct = false;
                    $scanOtherProduct = false;
                    $trxOutlet = false;

                    $rule = $cond['rule'];
                    unset($cond['rule']);
                    $conRuleNext = $cond['rule_next'];
                    unset($cond['rule_next']);

                    if(isset($cond['rules'])){
                        $cond = $cond['rules'];
                    }

                    /*========= Check conditions related to the subject of the transaction =========*/
                    $countTrxDate = 0;
                    $arr_tmp_product = [];
                    $arr_tmp_outlet = [];
                    foreach($cond as $i => $condition){
                        if(stristr($condition['subject'], 'trx')) $scanTrx = true;
                        if(stristr($condition['subject'], 'trx_product')) $scanProd = true;
                        if(stristr($condition['subject'], 'trx_product_tag')) $scanTag = true;

                        if($condition['subject'] == 'trx_count' && ($condition['operator'] == '=' || $condition['operator'] == '<' || $condition['operator'] == '<=') && $condition['parameter'] <= 0){
                            $notTrx = true;
                            unset($cond[$i]);
                        }

                        if($condition['subject'] == 'trx_product'){
                            array_push($arr_tmp_product,$condition);
                            unset($cond[$i]);
                        }

                        if($condition['subject'] == 'trx_outlet'){
                            array_push($arr_tmp_outlet,$condition);
                            unset($cond[$i]);
                        }

                        if($condition['subject'] == 'trx_product' || $condition['subject'] == 'trx_product_count' || $condition['subject'] == 'trx_product_tag' || $condition['subject'] == 'trx_product_tag_count'){
                            $userTrxProduct = true;
                        }elseif($condition['subject']  != 'trx_date' && stristr($condition['subject'], 'trx')){
                            $scanOtherProduct = true;
                        }
                        if($condition['subject'] == 'trx_date'){
                            $countTrxDate++;
                            if($condition['parameter'] != date('Y-m-d') && $condition['operator'] != '<='){
                                $exceptUserTrxProduct = true;
                            }
                        }

                        if($condition['subject'] == 'last_trx_date'){
                            $exceptUserTrxProduct = true;
                        }
                    }

                    if($exceptUserTrxProduct == true || $countTrxDate > 1){
                        $userTrxProduct = false;
                    }
                    /*================================== END check ==================================*/
                    if(is_object($cond)) $cond = $cond->toArray();
                    if(count($arr_tmp_outlet) > 0)array_push($cond, ['outlets' => $arr_tmp_outlet]);

                    if($scanTrx == true){
                        if($userTrxProduct == true){
                            array_push($cond, ['products' => $arr_tmp_product]);

                            if($scanOtherProduct == true){
                                $query = $query->leftJoin('transactions','transactions.id_user','=','users.id');
                                $query = $query->leftJoin('transaction_shipments','transactions.id_transaction','=','transaction_shipments.id_transaction');
                                if($scanTag == true){
                                    $query = $query->leftJoin('user_trx_products','users.id','=','user_trx_products.id_user');
                                    $query = $query->leftJoin('product_tags','product_tags.id_product','=','user_trx_products.id_product');
                                }else{
                                    $query = $query->leftJoin('user_trx_products','transactions.id_user','=','user_trx_products.id_user');
                                }
                            }else{
                                $query = $query->leftJoin('user_trx_products','users.id','=','user_trx_products.id_user');
                                if($scanTag == true){
                                    $query = $query->leftJoin('product_tags','product_tags.id_product','=','user_trx_products.id_product');
                                }
                            };

                        }elseif($scanProd == true){
                            $query = $query->leftJoin('transaction_products','users.id','=','transaction_products.id_user');
                            $query = $query->leftJoin('transactions','transactions.id_transaction','=','transaction_products.id_transaction');
                            $query = $query->leftJoin('transaction_shipments','transactions.id_transaction','=','transaction_shipments.id_transaction');
                            if($scanTag == true){
                                $query = $query->leftJoin('products','transaction_products.id_product','=','products.id_product');
                                $query = $query->leftJoin('product_tags','products.id_product','=','product_tags.id_product');
                            }
                        } else {
                            $query = $query->leftJoin('transactions','transactions.id_user','=','users.id');
                            $query = $query->leftJoin('transaction_shipments','transactions.id_transaction','=','transaction_shipments.id_transaction');
                        }

                        $query = $query->groupBy('users.id');
                        $query = $query->select('users.*',
                            'cities.*',
                            'provinces.*',
                            DB::raw('YEAR(CURDATE()) - YEAR(users.birthday) AS age')
                        );
                    } else {
                        $query = $query->select('users.*',
                            'cities.*',
                            'provinces.*',
                            DB::raw('YEAR(CURDATE()) - YEAR(users.birthday) AS age')
                        );
                    }

                    if($notTrx){
                        $query = $query->whereDoesntHave('transactions', function ($q) use ($cond, $rule, $userTrxProduct) {
                            $q = $this->queryFilter($cond, $rule, $q, $userTrxProduct);
                        });
                    }else{
                        $query = $this->queryFilter($cond, $rule, $query, $userTrxProduct);
                    }
                }else {
                    $query = $query->select('users.*',
                        'cities.*',
                        'provinces.*',
                        DB::raw('YEAR(CURDATE()) - YEAR(users.birthday) AS age')
                    );
                }
                $result = array_pluck($query->get()->toArray(),'id');

                if($key > 0){
                    if($ruleNext == 'and'){
                        $prevResult = array_intersect($result,$prevResult);
                    }else{
                        $prevResult = array_unique(array_merge($result, $prevResult));
                    }
                    $ruleNext = $conRuleNext;
                }else{
                    $prevResult = $result;
                    $ruleNext = $conRuleNext;
                }

                $key++;
            }

            /*============= Final query when condition not null =============*/
            $finalResult = User::leftJoin('cities','cities.id_city','=','users.id_city')
                ->leftJoin('provinces','provinces.id_province','=','cities.id_province')
                ->orderBy($order_field, $order_method)
                ->select('users.*',
                    'cities.*',
                    'provinces.*',
                    DB::raw('YEAR(CURDATE()) - YEAR(users.birthday) AS age')
                )
                ->whereIn('users.id', $prevResult);
        }else {
            $query = User::leftJoin('cities','cities.id_city','=','users.id_city')
                ->leftJoin('provinces','provinces.id_province','=','cities.id_province')
                ->orderBy($order_field, $order_method);

            /*============= Final query when condition is null =============*/
            $finalResult = $query->select('users.*',
                'cities.*',
                'provinces.*',
                DB::raw('YEAR(CURDATE()) - YEAR(users.birthday) AS age')
            );
        }

        $resultCount = $finalResult->count(); // get total result
        if($columns){
            $finalResult->select($columns);
        }
        if($key_free??false){
            $finalResult->where(function($query) use ($keyword){
                $query->orWhere('name','like','%'.$keyword.'%')->orWhere('email','like','%'.$keyword.'%')->orWhere('phone','like','%'.$keyword.'%');
            });
        }
        if($objOnly){
            return $finalResult;
        }

        $result = $finalResult->skip($skip)->take($take)->get()->toArray();
        if($result){
            $response = ['status'	=> 'success',
                'result'	=> $result,
                'total' => $resultCount
            ];
        } else{
            $response = [
                'status'	=> 'fail',
                'messages'	=> ['User Not Found']
            ];
        }

        return $response;
    }

    function queryFilter($conditions, $rule, $query, $userTrxProduct){
        foreach($conditions as $index => $condition){
            if(isset($condition['subject'])){
                if($condition['operator'] != '='){
                    $conditionParameter = $condition['operator'];
                }

                if($condition['operator']=='WHERE IN'){
                    $param=explode(',', $condition['parameter']);
                    if($rule == 'and'){
                        $query = $query->whereIn($condition['subject'],$param);
                    } else {
                        $query = $query->orWhereIn($condition['subject'],$param);
                    }
                    continue;
                }

                /*============= All query with rule 'AND' ==================*/
                if($rule == 'and'){
                    if($condition['subject'] == 'id' || $condition['subject'] == 'name' || $condition['subject'] == 'phone' || $condition['subject'] == 'email' || $condition['subject'] == 'address'){
                        $var = "users.".$condition['subject'];

                        if($condition['operator'] == 'like')
                            $query = $query->where($var,'like','%'.$condition['parameter'].'%');
                        elseif(strtoupper($condition['operator']) == 'WHERE IN')
                            $query = $query->whereIn($var,explode(',',$condition['parameter']));
                        else
                            $query = $query->where($var,'=',$condition['parameter']);
                    }

                    if($condition['subject'] == 'gender' || $condition['subject'] == 'is_suspended' || $condition['subject'] == 'email_verified' || $condition['subject'] == 'phone_verified' || $condition['subject'] == 'email_unsubscribed' || $condition['subject'] == 'provider' || $condition['subject'] == 'city_name' || $condition['subject'] == 'city_postal_code' || $condition['subject'] == 'province_name' || $condition['subject'] == 'level'){
                        if($condition['subject'] == 'city_name' || $condition['subject'] == 'city_postal_code') $var = "cities.".$condition['subject'];
                        else if($condition['subject'] == 'province_name') $var = "provinces.".$condition['subject'];
                        else $var = "users.".$condition['subject'];

                        if(isset($conditionParameter))$query = $query->where($var,'=',$conditionParameter);
                        else $query = $query->where($var,'=',$condition['parameter']);
                    }

                    if($condition['subject'] == 'device'){

                        if($conditionParameter == 'None'){
                            $query = $query->whereNull('users.android_device')->whereNull('users.ios_device');
                        }

                        if($conditionParameter == 'Android'){
                            $query = $query->whereNotNull('users.android_device')->whereNull('users.ios_device');
                        }

                        if($conditionParameter == 'IOS'){
                            $query = $query->notNull('users.android_device')->whereNotNull('users.ios_device');
                        }

                        if($conditionParameter == 'Both'){
                            $query = $query->whereNotNull('users.android_device')->whereNotNull('users.ios_device');
                        }
                    }

                    if($condition['subject'] == 'age'){
                        $query = $query->whereRaw(DB::raw('timestampdiff(year,users.birthday,curdate()) '.$condition['operator'].' '.$condition['parameter']));
                    }

                    if($condition['subject'] == 'birthday_date'){
                        $var = 'users.birthday';
                        $query = $query->where($var, $condition['operator'], $condition['parameter']);
                    }

                    if($condition['subject'] == 'birthday_month'){
                        $var = 'users.birthday';

                        $query = $query->whereMonth($var,'=',$conditionParameter);
                    }

                    if($condition['subject'] == 'birthday_year'){
                        $var = 'users.birthday';
                        $query = $query->whereYear($var, $condition['operator'], $condition['parameter']);
                    }

                    if($condition['subject'] == 'birthday_today'){
                        $var = 'users.birthday';
                        $query = $query->where(function ($query) use ($var){
                            $query->whereDay($var, '=', date('d'))
                                ->whereMonth($var, '=', date('m'));
                        });
                    }

                    if($condition['subject'] == 'membership'){
                        $query = $query->where('users.id_membership','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'points'){
                        $query = $query->where('users.points',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'balance'){
                        $query = $query->where('users.balance',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'register_date'){
                        $query = $query->where('users.created_at',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'register_today'){
                        $today = date('Y-m-d');
                        $query = $query->where('users.created_at', '=', $today);
                    }

                    /*======= Array tmp for condition =======*/
                    $arrVar = [
                        'trx_type' => 'transactions.trasaction_type',
                        'trx_payment_type' => 'transactions.trasaction_payment_type',
                        'trx_payment_status' => 'transactions.transaction_payment_status',
                        'trx_shipment_courier' => 'transaction_shipments.shipment_courier'
                    ];

                    if($condition['subject'] == 'trx_type' || $condition['subject'] == 'trx_shipment_courier' || $condition['subject'] == 'trx_payment_type' || $condition['subject'] == 'trx_payment_status'){
                        $conditionSubject = $condition['subject'];
                        $query = $query->where($arrVar[$conditionSubject],'=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_date' && $userTrxProduct == false){
                        $query = $query->where('transactions.transaction_date',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'last_trx'){
                        $condition['parameter'] = (int)$condition['parameter'] - 1;
                        $lastDate = date('Y-m-d', strtotime('-'.$condition['parameter'].' days'));
                        // return $condition['operator'];
                        $query = $query->whereDate('transactions.transaction_date',$condition['operator'],$lastDate);
                    }

                    if($condition['subject'] == 'trx_outlet'){
                        $query = $query->where('transactions.id_outlet','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_outlet_not'){
                        $query = $query->whereNotIn('transactions.id_outlet',[$conditionParameter]);
                    }

                    if($condition['subject'] == 'trx_count'){
                        $query = $query->whereRaw('(SELECT COUNT(id_transaction) FROM transactions WHERE id_user = users.id)'
                            .$condition['operator'].$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_subtotal'){
                        $query = $query->where('transactions.transaction_subtotal',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_tax'){
                        $query = $query->where('transactions.transaction_tax',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_service'){
                        $query = $query->where('transactions.transaction_service',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_discount'){
                        $query = $query->where('transactions.transaction_discount',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_payment_type'){
                        $query = $query->where('transactions.trasaction_payment_type','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_payment_status'){
                        $query = $query->where('transactions.transaction_payment_status','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_void_count'){
                        $query = $query->whereNotNull('transactions.void_date')->havingRaw('COUNT(*) '.$condition['operator'].' '.$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_shipment_value'){
                        $query = $query->where('transactions.transaction_shipment',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_product_not'){
                        if($userTrxProduct == true){
                            $query = $query->whereNotIn('user_trx_products.id_product',[$conditionParameter]);
                        }else{
                            $query = $query->whereNotIn('transaction_products.id_product',[$conditionParameter]);
                        }
                    }

                    if($condition['subject'] == 'trx_product_tag'){
                        $query = $query->where('product_tags.id_tag','=',$conditionParameter);
                    }
                }
                /*====================== End IF ============================*/

                /*============= All query with rule 'OR' ==================*/
                else{
                    if($condition['subject'] == 'all_user'){
                        $query = $query->orWhereRaw('1');
                    }
                    if($condition['subject'] == 'id' || $condition['subject'] == 'name' || $condition['subject'] == 'phone' || $condition['subject'] == 'email' || $condition['subject'] == 'address'){
                        $var = "users.".$condition['subject'];

                        if($condition['operator'] == 'like')
                            $query = $query->orWhere($var,'like','%'.$condition['parameter'].'%');
                        elseif(strtoupper($condition['operator']) == 'WHERE IN')
                            $query = $query->orWhereIn($var,explode(',',$condition['parameter']));
                        else
                            $query = $query->orWhere($var,'=',$condition['parameter']);
                    }

                    if($condition['subject'] == 'gender' || $condition['subject'] == 'is_suspended' || $condition['subject'] == 'email_verified' || $condition['subject'] == 'phone_verified' || $condition['subject'] == 'email_unsubscribed' || $condition['subject'] == 'provider' || $condition['subject'] == 'city_name' || $condition['subject'] == 'city_postal_code' || $condition['subject'] == 'province_name' || $condition['subject'] == 'level'){
                        if($condition['subject'] == 'city_name' || $condition['subject'] == 'city_postal_code') $var = "cities.".$condition['subject'];
                        else if($condition['subject'] == 'province_name') $var = "provinces.".$condition['subject'];
                        else $var = "users.".$condition['subject'];

                        if(isset($conditionParameter))$query = $query->orWhere($var,'=',$conditionParameter);
                        else $query = $query->orWhere($var,'=',$condition['parameter']);
                    }

                    if($condition['subject'] == 'device'){

                        if($conditionParameter == 'None'){
                            $query = $query->orWhereNull('users.android_device')->orWhereNull('users.ios_device');
                        }

                        if($conditionParameter == 'Android'){
                            $query = $query->orwhereNotNull('users.android_device')->orWhereNull('users.ios_device');
                        }

                        if($conditionParameter == 'IOS'){
                            $query = $query->orwhereNull('users.android_device')->orwhereNotNull('users.ios_device');
                        }

                        if($conditionParameter == 'Both'){
                            $query = $query->orwhereNotNull('users.android_device')->orwhereNotNull('users.ios_device');
                        }
                    }

                    if($condition['subject'] == 'age'){
                        $query = $query->orWhereRaw(DB::raw('timestampdiff(year,users.birthday,curdate()) '.$condition['operator'].' '.$condition['parameter']));
                    }

                    if($condition['subject'] == 'birthday_date'){
                        $var = 'users.birthday';
                        $query = $query->orWhere($var, $condition['operator'], $condition['parameter']);
                    }

                    if($condition['subject'] == 'birthday_month'){
                        $var = 'users.birthday';
                        $query = $query->orWhereRaw('MONTH('.$var.')='.$conditionParameter);
                    }

                    if($condition['subject'] == 'birthday_year'){
                        $var = 'users.birthday';
                        $query = $query->orWhereRaw('YEAR('.$var.')='.$condition['parameter']);
                    }

                    if($condition['subject'] == 'birthday_today'){
                        $var = 'users.birthday';
                        $query = $query->orWhere(function ($query) use ($var){
                            $query->whereDay($var, '=', date('d'))
                                ->whereMonth($var, '=', date('m'));
                        });
                    }

                    if($condition['subject'] == 'membership'){
                        $query = $query->orWhere('users.id_membership','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'points'){
                        $query = $query->orWhere('users.points',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'balance'){
                        $query = $query->orWhere('users.balance',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'register_date'){
                        $query = $query->orWhere('users.created_at',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'register_today'){
                        $today = date('Y-m-d');
                        $query = $query->orWhere('users.created_at', '=', $today);
                    }

                    /*======= Array tmp for condition =======*/
                    $arrVar = [
                        'trx_type' => 'transactions.trasaction_type',
                        'trx_payment_type' => 'transactions.trasaction_payment_type',
                        'trx_payment_status' => 'transactions.transaction_payment_status',
                        'trx_shipment_courier' => 'transaction_shipments.shipment_courier'
                    ];

                    if($condition['subject'] == 'trx_type' || $condition['subject'] == 'trx_shipment_courier' || $condition['subject'] == 'trx_payment_type' || $condition['subject'] == 'trx_payment_status'){
                        $conditionSubject = $condition['subject'];
                        $query = $query->orWhere($arrVar[$conditionSubject],'=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_date' && $userTrxProduct == false){
                        $query = $query->orWhere('transactions.transaction_date',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'last_trx'){
                        $condition['parameter'] = (int)$condition['parameter'] - 1;
                        $lastDate = date('Y-m-d', strtotime('-'.$condition['parameter'].' days'));
                        // return $condition['operator'];
                        $query = $query->orWhereDate('transactions.transaction_date',$condition['operator'],$lastDate);
                    }

                    if($condition['subject'] == 'trx_outlet'){
                        $query = $query->orWhere('transactions.id_outlet','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_outlet_not'){
                        $query = $query->orWhereNotIn('transactions.id_outlet',[$conditionParameter]);
                    }

                    if($condition['subject'] == 'trx_count'){
                        $query = $query->orWhereRaw('(SELECT COUNT(id_transaction) FROM transactions WHERE id_user = users.id)'
                            .$condition['operator'].$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_subtotal'){
                        $query = $query->orWhere('transactions.transaction_subtotal',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_tax'){
                        $query = $query->orWhere('transactions.transaction_tax',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_service'){
                        $query = $query->orWhere('transactions.transaction_service',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_discount'){
                        $query = $query->orWhere('transactions.transaction_discount',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_payment_type'){
                        $query = $query->orWhere('transactions.trasaction_payment_type','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_payment_status'){
                        $query = $query->orWhere('transactions.transaction_payment_status','=',$conditionParameter);
                    }

                    if($condition['subject'] == 'trx_void_count'){
                        $query = $query->orWhereNotNull('transactions.void_date')->havingRaw('COUNT(*) '.$condition['operator'].' '.$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_shipment_value'){
                        $query = $query->orWhere('transactions.transaction_shipment',$condition['operator'],$condition['parameter']);
                    }

                    if($condition['subject'] == 'trx_product_not'){
                        if($userTrxProduct == true){
                            $query = $query->orWhereNotIn('user_trx_products.id_product',[$conditionParameter]);
                        }else{
                            $query = $query->orWhereNotIn('transaction_products.id_product',[$conditionParameter]);
                        }
                    }

                    if($condition['subject'] == 'trx_product_tag'){
                        $query = $query->orWhere('product_tags.id_tag','=',$conditionParameter);
                    }
                }
                /*====================== End ELSE ============================*/

                if($condition['subject'] == 'trx_product_tag_count'){
                    if($userTrxProduct == true){
                        $query = $query->havingRaw('SUM(user_trx_products.product_qty)'.$condition['operator'].$condition['parameter']);
                    }else{
                        $query = $query->havingRaw('SUM(transaction_products.transaction_product_qty)'.$condition['operator'].$condition['parameter']);
                    }
                }
            }else{

                if(isset($condition['products'])){
                    $count_products = count($condition['products']);
                    $arr_id_products = [];

                    if($userTrxProduct == true){
                        $join_table = 'user_trx_products';
                        $having_column = 'user_trx_products.product_qty';
                    }else{
                        $join_table = 'transaction_products';
                        $having_column = 'transaction_products.transaction_product_qty';
                    }

                    foreach($condition['products'] as $prod){
                        if(isset($prod['parameterSpecialCondition'])){
                            $parameter = $prod['parameterSpecialCondition'];
                            $operator = $prod['operatorSpecialCondition'];
                        }else{
                            $parameter = $prod['parameter'];
                            $operator = $prod['operator'];
                        }

                        if($parameter == 0 || $parameter == NULL || $parameter == ""){
                            $parameter = 1;
                        }
                        $id = DB::table('users')->join($join_table, $join_table.'.id_user', '=', 'users.id')
                            ->where($join_table.'.id_product',$prod['id'])->havingRaw('SUM('.$having_column.')'.$operator.$parameter)
                            ->groupBy($join_table.'.id_user')
                            ->select($join_table.'.id_product')->first();
                        if($id){
                            array_push($arr_id_products, $id->id_product);
                        }
                    }

                    if($rule == 'and'){
                        $query = $query->whereIn($join_table.'.id_product',$arr_id_products);
                        $query = $query->havingRaw('COUNT(distinct '.$join_table.'.id_product) = '.$count_products);
                    }elseif ($rule == 'or'){
                        $query = $query->orWhereIn($join_table.'.id_product',$arr_id_products);
                    }
                }

                if(isset($condition['outlets'])){
                    $count_outlets = count($condition['outlets']);
                    $arr_id_outlet = [];

                    foreach($condition['outlets'] as $prod){
                        if(isset($prod['parameterSpecialCondition'])){
                            $parameter = $prod['parameterSpecialCondition'];
                            $operator = $prod['operatorSpecialCondition'];
                        }else{
                            $parameter = $prod['parameter'];
                            $operator = $prod['operator'];
                        }

                        if($parameter == 0 || $parameter == NULL || $parameter == ""){
                            $parameter = 1;
                        }
                        $id = DB::table('users')->join('transactions', 'transactions.id_user', '=', 'users.id')
                            ->where('transactions.id_outlet',$prod['id'])->havingRaw('Count(transactions.id_outlet)'.$operator.$parameter)
                            ->groupBy('transactions.id_user')
                            ->select('transactions.id_outlet')->first();

                        if($id){
                            array_push($arr_id_outlet, $id->id_outlet);
                        }
                    }

                    if($rule == 'and'){
                        $query = $query->whereIn('transactions.id_outlet',$arr_id_outlet);
                        $query = $query->havingRaw('COUNT(distinct transactions.id_outlet) = '.$count_outlets);
                    }elseif ($rule == 'or'){
                        $query = $query->orWhereIn('transactions.id_outlet',$arr_id_outlet);
                    }
                }
            }
        }

        return $query;
    }

    function getFeatureControl(Request $request){
        $post = $request->json()->all();
        $userQuery = User::where('phone','=',$post['phone'])->get()->toArray();
        if($userQuery){
            $user = $userQuery[0];

            if($user['level'] == 'Super Admin'){
                $checkFeature = Feature::select('id_feature')->get()->toArray();
            }else{
                $checkFeature = UserFeature::join('features', 'features.id_feature', '=', 'user_features.id_feature')
                    ->where('user_features.id_user', '=', $user['id'])
                    ->select('features.id_feature')->get()->toArray();
            }
            $result = [
                'status'  => 'success',
                'result'  => array_pluck($checkFeature, 'id_feature')
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'messages'  => ['No User Found']
            ];
        }
        return response()->json($result);
    }

    function check(users_phone $request){
        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::with('city')->where('phone', '=', $phone)->get()->toArray();

        if(isset($data[0]['is_suspended']) && $data[0]['is_suspended'] == '1'){
            return response()->json([
                'status' => 'success',
                'result' => $data,
                'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di hello@example.id']
            ]);
        }

        if(isset($data[0])){
            if($data[0]['email'] == null || $data[0]['name'] == null){
                $data[0]['phone_verified'] = '0';
            }
        }

        if(count($data) > 1){
            return response()->json([
                'status' => 'success',
                'result' => $data,
                'messages' => null
            ]);
        }else{
            return response()->json(MyHelper::checkGet($data));
        }
    }
    /**
     * [Users] Create User & PIN
     *
     * to register user based on phone and generate PIN
     *
     */
    function createPin(users_phone $request){
        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if(!$data){
            $pin = MyHelper::createRandomPIN(6, 'angka');
            // $pin = '777777';

            $provider = MyHelper::cariOperator($phone);
            $is_android 	= null;
            $is_ios 		= null;
            $device_id = $request->json('device_id');
            $device_token = $request->json('device_token');
            $device_type = $request->json('device_type');

            if($request->json('device_type') == "Android") {
                $is_android = $device_id;
            } else{
                $is_ios = $device_id;
            }

            if($request->json('device_token') != "") {
                $device_token = $request->json('device_token');
            }

            $create = User::create(['phone' => $phone,
                'provider' 		=> $provider,
                'password'		=> bcrypt($pin),
                'android_device' => $is_android,
                'ios_device' 	=> $is_ios
            ]);

            if($create){
                if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')){
                    app($this->home)->updateDeviceUser($create, $request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
                }
            }
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($_SERVER['HTTP_USER_AGENT'],'iOS')) $useragent = 'iOS';
            if(stristr($_SERVER['HTTP_USER_AGENT'],'okhttp')) $useragent = 'Android';
            if(stristr($_SERVER['HTTP_USER_AGENT'],'GuzzleHttp')) $useragent = 'Browser';


            if(\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM('Pin Sent', $phone,
                    ['pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s')
                    ],
                    $useragent
                );
            }

            app($this->membership)->calculateMembership($phone);

            //create user location when register
            if($request->json('latitude') && $request->json('longitude')){
                $userLocation = UserLocation::create([
                    'id_user' => $create['id'],
                    'lat' => $request->json('latitude'),
                    'lng' => $request->json('longitude'),
                    'action' => 'Register'
                ]);

            }

            if(env('APP_ENV') == 'production'){
                $result = ['status'	=> 'success',
                    'result'	=> ['phone'	=>	$create->phone,
                        'autocrm'	=>	$autocrm
                    ]
                ];
            }else{
                $result = ['status'	=> 'success',
                    'result'	=> ['phone'	=>	$create->phone,
                        'autocrm'	=>	$autocrm,
                        'pin'	=>	MyHelper::encPIN($pin)
                    ]
                ];
            }
            return response()->json($result);

        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This phone number already registered']
            ];
            return response()->json($result);
        }
    }

    /**
     * [Users] Check User PIN
     *
     * to check users phone number and PIN
     *
     */
    function checkPin(users_phone_pin $request){
        $is_android 	= 0;
        $is_ios 		= 0;
        $device_id 		= null;
        $device_token 	= null;

        $ip = null;
        if(!empty($request->json('ip'))){
            $ip = $request->json('ip');
        } else {
            if(!empty($request->header('ip-address-view'))){
                $ip = $request->header('ip-address-view');
            }else{
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        }

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(!empty($request->json('useragent'))){
            $useragent = $request->json('useragent');
        } else {
            if(!empty($request->header('user-agent-view'))){
                $useragent = $request->header('user-agent-view');
            }else{
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        $device = null;

        if($useragent == "Opera/9.80 (Windows NT 6.1) Presto/2.12.388 Version/12.16")
            $device = 'Web Browser';
        if(stristr($useragent, 'iOS'))
            $device = 'perangkat iOS';
        if(stristr($useragent, 'okhttp'))
            $device = 'perangkat Android';
        if(stristr($useragent, 'Linux; U;')){
            $sementara = preg_match('/\(Linux\; U\; (.+?)\; (.+?)\//', $useragent, $matches);
            $device = $matches[2];
        }
        if(empty($device))
            $device = $useragent;


        if($request->json('device_type') == "Android") {
            $is_android = 1;
            $device_type = "Android";
        } elseif($request->json('device_type') == "IOS") {
            $is_ios = 1;
            $device_type = "IOS";
        }

        if($request->json('device_id') != "") {
            $device_id = $request->json('device_id');
        }

        if($request->json('device_token') != "") {
            $device_token = $request->json('device_token');
        }

        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $datauser = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        $cekFraud = 0;
        if($datauser){
            if(Auth::attempt(['phone' => $phone, 'password' => $request->json('pin')])){
                //untuk verifikasi admin panel
                if($request->json('admin_panel')){
                    return ['status'=>'success'];
                }
                //kalo login success
                if($is_android != 0 || $is_ios != 0){

                    //kalo dari device
                    $checkdevice = UserDevice::where('device_type','=',$device_type)
                        ->where('device_id','=',$device_id)
                        ->where('device_token','=',$device_token)
                        ->where('id_user','=',$datauser[0]['id'])
                        ->get()
                        ->toArray();
                    if(!$checkdevice){
                        //not trusted device or new device
                        $createdevice = UserDevice::updateOrCreate(['device_id' => $device_id], [
                            'id_user'           => $datauser[0]['id'],
                            'device_token'		=> $device_token,
                            'device_type'		=> $device_type
                        ]);
                        if($device_type == "Android")
                            $update = User::where('id','=',$datauser[0]['id'])->update(['android_device' => $device_id, 'ios_device' => null]);
                        if($device_type == "IOS")
                            $update = User::where('id','=',$datauser[0]['id'])->update(['android_device' => null, 'ios_device' => $device_id]);

                        if(stristr($useragent,'iOS')) $useragent = 'iOS';
                        if(stristr($useragent,'okhttp')) $useragent = 'Android';
                        if(stristr($useragent,'GuzzleHttp')) $useragent = 'Browser';

                        // if(\Module::collections()->has('Autocrm')) {
                        // 	$autocrm = app($this->autocrm)->SendAutoCRM('Login Success', $request->json('phone'),
                        // 												['ip' => $ip,
                        // 												 'useragent' => $useragent,
                        // 												 'now' => date('Y-m-d H:i:s')
                        // 												]);
                        // }
                    }
                }

                if($device_id == null && $device_token == null){
                    //handle login dari web, tidak ada device id dan token sama sekali wajib notif
                    // app($this->Sms)->sendSMSAuto('login success', $datauser[0]['id'], date('F d, Y H:i'), $device, $ip);
                }
                if(stristr($useragent,'iOS')) $useragent = 'iOS';
                if(stristr($useragent,'okhttp')) $useragent = 'Android';
                if(stristr($useragent,'GuzzleHttp')) $useragent = 'Browser';

                //update count login failed
                if($datauser[0]['count_login_failed'] > 0){
                    $updateCountFailed = User::where('phone', $phone)->update(['count_login_failed' => 0]);
                }

                $result 			= [];
                $result['status'] 	= 'success';
                $result['date'] 	= date('Y-m-d H:i:s');
                $result['device'] 	= $device;
                $result['ip'] 		= $ip;

                if($request->json('latitude') && $request->json('longitude')){
                    $userLocation = UserLocation::create([
                        'id_user' => $datauser[0]['id'],
                        'lat' => $request->json('latitude'),
                        'lng' => $request->json('longitude'),
                        'action' => 'Login'
                    ]);
                }

                if($device_id) {
                    $fraud = FraudSetting::where('parameter', 'LIKE', '%device ID%')->where('fraud_settings_status', 'Active')->first();
                    if ($fraud) {
                        app($this->setting_fraud)->createUpdateDeviceLogin($datauser[0], $device_id);
                        $deviceCus = UsersDeviceLogin::where('device_id', '=' ,$device_id)
                            ->where('status','Active')
                            ->select('id_user')
                            ->orderBy('created_at','asc')
                            ->groupBy('id_user')
                            ->get()->toArray('id_user');

                        $count = count($deviceCus);
                        $check = array_slice($deviceCus, (int)$fraud['parameter_detail']);
                        $check = array_column($check,'id_user');

                        if($deviceCus && count($deviceCus) > (int)$fraud['parameter_detail'] && array_search($datauser[0]['id'], $check) !== false){
                            $sendFraud = app($this->setting_fraud)->checkFraud($fraud, $datauser[0], ['device_id' => $device_id, 'device_type' => $request->json('device_type')], 0, 0, null, 0);
                            $data = User::with('city')->where('phone', '=', $datauser[0]['phone'])->get()->toArray();

                            if ($data[0]['is_suspended'] == 1) {
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di suarapelanggan@champ-group.com']
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Akun Anda tidak dapat login di device ini karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di suarapelanggan@champ-group.com']
                                ]);
                            }
                        }
                    }
                }

                if(\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Login Success', $phone,
                        ['ip' => $ip,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s')
                        ]);
                }
            } else{
                //kalo login gagal
                if($datauser){
                    //update count login failed
                    $updateCountFailed = User::where('phone', $phone)->update(['count_login_failed' => $datauser[0]['count_login_failed'] + 1]);

                    $failedLogin = $datauser[0]['count_login_failed']+1;
                    //get setting login failed
                    $getSet = Setting::where('key', 'count_login_failed')->first();
                    if($getSet && $getSet->value){
                        if($failedLogin >= $getSet->value){
                            $autocrm = app($this->autocrm)->SendAutoCRM('Login Failed', $phone,
                                ['ip' => $ip,
                                    'useragent' => $useragent,
                                    'now' => date('Y-m-d H:i:s')
                                ]);
                        }
                    }
                }

                $result 			= [];
                $result['status'] 	= 'fail';
                $result['messages'] = ['Kata sandi yang kamu masukkan kurang tepat'];
                $result['date'] 	= date('Y-m-d H:i:s');
                $result['device'] 	= $device;
                $result['ip'] 		= $ip;
            }

            if($datauser[0]['pin_changed'] == '0'){
                $result['pin_changed'] = false;
            }else{
                $result['pin_changed'] = true;
            }
        }
        else {
            $result['status'] 	= 'fail';
            $result['messages'] = ['Nomor HP belum terdaftar'];
        }



        return response()->json($result);
    }

    function checkPinBackend(users_phone_pin $request){
        $phone = $request->json('phone');

        $datauser = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if($datauser){
            if(Auth::attempt(['phone' => $phone, 'password' => $request->json('pin')]) && $request->json('admin_panel')){
                return ['status'=>'success'];
            } else{
                $result 			= [];
                $result['status'] 	= 'fail';
            }
        }
        else {
            $result['status'] 	= 'fail';
            $result['messages'] = ['Nomor HP belum terdaftar'];
        }
        return response()->json($result);
    }

    function resendPin(users_phone $request){
        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if($data){
            $pinnya = rand(100000,999999);
            $pin = bcrypt($pinnya);
            /*if($data[0]['phone_verified'] == 0){*/
            $update = User::where('phone','=',$phone)->update(['password' => $pin]);

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($_SERVER['HTTP_USER_AGENT'],'iOS')) $useragent = 'iOS';
            if(stristr($_SERVER['HTTP_USER_AGENT'],'okhttp')) $useragent = 'Android';
            if(stristr($_SERVER['HTTP_USER_AGENT'],'GuzzleHttp')) $useragent = 'Browser';


            if(\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM('Pin Sent', $phone,
                    ['pin' => $pinnya,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s')], $useragent);
            }

            if(env('APP_ENV') == 'production'){
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$data[0]['phone'],
                    ]
                ];
            }else{
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$data[0]['phone'],
                    'pin'	=>	MyHelper::encPIN($pinnya)
                    ]
                ];
            }
            /*} else {
                $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['This phone number is already verified']
                    ];
            }*/
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function forgotPin(users_forgot $request){
        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $user = User::where('phone', '=', $phone)->first();

        if(!$user){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['User not found.']
            ];
            return response()->json($result);
        }

        if($user['email'] == null){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['User email is empty.']
            ];
            return response()->json($result);
        }

        $data = User::where('phone', '=', $phone)
            ->where('email', '=', $request->json('email'))
            ->get()
            ->toArray();

        if($data){
            $pin = MyHelper::createRandomPIN(6, 'angka');
            $password = bcrypt($pin);
            $update = User::where('id','=',$data[0]['id'])->update(['password' => $password, 'phone_verified' => '0']);

            if(!empty($request->header('user-agent-view'))){
                $useragent = $request->header('user-agent-view');
            }else{
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }

            if(stristr($useragent,'iOS')) $useragent = 'iOS';
            if(stristr($useragent,'okhttp')) $useragent = 'Android';
            if(stristr($useragent,'GuzzleHttp')) $useragent = 'Browser';

            $autocrm = app($this->autocrm)->SendAutoCRM('Pin Forgot', $phone,
                ['pin' => $pin,
                    'useragent' => $useragent,
                    'now' => date('Y-m-d H:i:s')], $useragent);

            if(env('APP_ENV') == 'production'){
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$phone
                    ]
                ];
            }else{
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$phone,
                    'pin'	    =>	MyHelper::encPIN($pin)
                    ]
                ];
            }
            return response()->json($result);

        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Email yang kamu masukkan kurang tepat']
            ];
            return response()->json($result);
        }
    }

    function verifyPin(users_phone_pin $request){

        $phone = $request->json('phone');
        $post = $request->json()->all();

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if($data){
            if($data[0]['email'] == null || $data[0]['name']){
                $update = User::where('id','=',$data[0]['id'])->update(['phone_verified' => '0']);
                $data = User::where('phone', '=', $phone)
                    ->get()
                    ->toArray();
            }
            if($data[0]['phone_verified'] == 0){
                if(Auth::attempt(['phone' => $phone, 'password' => $request->json('pin')])){
                    if(isset($post['device_id']) && isset($post['device_type'])) {
                        $device_id = $post['device_id'];
                        $device_type = $post['device_type'];
                        $fraud = FraudSetting::where('parameter', 'LIKE', '%device ID%')->where('fraud_settings_status', 'Active')->first();
                        if ($fraud) {
                            app($this->setting_fraud)->createUpdateDeviceLogin($data[0], $device_id);

                            $deviceCus = UsersDeviceLogin::where('device_id', '=' ,$device_id)
                                ->where('status','Active')
                                ->select('id_user')
                                ->orderBy('created_at','asc')
                                ->groupBy('id_user')
                                ->get()->toArray('id_user');

                            $count = count($deviceCus);
                            $check = array_slice($deviceCus, (int)$fraud['parameter_detail']);
                            $check = array_column($check,'id_user');

                            if($deviceCus && count($deviceCus) > (int)$fraud['parameter_detail'] && array_search($data[0]['id'], $check) !== false){
                                $sendFraud = app($this->setting_fraud)->checkFraud($fraud, $data[0], ['device_id' => $device_id, 'device_type' => $request->json('device_type')], 0, 0, null, 0);
                                $data = User::with('city')->where('phone', '=', $phone)->get()->toArray();

                                if ($data[0]['is_suspended'] == 1) {
                                    return response()->json([
                                        'status' => 'fail',
                                        'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di suarapelanggan@champ-group.com']
                                    ]);
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'messages' => ['Akun Anda tidak dapat di daftarkan karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di suarapelanggan@champ-group.com']
                                    ]);
                                }
                            }
                        }
                    }

                    $update = User::where('id','=',$data[0]['id'])->update(['phone_verified' => '1']);
                    if($update){
                        $profile = User::select('phone','email','name','id_city','gender','phone_verified', 'email_verified')
                            ->where('phone', '=', $phone)
                            ->get()
                            ->toArray();
                        if(\Module::collections()->has('Autocrm')) {
                            $autocrm = app($this->autocrm)->SendAutoCRM('Pin Verify', $phone);
                        }
                        $result = [
                            'status'	=> 'success',
                            'result'	=> ['phone'	=>	$data[0]['phone'],
                                'profile'=> $profile
                            ]
                        ];
                    }
                } else {
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['OTP yang kamu masukkan salah']
                    ];
                }
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['This phone number is already verified']
                ];
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function changePin(users_phone_pin_new $request){

        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if($data){
            if(Auth::attempt(['phone' => $phone, 'password' => $request->json('pin_old')])){
                $pin 	= bcrypt($request->json('pin_new'));
                $update = User::where('id','=',$data[0]['id'])->update(['password' => $pin, 'phone_verified' => '1', 'pin_changed' => '1']);
                if(\Module::collections()->has('Autocrm')) {
                    if($data[0]['first_pin_change'] < 1){
                        $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                        $changepincount = $data[0]['first_pin_change']+1;
                        $update = User::where('id','=',$data[0]['id'])->update(['first_pin_change' => $changepincount]);
                    }else{
                        $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone);

                        $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                            ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();

                    }
                }
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$data[0]['phone']
                    ]
                ];
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['Current PIN doesn\'t match']
                ];
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function createUserFromAdmin(users_create $request){
        $post = $request->json()->all();
        $d = explode('/',$post['birthday']);
        $post['birthday'] = $d[2]."-".$d[0]."-".$d[1];

        if($post['pin'] == null) {
            $pin = MyHelper::createRandomPIN(6, 'angka');
            $pin = '777777';
        } else {
            $pin = $post['pin'];
        }

        $post['password'] = bcrypt($pin);
        $post['provider'] = MyHelper::cariOperator($post['phone']);

        $sent_pin = $post['sent_pin'];
        if(isset($post['pickup_order'])){
            $pickup_order = $post['pickup_order'];
            unset($post['pickup_order']);
        }
        if(isset($post['enquiry'])){
            $enquiry = $post['enquiry'];
            unset($post['enquiry']);
        }

        if(isset($post['delivery'])){
            $delivery = $post['delivery'];
            unset($post['delivery']);
        }
        unset($post['pin']);
        unset($post['sent_pin']);


        if(isset($post['id_card_image']) && !empty($post['id_card_image'])){
            $idCardImage = $request->json('id_card_image');
            $path = 'img/customer/idcard/';
            $upload = MyHelper::uploadPhoto($idCardImage, $path, null, $post['phone']);

            if ($upload['status'] == "success") {
                $post['id_card_image'] = $upload['path'];
            }
        }

        $result = MyHelper::checkGet(User::create($post));

        if($result['status'] == "success"){
            if($post['level'] == 'Admin Outlet'){
                foreach($post['id_outlet'] as $id_outlet){
                    $dataUserOutlet = [];
                    $dataUserOutlet['id_user'] = $result['result']['id'];
                    $dataUserOutlet['id_outlet'] = $id_outlet;
                    $dataUserOutlet['enquiry'] = $enquiry;
                    $dataUserOutlet['pickup_order'] = $pickup_order;
                    $dataUserOutlet['delivery'] = $delivery;
                    UserOutlet::create($dataUserOutlet);
                }
            }

            if($sent_pin == 'Yes'){
                if(!empty($request->header('user-agent-view'))){
                    $useragent = $request->header('user-agent-view');
                }else{
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if(stristr($useragent,'iOS')) $useragent = 'iOS';
                if(stristr($useragent,'okhttp')) $useragent = 'Android';
                if(stristr($useragent,'GuzzleHttp')) $useragent = 'Browser';

                if(\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Sent', $post['phone'],
                        ['pin' => $pin,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s')
                        ], $useragent);
                }
            }
        }
        return response()->json($result);
    }

    function profileUpdate(users_profile $request){
        $phone = preg_replace("/[^0-9]/", "", $request->json('phone'));

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail'){
            return response()->json([
                'status' => 'fail',
                'messages' => [$checkPhoneFormat['messages']]
            ]);
        }elseif(isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success'){
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if($data){
            // $pin_x = MyHelper::decryptkhususpassword($data[0]['pin_k'], md5($data[0]['id_user'], true));
            if($request->json('email') != ""){
                $checkEmail = User::where('email', '=', $request->json('email'))
                    ->get()
                    ->first();
                if($checkEmail){
                    if($checkEmail['phone'] != $phone){
                        $result = [
                            'status'	=> 'fail',
                            'messages'	=> ['This email has already been registered to another account. Please choose other email.']
                        ];
                        return response()->json($result);
                    }
                }
            }
            if($data[0]['phone_verified'] == 1){
                // if(Auth::attempt(['phone' => $request->json('phone'), 'password' => $request->json('pin')])){
                $dataupdate = [];
                if($request->json('name')){
                    $dataupdate['name'] = $request->json('name');
                }
                if($request->json('email')){
                    $dataupdate['email'] = $request->json('email');
                }
                if($request->json('gender')){
                    $dataupdate['gender'] = $request->json('gender');
                }
                if($request->json('birthday')){
                    $dataupdate['birthday'] = $request->json('birthday');
                }
                if($request->json('id_city')){
                    $dataupdate['id_city'] = $request->json('id_city');
                }
                if($request->json('relationship')){
                    $dataupdate['relationship'] = $request->json('relationship');
                }
                if($request->json('celebrate')){
                    $dataupdate['celebrate'] = $request->json('celebrate');
                }
                if($request->json('job')){
                    $dataupdate['job'] = $request->json('job');
                }
                if($request->json('address')){
                    $dataupdate['address'] = $request->json('address');
                }
                if($request->json('id_card_image') && !empty($request->json('id_card_image'))){
                    if(is_null($data[0]['id_card_image']) || empty($data[0]['id_card_image'])){
                        $idCardImage = $request->json('id_card_image');
                        $path = 'img/customer/idcard/';
                        $upload = MyHelper::uploadPhoto($idCardImage, $path, null, $phone);

                        if ($upload['status'] == "success") {
                            $dataupdate['id_card_image'] = $upload['path'];
                        }
                    }
                }

                DB::beginTransaction();

                $referral = \Modules\PromoCampaign\Lib\PromoCampaignTools::createReferralCode($data[0]['id']);

                if(!$referral){
                    DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages' => ['failed create referral code']
                    ];
                }

                $update = User::where('id','=',$data[0]['id'])->update($dataupdate);

                $datauser = User::where('id','=',$data[0]['id'])->get()->toArray();

                //cek complete profile ?
                if($datauser[0]['complete_profile'] != "1"){
                    if($datauser[0]['name'] != "" && $datauser[0]['email'] != "" && $datauser[0]['gender'] != "" && $datauser[0]['birthday'] != "" && $datauser[0]['id_city'] != "" && $datauser[0]['celebrate'] != "" && $datauser[0]['job'] != "" && $datauser[0]['address'] != ""){
                        //get point

                        $complete_profile_cashback = 0;
                        // $setting_profile_point = Setting::where('key', 'complete_profile_point')->first();
                        $setting_profile_cashback = Setting::where('key', 'complete_profile_cashback')->first();
                        /*if (isset($setting_profile_point->value)) {
                            $complete_profile_point = $setting_profile_point->value;
                        }*/
                        if (isset($setting_profile_cashback->value)) {
                            $complete_profile_cashback = $setting_profile_cashback->value;
                        }

                        /*** uncomment this if point feature is active
                        // add point
                        $setting_point = Setting::where('key', 'point_conversion_value')->first();
                        $log_point = [
                        'id_user'                     => $user->id,
                        'point'                       => $complete_profile_point,
                        'id_reference'                => null,
                        'source'                      => 'Completing User Profile',
                        'grand_total'                 => 0,
                        'point_conversion'            => $setting_point->value,
                        'membership_level'            => $level,
                        'membership_point_percentage' => $point_percentage
                        ];
                        $insert_log_point = LogPoint::create($log_point);

                        // update user point
                        $new_user_point = LogPoint::where('id_user', $user->id)->sum('point');
                        $user_update = $user->update(['points' => $new_user_point]);
                         */

                        /* add cashback */
                        $balance_nominal = $complete_profile_cashback;
                        // add log balance & update user balance
                        $balanceController = new BalanceController();
                        $addLogBalance = $balanceController->addLogBalance($datauser[0]['id'], $balance_nominal, null, "Welcome Point", 0);

                        if ( !$addLogBalance ) {
                            DB::rollback();
                            return [
                                'status' => 'fail',
                                'messages' => 'Failed to save data'
                            ];
                        }
                        if($balance_nominal??false){
                            $send   = app($this->autocrm)->SendAutoCRM('Complete User Profile Point Bonus', $datauser[0]['phone'],
                                [
                                    'received_point' => (string) $balance_nominal
                                ]
                            );
                            if($send != true){
                                DB::rollback();
                                return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Failed Send notification to customer']
                                ]);
                            }
                        }
                        $update = User::where('id','=',$data[0]['id'])->update(['complete_profile' => '1']);

                        $checkMembership = app($this->membership)->calculateMembership($datauser[0]['phone']);
                    }
                }

                $urlIdCard = "";
                if(!empty($datauser[0]['id_card_image']) && !is_null($datauser[0]['id_card_image'])){
                    $urlIdCard = env('S3_URL_API').$datauser[0]['id_card_image'];
                }
                DB::commit();
                $result = [
                    'status'	=> 'success',
                    'result'	=> ['phone'	=>	$data[0]['phone'],
                        'name' => $datauser[0]['name'],
                        'email' => $datauser[0]['email'],
                        'gender' => $datauser[0]['gender'],
                        'birthday' => $datauser[0]['birthday'],
                        'id_city' => $datauser[0]['id_city'],
                        'relationship' => $datauser[0]['relationship'],
                        'celebrate' => $datauser[0]['celebrate'],
                        'job' => $datauser[0]['job'],
                        'address' => $datauser[0]['address'],
                        'id_card_image' => $urlIdCard
                    ],
                    'message'	=> 'Data telah berhasil diubah'
                ];
                // } else {
                // 	$result = [
                //         'status'	=> 'fail',
                //         'messages'	=> ['Current PIN doesn\'t match']
                //     ];
                // }
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['This phone number isn\'t verified']
                ];
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function createAdminOutlet(Request $request){
        $post = $request->json()->all();
        $query = null;
        foreach($post['id_outlet'] as $id_outlet){
            $data = [];
            $data['phone'] = $post['phone'];
            $data['name'] = $post['name'];
            $data['email'] = $post['email'];
            $data['id_outlet'] = $id_outlet;
            $data['enquiry'] = 0;
            $data['pickup_order'] = 0;
            $data['delivery'] = 0;
            $data['payment'] = 0;

            foreach($post['type'] as $type){
                $data[$type] = 1;
            }

            $check = UserOutlet::where('phone',$data['phone'])
                ->where('id_outlet',$data['id_outlet'])
                ->first();
            if($check){
                $query = UserOutlet::where('phone',$data['phone'])
                    ->where('id_outlet',$data['id_outlet'])
                    ->update($data);
            } else {
                $query = UserOutlet::create($data);
            }
        }
        return response()->json(MyHelper::checkCreate($query));
    }

    function deleteAdminOutlet(Request $request){
        $post = $request->json()->all();
        $query = UserOutlet::where('phone',$post['phone'])
            ->where('id_outlet',$post['id_outlet'])
            ->delete();
        return response()->json(MyHelper::checkDelete($query));
    }

    function listAdminOutlet(Request $request){
        $check = UserOutlet::join('outlets','outlets.id_outlet','=','user_outlets.id_outlet')
            ->get()
            ->toArray();
        return response()->json(MyHelper::checkGet($check));
    }

    function detailAdminOutlet(Request $request){
        $post = $request->json()->all();
        $check = UserOutlet::join('outlets','outlets.id_outlet','=','user_outlets.id_outlet')
            ->where('user_outlets.phone','=',$post['phone'])
            ->where('user_outlets.id_outlet','=',$post['id_outlet'])
            ->first();
        return response()->json(MyHelper::checkGet($check));
    }

    function listVar($var){
        if($var == 'phone') $query = User::select('phone')->get()->toArray();
        if($var == 'email') $query = User::select('email')->get()->toArray();
        if($var == 'name') $query = User::select('name')->get()->toArray();

        return response()->json($query);
    }
    function list(Request $request){
        $post = $request->json()->all();
        if(isset($post['order_field'])) $order_field = $post['order_field']; else $order_field = 'id';
        if(isset($post['order_method'])) $order_method = $post['order_method']; else $order_method = 'desc';
        if(isset($post['skip'])) $skip = $post['skip']; else $skip = '0';
        if(isset($post['take'])) $take = $post['take']; else $take = '10';
        if(isset($post['conditions'])) $conditions = $post['conditions']; else $conditions = null;

        $query = $this->UserFilter($conditions, $order_field, $order_method, $skip, $take);

        return response()->json($query);
    }

    function activity(Request $request){
        $post = $request->json()->all();
        $user = $request->user();
        
        if(isset($post['order_field'])) $order_field = $post['order_field']; else $order_field = 'id';
        if(isset($post['order_method'])) $order_method = $post['order_method']; else $order_method = 'desc';
        if(isset($post['skip'])) $skip = $post['skip']; else $skip = '0';
        if(isset($post['take'])) $take = $post['take']; else $take = '10';
        if(isset($post['rule'])) $rule = $post['rule']; else $rule = 'and';
        if(isset($post['conditions'])) $conditions = $post['conditions']; else $conditions = null;
        
        $query = $this->LogActivityFilter($rule, $conditions, $order_field, $order_method, $skip, $take);

        return response()->json($query);
    }

    public function delete(Request $request)
    {
        $post = $request->json()->all();

        if(is_array($post['phone'])){
            $messages = "Users ";
            foreach($post['phone'] as $row){
                $checkUser = User::where('phone','=',$row)->get()->toArray();
                if(!$checkUser) continue;

                if($checkUser[0]['level'] != 'Super Admin' && $checkUser[0]['level'] != 'Admin')
                    $action = User::where('phone','=',$row)->delete();
                else
                    continue;
                if($action){
                    $messages .= $row.", ";
                }
            }
            $messages = substr($messages, 0, -2);
            $messages .= " Has been Deleted";

            $result = ['status'	=> 'success',
                'result'	=> [$messages]
            ];
        } else {
            $checkUser = User::where('phone','=',$post['phone'])->get()->toArray();
            if($checkUser){
                if($checkUser[0]['level'] != 'Super Admin' && $checkUser[0]['level'] != 'Admin'){
                    $deleteUser = User::where('phone','=',$post['phone'])->delete();

                    if($deleteUser){
                        $result = ['status'	=> 'success',
                            'result'	=> ['User '.$post['phone'].' has been deleted']
                        ];
                    } else {
                        $result = [
                            'status'	=> 'fail',
                            'messages'	=> ['User Admin & Super Admin Cannot be deleted']
                        ];
                    }
                } else {
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['User Admin & Super Admin Cannot be deleted']
                    ];
                }
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['User Not Found']
                ];
            }
        }
        return response()->json($result);
    }

    public function deleteLog(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_log_activities_apps'])) {
            $deleteLog = LogActivitiesApps::where('id_log_activities_apps','=',$post['id_log_activities_apps'])->delete();
            if($deleteLog){
                $result = ['status'	=> 'success',
                    'result'	=> ['User Log has been deleted']
                ];
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['User Admin & Super Admin Cannot be deleted']
                ];
            }
        } elseif (isset($post['id_log_activities_be'])) {
            $deleteLog = LogActivitiesBE::where('id_log_activities_be','=',$post['id_log_activities_be'])->delete();
            if($deleteLog){
                $result = ['status'	=> 'success',
                    'result'	=> ['User Log has been deleted']
                ];
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['User Admin & Super Admin Cannot be deleted']
                ];
            }
        }
        return response()->json($result);
    }

    public function phoneVerified(Request $request)
    {
        $post = $request->json()->all();

        if(is_array($post['phone'])){
            $messages = "Users ";
            foreach($post['phone'] as $row){
                $updateUser = User::where('phone','=',$row)->update(['phone_verified' => 1]);
                if($updateUser){
                    $messages .= $row.", ";
                }
            }
            $messages = substr($messages, 0, -2);
            $messages .= " Has been Phone Verified";

            $result = ['status'	=> 'success',
                'result'	=> [$messages]
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This function is for bulk update']
            ];
        }
        return response()->json($result);
    }

    public function phoneUnverified(Request $request)
    {
        $post = $request->json()->all();

        if(is_array($post['phone'])){
            $messages = "Users ";
            foreach($post['phone'] as $row){
                $updateUser = User::where('phone','=',$row)->update(['phone_verified' => 0]);
                if($updateUser){
                    $messages .= $row.", ";
                }
            }
            $messages = substr($messages, 0, -2);
            $messages .= " Has been Phone Unverified";

            $result = ['status'	=> 'success',
                'result'	=> [$messages]
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This function is for bulk update']
            ];
        }
        return response()->json($result);
    }

    public function emailVerified(Request $request)
    {
        $post = $request->json()->all();

        if(is_array($post['phone'])){
            $messages = "Users ";
            foreach($post['phone'] as $row){
                $updateUser = User::where('phone','=',$row)->update(['email_verified' => 1]);
                if($updateUser){
                    $messages .= $row.", ";
                }
            }
            $messages = substr($messages, 0, -2);
            $messages .= " Has been Email Verified";

            $result = ['status'	=> 'success',
                'result'	=> [$messages]
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This function is for bulk update']
            ];
        }
        return response()->json($result);
    }

    public function emailUnverified(Request $request)
    {
        $post = $request->json()->all();

        if(is_array($post['phone'])){
            $messages = "Users ";
            foreach($post['phone'] as $row){
                $updateUser = User::where('phone','=',$row)->update(['email_verified' => 0]);
                if($updateUser){
                    $messages .= $row.", ";
                }
            }
            $messages = substr($messages, 0, -2);
            $messages .= " Has been Email Unverified";

            $result = ['status'	=> 'success',
                'result'	=> [$messages]
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['This function is for bulk update']
            ];
        }
        return response()->json($result);
    }

    public function show(Request $request)
    {
        $post = $request->json()->all();

        $query = User::leftJoin('cities','cities.id_city','=','users.id_city')
            ->leftJoin('provinces','provinces.id_province','=','cities.id_province')
            ->with('history_transactions.outlet_name', 'history_balance.detail_trx', 'user_membership')
            ->where('phone','=',$post['phone'])
            ->get()
            ->first();


        if($query){

            // total perolehan balance
            $query['balance_acquisition'] = LogBalance::where('id_user', $query['id'])
                ->where('balance', '>', 0)
                ->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
                ->sum('balance');

            //on going
            $query['on_going'] = $transaction = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                ->with('outlet_name')
                ->where('transaction_payment_status', 'Completed')
                ->whereDate('transaction_date', date('Y-m-d'))
                ->whereNull('taken_at')
                ->whereNull('reject_at')
                ->where('id_user', $query['id'])
                ->select('transactions.id_transaction', 'transaction_receipt_number','trasaction_type','transaction_grandtotal', 'transaction_payment_status', 'transaction_date', 'receive_at', 'ready_at', 'taken_at', 'reject_at')
                ->orderBy('transaction_date', 'DESC')
                ->get();

            $result = ['status'	=> 'success',
                'result'	=> $query,
                // 	   'trx'     => $countTrx,
                // 		'voucher' => $countVoucher
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['User Not Found']
            ];
        }
        return response()->json($result);
    }

    public function log(Request $request)
    {
        $post = $request->json()->all();
        if(isset($post['take'])){
            $take = $post['take'];
        }else{
            $take = 10;
        }
        if(isset($post['skip'])){
            $skip = $post['skip'];
        }else{
            $skip = 0;
        }

        if(isset($post['rule'])){
            $rule = $post['rule'];
        }else{
            $rule = 'and';
        }


        $queryApps = LogActivitiesApps::where('phone','=',$post['phone'])
            ->orderBy('id_log_activities_apps','desc')
            ->select('id_log_activities_apps', 'response_status', 'ip', 'created_at', 'subject', 'useragent', 'module');
        $queryBE = LogActivitiesBE::where('phone','=',$post['phone'])
            ->orderBy('id_log_activities_be','desc')
            ->select('id_log_activities_be', 'response_status', 'ip', 'created_at', 'subject', 'useragent', 'module');

        if(isset($post['date_start'])){
            $queryApps = $queryApps->where('created_at', '>=', date('Y-m-d H:i:00', strtotime($post['date_start'])));
            $queryBE = $queryBE->where('created_at', '>=', date('Y-m-d H:i:00', strtotime($post['date_start'])));
        }
        if(isset($post['date_end'])){
            $queryApps = $queryApps->where('created_at', '<=', date('Y-m-d H:i:00', strtotime($post['date_end'])));
            $queryBE = $queryBE->where('created_at', '<=', date('Y-m-d H:i:00', strtotime($post['date_end'])));
        }
        if(isset($post['conditions'])){
            $data = ['post' => $post, 'rule' => $rule];
            $queryApps->where(function ($query) use ($data) {
                foreach($data['post']['conditions'] as $condition){
                    if(isset($condition['subject'])){
                        if($condition['operator'] != '=' && $condition['operator'] != 'like'){
                            $condition['parameter'] = $condition['operator'];
                        }

                        if($condition['operator'] == 'like'){
                            if($data['rule'] == 'and'){
                                $query = $query->where($condition['subject'],'LIKE','%'.$condition['parameter'].'%');
                            } else {
                                $query = $query->orWhere($condition['subject'],'LIKE', '%'.$condition['parameter'].'%');
                            }
                        }else{
                            if($data['rule'] == 'and'){
                                $query = $query->where($condition['subject'],'=',$condition['parameter']);
                            } else {
                                $query = $query->orWhere($condition['subject'],'=',$condition['parameter']);
                            }
                        }
                    }
                }
            });

            $queryBE->where(function ($query) use ($data) {
                foreach($data['post']['conditions'] as $condition){
                    if(isset($condition['subject'])){
                        if($condition['operator'] != '=' && $condition['operator'] != 'like'){
                            $condition['parameter'] = $condition['operator'];
                        }

                        if($condition['operator'] == 'like'){
                            if($data['rule'] == 'and'){
                                $query = $query->where($condition['subject'],'LIKE','%'.$condition['parameter'].'%');
                            } else {
                                $query = $query->orWhere($condition['subject'],'LIKE', '%'.$condition['parameter'].'%');
                            }
                        }else{
                            if($data['rule'] == 'and'){
                                $query = $query->where($condition['subject'],'=',$condition['parameter']);
                            } else {
                                $query = $query->orWhere($condition['subject'],'=',$condition['parameter']);
                            }
                        }
                    }
                }
            });
        }

        if(isset($post['pagination'])){
            $queryApps = $queryApps->paginate($post['take']);
            $queryBE = $queryBE->paginate($post['take']);
        }else{
            $queryApps = $queryApps->skip($skip)->take($take)
                ->get()
                ->toArray();
            $queryBE = $queryBE->skip($skip)->take($take)
                ->get()
                ->toArray();
        }

        if($queryApps || $queryBE){
            $result = ['status'	=> 'success',
                'result'	=> [
                    'mobile' => $queryApps,
                    'be' => $queryBE
                ]
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Log Activity Not Found']
            ];
        }
        return response()->json($result);
    }

    public function detailLog($id, $log_type, Request $request){

        if($log_type == 'apps'){
            $log = LogActivitiesApps::where('id_log_activities_apps', MyHelper::decSlug($id))->first();
        }else{
            $log = LogActivitiesBE::where('id_log_activities_be', MyHelper::decSlug($id))->first();
        }
        if($log){
            $log->user      = MyHelper::decrypt2019($log->user);
            $log->request   = MyHelper::decrypt2019($log->request);
            $log->response  = MyHelper::decrypt2019($log->response);
        }
        return response()->json(MyHelper::checkGet($log));
    }


    public function updateProfileByAdmin(Request $request)
    {
        $post = $request->json()->all();

        $user = User::where('phone',$post['phone'])->get()->toArray();

        if(isset($post['update']['phone'])){
            if($post['update']['phone'] != $user[0]['phone']){
                $check = User::where('phone',$post['update']['phone'])->get()->toArray();
                if($check){
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['Update profile failed. Phone Number already exist.']
                    ];
                    return response()->json($result);
                }
            }
        }

        if(isset($post['update']['email'])){
            if($post['update']['email'] != $user[0]['email']){
                $check = User::where('email',$post['update']['email'])->get()->toArray();
                if($check){
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['Update profile failed. Email already exist.']
                    ];
                    return response()->json($result);
                }
            }
        }

        if(isset($post['update']['birthday'])){
            if(stristr($post['update']['birthday'], '/')){
                $explode = explode('/', $post['update']['birthday']);
                $post['update']['birthday'] = $explode[2].'-'.$explode[1].'-'.$explode[0];
            }
        }

        if(isset($post['update']['id_card_image']) && !empty($post['update']['id_card_image'])){
            if(is_null($user[0]['id_card_image']) || empty($user[0]['id_card_image'])){
                $idCardImage = $post['update']['id_card_image'];
                $path = 'img/customer/idcard/';
                $upload = MyHelper::uploadPhoto($idCardImage, $path, null, $user[0]['phone']);

                if ($upload['status'] == "success") {
                    $post['update']['id_card_image'] = $upload['path'];
                }
            }else{
                unset($post['update']['id_card_image']);
            }
        }

        $update = User::where('phone',$post['phone'])->update($post['update']);

        return MyHelper::checkUpdate($update);
    }

    public function updateProfilePhotoByAdmin(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['photo'])) {
            $upload = MyHelper::uploadPhotoStrict($post['photo'], $path = 'img/user/', 500, 500);

            if ($upload['status'] == "success") {
                $updatenya['photo'] = $upload['path'];
                $update = User::where('phone',$post['phone'])->update($updatenya);
                return MyHelper::checkUpdate($update);
            } else{
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['Update profile photo failed.']
                ];
                return response()->json($result);
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update profile photo failed.']
            ];
            return response()->json($result);
        }
    }

    public function updateProfilePasswordByAdmin(users_phone_pin_new_admin $request)
    {
        $post = $request->json()->all();
        if (isset($post['password_new'])) {
            $password = bcrypt($post['password_new']);
            $update = User::where('phone',$post['phone'])->update(['password' => $password]);

            $user = User::where('phone',$post['phone'])->first();

            if($user){

                $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                    ->where('oauth_access_tokens.user_id', $user->id)->where('oauth_access_token_providers.provider', 'users')->delete();
            }

            return MyHelper::checkUpdate($update);
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update profile password failed.']
            ];
            return response()->json($result);
        }
    }

    public function updateDoctorOutletByAdmin(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        if (isset($post['outlet'])) {
            $checkDoctor = User::join('doctors','doctors.id_user','=','users.id')
                ->where('users.phone',$post['phone'])
                ->get()
                ->first();
            if($checkDoctor){
                OutletDoctor::where('id_doctor','=',$checkDoctor['id_doctor'])->delete();
                foreach($post['outlet'] as $outlet){
                    $dataOutletDoctor = [];
                    $dataOutletDoctor['id_outlet'] = $outlet;
                    $dataOutletDoctor['id_doctor'] = $checkDoctor['id_doctor'];

                    $addOutlet = OutletDoctor::insert($dataOutletDoctor);
                }
                return MyHelper::checkUpdate($addOutlet);
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['Update doctor outlet failed.','Doctor not found']
                ];
                return response()->json($result);
            }
        }
    }
    public function updateProfileLevelByAdmin(Request $request)
    {
        $post = $request->json()->all();
        $user = User::where('id', $request->user()->id)->first()->makeVisible('password');

        if(!Hash::check($post['password_level'], $user['password'])){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update level failed. Wrong PIN']
            ];
            return response()->json($result);
        }
        if (isset($post['level'])) {
            $checkUser = User::where('phone',$post['phone'])->get()->first();
            if($checkUser){
                if($post['level'] == 'Super Admin' && $user['level'] != "Super Admin"){
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['Update level failed. Only Super Admin are allowed to grant Super Admin level to another user.']
                    ];
                    return response()->json($result);
                }
                if($user['level'] != 'Super Admin' && $user['level'] != "Admin"){
                    $result = [
                        'status'	=> 'fail',
                        'messages'	=> ['Update level failed. Only Super Admin and Admin are allowed to modify Level']
                    ];
                    return response()->json($result);
                }
                if($post['level'] == 'Admin Outlet'){
                    foreach($post['id_outlet'] as $id_outlet){
                        $checkAdminOutlet = UserOutlet::where('id_user','=',$checkUser['id'])
                            ->where('id_outlet','=',$id_outlet)
                            ->get()
                            ->first();
                        $dataAdminOutlet = [];
                        $dataAdminOutlet['id_user'] = $checkUser['id'];
                        $dataAdminOutlet['id_outlet'] = $id_outlet;
                        $dataAdminOutlet['enquiry'] = $post['enquiry'];
                        $dataAdminOutlet['pickup_order'] = $post['pickup_order'];
                        $dataAdminOutlet['delivery'] = $post['delivery'];

                        if($checkAdminOutlet){
                            $updateAdminOutlet = UserOutlet::where('id_user','=',$checkUser['id'])
                                ->where('id_outlet','=',$id_outlet)
                                ->update($dataAdminOutlet);
                        } else {
                            $updateAdminOutlet = UserOutlet::create($dataAdminOutlet);
                        }
                    }
                }
                $update = User::where('phone',$post['phone'])->update(['level' => $post['level']]);

                return MyHelper::checkUpdate($update);
            } else {
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['Update level failed.','User not found']
                ];
                return response()->json($result);
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update level failed.']
            ];
            return response()->json($result);
        }
    }

    public function updateProfilePermissionByAdmin(Request $request)
    {
        $post = $request->json()->all();
        $user = User::where('id', $request->user()->id)->first()->makeVisible('password');

        if(!Hash::check($post['password_permission'], $user['password'])){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update permission failed. Wrong PIN']
            ];
            return response()->json($result);
        }

        if($user['level'] != 'Super Admin' && $user['level'] != "Admin"){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update permission failed. Only Super Admin and Admin are allowed to modify permission']
            ];
            return response()->json($result);
        }

        $user = User::where('phone',$post['phone'])->first();
        if(!$user){
            return [
                'status'=>'fail',
                'messages'=>'User not found'
            ];
        }
        DB::beginTransaction();
        $create = null;
        if(isset($post['module'])){
            $delete=UserFeature::where('id_user',$user->id)->delete();
            foreach($post['module'] as $id_feature){
                $create = UserFeature::updateOrCreate(['id_user'=>$user->id,'id_feature'=>$id_feature]);
                // $create = DB::insert('insert into user_features (id_user, id_feature) values (?, ?)', [$user->id, $id_feature]);
                if(!$create){
                    DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages'=>['Update user permission failed']
                    ];
                }
            }
        }
        DB::commit();
        $result = ['status'	=> 'success'];
        return response()->json($result);
    }

    public function updateSuspendByAdmin(Request $request)
    {
        $post = $request->json()->all();
        $user = User::where('id', $request->user()->id)->first()->makeVisible('password');

        if(!Hash::check($post['password_suspend'], $user['password'])){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update suspend status failed. Wrong PIN']
            ];
            return response()->json($result);
        }

        if($user['level'] != 'Super Admin' && $user['level'] != "Admin"){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update suspend status failed. Only Super Admin and Admin are allowed to modify permission']
            ];
            return response()->json($result);
        }

        $update = User::where('phone',$post['phone'])->update(['is_suspended' => $post['is_suspended']]);

        $data = User::where('phone',$post['phone'])->get();

        if(isset($data[0]['is_suspended']) && $data[0]['is_suspended'] == '1'){
            $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();

        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updateUserOutletByAdmin(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if(!Auth::check(['phone' => $user['phone'], 'password' => $post['password_outlet_setting']])){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update user outlet failed. Wrong PIN']
            ];
            return response()->json($result);
        }

        if($user['level'] != 'Super Admin' && $user['level'] != "Admin Outlet"){
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Update user outlet failed. Only Admin Outlet and Admin are allowed to modify user outlet']
            ];
            return response()->json($result);
        }

        $user = User::where('phone',$post['phone'])->get()->toArray();

        $update = [];
        $update['delivery'] = '0';
        $update['enquiry'] = '0';
        $update['pickup_order'] = '0';
        $false = UserOutlet::where('id_user',$user[0]['id'])->update($update);


        foreach($post['outlets'] as $outlet){
            $check = UserOutlet::where('id_user',$user[0]['id'])->where('id_outlet',$outlet['id_outlet'])->first();

            if(isset($check)){
                if(isset($outlet['enquiry'])){
                    UserOutlet::where('id_user',$user[0]['id'])->where('id_outlet',$outlet['id_outlet'])->update(['enquiry' => $outlet['enquiry']]);
                }

                if(isset($outlet['delivery'])){
                    UserOutlet::where('id_user',$user[0]['id'])->where('id_outlet',$outlet['id_outlet'])->update(['delivery' => $outlet['delivery']]);
                }

                if(isset($outlet['pickup_order'])){
                    UserOutlet::where('id_user',$user[0]['id'])->where('id_outlet',$outlet['id_outlet'])->update(['pickup_order' => $outlet['pickup_order']]);
                }
            }
        }

        $result = ['status'	=> 'success'];
        return response()->json($result);
    }

    public function outletUser(Request $request){
        $post = $request->json()->all();

        $query = UserOutlet::leftJoin('users','users.id','=','user_outlets.id_user')
            ->leftJoin('outlets','outlets.id_outlet','=','user_outlets.id_outlet')
            ->where('users.phone','=',$post['phone'])
            ->where('users.level','=','Admin Outlet')
            ->orderBy('user_outlets.id_outlet','desc')
            ->get()
            ->toArray();
        if($query){
            $result = ['status'	=> 'success',
                'result'	=> $query
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['User Outlet Is Empty']
            ];
        }
        return response()->json($result);
    }
    public function inboxUser(Request $request)
    {
        $user = $request->user();

        $query = UserInbox::where('id_user','=',$user->id)
            ->orderBy('id_user_inboxes','desc')
            ->get()
            ->toArray();
        if($query){
            $result = ['status'	=> 'success',
                'result'	=> $query
            ];
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['Inbox Is Empty']
            ];
        }
        return response()->json($result);
    }

    public function getUserNotification(Request $request){
        $post = $request->json()->all();
        $user = $request->user();

        $tranPending = Transaction::where('transaction_payment_status', 'Pending')->where('id_user', $user->id)->count();
        $userNotification = UserNotification::where('id_user', $user->id)->first();
        if(empty($userNotification)){
            $notif['inbox'] = app($this->inbox)->listInboxUnread($user->id);
            $notif['voucher'] = 0;
            $notif['history'] = $tranPending;

            $createUserNotif = UserNotification::create(['id_user' => $user->id]);
            $result = [
                'status'	=> 'success',
                'result'	=> $notif
            ];
        }else{
            // update voucher jika sudah diliat
            if(isset($post['type']) && $post['type'] == 'voucher'){
                $updateNotif = UserNotification::where('id_user', $user->id)->update([$post['type']=>0]);
                $userNotification = UserNotification::where('id_user', $user->id)->first();
            }

            $notif['inbox'] = app($this->inbox)->listInboxUnread($user->id);
            $notif['voucher'] = $userNotification['voucher'];
            $notif['history'] = $tranPending;
            $result = [
                'status'	=> 'success',
                'result'	=> $notif
            ];
        }
        return response()->json($result);
    }

    // get user profile
    public function getUserDetail()
    {
        $user = Auth::user();

        if ($user->id_city != null) {
            $user = $user->setAttribute('city_name', $user->city->city_name);
        }

        return response()->json(MyHelper::checkGet($user));
    }

    public function resetCountTransaction(Request $request){
        $user = User::get();

        DB::beginTransaction();


        //reset transaction week
        foreach($user as $dataUser){
            $countTrx = Transaction::whereDate('transaction_date', '>=' ,date('Y-m-d', strtotime(' - 6 days')))->whereDate('transaction_date', '<=' ,date('Y-m-d'))
                ->where('id_user', $dataUser->id)
                ->where('transaction_payment_status', 'Completed')
                ->count();

            $update = User::where('id', $dataUser->id)->update(['count_transaction_week' => $countTrx]);
            // if(!$update){
            // 	DB::rollBack();
            // 	return response()->json([
            // 		'status'   => 'fail',
            // 		'messages' => 'failed update count transaction week.'
            // 	]);
            // }

            $countTrx = Transaction::whereDate('transaction_date', '=' ,date('Y-m-d'))
                ->where('id_user', $dataUser->id)
                ->where('transaction_payment_status', 'Completed')
                ->count();

            $update = User::where('id', $dataUser->id)->update(['count_transaction_day' => $countTrx]);
            // if(!$update){
            // 	DB::rollBack();
            // 	return response()->json([
            // 		'status'   => 'fail',
            // 		'messages' => 'failed update count transaction day.'
            // 	]);
            // }
        }

        DB::commit();
        return response()->json([
            'status'   => 'success'
        ]);

    }

    public function deleteUser($phone, Request $request)
    {
        $data['phone'] = $phone;
        return view('users::delete', $data);
    }

    public function deleteUserAction(Request $request)
    {
        $post = $request->except('_token');
        $checkUser = User::where('phone','=',$post['phone'])->get()->toArray();
        if($checkUser){
            $deleteUser = User::where('phone','=',$post['phone'])->delete();
            if($deleteUser == 1){
                $result = ['status'	=> 'success',
                    'result'	=> ['User '.$post['phone'].' has been deleted']
                ];
            } else{
                $result = [
                    'status'	=> 'fail',
                    'messages'	=> ['User Not Found']
                ];
            }
        } else {
            $result = [
                'status'	=> 'fail',
                'messages'	=> ['User Not Found']
            ];
        }
        return $result;
    }

    function getAllName(){
        $user = User::select('id', 'name', 'phone')->get();

        return response()->json(MyHelper::checkGet($user));
    }

    function getDetailUser(Request $request){
        $post = $request->json()->all();
        if(isset($post['phone'])){
            $user = User::with('city.province', 'user_membership')->where('phone', $post['phone'])->first();
        }else{
            $user = User::with('city.province', 'user_membership')->where('id', $post['id_user'])->first();
        }

        if($user){
            $user['balance_acquisition'] = LogBalance::where('id_user', $user['id'])
                ->where('balance', '>', 0)
                ->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
                ->sum('balance');
        }

        return response()->json(MyHelper::checkGet($user));
    }

    public function favorite(Request $request) {
        $data = Favorite::whereHas('user',function($query) use ($request){
            $query->where('phone',$request->json('phone'));
        })->with([
            'product'=>function($query){
                $query->select('id_product','product_name','product_code');
            },
            'outlet'=>function($query){
                $query->select('id_outlet','outlet_name','outlet_code');
            },
            'modifiers'=>function($query){
                $query->select('text');
            }
        ]);
        if($request->page){
            $data = $data->paginate(10);
        }else{
            $data = $data->get();
        }
        return MyHelper::checkGet($data??[]);
    }

    public function getExtraToken(Request $request) {
        $post = $request->json()->all();
        $user = $request->user();

        if (isset($post['token_header'])) {
            $decUser = MyHelper::decrypt2019($post['token_header']);
            $encUser = MyHelper::encrypt2019($decUser);
            UserExtraToken::updateOrCreate(['id_user' => $user['id']], ['id_user' => $user['id'], 'extra_token' => $encUser]);
            return MyHelper::checkGet($encUser);
        } else {
            $encUser = MyHelper::encrypt2019(['id_user' => $user['id']]);
            return MyHelper::checkGet($encUser);
        }
    }
}
