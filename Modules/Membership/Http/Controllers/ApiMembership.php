<?php

namespace Modules\Membership\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Membership;
use App\Http\Models\MembershipPromoId;
use App\Http\Models\UsersMembership;
use App\Http\Models\UsersMembershipPromoId;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Lib\MyHelper;
use App\Http\Models\Configs;
use App\Http\Models\LogBalance;

use DB;

class ApiMembership extends Controller
{
	function __construct() {
		date_default_timezone_set('Asia/Jakarta');
	}

    public function listMembership(Request $request){
		$post = $request->json()->all();
		if(isset($post['id_membership'])){
			$query = Membership::where('id_membership','=',$post['id_membership'])->get()->toArray();
        } else {
			$query = Membership::with('membership_promo_id')->get()->toArray();
		}
        return response()->json(MyHelper::checkGet($query));
    }

    function create(Request $request) {
        $post = $request->json()->all();
        $save = Membership::create($post);

        return response()->json(MyHelper::checkCreate($save));
    }

    function update(Request $request) {
		DB::beginTransaction();
    	$post = $request->json()->all();
		$current = Membership::get()->toArray();
		$exist = false;
		foreach($current as $cur){
			$exist = false;
			foreach($post['membership'] as $i => $membership){
				$membership = $this->checkInputBenefitMembership($membership);
				if($cur['id_membership'] == $membership['id_membership']){
					$exist = true;
					$data = [];
					$data['membership_name'] = $membership['membership_name'];
					$data['membership_type'] = $post['membership_type'];

					if(isset($membership['membership_name_color'])){
						$data['membership_name_color'] = str_replace('#','',$membership['membership_name_color']);
					}

					if (isset($membership['membership_image'])) {
						if (!file_exists('img/membership/')) {
							mkdir('img/membership/', 0777, true);
						}

						//delete photo
						if($cur['membership_image']){
							$deletephoto = MyHelper::deletePhoto($cur['membership_image']);
						}
						$upload = MyHelper::uploadPhotoStrict($membership['membership_image'], $path = 'img/membership/', 500, 500);

						if ($upload['status'] == "success") {
							$data['membership_image'] = $upload['path'];
						} else{
							DB::rollback();
							$result = [
									'status'	=> 'fail',
									'messages'	=> ['Upload Membership Image failed.']
								];
							return response()->json($result);
						}
					}

					if (isset($membership['membership_next_image'])) {
						if (!file_exists('img/membership/')) {
							mkdir('img/membership/', 0777, true);
						}

						//delete photo
						if($cur['membership_next_image']){
							$deletenextphoto = MyHelper::deletePhoto($cur['membership_next_image']);
						}
						$upload = MyHelper::uploadPhotoStrict($membership['membership_next_image'], $path = 'img/membership/', 500, 500);

						if ($upload['status'] == "success") {
							$data['membership_next_image'] = $upload['path'];
						} else{
							DB::rollback();
							$result = [
									'status'	=> 'fail',
									'messages'	=> ['Upload Membership Image failed.']
								];
							return response()->json($result);
						}
					}

					if(!isset($membership['min_retain_value'])){
						$membership['min_retain_value'] = null;
					}

					if($membership['min_value'] == null) $membership['min_value'] = '0';
					if($membership['min_retain_value'] == null) $membership['min_retain_value'] = '0';
					if($post['membership_type'] == 'value'){
						$data['min_total_value'] = $membership['min_value'];
						$data['min_total_count'] = null;
						$data['min_total_balance'] = null;

						$data['retain_min_total_value'] = $membership['min_retain_value'];
						$data['retain_min_total_count'] = null;
						$data['retain_min_total_balance'] = null;
					}

					if($post['membership_type'] == 'count'){
						$data['min_total_value'] = null;
						$data['min_total_count'] = $membership['min_value'];
						$data['min_total_balance'] = null;

						$data['retain_min_total_value'] = null;
						$data['retain_min_total_count'] = $membership['min_retain_value'];
						$data['retain_min_total_balance'] = null;
					}

					if($post['membership_type'] == 'balance'){
						$data['min_total_value'] = null;
						$data['min_total_count'] = null;
						$data['min_total_balance'] = $membership['min_value'];

						$data['retain_min_total_value'] = null;
						$data['retain_min_total_count'] = null;
						$data['retain_min_total_balance'] = $membership['min_retain_value'];
					}

					if(isset($post['retain_days'])){
						$data['retain_days'] = $post['retain_days'];
					}

					$data['benefit_point_multiplier'] = $membership['benefit_point_multiplier'];
					$data['benefit_cashback_multiplier'] = $membership['benefit_cashback_multiplier'];
					$data['benefit_discount'] = $membership['benefit_discount'];
					// $data['benefit_promo_id'] = $membership['benefit_promo_id'];

					if(isset($membership['cashback_maximum'])){
						$data['cashback_maximum'] = $membership['cashback_maximum'];
					}

					$query = Membership::where('id_membership', $membership['id_membership'])->update($data);
					//benefit promo id
					$deletePromoId = MembershipPromoId::where('id_membership', $membership['id_membership'])->delete();
					if(isset($membership['benefit_promo_id'])){
						foreach($membership['benefit_promo_id']  as $promoid){
							if($promoid['promo_id']){
								$savePromoid = MembershipPromoId::Create(['id_membership' => $membership['id_membership'], 'promo_name' => $promoid['promo_name'], 'promo_id' => $promoid['promo_id']]);
								if(!$savePromoid){
									DB::rollback();
									$result = [
										'status'	=> 'fail',
										'messages'	=> ['Update membership failed.']
									];
									return response()->json($result);
								}
							}
						}
					}
					break;
				}
			}
			if($exist == false){
				if($cur['membership_image']){
					$deletephoto = MyHelper::deletePhoto($cur['membership_image']);
				}
				if($cur['membership_next_image']){
					$deletenextphoto = MyHelper::deletePhoto($cur['membership_next_image']);
				}
				$query = Membership::where('id_membership', $cur['id_membership'])->delete();
			}
		}
    	foreach($post['membership'] as $index => $membership){
			$membership = $this->checkInputBenefitMembership($membership);
			if($membership['id_membership'] == null){
				$data = [];
				$data['membership_name'] = $membership['membership_name'];
				$data['membership_type'] = $post['membership_type'];

				if(isset($membership['membership_name_color'])){
					$data['membership_name_color'] = str_replace('#','',$membership['membership_name_color']);
				}

				if (isset($post['membership_image'])) {
					if (!file_exists('img/membership/')) {
						mkdir('img/membership/', 0777, true);
					}
					$upload = MyHelper::uploadPhotoStrict($post['membership_image'], $path = 'img/membership/', 500, 500);

					if ($upload['status'] == "success") {
						$post['membership_image'] = $upload['path'];
					} else{
						DB::rollback();
						$result = [
								'status'	=> 'fail',
								'messages'	=> ['Upload Membership Image failed.']
							];
						return response()->json($result);
					}
				}

				if (isset($post['membership_next_image'])) {
					if (!file_exists('img/membership/')) {
						mkdir('img/membership/', 0777, true);
					}
					$upload = MyHelper::uploadPhotoStrict($post['membership_next_image'], $path = 'img/membership/', 500, 500);

					if ($upload['status'] == "success") {
						$post['membership_next_image'] = $upload['path'];
					} else{
						DB::rollback();
						$result = [
								'status'	=> 'fail',
								'messages'	=> ['Upload Membership Image failed.']
							];
						return response()->json($result);
					}
				}

				if(!isset($membership['min_retain_value'])) {
					$membership['min_retain_value'] = 0;
				}

				if($post['membership_type'] == 'value'){
					$data['min_total_value'] = $membership['min_value'];
					$data['min_total_count'] = null;
					$data['min_total_balance'] = null;

					$data['retain_min_total_value'] = $membership['min_retain_value'];
					$data['retain_min_total_count'] = null;
					$data['retain_min_total_balance'] = null;
				}

				if($post['membership_type'] == 'count'){
					$data['min_total_value'] = null;
					$data['min_total_count'] = $membership['min_value'];
					$data['min_total_balance'] = null;

					$data['retain_min_total_value'] = null;
					$data['retain_min_total_count'] = $membership['min_retain_value'];
					$data['retain_min_total_balance'] = null;
				}

				if($post['membership_type'] == 'balance'){
					$data['min_total_value'] = null;
					$data['min_total_count'] = null;
					$data['min_total_balance'] = $membership['min_value'];

					$data['retain_min_total_value'] = null;
					$data['retain_min_total_count'] = null;
					$data['retain_min_total_balance'] = $membership['min_retain_value'];
				}

				$data['benefit_point_multiplier'] = $membership['benefit_point_multiplier'];
				$data['benefit_cashback_multiplier'] = $membership['benefit_cashback_multiplier'];
				if(isset($post['retain_days'])){
					$data['retain_days'] = $post['retain_days'];
				}
				$data['benefit_discount'] = $membership['benefit_discount'];
				// $data['benefit_promo_id'] = $membership['benefit_promo_id'];

				if(isset($membership['cashback_maximum'])){
					$data['cashback_maximum'] = $membership['cashback_maximum'];
				}
				$query = Membership::create($data);

				if(!$query){
					DB::rollback();
					$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update Membership failed.']
						];
					return response()->json($result);
				}

				if(isset($membership['benefit_promo_id'])){
					foreach($membership['benefit_promo_id'] as $promoid){
						if($promoid['promo_id']){
							$savePromoid = MembershipPromoId::Create(['id_membership' => $membership['id_membership'], 'promo_name' => $promoid['promo_name'], 'promo_id' => $promoid['promo_id']]);
							if(!$savePromoid){
								DB::rollback();
								$result = [
									'status'	=> 'fail',
									'messages'	=> ['Update membership failed.']
								];
								return response()->json($result);
							}
						}
					}
				}
			}
		}

