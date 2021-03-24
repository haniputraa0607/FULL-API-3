<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Outlet\Entities\OutletScheduleUpdate;
use Modules\Disburse\Entities\BankAccountOutlet;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankName;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Jobs\SendOutletJob;
use App\Lib\MyHelper;

use Session;
use DB;

class ApiOutletFranchiseController extends Controller
{

	function __construct() {
		date_default_timezone_set('Asia/Jakarta');

		$this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
	}

	public function detail(Request $request)
	{
		$post = $request->json()->all();
		$data = Outlet::where('id_outlet', $request->id_outlet)
			->with(['user_outlets','city','today', 'outlet_schedules'])
			->first();

		if ($data) {
			$data['bank_account'] = BankAccountOutlet::join('bank_accounts', 'bank_account_outlets.id_bank_account','=','bank_accounts.id_bank_account')->where('id_outlet', $data['id_outlet'])->first();
			foreach ($data['outlet_schedules'] as $key => &$value) {
				$value['open'] 	= app($this->outlet)->getOneTimezone($value['open'], $data['time_zone_utc']);
				$value['close'] = app($this->outlet)->getOneTimezone($value['close'], $data['time_zone_utc']);
			}
		}
		$result = MyHelper::checkGet($data);
		return $result;
	}

	public function update(Request $request)
	{
		$post = $request->json()->all();
		$outlet = Outlet::where('id_outlet', $request->id_outlet)->first();

		if (!$outlet) {
			$result = [
				'status' => 'fail', 
				'messages' => ['Outlet not found']
			];
			return $result;
		}

		$data = [
			'outlet_phone' => $post['outlet_phone']
		];

   		// update pin
		if ($request->update_pin_type == 'input' || $request->update_pin_type == 'random') {
			$pin = null;
			if ($request->update_pin_type == 'input') {
				if ($request->outlet_pin && $request->outlet_pin_confirm && $request->outlet_pin == $request->outlet_pin_confirm) {
					$pin = $request->outlet_pin;
				}else{
					$result = [
						'status' => 'fail', 
						'messages' => ['Pin doesn\'t match']
					];
					return $result;       	
				}
			}elseif ($request->update_pin_type == 'random') {
				$pin = MyHelper::createRandomPIN(6, 'angka');
			}

			if ($pin) {
				$pin_encrypt = \Hash::make($pin);
				$data['outlet_pin'] = $pin_encrypt;
			}
		}

		try {
			$update = Outlet::where('id_outlet', $request->id_outlet)->update($data);
		} catch (\Exception $e) {
			\Log::error($e);
			return ['status' => 'fail','messages' => ['failed to update data']];
		}

		if (!empty($pin)) {
			$data_pin[] = ['id_outlet' => $outlet->id_outlet, 'data' => $pin];

            // sent pin to outlet
			if (isset($outlet['outlet_email'])) {
				$variable = $outlet->toArray();
				$queue_data[] = [
					'pin' 			=> $pin,
					'date_sent' 	=> date('Y-m-d H:i:s'),
					'outlet_name' 	=> $outlet['outlet_name'],
					'outlet_code' 	=> $outlet['outlet_code'],
				]+$variable;
			}
			MyHelper::updateOutletFile($data_pin);

			if (isset($queue_data)) {
				SendOutletJob::dispatch($queue_data)->allOnConnection('outletqueue');
			}
		}

		$result = MyHelper::checkUpdate($data);

		return $result;
	}

	public function updateSchedule(Request $request)
	{
		$post = $request->json()->all();
		DB::beginTransaction();
		$date_time = date('Y-m-d H:i:s');
		$outlet = Outlet::where('id_outlet', $request->id_outlet)->first();

		if (!$outlet) {
			$result = [
				'status' => 'fail', 
				'messages' => ['Outlet not found']
			];
			return $result;
		}

		foreach ($request->data ?? [] as $key => $value) {
			$value['open'] = app($this->outlet)->setOneTimezone($value['open'], $outlet->time_zone_utc);
			$value['close'] = app($this->outlet)->setOneTimezone($value['close'], $outlet->time_zone_utc);
			$is_closed = isset($value['is_closed']) ? 1 : 0;
			$data = [
				'day'       => $value['day'],
				'open'      => $value['open'],
				'close'     => $value['close'],
				'is_closed' => $is_closed,
				'id_outlet' => $request->id_outlet
			];
			$old = OutletSchedule::select('id_outlet_schedule','id_outlet','day','open','close','is_closed')->where(['id_outlet' => $request->id_outlet, 'day' => $value['day']])->first();
			$old_data = $old?$old->toArray():[];
			if($old){
				$save 	= $old->update($data);
				$new 	= $old;
				if (!$save) {
					DB::rollBack();
					return response()->json(['status' => 'fail']);
				}
			}else{
				$new = OutletSchedule::create($data);
				if (!$new) {
					DB::rollBack();
					return response()->json(['status' => 'fail']);
				}
			}

			$new_data = $new->toArray();
			unset($new_data['created_at']);
			unset($new_data['updated_at']);
			if(array_diff($new_data,$old_data)){
				$create = OutletScheduleUpdate::create([
					'id_outlet' => $request->id_outlet,
					'id_outlet_schedule' => $new_data['id_outlet_schedule'],
					'id_user' => $request->user()->id_user_franchise,
					'id_outlet_app_otp' => null,
					'user_type' => 'user_franchises',
					'date_time' => $date_time,
					'old_data' => $old_data?json_encode($old_data):null,
					'new_data' => json_encode($new_data)
				]);
			}
		}

		DB::commit();
		return response()->json(['status' => 'success']);
	}

