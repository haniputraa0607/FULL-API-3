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
    public function updateBankAccount(Request $request){
        $post = $request->json()->all();

        if(isset($post['outlets']) && $post['outlets'] == 'all'){
            if(!empty($post['id_user_franchisee'])){
                $update = Outlet::join('user_franchisee_outlet', 'outlets.id_outlet', 'user_franchisee_outlet.id_outlet')
                    ->where('user_franchisee_outlet.id_user_franchisee', $post['id_user_franchisee'])
                    ->update([
                        'id_bank_name' => $post['id_bank_name'],
                        'account_number' => $post['account_number'],
                        'recipient_name' => $post['recipient_name']
                    ]);
            }else{
                $update = Outlet::whereNotNull('created_at')->update([
                    'id_bank_name' => $post['id_bank_name'],
                    'account_number' => $post['account_number'],
                    'recipient_name' => $post['recipient_name']
                ]);
            }
        }elseif(isset($post['outlets'])){
            $update = Outlet::whereIn('id_outlet', $post['id_outlet'])
                ->update([
                    'id_bank_name' => $post['id_bank_name'],
                    'account_number' => $post['account_number'],
                    'recipient_name' => $post['recipient_name']
                ]);
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function getBank(Request $request){
        $post = $request->json()->all();
        $bank = BankName::select('id_bank_name', 'bank_code', 'bank_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($bank));
    }

    public function getMdr(Request $request){
        $post = $request->json()->all();
        $mdr_global = MDR::whereNull('payment_name')->first();
        $mdr = MDR::whereNotNull('payment_name')->get()->toArray();

        $result = [
            'status' => 'success',
            'result' => [
                'mdr' => $mdr,
                'mdr_global' => $mdr_global
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
        $update = MDR::where('id_mdr', $post['id_mdr'])->update(['charged' => $post['charged'], 'mdr' => $post['mdr'], 'percent_type' => $post['percent_type']]);
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
            $update = Setting::where('key', 'global_setting_point_charged')->update(['value' => $post['fee']]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'global_setting_point_charged')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

}
