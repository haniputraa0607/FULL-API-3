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
use App\Http\Models\LogBalance;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementGroup;

class ApiMembershipWebview extends Controller
{
	public function detail(Request $request)
    {
		$post = [
			'id_user' => $request->user()->id
		];
		$result = [];
		$result['user_membership'] = UsersMembership::with('user', 'membership')->where('id_user', $post['id_user'])->orderBy('id_log_membership', 'desc')->first();
		$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
		if(!$settingCashback || !$settingCashback->value){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Cashback conversion not found']
			]);
		}
		switch ($result['user_membership']->membership_type) {
			case 'balance':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_balance;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_balance;
				break;
			case 'count':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_count;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_count;
				break;
			case 'value':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_value;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_value;
				break;
			case 'achievement':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_achievement;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_achievement;
				
				break;
		}

		$getUserAch = AchievementGroup::select(
			'achievement_groups.id_achievement_group',
			'achievement_groups.name as name_group',
			'achievement_groups.logo_badge_default',
			'achievement_groups.description',
			'achievement_details.name as name_badge',
			'achievement_details.logo_badge'
		)->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
		->join('achievement_users', 'achievement_details.id_achievement_detail', 'achievement_users.id_achievement_detail')
		->where('id_user', $post['id_user'])->orderBy('achievement_details.id_achievement_detail', 'DESC')->get()->toArray();
		$result['user_badge'] = [];
		foreach ($getUserAch as $userAch) {
			$search = array_search(MyHelper::decSlug($userAch['id_achievement_group']), array_column($result['user_badge'], 'id_achievement_group'));
			if ($search === false) {
				$result['user_badge'][] = [
					'id_achievement_group'		=> MyHelper::decSlug($userAch['id_achievement_group']),
					'name_group'				=> $userAch['name_group'],
					'logo_badge_default'		=> env('S3_URL_API').$userAch['logo_badge_default'],
					'description'				=> $userAch['description'],
					'name_badge'				=> $userAch['name_badge'],
					'logo_badge'				=> env('S3_URL_API').$userAch['logo_badge']
				];
			}
		}
		// $result['user_membership']['membership_bg_image'] = env('S3_URL_API') . $result['user_membership']->membership->membership_bg_image;
		// $result['user_membership']['membership_background_card_color'] = $result['user_membership']->membership->membership_background_card_color;
		// $result['user_membership']['membership_background_card_pattern'] = (is_null($result['user_membership']->membership->membership_background_card_pattern)) ? null : env('S3_URL_API') . $result['user_membership']->membership->membership_background_card_pattern;
		// $result['user_membership']['membership_text_color'] = $result['user_membership']->membership->membership_text_color;

		unset($result['user_membership']['membership']);
		unset($result['user_membership']['min_total_count']);
		unset($result['user_membership']['min_total_value']);
		unset($result['user_membership']['min_total_balance']);
		unset($result['user_membership']['min_total_achievement']);
		unset($result['user_membership']['retain_min_total_value']);
		unset($result['user_membership']['retain_min_total_count']);
		unset($result['user_membership']['retain_min_total_balance']);
		unset($result['user_membership']['retain_min_total_achievement']);
		unset($result['user_membership']['created_at']);
		unset($result['user_membership']['updated_at']);

		$membershipUser['name'] = $result['user_membership']->user->name;
		$allMembership = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->orderBy('min_total_balance', 'asc')->orderBy('min_total_achievement', 'asc')->get()->toArray();
		$nextMembershipName = "";
		// $nextMembershipImage = "";
		$nextTrx = 0;
		$nextTrxType = '';
		if(count($allMembership) > 0){
			if($result['user_membership']){
				$result['user_membership']['membership_image'] = env('S3_URL_API') . $result['user_membership']['membership_image'];
				foreach($allMembership as $index => $dataMembership){
					$allMembership[$index]['benefit_text']=json_decode($dataMembership['benefit_text'],true)[0]??[];
					switch ($dataMembership['membership_type']) {
						case 'count':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_count'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_count'];
							if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_count'];
									$nextTrxType = 'count';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  env('S3_URL_API') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'value':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_value'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_value'];
							if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_value'];
									$nextTrxType = 'value';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  env('S3_URL_API') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'balance':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_balance'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_balance'];
							if($dataMembership['min_total_balance'] > $result['user_membership']['min_total_balance']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_balance'];
									$nextTrxType = 'balance';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  env('S3_URL_API') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'achievement':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_achievement'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_achievement'];
							if($dataMembership['min_total_achievement'] > $result['user_membership']['min_total_achievement']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_achievement'];
									$nextTrxType = 'achievement';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  env('S3_URL_API') . $dataMembership['membership_image'];
								}
							}
							break;
					}
					
					if ($dataMembership['membership_name'] == $result['user_membership']['membership_name']) {
						$indexNow = $index;
					}

					unset($allMembership[$index]['min_total_count']);
					unset($allMembership[$index]['min_total_value']);
					unset($allMembership[$index]['min_total_balance']);
					unset($allMembership[$index]['min_total_achievement']);
					unset($allMembership[$index]['retain_min_total_value']);
					unset($allMembership[$index]['retain_min_total_count']);
					unset($allMembership[$index]['retain_min_total_balance']);
					unset($allMembership[$index]['retain_min_total_achievement']);
					unset($allMembership[$index]['created_at']);
					unset($allMembership[$index]['updated_at']);
					
					$allMembership[$index]['membership_image'] = env('S3_URL_API').$allMembership[$index]['membership_image'];
					// $allMembership[$index]['membership_bg_image'] = env('S3_URL_API').$allMembership[$index]['membership_bg_image'];
					$allMembership[$index]['membership_next_image'] = $allMembership[$index]['membership_next_image']?env('S3_URL_API').$allMembership[$index]['membership_next_image']:null;
					$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}else{
				$membershipUser = User::find($post['id_user']);
				$nextMembershipName = $allMembership[0]['membership_name'];
				// $nextMembershipImage = env('S3_URL_API') . $allMembership[0]['membership_image'];
				if($allMembership[0]['membership_type'] == 'count'){
					$nextTrx = $allMembership[0]['min_total_count'];
					$nextTrxType = 'count';
				}
				if($allMembership[0]['membership_type'] == 'value'){
					$nextTrx = $allMembership[0]['min_total_value'];
					$nextTrxType = 'value';
				}
				foreach($allMembership as $j => $dataMember){
					$allMembership[$j]['membership_image'] = env('S3_URL_API').$allMembership[$j]['membership_image'];
					$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}
		}
		$membershipUser['next_level'] = $nextMembershipName;
		// $result['next_membership_image'] = $nextMembershipImage;
		if(isset($result['user_membership'])){
			if($nextTrxType == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($count_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $count_transaction;
			}elseif($nextTrxType == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($subtotal_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $subtotal_transaction;
				$membershipUser['progress_active'] = ($subtotal_transaction / $nextTrx) * 100;
				// $result['next_trx']		= $subtotal_transaction - $nextTrx;
			}elseif($nextTrxType == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal', 'Point Injection', 'Welcome Point'])->where('balance', '>', 0)->sum('balance');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($total_balance,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $total_balance;
				$membershipUser['progress_active'] = ($total_balance / $nextTrx) * 100;
				// $result['next_trx']		= $nextTrx - $total_balance;
			}elseif($nextTrxType == 'achievement'){
				$total_achievement = DB::table('achievement_users')
				->join('achievement_details', 'achievement_users.id_achievement_detail', '=', 'achievement_details.id_achievement_detail')
				->join('achievement_groups', 'achievement_details.id_achievement_group', '=', 'achievement_groups.id_achievement_group')
				->where('id_user', $post['id_user'])
				->where('achievement_groups.status', 'Active')
				->where('achievement_groups.is_calculate', 1)
				->groupBy('achievement_groups.id_achievement_group')->get()->count();
				$membershipUser['progress_now_text'] = $total_achievement;
				$membershipUser['progress_now'] = (int) $total_achievement;
				$membershipUser['progress_active'] = ($total_achievement / $nextTrx) * 100;
				// $result['next_trx']		= $nextTrx - $total_balance;
			}
		}
		$result['all_membership'] = $allMembership;
		//user dengan level tertinggi
		if($nextMembershipName == ""){
			$result['progress_active'] = 100;
			$result['next_trx'] = 0;
			if($allMembership[0]['membership_type'] == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($count_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $count_transaction;
			}elseif($allMembership[0]['membership_type'] == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($subtotal_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $subtotal_transaction;
			}elseif($allMembership[0]['membership_type'] == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal', 'Point Injection', 'Welcome Point'])->where('balance', '>', 0)->sum('balance');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($total_balance,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $total_balance;
			}elseif($allMembership[0]['membership_type'] == 'achievement'){
				$total_achievement = DB::table('achievement_users')
				->join('achievement_details', 'achievement_users.id_achievement_detail', '=', 'achievement_details.id_achievement_detail')
				->join('achievement_groups', 'achievement_details.id_achievement_group', '=', 'achievement_groups.id_achievement_group')
				->where('id_user', $post['id_user'])
				->where('achievement_groups.status', 'Active')
				->where('achievement_groups.is_calculate', 1)
				->groupBy('achievement_groups.id_achievement_group')->count();
				$membershipUser['progress_now_text'] = $total_achievement;
				$membershipUser['progress_now'] = (int) $total_achievement;
			}
		}
		unset($result['user_membership']['user']);
		$membershipUser['progress_min_text']		=  MyHelper::requestNumber($result['user_membership']['min_value'],'_CURRENCY');
		$membershipUser['progress_min']		= $result['user_membership']['min_value'];
		if (isset($allMembership[$indexNow + 1])) {
			$membershipUser['progress_max_text']	= MyHelper::requestNumber($result['all_membership'][$indexNow + 1]['min_value'],'_CURRENCY');
			$membershipUser['progress_max']	= $result['all_membership'][$indexNow + 1]['min_value'];
		} else {
			$membershipUser['progress_max_text']	= MyHelper::requestNumber(1500000,'_CURRENCY');
			$membershipUser['progress_max']	= 1500000;
		}
		$result['user_membership']['user']	= $membershipUser;

		return response()->json(MyHelper::checkGet($result));
	}
    // public function webview(Request $request)
    // {
	// 	$check = $request->json('check');
    //     if (empty($check)) {
	// 		$user = $request->user();
	// 		$dataEncode = [
	// 			'id_user' => $user->id,
	// 		];
	// 		$encode = json_encode($dataEncode);
	// 		$base = base64_encode($encode);
	// 		$send = [
	// 			'status' => 'success',
	// 			'result' => [
	// 				'url'              => env('API_URL').'api/membership/web/view?data='.$base
	// 			],
	// 		];
	// 		return response()->json($send);
    //     }
	// 	$post = $request->json()->all();
	// 	$result = [];
	// 	$result['user_membership'] = UsersMembership::with('user')->where('id_user', $post['id_user'])->orderBy('id_log_membership', 'desc')->first();
	// 	$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
	// 	if(!$settingCashback || !$settingCashback->value){
	// 		return response()->json([
	// 			'status' => 'fail',
	// 			'messages' => ['Cashback conversion not found']
	// 		]);
	// 	}
	// 	$allMembership = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->orderBy('min_total_balance', 'asc')->get()->toArray();
	// 	$nextMembershipName = "";
	// 	$nextMembershipImage = "";
	// 	$nextTrx = 0;
	// 	$nextTrxType = '';
	// 	if(count($allMembership) > 0){
	// 		if($result['user_membership']){
	// 			foreach($allMembership as $index => $dataMembership){
	// 				$allMembership[$index]['benefit_text']=json_decode($dataMembership['benefit_text'],true)??[];
	// 				if($dataMembership['membership_type'] == 'count'){
	// 				    $allMembership[$index]['min_value'] = $dataMembership['min_total_count'];
	// 					if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_count'];
	// 							$nextTrxType = 'count';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				if($dataMembership['membership_type'] == 'value'){
	// 					$allMembership[$index]['min_value'] = $dataMembership['min_total_value'];
	// 					if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_value'];
	// 							$nextTrxType = 'value';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				if($dataMembership['membership_type'] == 'balance'){
	// 				    $allMembership[$index]['min_value'] = $dataMembership['min_total_balance'];
	// 					if($dataMembership['min_total_balance'] > $result['user_membership']['min_total_balance']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_balance'];
	// 							$nextTrxType = 'balance';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				$allMembership[$index]['membership_image'] = env('S3_URL_API').$allMembership[$index]['membership_image'];
	// 				$allMembership[$index]['membership_next_image'] = $allMembership[$index]['membership_next_image']?env('S3_URL_API').$allMembership[$index]['membership_next_image']:null;
	// 				$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier'] * $settingCashback->value;
	// 			}
	// 		}else{
	// 			$result['user_membership']['user'] = User::find($post['id_user']);
	// 			$nextMembershipName = $allMembership[0]['membership_name'];
	// 			$nextMembershipImage = $allMembership[0]['membership_image'];
	// 			if($allMembership[0]['membership_type'] == 'count'){
	// 				$nextTrx = $allMembership[0]['min_total_count'];
	// 				$nextTrxType = 'count';
	// 			}
	// 			if($allMembership[0]['membership_type'] == 'value'){
	// 				$nextTrx = $allMembership[0]['min_total_value'];
	// 				$nextTrxType = 'value';
	// 			}
	// 			foreach($allMembership as $j => $dataMember){
	// 				$allMembership[$j]['membership_image'] = env('S3_URL_API').$allMembership[$j]['membership_image'];
	// 				$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier'] * $settingCashback->value;
	// 			}
	// 		}
	// 	}
	// 	$result['next_membership_name'] = $nextMembershipName;
	// 	$result['next_membership_image'] = $nextMembershipImage;
	// 	if(isset($result['user_membership'])){
	// 		if($nextTrxType == 'count'){
	// 			$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $count_transaction;
	// 		}elseif($nextTrxType == 'value'){
	// 			$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
	// 			$result['progress_active'] = ($subtotal_transaction / $nextTrx) * 100;
	// 			$result['next_trx']		= $subtotal_transaction - $nextTrx;
	// 		}elseif($nextTrxType == 'balance'){
	// 			$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
	// 			$result['user_membership']['user']['progress_now'] = $total_balance;
	// 			$result['progress_active'] = ($total_balance / $nextTrx) * 100;
	// 			$result['next_trx']		= $nextTrx - $total_balance;
	// 		}
	// 	}
	// 	$result['all_membership'] = $allMembership;
	// 	//user dengan level tertinggi
	// 	if($nextMembershipName == ""){
	// 		$result['progress_active'] = 100;
	// 		$result['next_trx'] = 0;
	// 		if($allMembership[0]['membership_type'] == 'count'){
	// 			$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $count_transaction;
	// 		}elseif($allMembership[0]['membership_type'] == 'value'){
	// 			$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
	// 		}elseif($allMembership[0]['membership_type'] == 'balance'){
	// 			$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
	// 			$result['user_membership']['user']['progress_now'] = $total_balance;
	// 		}
	// 	}
	// 	return response()->json(MyHelper::checkGet($result));
	// }
	// public function detailWebview(Request $request)
	// {
	// 	$bearer = $request->header('Authorization');

	// 	if ($bearer == "") {
	// 		return view('error', ['msg' => 'Unauthenticated']);
	// 	}
	// 	$data = json_decode(base64_decode($request->get('data')), true);
	// 	$data['check'] = 1;
	// 	$check = MyHelper::postCURLWithBearer('api/membership/detail/webview?log_save=0', $data, $bearer);
	// 	if (isset($check['status']) && $check['status'] == 'success') {
	// 		$data['result'] = $check['result'];
	// 	} elseif (isset($check['status']) && $check['status'] == 'fail') {
	// 		return view('error', ['msg' => 'Data failed']);
	// 	} else {
	// 		return view('error', ['msg' => 'Something went wrong, try again']);
	// 	}
	// 	$data['max_value'] = end($check['result']['all_membership'])['min_value'];

	// 	return view('membership::webview.detail_membership', $data);
	// }
}