<?php

namespace Modules\Membership\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Lib\MyHelper;

class ApiMembershipWebview extends Controller
{
    public function webview(Request $request)
    {
        $check = $request->json('check');
		
        if (empty($check)) {
			$user = $request->user();
			$membership = UsersMembership::where('id_user', $user['id'])->orderBy('id_log_membership', 'DESC')->first();
			if($membership && $membership['membership_name'] != null){
				$membershipName = $membership['membership_name'];
				$idMembership = $membership['id_log_membership'];
			}else{
				$membershipName = ""; 
				$idMembership = null;
			}

			$dataEncode = [
				'id_user_membership' => $idMembership,
				'id_user' => $user->id,
			];

			$encode = json_encode($dataEncode);
			$base = base64_encode($encode);

			$send = [
				'status' => 'success',
				'result' => [
					'membership_name'  => $membershipName,
					'url'              => env('VIEW_URL').'/membership/web/view?data='.$base
				],
			];

			return response()->json($send);
				
        }

		$post = $request->json()->all();
		$result = [];
		if($post['id_user_membership']){
			$result['user_membership'] = UsersMembership::with('user')->find($post['id_user_membership']);
		}

		$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
		if(!$settingCashback || !$settingCashback->value){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Cashback conversion not found']
			]);
		}

		$allMembership = Membership::orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->get()->toArray();

		$nextMembershipName = "";
		$nextTrx = 0;
		$nextTrxType = '';
		if(count($allMembership) > 0){
			if(isset($result['user_membership'])){
				foreach($allMembership as $index => $dataMembership){
					if($dataMembership['min_total_count']){
						if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
							if(!$nextMembershipName){
								$nextTrx = $dataMembership['min_total_count'];
								$nextTrxType = 'count';
								$nextMembershipName = $dataMembership['membership_name']; 
							}
						}
					}
					if($dataMembership['min_total_value']){
						if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
							if(!$nextMembershipName){
								$nextTrx = $dataMembership['min_total_value'];
								$nextTrxType = 'value';
								$nextMembershipName = $dataMembership['membership_name']; 
							}
						}
					}
					$allMembership[$index]['membership_image'] = env('APP_API_URL').$allMembership[$index]['membership_image']; 
					$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier']/100 * $settingCashback->value;
				}
			}else{
				$result['user_membership']['user'] = User::find($post['id_user']);
				if(count($allMembership) > 0){
					$nextMembershipName = $allMembership[0]['membership_name'];
					if($allMembership[0]['min_total_count'] != null){
						$nextTrx = $allMembership[0]['min_total_count'];
						$nextTrxType = 'count';
					}
					if($allMembership[0]['min_total_value'] != null){
						$nextTrx = $allMembership[0]['min_total_value'];
						$nextTrxType = 'value';
					}
				}

				foreach($allMembership as $j => $dataMember){
					$allMembership[$j]['membership_image'] = env('APP_API_URL').$allMembership[$j]['membership_image']; 
					$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier']/100 * $settingCashback->value;
				}
			}
		}

		$result['next_membership_name'] = $nextMembershipName;

		if(isset($result['user_membership'])){
			if($nextTrxType == 'count'){
				$result['progress_active'] = $result['user_membership']['user']['count_transaction'] / $nextTrx * 100;
				$result['next_trx']	= $nextTrx - $result['user_membership']['user']['count_transaction'];
			}elseif($nextTrxType == 'value'){
				$result['progress_active'] = $result['user_membership']['user']['subtotal_transaction'] / $nextTrx * 100;
				$result['next_trx']	= $nextTrx - $result['user_membership']['user']['subtotal_transaction'];
			}
		}

		$result['all_membership'] = $allMembership;
		
		//user dengan level tertinggi
		if(!isset($result['progress_active'])){
			$result['progress_active'] = 100;
			$result['next_trx'] = 0;
		}

		return response()->json(MyHelper::checkGet($result));
    }
}