	public function updateBankAccount(Request $request)
	{
		// return $request->all();
		$check_account = BankAccount::where('beneficiary_account', $request->beneficiary_account)->first();

		if ($check_account) {
			$account_outlet = BankAccountOutlet::join('outlets', 'outlets.id_outlet','=','bank_account_outlets.id_outlet')
								->where('id_bank_account','=',$check_account->id_bank_account)
								->get();

			if ($account_outlet) {
				$outlet_name = $account_outlet->pluck('outlet_name');
				$outlet_name_arr = $outlet_name->toArray();
				$outlet_name_arr = array_map(function($value) { return '"'.$value.'"'; }, $outlet_name_arr);
				$outlet_name_string = implode(', ', $outlet_name_arr);
			}

			if ( $check_account->id_bank_name != $request->id_bank_name
				|| $check_account->beneficiary_name != $request->beneficiary_name
				|| $check_account->beneficiary_alias != $request->beneficiary_alias
				|| $check_account->beneficiary_account != $request->beneficiary_account
				|| $check_account->beneficiary_email != $request->beneficiary_email
			) {
				$msg = [
					'status' => 'fail',
					'messages' => [
						'Akun sudah pernah dibuat, data yang dimasukkan tidak sesuai'
					]
				];

				if ($outlet_name_string) {
					$msg['messages'][] = 'Akun telah digunakan untuk outlet '.$outlet_name_string;
				}

				return $msg;
			}

			$update = BankAccountOutlet::where('id_outlet', $request->id_outlet)->update(['id_bank_account' => $check_account->id_bank_account]);

			if ($update) {
				$result = MyHelper::checkUpdate($update);
				return $result;
			}
		}
		else{

			if(!empty($request->beneficiary_email)){
	            $domain = substr($request->beneficiary_email, strpos($request->beneficiary_email, "@") + 1);
	            if(!filter_var($request->beneficiary_email, FILTER_VALIDATE_EMAIL) ||
	                checkdnsrr($domain, 'MX') === false){
	                return response()->json(['status' => 'fail', 'messages' => 'Format email tidak valid']);
	            }
	        }

	        if(preg_match('/[^A-Za-z0-9 ]/', $request->beneficiary_name) > 0){
	            return response()->json(['status' => 'fail', 'messages' => 'Beneficiary name hanya boleh berisi spasi, alfanumerik, huruf non latin, angka non latin']);
	        }

	        $bankCode = BankName::where('id_bank_name', $request->id_bank_name)->first()->bank_code;

	        $validationAccount = MyHelper::connectIris('Account Validation' ,'GET','api/v1/account_validation?bank='.$bankCode.'&account='.$request->beneficiary_account, [], []);

	        if(isset($validationAccount['status']) && $validationAccount['status'] == 'success' && isset($validationAccount['response']['account_name'])){
	        	DB::beginTransaction();

	        	$dt = [
		            'id_bank_name' => $request->id_bank_name,
		            'beneficiary_name' => $request->beneficiary_name,
		            'beneficiary_alias' => $request->beneficiary_alias,
		            'beneficiary_account' => $request->beneficiary_account,
		            'beneficiary_email' => $request->beneficiary_email
		        ];

	            $bankAccount = BankAccount::create($dt);

	            if($bankAccount){
	                $delete = true;
	                $dtToInsert = [];
	                if ($request->id_outlet){
	                    $getDataBankOutlet = BankAccountOutlet::where('id_outlet', $request->id_outlet)->count();
	                    if($getDataBankOutlet > 0) {
	                        $delete = BankAccountOutlet::where('id_outlet', $request->id_outlet)->delete();
	                    }

                        $dtToInsert[] = [
                            'id_bank_account' => $bankAccount['id_bank_account'],
                            'id_outlet' => $request->id_outlet,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
	                }

	                if($delete){
	                    $insertBankAccountOutlet = BankAccountOutlet::insert($dtToInsert);
	                    if($insertBankAccountOutlet){
	                        DB::commit();
	                        return response()->json(['status' => 'success']);
	                    }else{
	                        DB::rollBack();
	                        return response()->json(['status' => 'fail', 'messages' => 'Gagal menyimpan akun bank outlet']);
	                    }
	                }else{
	                    DB::rollBack();
	                    return response()->json(['status' => 'fail', 'messages' => 'Gagal menghapus akun bank outlet']);
	                }
	            }else{
	                DB::rollBack();
	                return response()->json(['status' => 'fail', 'messages' => 'Gagal menyimpan akun bank']);
	            }
	        }
	        else{
	            return response()->json(['status' => 'fail', 'messages' => 'Validasi akun gagal']);
	        }
		}
	}
}