		//kalkulasi ulang membership user
		$calculate = $this->calculateMembershipAllUser();
		if(isset($calculate['status']) && $calculate['status'] == 'success'){
			DB::commit();
			return response()->json(['status' => 'success']);
		}elseif(isset($calculate['status']) && $calculate['status'] == 'fail'){
			DB::rollback();
			return response()->json($calculate);
		}else{
			DB::rollback();
			return response()->json([
				'status' => 'fail',
				'messages' => ['Update Membership failed.']
			]);
		}

    }

    function delete(Request $request) {
		$post = $request->json()->all();
    	$check = Membership::where('id_membership', $post['id_membership'])->first();

    	if ($check) {
			$checkMember = UsersMembership::where('id_membership', $post['id_membership'])->first();
			if (!$checkMember) {
				$delete = Membership::where('id_membership', $request->json('id_membership'))->delete();
				return response()->json(MyHelper::checkDelete($delete));
			} else {
				return response()->json([
					'status'   => 'fail',
					'messages' => ['membership has been used.']
				]);
			}
    	}
    	else {
    		return response()->json([
				'status'   => 'fail',
				'messages' => ['membership not found.']
    		]);
    	}

    }

	public function calculateMembership($phone) {
    	$check = User::leftJoin('users_memberships', 'users_memberships.id_user','=','users.id')
						->orderBy('users_memberships.id_log_membership', 'desc')
						->where('users.phone', $phone)
						->first()
						->toArray();

		if($check){
			$membership_all = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count','asc')->orderBy('min_total_balance','asc')->get()->toArray();
			if(empty($membership_all)){
				return [
					'status'   => 'fail',
					'messages' => ['Membership Level not found.']
				];
			}

			if(!empty($check['retain_date'])){

				//sudah pernah punya membership
				$membership = Membership::where('id_membership', $check['id_membership'])->first();

				// untuk membership yang pakai retain
				// cek config retain
				$retain = 1;
				$config = Configs::where('config_name', 'retain membership')->first();
				if($config && $config['is_active'] == '0'){
					$retain = 0;
				}

				if($retain == 1 && $membership['retain_days'] > 0){

					//ambil batas tanggal terhitung diceknya
					$date_start = date('Y-m-d', strtotime($check['retain_date'].' -'.$membership['retain_days'].' days'));

					$trx_count = Transaction::where('id_user',$check['id'])
											->whereDate('transaction_date','>=',$date_start)
											->whereDate('transaction_date','<=',date('Y-m-d', strtotime($check['retain_date'])))
											->where('transaction_payment_status', 'Completed')
											->count('transaction_subtotal');

					$trx_value = Transaction::where('id_user',$check['id'])
											->whereDate('transaction_date','>=',$date_start)
											->whereDate('transaction_date','<=', date('Y-m-d', strtotime($check['retain_date'])))
											->where('transaction_payment_status', 'Completed')
											->sum('transaction_subtotal');

					$total_balance = LogBalance::where('id_user',$check['id'])
											->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
											->where('balance', '>', 0)
											->whereDate('created_at','>=',$date_start)
											->whereDate('created_at','<=', date('Y-m-d', strtotime($check['retain_date'])))
											->sum('balance');

					//update user count & subtotal transaction
					$user = User::where('users.phone', $phone)->update(['subtotal_transaction'=> $trx_value, 'count_transaction' => $trx_count]);

					$membership_baru = null;

					if(strtotime($check['retain_date']) > strtotime(date('Y-m-d'))){
						//belum waktunya dicek untuk retain
						//cek naik level
						foreach($membership_all as $i => $all){

							//cek cuma kalo lebih dari membership yang sekarang
							//cek total transaction value
							if($all['membership_type'] == 'value'){
								if($all['min_total_value'] > $membership['min_total_value']){
									if($trx_value >= $all['min_total_value']){
										$membership_baru = $all;
									}
								}
							}
							//cek total transaction count
							if($all['membership_type'] == 'count'){
								if($all['min_total_count'] > $membership['min_total_count']){
									if($trx_count >= $all['min_total_count']){
										$membership_baru = $all;
									}
								}
							}

							//cek total transaction balance
							if($all['membership_type'] == 'balance'){
								if($all['min_total_balance'] > $membership['min_total_balance']){
									if($total_balance >= $all['min_total_balance']){
										$membership_baru = $all;
									}
								}
							}
						}
					} else {
						//sudah waktunya dicek untuk retain
						//cek naik level
						foreach($membership_all as $all){
							//cek cuma kalo lebih dari membership yang sekarang
							//cek total transaction value
							if($all['membership_type'] == 'value'){
								if($all['min_total_value'] > $membership['min_total_value']){
									if($trx_value >= $all['min_total_value']){
										//level up
										$membership_baru = $all;
									}
								}
							}
							//cek total transaction count
							if($all['membership_type'] == 'count'){
								if($all['min_total_count'] > $membership['min_total_count']){
									if($trx_count >= $all['min_total_count']){
										//level up
										$membership_baru = $all;
									}
								}
							}
							//cek total balance
							if($all['membership_type'] == 'balance'){
								if($all['min_total_balance'] > $membership['min_total_balance']){
									if($total_balance >= $all['min_total_balance']){
										//level up
										$membership_baru = $all;
									}
								}
							}
						}
						if($membership_baru == null){
							//cek retain level
							if($trx_value >= $membership['retain_min_total_value'] || $trx_count >= $membership['retain_min_total_count'] || $total_balance >= $membership['retain_min_total_balance']){
								//level retained
								$membership_baru = null;
							} else {
								foreach($membership_all as $all){
									//cek cuma kalo kurang dari membership yang sekarang
									//cek total transaction value
									if($all['membership_type'] == 'value'){
										if($all['min_total_value'] < $membership['min_total_value']){
											if($trx_value >= $all['min_total_value']){
												//level up
												$membership_baru = $all;
											}
										}
									}
									//cek total transaction count
									if($all['membership_type'] == 'count'){
										if($all['min_total_count'] < $membership['min_total_count']){
											if($trx_count >= $all['min_total_count']){
												//level up
												$membership_baru = $all;
											}
										}
									}
									//cek total balance
									if($all['membership_type'] == 'balance'){
										if($all['min_total_balance'] < $membership['min_total_balance']){
											if($trx_count >= $all['min_total_balance']){
												//level up
												$membership_baru = $all;
											}
										}
									}
								}
							}
						}
					}
				}
				// untuk membership yang gak pakai retain
				else{
					$trx_count = Transaction::where('id_user',$check['id'])
											->where('transaction_payment_status', 'Completed')
											->count('transaction_subtotal');

					$trx_value = Transaction::where('id_user',$check['id'])
											->where('transaction_payment_status', 'Completed')
											->sum('transaction_subtotal');

					$total_balance = LogBalance::where('id_user',$check['id'])
											->where('balance', '>', 0)
											->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
											->sum('balance');

					//update user count & subtotal transaction
					$user = User::where('users.phone', $phone)->update(['subtotal_transaction'=> $trx_value, 'count_transaction' => $trx_count]);

					$membership_baru = null;
					//cek naik level
					foreach($membership_all as $all){
						//cek cuma kalo lebih dari membership yang sekarang
						//cek total transaction value
						if($all['membership_type'] == 'value'){
							if($all['min_total_value'] > $membership['min_total_value']){
								if($trx_value >= $all['min_total_value']){
									//level up
									$membership_baru = $all;
								}
							}
						}
						//cek total transaction count
						if($all['membership_type'] == 'count'){
							if($all['min_total_count'] > $membership['min_total_count']){
								if($trx_count >= $all['min_total_count']){
									//level up
									$membership_baru = $all;
								}
							}
						}
						//cek total balance
						if($all['membership_type'] == 'balance'){
							if($all['min_total_balance'] > $membership['min_total_balance']){
								if($total_balance >= $all['min_total_balance']){
									//level up
									$membership_baru = $all;
								}
							}
						}
					}
				}
			} else {
				//belum pernah punya membership
				//bisa langsung lompat membership

				$trx_count = Transaction::where('id_user',$check['id'])
											->where('transaction_payment_status', 'Completed')
											->count('transaction_subtotal');

				$trx_value = Transaction::where('id_user',$check['id'])
										->where('transaction_payment_status', 'Completed')
										->sum('transaction_subtotal');

				$total_balance = LogBalance::whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
											->where('balance', '>', 0)
											->where('id_user',$check['id'])->sum('balance');

				//update user count & subtotal transaction
				$user = User::where('users.phone', $phone)->update(['subtotal_transaction'=> $trx_value, 'count_transaction' => $trx_count]);

				$membership_baru = null;
				//cek naik level
				foreach($membership_all as $all){
					//cek total transaction value
					if($all['membership_type'] == 'value'){
						if($trx_value >= $all['min_total_value']){
							//level up
							$membership_baru = $all;
						}
					}

					//cek total transaction count
					if($all['membership_type'] == 'count'){
						if($trx_count >= $all['min_total_count']){
							//level up
							$membership_baru = $all;
						}
					}
					//cek total balance
					if($all['membership_type'] == 'balance'){
						if($total_balance >= $all['min_total_balance']){
							//level up
							$membership_baru = $all;
						}
					}
				}
			}

			if($membership_baru != null){
				$date_end = date("Y-m-d", strtotime(date('Y-m-d').' +'.$membership_baru['retain_days'].' days'));

				$data 									= [];
				$data['id_user'] 						= $check['id'];
				$data['id_membership'] 					= $membership_baru['id_membership'];
				$data['membership_name'] 				= $membership_baru['membership_name'];
				$data['membership_name_color'] 			= $membership_baru['membership_name_color'];
				$data['membership_image'] 				= $membership_baru['membership_image'];
				$data['membership_next_image'] 			= $membership_baru['membership_next_image'];
				$data['membership_type'] 				= $membership_baru['membership_type'];
				$data['min_total_value'] 				= $membership_baru['min_total_value'];
				$data['min_total_count'] 				= $membership_baru['min_total_count'];
				$data['min_total_balance'] 				= $membership_baru['min_total_balance'];
				$data['retain_date'] 					= $date_end;
				$data['retain_min_total_value'] 		= $membership_baru['retain_min_total_value'];
				$data['retain_min_total_count'] 		= $membership_baru['retain_min_total_count'];
				$data['retain_min_total_balance'] 		= $membership_baru['retain_min_total_balance'];
				$data['benefit_point_multiplier'] 		= $membership_baru['benefit_point_multiplier'];
				$data['benefit_cashback_multiplier'] 	= $membership_baru['benefit_cashback_multiplier'];
				$data['benefit_promo_id'] 				= $membership_baru['benefit_promo_id'];
				$data['benefit_discount'] 				= $membership_baru['benefit_discount'];
				$data['cashback_maximum'] 				= $membership_baru['cashback_maximum'];

				$query = UsersMembership::create($data);

				if($query){
					//update membership user
					$user = User::where('phone', $phone)->update(['id_membership' => $query['id_membership']]);

					foreach($membership_baru['membership_promo_id'] as $promoid){
						$savePromoid = UsersMembershipPromoId::Create(['id_users_membership' => $query->id_log_membership, 'promo_name' => $promoid['promo_name'], 'promo_id' => $promoid['promo_id']]);
						if(!$savePromoid){
							return [
								'status' => 'fail',
								'messages' => ['Update user membership failed.']
							];
						}
					}
				}

				return [
					'status'   => 'success',
					'membership' => $data
				];
			} else{
				return [
					'status'   => 'success',
					'membership' => $check
				];
			}
		} else {
			return [
				'status'   => 'fail',
				'messages' => ['user not found.']
    		];
		}
	}

	public function calculateMembershipAllUser() {
    	$users = User::all();

		$membership_all = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count','asc')->orderBy('min_total_balance','asc')->get()->toArray();
		if(empty($membership_all)){
			return [
				'status'   => 'fail',
				'messages' => ['Membership Level not found.']
			];
		}

		foreach($users as $datauser){

			$membership_baru = null;
			//cek level
			foreach($membership_all as $all){
				//cek total transaction value
				if($all['membership_type'] == 'value'){
					if($datauser->subtotal_transaction >= $all['min_total_value']){
						//level up
						$membership_baru = $all;
					}
				}

				//cek total transaction count
				if($all['membership_type'] == 'count'){
					if($datauser->count_transaction >= $all['min_total_count']){
						//level up
						$membership_baru = $all;
					}
				}

				//cek total transaction count
				if($all['membership_type'] == 'balance'){
					if($datauser->balance >= $all['min_total_balance']){
						//level up
						$membership_baru = $all;
					}
				}
			}

			if($membership_baru != null){
				$date_end = date("Y-m-d", strtotime(date('Y-m-d').' +'.$membership_baru['retain_days'].' days'));

				$data 									= [];
				$data['id_user'] 						= $datauser->id;
				$data['id_membership'] 					= $membership_baru['id_membership'];
				$data['membership_name'] 				= $membership_baru['membership_name'];
				$data['membership_name_color'] 			= $membership_baru['membership_name_color'];
				$data['membership_image'] 				= $membership_baru['membership_image'];
				$data['membership_next_image'] 			= $membership_baru['membership_next_image'];
				$data['membership_type'] 				= $membership_baru['membership_type'];
				$data['min_total_value'] 				= $membership_baru['min_total_value'];
				$data['min_total_count'] 				= $membership_baru['min_total_count'];
				$data['min_total_balance'] 				= $membership_baru['min_total_balance'];
				$data['retain_date'] 					= $date_end;
				$data['retain_min_total_value'] 		= $membership_baru['retain_min_total_value'];
				$data['retain_min_total_count'] 		= $membership_baru['retain_min_total_count'];
				$data['retain_min_total_balance'] 		= $membership_baru['retain_min_total_balance'];
				$data['benefit_point_multiplier'] 		= $membership_baru['benefit_point_multiplier'];
				$data['benefit_cashback_multiplier'] 	= $membership_baru['benefit_cashback_multiplier'];
				$data['benefit_promo_id'] 				= $membership_baru['benefit_promo_id'];
				$data['benefit_discount'] 				= $membership_baru['benefit_discount'];
				$data['cashback_maximum'] 				= $membership_baru['cashback_maximum'];

				$query = UsersMembership::create($data);
				if($query){
					//update membership user
					$user = User::where('phone', $datauser->phone)->update(['id_membership' => $query['id_membership']]);

					foreach($membership_baru['membership_promo_id'] as $promoid){
						$savePromoid = UsersMembershipPromoId::Create(['id_users_membership' => $query->id_log_membership, 'promo_name' => $promoid['promo_name'], 'promo_id' => $promoid['promo_id']]);
						if(!$savePromoid){
							return [
								'status' => 'fail',
								'messages' => ['Update user membership failed.']
							];
						}
					}
				}else{
					return [
						'status' => 'fail',
						'messages' => ['Update user membership failed.']
					];
				}

			}
		}

		return [
			'status' => 'success'
		];
	}


	function checkInputBenefitMembership($data=[]) {
        if (!isset($data['benefit_point_multiplier'])) {
            $data['benefit_point_multiplier'] = 0;
        }
        if (!isset($data['benefit_cashback_multiplier'])) {
            $data['benefit_cashback_multiplier'] = 0;
        }
        if (!isset($data['benefit_promo_id'])) {
            $data['benefit_promo_id'] = NUll;
        }
        if (!isset($data['benefit_discount'])) {
            $data['benefit_discount'] = 0;
        }

        return $data;
	}

	public function updateSubtotalTrxUser(){
		$users = User::all();
		foreach($users as $datauser){

			$trx_count = Transaction::where('id_user',$datauser->id)
										->where('transaction_payment_status', 'Completed')
										->count('transaction_subtotal');

			$trx_value = Transaction::where('id_user',$datauser->id)
									->where('transaction_payment_status', 'Completed')
									->sum('transaction_subtotal');

			$total_balance = LogBalance::where('id_user', $datauser->id)
										->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])
										->where('balance', '>', 0)
										->sum('balance');

			$update = User::where('phone', $datauser->phone)->update(['subtotal_transaction' => $trx_value, 'count_transaction' => $trx_count, 'balance' => $total_balance]);
		}
		return 'success';
	}
}
