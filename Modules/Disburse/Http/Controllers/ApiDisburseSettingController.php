<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\MDR;

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
        if(isset($post['outlets']) && $post['outlets'] == 'all'){
            if(!empty($post['id_user_franchise'])){
                $update = Outlet::join('user_franchise_outlet', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                    ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise'])
                    ->update($dt);
            }
        }elseif(isset($post['outlets'])){
            $update = Outlet::whereIn('id_outlet', $post['id_outlet'])
                ->update($dt);
        }
        return response()->json(MyHelper::checkUpdate($update));
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
        $update = MDR::where('id_mdr', $post['id_mdr'])->update(['mdr' => $post['mdr'], 'mdr_central' => $post['mdr_central'],'percent_type' => $post['percent_type']]);
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
            $update = Setting::where('key', 'global_setting_fee')->update(['value' => $post['fee']]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'global_setting_fee')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

}
