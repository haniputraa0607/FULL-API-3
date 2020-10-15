<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankAccountOutlet;
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

    public function addBankAccount(Request $request){
        $post = $request->json()->all();

        $dt = [
            'id_bank_name' => $post['id_bank_name'],
            'beneficiary_name' => $post['beneficiary_name'],
            'beneficiary_alias' => $post['beneficiary_alias'],
            'beneficiary_account' => $post['beneficiary_account'],
            'beneficiary_email' => $post['beneficiary_email']
        ];

        if(!empty($dt['beneficiary_email'])){
            $domain = substr($dt['beneficiary_email'], strpos($dt['beneficiary_email'], "@") + 1);
            if(!filter_var($dt['beneficiary_email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false){
                return response()->json(['status' => 'fail', 'message' => 'invalid email address']);
            }
        }

        if(preg_match('/[^A-Za-z0-9 ]/', $dt['beneficiary_name']) > 0){
            return response()->json(['status' => 'fail', 'message' => 'Beneficiary name only allows space, alphanumeric, non-latin letter, non-latin numeric']);
        }

        $bankCode = BankName::where('id_bank_name', $post['id_bank_name'])->first()->bank_code;

        $validationAccount = MyHelper::connectIris('Account Validation' ,'GET','api/v1/account_validation?bank='.$bankCode.'&account='.$post['beneficiary_account'], [], []);

        if(isset($validationAccount['status']) && $validationAccount['status'] == 'success' && isset($validationAccount['response']['account_name'])){
            /*Step
            1.Add to table bank account first
            2.Check outlet type "all" or "specific outlet"
            3.Insert to table bank account outlet base on type outlet
            */
            DB::beginTransaction();

            $bankAccount = BankAccount::where('beneficiary_account', $dt['beneficiary_account'])->first();//check account number is already exist or not
            if(!$bankAccount){
                $bankAccount = BankAccount::create($dt);
            }

            if($bankAccount){
                $delete = true;
                $dtToInsert = [];
                if (isset($post['id_outlet']) && !empty($post['id_outlet'])){
                    $getDataBankOutlet = BankAccountOutlet::whereIn('id_outlet', $post['id_outlet'])->count();
                    if($getDataBankOutlet > 0) {
                        $delete = BankAccountOutlet::whereIn('id_outlet', $post['id_outlet'])->delete();
                    }

                    foreach ($post['id_outlet'] as $val){
                        $dtToInsert[] = [
                            'id_bank_account' => $bankAccount['id_bank_account'],
                            'id_outlet' => $val,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }

                if($delete){
                    $insertBankAccountOutlet = BankAccountOutlet::insert($dtToInsert);
                    if($insertBankAccountOutlet){
                        DB::commit();
                        return response()->json(['status' => 'success']);
                    }else{
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'message' => 'failed insert bank account outlet']);
                    }
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'message' => 'failed delete bank account outlet']);
                }
            }else{
                DB::rollBack();
                return response()->json(['status' => 'fail', 'message' => 'failed insert bank account']);
            }
        }else{
            return response()->json(['status' => 'fail', 'message' => 'validation account failed']);
        }

    }

    function editBankAccount(Request $request){
        $post = $request->json()->all();

        $dt = [
            'id_bank_name' => $post['id_bank_name'],
            'beneficiary_name' => $post['beneficiary_name'],
            'beneficiary_alias' => $post['beneficiary_alias'],
            'beneficiary_account' => $post['beneficiary_account'],
            'beneficiary_email' => $post['beneficiary_email']
        ];

        if(!empty($dt['beneficiary_email'])){
            $domain = substr($dt['beneficiary_email'], strpos($dt['beneficiary_email'], "@") + 1);
            if(!filter_var($dt['beneficiary_email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false){
                return response()->json(['status' => 'fail', 'message' => 'invalid email address']);
            }
        }

        if(preg_match('/[^A-Za-z0-9 ]/', $dt['beneficiary_name']) > 0){
            return response()->json(['status' => 'fail', 'message' => 'Beneficiary name only allows space, alphanumeric, non-latin letter, non-latin numeric']);
        }
        $bankCode = BankName::where('id_bank_name', $post['id_bank_name'])->first()->bank_code;

        $validationAccount = MyHelper::connectIris('Account Validation' ,'GET','api/v1/account_validation?bank='.$bankCode.'&account='.$post['beneficiary_account'], [], []);

        if(isset($validationAccount['status']) && $validationAccount['status'] == 'success' && isset($validationAccount['response']['account_name'])){
            $bankAccount = BankAccount::where('beneficiary_account', $dt['beneficiary_account'])->first();//check account number is already exist or not
            if($bankAccount && $bankAccount['beneficiary_account'] != $post['beneficiary_account_number']){
                return response()->json(['status' => 'fail', 'message' => 'bank account already exist']);
            }else{
                $getOldBankAccount = BankAccount::where('beneficiary_account', $post['beneficiary_account_number'])->first();
                $bankAccount = BankAccount::where('beneficiary_account', $post['beneficiary_account_number'])->update($dt);
                if($bankAccount){

                    if (isset($post['id_outlet'])){
                        $delete = true;
                        $getDataBankOutlet = BankAccountOutlet::whereIn('id_outlet', $post['id_outlet'])->orWhere('id_bank_account',$getOldBankAccount['id_bank_account'])->count();
                        if($getDataBankOutlet > 0){
                            $delete = BankAccountOutlet::whereIn('id_outlet', $post['id_outlet'])->orWhere('id_bank_account',$getOldBankAccount['id_bank_account'])->delete();
                        }

                        foreach ($post['id_outlet'] as $val){
                            $dtToInsert[] = [
                                'id_bank_account' => $getOldBankAccount['id_bank_account'],
                                'id_outlet' => $val
                            ];
                        }
                    }

                    if($delete){
                        $insertBankAccountOutlet = BankAccountOutlet::insert($dtToInsert);
                        if($insertBankAccountOutlet){
                            DB::commit();
                            return response()->json(['status' => 'success']);
                        }else{
                            DB::rollBack();
                            return response()->json(['status' => 'fail', 'message' => 'failed insert bank account outlet']);
                        }
                    }else{
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'message' => 'failed delete bank account outlet']);
                    }
                }else{
                    return response()->json(['status' => 'fail', 'message' => 'failed insert data']);
                }
            }
        }else{
            return response()->json(['status' => 'fail', 'message' => 'validation account failed']);
        }
    }

    function deleteBankAccount(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_bank_account']) && !empty($post['id_bank_account'])){
            $check = BankAccountOutlet::where('id_bank_account', $post['id_bank_account'])->pluck('id_outlet')->toArray();
            if($check){
                $del = BankAccount::where('id_bank_account', $post['id_bank_account'])->delete();
                return response()->json(MyHelper::checkDelete($del));
            }else{
                return response()->json(['status' => 'fail', 'message' => 'Can not delete bank account']);
            }
        }else{
            return response()->json(['status' => 'fail', 'message' => 'Incomplete Data']);
        }
    }

    function importBankAccount(Request $request){
        $post = $request->json()->all();

        if(isset($post['data_import']) && !empty($post['data_import'])){
            $arrFailed = [];
            $arrSuccess = [];
            $listBank = BankName::get()->toArray();
            foreach ($post['data_import'] as $val){
                if(empty($val['beneficiary_account'])){
                    continue;
                }
                $val = (array)$val;
                $searchBankCode = array_search($val['bank_code'], array_column($listBank, 'bank_code'));
                $val['beneficiary_account'] = preg_replace("/[^0-9]/", "",$val['beneficiary_account']);

                if($searchBankCode !== false){
                    if(!empty($val['beneficiary_email'])){
                        $domain = substr($val['beneficiary_email'], strpos($val['beneficiary_email'], "@") + 1);
                        if(!filter_var($val['beneficiary_email'], FILTER_VALIDATE_EMAIL) ||
                            checkdnsrr($domain, 'MX') === false){
                            $arrFailed[] = $val['outlet_code'].' : '.'Please use invalid email';
                            continue;
                        }
                    }

                    if(preg_match('/[^A-Za-z0-9 ]/', $val['beneficiary_name']) > 0){
                        $arrFailed[] = $val['outlet_code'].' : '.'Beneficiary name can not use latin numeric and latin letter';
                        continue;
                    }

                    $dt = [
                        'id_bank_name' => $listBank[$searchBankCode]['id_bank_name'],
                        'beneficiary_name' => $val['beneficiary_name'],
                        'beneficiary_alias' => $val['beneficiary_alias'],
                        'beneficiary_account' => $val['beneficiary_account'],
                        'beneficiary_email' => $val['beneficiary_email']
                    ];

                    $check = BankAccount::where('beneficiary_account', $val['beneficiary_account'])->first();//check account number is already exist or not
                    $outlet = Outlet::where('outlet_code', $val['outlet_code'])->first();//get Outlet
                    if($check){
                        $updateBank = BankAccount::where('id_bank_account', $check['id_bank_account'])->update(
                            [
                                'beneficiary_name' => $val['beneficiary_name'],
                                'beneficiary_alias' => $val['beneficiary_alias'],
                                'beneficiary_email' => $val['beneficiary_email']
                            ]
                        );
                        if($outlet){
                            $delete = BankAccountOutlet::where('id_outlet', $outlet['id_outlet'])->delete();
                            $dtInsertToBankOutlet = [
                                'id_outlet' => $outlet['id_outlet'],
                                'id_bank_account' => $check['id_bank_account']
                            ];
                            $update = BankAccountOutlet::updateOrCreate($dtInsertToBankOutlet, $dtInsertToBankOutlet);
                        }
                    }else{
                        $addBankAccount = BankAccount::create($dt);
                        if($outlet){
                            $delete = BankAccountOutlet::where('id_outlet', $outlet['id_outlet'])->delete();
                            $dtInsertToBankOutlet = [
                                'id_outlet' => $outlet['id_outlet'],
                                'id_bank_account' => $addBankAccount['id_bank_account']
                            ];
                            $update = BankAccountOutlet::updateOrCreate($dtInsertToBankOutlet, $dtInsertToBankOutlet);
                        }
                    }

                    if(!$update){
                        $arrFailed[] = $val['outlet_code'].' : Failed submit data';
                    }else{
                        $arrSuccess[] = $val['outlet_code'].'-'.$val['outlet_name'];
                    }
                }else{
                    $arrFailed[] = $val['outlet_code'].' : Please use existing bank code';
                }
            }

            return response()->json(['status' => 'success', 'data_failed' => $arrFailed, 'data_success' => $arrSuccess]);
        }else{
            return response()->json(['status' => 'fail', 'message' => 'Empty data']);
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
                                        'percent_type' => $post['percent_type']]);
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

    function settingTimeToSent(Request $request){
        $post = $request->json()->all();
        if($post){
            $update = Setting::where('key', 'disburse_global_setting_time_to_sent')->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'disburse_global_setting_time_to_sent')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

    function settingFeeDisburse(Request $request){
        $post = $request->json()->all();
        if($post){
            $update = Setting::where('key', 'disburse_setting_fee_transfer')->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'disburse_setting_fee_transfer')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

    function settingSendEmailTo(Request $request){
        $post = $request->json()->all();
        if($post){
            $update = Setting::where('key', 'disburse_setting_email_send_to')->update($post);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            $setting = Setting::where('key', 'disburse_setting_email_send_to')->first();
            return response()->json(MyHelper::checkGet($setting));
        }
    }

    function listBankAccount(Request $request){
        $post = $request->json()->all();

        $outlet = BankAccount::leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
            ->select('bank_accounts.id_bank_account', 'bank_accounts.id_bank_name', 'bank_accounts.beneficiary_name', 'bank_accounts.beneficiary_alias', 'bank_accounts.beneficiary_account', 'bank_accounts.beneficiary_email',
                'bank_name.bank_name', 'bank_name.bank_code')->with(['bank_account_outlet']);

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $check = array_search('outlet_dont_have_account', array_column($post['conditions'], 'subject'));
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $outlet->whereIn('bank_accounts.id_bank_account', function ($query) use ($row){
                                    $query->select('bank_account_outlets.id_bank_account')
                                        ->from('bank_account_outlets')
                                        ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_code',$row['parameter']);
                                });
                            }else{
                                $outlet->whereIn('bank_accounts.id_bank_account', function ($query) use ($row){
                                    $query->select('bank_account_outlets.id_bank_account')
                                        ->from('bank_account_outlets')
                                        ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $outlet->whereIn('bank_accounts.id_bank_account', function ($query) use ($row){
                                    $query->select('bank_account_outlets.id_bank_account')
                                        ->from('bank_account_outlets')
                                        ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_name',$row['parameter']);
                                });
                            }else{
                                $outlet->whereIn('bank_accounts.id_bank_account', function ($query) use ($row){
                                    $query->select('bank_account_outlets.id_bank_account')
                                        ->from('bank_account_outlets')
                                        ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }

                        if($row['subject'] == 'beneficiary_name'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_name', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_alias'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_alias', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_account'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_account', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_email'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_email', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $outlet->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('bank_accounts.id_bank_account', function ($q) use ($row){
                                        $q->select('bank_account_outlets.id_bank_account')
                                            ->from('bank_account_outlets')
                                            ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_code',$row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('bank_accounts.id_bank_account', function ($q) use ($row){
                                        $q->select('bank_account_outlets.id_bank_account')
                                            ->from('bank_account_outlets')
                                            ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_code','like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('bank_accounts.id_bank_account', function ($q) use ($row){
                                        $q->select('bank_account_outlets.id_bank_account')
                                            ->from('bank_account_outlets')
                                            ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_name',$row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('bank_accounts.id_bank_account', function ($q) use ($row){
                                        $q->select('bank_account_outlets.id_bank_account')
                                            ->from('bank_account_outlets')
                                            ->join('outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_name','like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }

                            if($row['subject'] == 'beneficiary_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_alias'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_alias', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_account'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_account', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        if(isset($post['page'])){
            $outlet = $outlet->paginate(25);
        }else{
            $outlet = $outlet->get()->toArray();
        }

        $getOutletDontHaveAccount = [];
        if(isset($check) && $check !== false){
            $getOutletDontHaveAccount = Outlet::leftJoin('bank_account_outlets as bao', 'bao.id_outlet', 'outlets.id_outlet')
                                        ->whereNull('id_bank_account_outlet')
                                        ->select('outlets.outlet_code', 'outlets.outlet_name')->get()->toArray();
        }

        $datas = [
            'list_bank' =>$outlet,
            'list_outlet_dont_have_account' => $getOutletDontHaveAccount
        ];

        return response()->json(MyHelper::checkGet($datas));
    }
}
