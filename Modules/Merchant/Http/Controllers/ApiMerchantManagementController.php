<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Recruitment\Entities\UserHairStylist;

class ApiMerchantManagementController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function list(Request $request){
        $post = $request->json()->all();

        $data = Merchant::whereIn('merchant_status', ['Active', 'Inactive'])
            ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->orderBy('merchants.created_at', 'desc');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('merchants.created_at', '>=', $start_date)
                ->whereDate('merchants.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['operator'] == '='){
                            $data->where($row['subject'], $row['parameter']);
                        }else{
                            $data->where($row['subject'], 'like', '%'.$row['parameter'].'%');
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['operator'] == '='){
                                $subquery->orWhere($row['subject'], $row['parameter']);
                            }else{
                                $subquery->orWhere($row['subject'], 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function update(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_merchant'])) {
            $check = Merchant::where('id_merchant', $post['id_merchant'])->first();
            if (empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Detail not found']]);
            }

            $update = Merchant::where('id_merchant', $check['id_merchant'])->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function canditateList(Request $request){
        $post = $request->json()->all();

        $data = Merchant::whereNotIn('merchant_status', ['Active', 'Inactive'])
            ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
            ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->orderBy('merchants.created_at', 'desc');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('merchants.created_at', '>=', $start_date)
                ->whereDate('merchants.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'merchant_completed_step'){
                            $data->where('merchant_completed_step', $row['operator']);
                        }else{
                            if($row['operator'] == '='){
                                $data->where($row['subject'], $row['parameter']);
                            }else{
                                $data->where($row['subject'], 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'merchant_completed_step'){
                                $subquery->orWhere('merchant_completed_step', $row['operator']);
                            }else{
                                if($row['operator'] == '='){
                                    $subquery->orWhere($row['subject'], $row['parameter']);
                                }else{
                                    $subquery->orWhere($row['subject'], 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function canditateUpdate(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_merchant'])){
            $check = Merchant::leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                    ->leftJoin('users', 'users.id', 'merchants.id_user')
                    ->where('id_merchant', $post['id_merchant'])->first();
            if(empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Detail not found']]);
            }
            $type = ($post['action_type'] == 'approve' ? 'Active' : $post['action_type']);
            $update = Merchant::where('id_merchant', $check['id_merchant'])->update(['merchant_status' => ucfirst($type)]);
            if($update){
                $update = Outlet::where('id_outlet', $check['id_outlet'])->update(['outlet_status' => 'Active']);
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    ucfirst($post['action_type']).' Merchant',
                    $check['phone'],
                    [
                        'merchant_name' => $check['outlet_name'],
                        'merchant_phone' => $check['outlet_phone'],
                        "merchant_pic_name" => $check['merchant_pic_name'],
                        "merchant_pic_email" => $check['merchant_pic_email'],
                        "merchant_pic_phone" => $check['merchant_pic_phone']
                    ]
                );
            }

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detail(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_merchant'])){
            $detail = Merchant::leftJoin('users', 'users.id', 'merchants.id_user')
                    ->leftJoin('outlets', 'outlets.id_outlet', 'merchants.id_outlet')
                    ->leftJoin('cities', 'outlets.id_city', 'cities.id_city')
                    ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                    ->where('id_merchant', $post['id_merchant'])->select('merchants.*', 'users.name', 'users.phone', 'users.email', 'provinces.id_province', 'users.phone', 'outlets.*')
                    ->first();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_merchant'])){
            $check = Merchant::where('id_merchant', $post['id_merchant'])->first();
            if($check['merchant_status'] == 'Active' || $check['merchant_status'] == 'Inactive'){
                return response()->json(['status' => 'fail', 'messages' => ['Can not delete active/inactive merchant']]);
            }
            $del = Merchant::where('id_merchant', $post['id_merchant'])->delete();
            return response()->json(MyHelper::checkDelete($del));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
