<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\MDR;
use DB;

class ApiDisburseSettingController extends Controller
{
    public function bankNameList(Request $request){
        $post = $request->json()->all();
        $bank = BankName::select('id_bank_name', 'bank_code', 'bank_name')->paginate(25);
        return response()->json(MyHelper::checkGet($bank));
    }

    public function bankNameCreate(Request $request){
        $post = $request->json()->all();
        $bank = BankName::insert($post);
        return response()->json(MyHelper::checkCreate($bank));
    }

    public function bankNameEdit(Request $request, $id){
        $post = $request->json()->all();
        if(!empty($post)){
            $update = BankName::where('id_bank_name', $id)->update($post);
            return response()->json(MyHelper::checkCreate($update));
        }else{
            $get = BankName::where('id_bank_name', $id)->first();
            return response()->json(MyHelper::checkGet($get));
        }
    }

    public function getBank(Request $request){
        $post = $request->json()->all();
        $bank = BankName::select('id_bank_name', 'bank_code', 'bank_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($bank));
    }

    public function updateBankAccount(Request $request){
        $post = $request->json()->all();

        $dt = [
            'id_bank_name' => $post['id_bank_name'],
            'beneficiary_name' => $post['beneficiary_name'],
            'beneficiary_alias' => $post['beneficiary_alias'],
            'beneficiary_account' => $post['beneficiary_account'],
            'beneficiary_email' => $post['beneficiary_email']
        ];
        $bankCode = BankName::where('id_bank_name', $post['id_bank_name'])->first()->bank_code;

        $validationAccount = MyHelper::connectIris('Account Validation' ,'GET','api/v1/account_validation?bank='.$bankCode.'&account='.$post['beneficiary_account'], [], []);

        if(isset($validationAccount['status']) && $validationAccount['status'] == 'success' && isset($validationAccount['response']['account_name'])){
            if(isset($post['outlet_code'])){
                $update = Outlet::where('outlets.outlet_code', $post['outlet_code'])->update($dt);
            }elseif(isset($post['outlets']) && $post['outlets'] == 'all'){
                if(!empty($post['id_user_franchise'])){
                    $update = Outlet::join('user_franchise_outlet', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                        ->whereNull('outlets.beneficiary_account')
                        ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise'])
                        ->update($dt);
                }else{
                    $update = Outlet::whereNull('outlets.beneficiary_account')
                        ->update($dt);
                }
            }elseif(isset($post['outlets'])){
                $update = Outlet::whereIn('id_outlet', $post['id_outlet'])
                    ->update($dt);
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'message' => 'validation account failed']);
        }

    }

    public function getMdr(Request $request){
        $post = $request->json()->all();
        $mdr = MDR::whereNotNull('payment_name')->get()->toArray();

        $result = [
            'status' => 'success',
            'result' => [
                'mdr' => $mdr
            ]
        ];
        return response()->json($result);
    }

    function updateMdrGlobal(Request $request){
        $post = $request->json()->all();
        $update = MDR::where('id_mdr', $post['id_mdr'])->update(['charged' => $post['charged']]);
        return response()->json(MyHelper::checkUpdate($update));
    }

    function updateMdr(Request $request){
        $post = $request->json()->all();
        $update = MDR::where('id_mdr', $post['id_mdr'])->update([
                                        'mdr' => $post['mdr'],
                                        'mdr_central' => $post['mdr_central'],
                                        'percent_type' => $post['percent_type'],
                                        'days_to_sent' =>  $post['days_to_sent']??NULL]);
        return response()->json(MyHelper::checkUpdate($update));
    }

    function globalSettingPointCharged(Request $request){
        $post = $request->json()->all();

        if($post){
            $check = (int)$post['outlet'] + (int)$post['central'];
            if($check !== 100){
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Value not valid'
                ]);
            }

            $data = [
                'outlet' => $post['outlet'],
                'central' => $post['central']
            ];
            $data = json_encode($data);
            $update = Setting::where('key', 'global_setting_point_charged')->update(['value_text' => $data]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'global_setting_point_charged')->first();
            if($setting){
                $setting = json_decode($setting['value_text']);
            }
            return response()->json(MyHelper::checkGet($setting));
        }
    }

    function globalSettingFee(Request $request){
        $post = $request->json()->all();

        if($post){
            $data = [
                'fee_outlet' => $post['fee_outlet'],
                'fee_central' => $post['fee_central']
            ];
            $data = json_encode($data);
            $update = Setting::where('key', 'global_setting_fee')->update(['value_text' => $data]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'global_setting_fee')->first();
            if($setting){
                $setting = json_decode($setting['value_text']);
            }
            return response()->json(MyHelper::checkGet($setting));
        }
    }

    function getOutlets(Request $request){
        $post = $request->json()->all();

        if(isset($post['start'])){
            $start = $post['start'];
            $length = $post['length'];
        }


        $outlet = Outlet::where('outlet_special_status', 1)
            ->select('id_outlet as 0', DB::raw('CONCAT(outlet_code," - ", outlet_name) as "1"'), 'status_franchise as 2',
            DB::raw('CONCAT(outlet_special_fee," %") as "3"'));

        if(isset($post["search"]["value"]) && !empty($post["search"]["value"])){
            $key = $post["search"]["value"];
            $outlet->where(function ($q) use ($key){
                $q->orWhere('outlets.outlet_code', 'like', '%'.$key.'%');
                $q->orWhere('outlets.outlet_name', 'like', '%'.$key.'%');
                $q->orWhere('outlets.outlet_special_fee', 'like', '%'.$key.'%');

                if(strpos(strtolower($key), 'not') !== false){
                    $q->orWhere('outlets.status_franchise', 0);
                    $q->orWhereNull('outlets.status_franchise');
                }elseif(strpos(strtolower($key), 'franchise') !== false){
                    $q->orWhere('outlets.status_franchise', 1);
                }
            });
        }
        $total = $outlet->count();
        $data = $outlet->skip($start)->take($length)->get()->toArray();

        $result = [
            'status' => 'success',
            'result' => $data,
            'total' => $total
        ];

        return response()->json($result);
    }

    function settingFeeOutletSpecial(Request $request){
        $post = $request->json()->all();

        if(isset($post['outlet_special_fee']) && !empty($post['outlet_special_fee'])
            && isset($post['id_outlet'])){
            if($post['id_outlet'] == 'all'){
                $update = Outlet::where('outlet_special_status', 1)
                    ->update(['outlet_special_fee' => $post['outlet_special_fee']]);
            }else{
                $update = Outlet::whereIn('id_outlet', $post['id_outlet'])
                    ->update(['outlet_special_fee' => $post['outlet_special_fee']]);
            }

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json([
                'status' => 'fail',
                'messages' => 'Incompleted input'
            ]);
        }
    }

    function settingOutletSpecial(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_outlet'])){
            $update = Outlet::whereIn('id_outlet', $post['id_outlet'])->update(['outlet_special_status' => $post['status']]);

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'Incompleted input'
            ]);
        }
    }

    function settingApproverPayouts(Request $request){
        $post = $request->json()->all();
        if($post){
            $update = Setting::where('key', 'disburse_auto_approve_setting')->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'disburse_auto_approve_setting')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

}
