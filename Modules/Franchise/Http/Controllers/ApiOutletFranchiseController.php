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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Jobs\SendOutletJob;
use App\Lib\MyHelper;

use Session;

class ApiOutletFranchiseController extends Controller
{
   public function detail(Request $request)
   {
   		$post = $request->json()->all();
   		$data = Outlet::where('id_outlet', $request->id_outlet)
		   		->with(['user_outlets','city','today', 'outlet_schedules'])
		   		->first();

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
}
