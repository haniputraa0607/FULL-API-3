<?php

namespace Modules\Promotion\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Http\Models\Promotion;
use App\Http\Models\PromotionRule;
use App\Http\Models\PromotionRuleParent;
use App\Http\Models\PromotionContent;
use App\Http\Models\PromotionContentShortenLink;
use App\Http\Models\PromotionSchedule;
use App\Http\Models\PromotionQueue;
use App\Http\Models\PromotionSent;
use App\Http\Models\Deal;
use App\Http\Models\DealsVoucher;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPromotionTemplate;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Treatment;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\WhatsappContent;
use App\Http\Models\News;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\classMaskingJson;
use App\Lib\classJatisSMS;
use App\Lib\apiwha;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

class ApiPromotion extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->user     = "Modules\Users\Http\Controllers\ApiUser";
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->dealsVoucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
		$this->dealsClaim  = "Modules\Deals\Http\Controllers\ApiDealsClaim";
		$this->deals  = "Modules\Deals\Http\Controllers\ApiDeals";
		$this->rajasms = new classMaskingJson();
		$this->jatissms = new classJatisSMS();
		$this->apiwha = new apiwha();
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function list(Request $request)
    {
		$post = $request->json()->all();

		$promotions = Promotion::with('schedules');
		if(isset($post['promotion_name'])){
			$promotions = $promotions->where('promotion_name', 'LIKE', '%'.$post['promotion_name'].'%');
		}
		$promotions = $promotions->orderBy('id_promotion', 'Desc')->paginate(10)->toArray();
		return response()->json(MyHelper::checkGet($promotions));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function CreatePromotion(Request $request)
    {
		DB::beginTransaction();

        $post = $request->json()->all();
		$user = $request->user();

		$data 							= [];
		$data['promotion_name'] 		= $post['promotion_name'];
		$data['id_user'] 				= $user['id'];
		$data['promotion_type'] 		= $post['promotion_type'];
		if(!isset($post['promotion_user_limit']) || $post['promotion_type'] == 'Campaign Series') $post['promotion_user_limit'] = '1';
		$data['promotion_user_limit'] 	= $post['promotion_user_limit'];
		$data['promotion_vouchers'] = 0;
		if(isset($post['promotion_series']))
		$data['promotion_series'] 	= $post['promotion_series'];

		if(isset($post['id_promotion'])) {
			$queryPromotion = Promotion::where('id_promotion','=',$post['id_promotion'])->update($data);
			$id_promotion = $post['id_promotion'];
		} else {
			$queryPromotion = Promotion::create($data);
			$id_promotion = $queryPromotion->id_promotion;
		}

		//schedule
		if($queryPromotion){
			$data = [];

			if($post['promotion_type'] == 'Scheduled Campaign'){
				$tgl = explode('-',$post['schedule_date']);
				$data['schedule_exact_date'] = $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
			}

			if($post['promotion_type'] == 'Recurring Campaign' || $post['promotion_type'] == 'Campaign Series'){
				if($post['recurring_rule'] == 'date_every_year'){
					$data['schedule_date_month'] = $post['schedule_date_month'];
				}
				if($post['recurring_rule'] == 'date_every_month'){
					$data['schedule_date_every_month'] = $post['schedule_date_every_month'];
				}
				if($post['recurring_rule'] == 'day_every_week'){
					$data['schedule_day_every_week'] = $post['schedule_day_every_week'];
					$data['schedule_week_in_month'] = $post['schedule_week_in_month'];

				}
				if($post['recurring_rule'] == 'everyday'){
					$data['schedule_everyday'] = 'Yes';
				}
			}

			$data['schedule_time'] = $post['schedule_time'];

			if($post['promotion_type'] == 'Instant Campaign'){
				$data['schedule_exact_date'] = date('Y-m-d');
				$data['schedule_time'] = date('H:i:s');
			}

			if(isset($post['id_promotion']))
				PromotionSchedule::where('id_promotion','=',$post['id_promotion'])->delete();

			$data['id_promotion'] = $id_promotion;

			$queryPromotion = PromotionSchedule::create($data);
		}

		if($queryPromotion){

			if(isset($post['id_promotion'])) $data['id_promotion'] = $post['id_promotion'];
			else $data['id_promotion'] = $queryPromotion->id_promotion;

			//filter jika ada condition
			if(isset($post['conditions'])){
				$queryPromotionRule = MyHelper::insertCondition('promotion', $data['id_promotion'], $post['conditions']);
				if(isset($queryPromotionRule['status']) && $queryPromotionRule['status'] == 'success'){
					$resultrule = $queryPromotionRule['data'];
				}else{
					DB::rollBack();
					$result = [
						'status'  => 'fail',
						'messages'  => ['Create Promotion Failed']
					];
				}
				DB::commit();
				$result = [
						'status'  => 'success',
						'result'  => 'Set Promotion Info & Receipient Success',
						'promotion'  => $queryPromotion,
						'rule'  => $resultrule
					];
			}else{
				// delete rule promotion
				$deleteRuleParent = PromotionRuleParent::where('id_promotion', $data['id_promotion'])->get();
				if(count($deleteRuleParent)>0){
					foreach ($deleteRuleParent as $key => $value) {
						$delete = PromotionRule::where('id_promotion_rule_parent', $value['id_promotion_rule_parent'])->delete();
					}
					$deleteRuleParent = PromotionRuleParent::where('id_promotion', $data['id_promotion'])->delete();
				}
				DB::commit();
				$result = [
					'status'  => 'success',
					'result'  => 'Set Promotion Info & Receipient Success',
					'promotion'  => $queryPromotion,
					'rule'  => []
				];
			}
		} else {
			DB::rollBack();
			$result = [
					'status'  => 'fail',
					'messages'  => ['Create Promotion Failed']
				];
		}

	   return response()->json($result);
    }

	public function ShowPromotionStep2(Request $request){
		$post = $request->json()->all();
		$user = $request->user();

		$promotion = Promotion::where('id_promotion','=',$post['id_promotion'])->first();
		if($promotion){
			// for display in step 3
			if(isset($post['summary'])){
				foreach ($promotion->contents as $content) {
					// update link clicked
					$updateClickEmail = $this->updateLinkClicked($content['id_promotion_content'], 'email');
					$updateClickSms = $this->updateLinkClicked($content['id_promotion_content'], 'sms');

					// update nominal transaction
					if(!empty($content['id_deals'])){
						$sumTransaction = Transaction::join('deals_vouchers', 'transactions.id_deals_voucher', 'deals_vouchers.id_deals_voucher')
													->where('deals_vouchers.id_deals', $content['id_deals'])->sum('transaction_grandtotal');
						$updateContent = PromotionContent::where('id_promotion_content', $content['id_promotion_content'])->update(['promotion_sum_transaction' => $sumTransaction]);
					}
				}
				$promotion = Promotion::where('id_promotion','=',$post['id_promotion'])->with(['user', 'promotion_rule_parents', 'promotion_rule_parents.rules', 'schedules', 'contents', 'contents.deals','contents.deals.outlets','contents.deals.deals_vouchers', 'contents.whatsapp_content'])->first();
			}
			// for display user in step 2
			else{
				// filter user
				if(count($promotion->promotion_rule_parents) >0){
					$cond = Promotion::with(['user', 'promotion_rule_parents', 'promotion_rule_parents.rules'])->where('id_promotion','=',$post['id_promotion'])->get()->first();
					$users = app($this->user)->UserFilter($cond['promotion_rule_parents']);

					$promotion = Promotion::where('id_promotion','=',$post['id_promotion'])->with(['user', 'promotion_rule_parents', 'promotion_rule_parents.rules', 'schedules', 'contents', 'contents.deals','contents.deals.outlets','contents.deals.deals_vouchers', 'contents.whatsapp_content'])->first();
					if($users['status'] == 'success'){
						// exclude user in queue
						if(count($promotion->contents) > 0){
							$exUserQueue = PromotionQueue::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();
							$idUsers = array_diff(array_pluck($users['result'], 'id'), array_pluck($exUserQueue, 'id_user'));
							$users = User::leftJoin('cities','cities.id_city','=','users.id_city')->whereIn('id', $idUsers)->get();
							$promotion['users'] = $users;
						}else{
							$promotion['users'] = $users['result'];
						}

					}
					else{
						$promotion['users'] = [];
					}
				// get All user
				}else{
					$promotion = Promotion::where('id_promotion','=',$post['id_promotion'])->with(['user', 'promotion_rule_parents', 'promotion_rule_parents.rules', 'schedules', 'contents', 'contents.deals','contents.deals.outlets','contents.deals.deals_vouchers', 'contents.whatsapp_content'])->first();

					// exclude user in queue
					if(count($promotion->contents) > 0){
						$exUserQueue = PromotionQueue::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();
						$users = User::leftJoin('cities','cities.id_city','=','users.id_city')->whereNotIn('id', array_pluck($exUserQueue, 'id_user'))->get();
						$promotion['users'] = $users;
					}else{
						$promotion['users'] = User::leftJoin('cities','cities.id_city','=','users.id_city')->get();
					}
				}

				if($promotion['promotion_user_limit'] == '1' && count($promotion->contents) > 0){
					$exUser = PromotionSent::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();
					$exUserQueue = PromotionQueue::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();

					$idUsers = array_diff(array_pluck($promotion['users'], 'id'), array_pluck($exUser, 'id_user'));
					$idUsers = array_diff($idUsers, array_pluck($exUserQueue, 'id_user'));

					$promotion['users'] = User::join('cities', 'cities.id_city', 'users.id_city')->whereIn('id', $idUsers)->get();
				}
			}

			$result = [
					'status'  => 'success',
					'result'  => $promotion
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Promotion Not Found']
				];
		}
		return response()->json($result);
    }

	public function update(Request $request){
		$post = $request->json()->all();
		$id_promotion = $post['id_promotion'];
		$id_content = [];

		DB::beginTransaction();

		$promotion = Promotion::where('id_promotion','=',$id_promotion)->first();

		unset($post['id_promotion']);

		if (isset($post['promotion_push_image'])) {
			foreach ($post['promotion_push_image'] as $keyImage => $valueImage) {
				$upload = MyHelper::uploadPhoto($valueImage, $path = 'img/push/', 600);
				if ($upload['status'] == "success") {
					$post['promotion_push_image'][$keyImage] = $upload['path'];
				} else{
					$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update Push Notification Image failed.']
						];
					return response()->json($result);
				}
			}

		}

		// return response()->json($post);
		$query = null;
		$arrayShorten = [];
		$contentWhatsapp = [];
		if(isset($post['promotion_series_days'])){
			foreach($post['promotion_series_days'] as $key => $row){
				$arrayShorten 					= [];
				$contentWhatsapp 				= [];
				$data 							= [];
				$data['id_promotion']			= $id_promotion;
				$data['promotion_series_days']	= $post['promotion_series_days'][$key];

				if(isset($post['promotion_channel'][$key]) && in_array('deals', $post['promotion_channel'][$key])){
					//get deals template
					$dealsTemplate = DealsPromotionTemplate::find($post['id_deals_promotion_template'][$key]);
					if(!$dealsTemplate){
						DB::rollBack();
						$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update Promotion Content Failed.', 'Deals Not Found.']
						];
						return response()->json($result);
					}
					$data['voucher_value']			= $dealsTemplate['deals_voucher_value'];
					$data['voucher_given']			= (int)$post['voucher_given'][$key];
					$data['id_deals_promotion_template'] = $post['id_deals_promotion_template'][$key];
				}

				if(isset($post['promotion_channel'][$key]) && in_array('email', $post['promotion_channel'][$key])){
					$data['promotion_channel_email']	= '1';
					$data['promotion_email_subject']	= $post['promotion_email_subject'][$key];
					$data['promotion_email_content']	= $post['promotion_email_content'][$key];

					preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $post['promotion_email_content'][$key], $match);
					if(count($match)>0){
						$match = array_unique($match[0]);
						foreach ($match as $value) {
							$cek = false;
							if(isset($post['id_promotion_content'][$key]) && $post['id_promotion_content'][$key] != "") {
								$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'][$key])
																			->where('type', 'email')->where('original_link', $value)->first();
								if($getShortLink){
									$cek = true;
									$dataLink['original_link'] 	= $value;
									$dataLink['short_link'] 	= $getShortLink->short_link;
									$dataLink['type'] 			= 'email';
									array_push($arrayShorten, $dataLink);
								}
							}
							if($cek == false){
								$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
								if(isset($shortLink['short'])){
									$dataLink['original_link'] = $value;
									$dataLink['short_link'] = $shortLink['short'];
									$dataLink['type'] = 'email';
									array_push($arrayShorten, $dataLink);
								}
							}
						}
					}

				} else {
					$data['promotion_channel_email']	= '0';
				}

				if(isset($post['promotion_channel'][$key]) && in_array('sms', $post['promotion_channel'][$key])){
					$data['promotion_channel_sms']	= '1';
					$data['promotion_sms_content']	= $post['promotion_sms_content'][$key];

					preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $post['promotion_sms_content'][$key], $match);
					if(count($match)>0){
						$match = array_unique($match[0]);
						foreach ($match as $value) {
							$cek = false;
							if(isset($post['id_promotion_content'][$key]) && $post['id_promotion_content'][$key] != "") {
								$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'][$key])
																			->where('type', 'sms')->where('original_link', $value)->first();
								if($getShortLink){
									$cek = true;
									$dataLink['original_link'] 	= $value;
									$dataLink['short_link'] 	= $getShortLink->short_link;
									$dataLink['type'] 			= 'sms';
									array_push($arrayShorten, $dataLink);
								}
							}
							if($cek == false){
								$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
								if(isset($shortLink['short'])){
									$dataLink['original_link'] = $value;
									$dataLink['short_link'] = $shortLink['short'];
									$dataLink['type'] = 'sms';
									array_push($arrayShorten, $dataLink);
								}
							}
						}
					}
				} else {
					$data['promotion_channel_sms']	= '0';
				}

				if(isset($post['promotion_channel'][$key]) && in_array('push', $post['promotion_channel'][$key])){
					$data['promotion_channel_push']	= '1';
					$data['promotion_push_subject']	= $post['promotion_push_subject'][$key];
					$data['promotion_push_content']	= $post['promotion_push_content'][$key];
					$data['promotion_push_clickto']	= $post['promotion_push_clickto'][$key];
					$data['promotion_push_link']	= $post['promotion_push_link'][$key];
					if(isset($post['promotion_push_id_reference'][$key])){
						$data['promotion_push_id_reference']	= $post['promotion_push_id_reference'][$key];
					}
					if(isset($post['promotion_push_image'][$key])){
						$data['promotion_push_image']	= $post['promotion_push_image'][$key];
					}

				} else {
					$data['promotion_channel_push']	= '0';
				}

				if(isset($post['promotion_channel'][$key]) && in_array('inbox', $post['promotion_channel'][$key])){
					$data['promotion_channel_inbox']	= '1';
					$data['promotion_inbox_subject']	= $post['promotion_inbox_subject'][$key];
					$data['promotion_inbox_clickto']	= $post['promotion_inbox_clickto'][$key];
					if(isset($post['promotion_inbox_id_reference'][$key])){
						$data['promotion_inbox_id_reference']	= $post['promotion_inbox_id_reference'][$key];
					}else{
						$data['promotion_inbox_id_reference'] = null;
					}
					if(isset($post['promotion_inbox_link'][$key])){
						$data['promotion_inbox_link']	= $post['promotion_inbox_link'][$key];
					}else{
						$data['promotion_inbox_link'] = null;
					}
					if(isset($post['promotion_inbox_content'][$key])){
						$data['promotion_inbox_content']	= $post['promotion_inbox_content'][$key];
					}else{
						$data['promotion_inbox_content'] = null;
					}

				} else {
					$data['promotion_channel_inbox']	= '0';
				}

				if(isset($post['promotion_channel'][$key]) && in_array('whatsapp', $post['promotion_channel'][$key])){
					$data['promotion_channel_whatsapp']	= '1';

					//whatsapp contents
					$contentWa = $post['promotion_whatsapp_content'][$key];
					if($contentWa){

						if(isset($post['id_promotion_content'][$key])){
							//delete content
							$idOld = array_filter(array_pluck($contentWa,'id_whatsapp_content'));

							$contentOld = WhatsappContent::where('source', 'promotion')->where('id_reference', $post['id_promotion_content'][$key])->whereNotIn('id_whatsapp_content', $idOld)->get();
							if(count($contentOld) > 0){
								foreach($contentOld as $old){
									if($old['content_type'] == 'image' || $old['content_type'] == 'file'){
										MyHelper::deletePhoto(str_replace(env('S3_URL_API'), '', $old['content']));
									}
								}

								$delete =  WhatsappContent::where('source', 'promotion')->where('id_reference', $post['id_promotion_content'][$key])->whereNotIn('id_whatsapp_content', $idOld)->delete();
								if(!$delete){
									DB::rollBack();
									$result = [
											'status'	=> 'fail',
											'messages'	=> ['Update WhatsApp Content Failed.']
										];
									return response()->json($result);
								}
							}
						}


						//create or update content
						foreach($contentWa as $content){

							if($content['content']){
								//delete file if update
								if($content['id_whatsapp_content']){
									$waContent = WhatsappContent::find($content['id_whatsapp_content']);
									if($waContent && ($waContent->content_type == 'image' || $waContent->content_type == 'file')){
										MyHelper::deletePhoto(str_replace(env('S3_URL_API'), '', $waContent->content));
									}
								}

								if($content['content_type'] == 'image'){
									if (!file_exists('whatsapp/img/promotion/')) {
										mkdir('whatsapp/img/promotion/', 0777, true);
									}

									//upload file
									$upload = MyHelper::uploadPhoto($content['content'], $path = 'whatsapp/img/promotion/');
									if ($upload['status'] == "success") {
										$content['content'] = env('S3_URL_API').$upload['path'];
									} else{
										DB::rollBack();
										$result = [
												'status'	=> 'fail',
												'messages'	=> ['Update WhatsApp Content Image Failed.']
											];
										return response()->json($result);
									}
								}
								else if($content['content_type'] == 'file'){
									if (!file_exists('whatsapp/file/promotion/')) {
										mkdir('whatsapp/file/promotion/', 0777, true);
									}

									$i = 1;
									$filename = $content['content_file_name'];
									while (file_exists('whatsapp/file/promotion/'.$content['content_file_name'].'.'.$content['content_file_ext'])) {
										$content['content_file_name'] = $filename.'_'.$i;
										$i++;
									}

									$upload = MyHelper::uploadFile($content['content'], $path = 'whatsapp/file/promotion/', $content['content_file_ext'], $content['content_file_name']);
									if ($upload['status'] == "success") {
										$content['content'] = env('S3_URL_API').$upload['path'];
									} else{
										DB::rollBack();
										$result = [
												'status'	=> 'fail',
												'messages'	=> ['Update WhatsApp Content File Failed.']
											];
										return response()->json($result);
									}
								}
								else if($content['content_type'] == 'text'){
									preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $content['content'], $match);
									if(count($match)>0){
										$match = array_unique($match[0]);
										foreach ($match as $value) {
											$cek = false;
											if(isset($post['id_promotion_content'][$key])) {
												$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'][$key])
																							->where('type', 'whatsapp')->where('original_link', $value)->first();
												if($getShortLink){
													$cek = true;
													$dataLink['original_link'] 	= $value;
													$dataLink['short_link'] 	= $getShortLink->short_link;
													$dataLink['type'] 			= 'whatsapp';
													array_push($arrayShorten, $dataLink);
												}
											}
											if($cek == false){
												$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
												if(isset($shortLink['short'])){
													$dataLink['original_link'] = $value;
													$dataLink['short_link'] = $shortLink['short'];
													$dataLink['type'] = 'whatsapp';
													array_push($arrayShorten, $dataLink);
												}
											}
										}
									}
								}

								if(isset($post['id_promotion_content'][$key])) {
									$dataContent['id_reference'] = $post['id_promotion_content'][$key];
								}

								$dataContent['id_whatsapp_content'] = $content['id_whatsapp_content'];
								$dataContent['source'] 		 		= 'promotion';
								$dataContent['content_type'] 		= $content['content_type'];
								$dataContent['content'] 	 		= $content['content'];

								array_push($contentWhatsapp, $dataContent);
							}

						}
					}

				} else {
					$data['promotion_channel_whatsapp']	= '0';
				}

				// update or create promotion content
				if(isset($post['id_promotion_content'][$key]) && $post['id_promotion_content'][$key] != "") {
					$query = PromotionContent::where('id_promotion_content','=',$post['id_promotion_content'][$key])->first();

					if(isset($post['promotion_push_image'][$key]) && $query['promotion_push_image'][$key] != null){
						unlink($query['promotion_push_image']);
					}
					$query = $query->update($data);

					$id_promotion_content = $post['id_promotion_content'][$key];
					array_push($id_content, $id_promotion_content);

					if($query){
						$deleteShortLink = PromotionContentShortenLink::where('id_promotion_content', $id_promotion_content)->delete();
					}
				} else {
					$query = PromotionContent::create($data);
					$id_promotion_content = $query->id_promotion_content;
					array_push($id_content, $id_promotion_content);
				}

				if($query){
					if(count($arrayShorten) > 0){
						foreach ($arrayShorten as $j => $value) {
							$arrayShorten[$j]['id_promotion_content'] = $id_promotion_content;
						}
						$insertShorten = PromotionContentShortenLink::insert($arrayShorten);
					}

					//update or create whatsapp content
					// $a = [$contentWhatsapp];
					// return $a;
					foreach($contentWhatsapp as $waContent){
						if(!isset($waContent['id_reference'])){
							$waContent['id_reference'] = $id_promotion_content;
						}
						// return$waContent['id_whatsapp_content'];
						//update
						if(isset($waContent['id_whatsapp_content']) && $waContent['id_whatsapp_content'] && $waContent['content']){
							$insertWhatsapp = WhatsappContent::where('id_whatsapp_content', (int)$waContent['id_whatsapp_content'])->update($waContent);
						}
						//create
						else{
							$insertWhatsapp = WhatsappContent::create($waContent);
						}

						if(!$insertWhatsapp){
							DB::rollBack();
							$result = [
								'status'	=> 'fail',
								'messages'	=> ['Update WhatsApp Content Failed.']
							];
							return response()->json($result);
						}
					}
				}else{
					DB::rollBack();
					$result = [
						'status'	=> 'fail',
						'messages'	=> ['Update Promotion Content Failed.']
					];
					return response()->json($result);
				}

				// return response()->json($query);
				if(isset($post['promotion_channel'][$key]) && in_array('deals', $post['promotion_channel'][$key])){
					//kalo ada deals
					$dataDeals 							= [];
					$dataDeals['deals_type']			= "Promotion";

					//get deals template
					$dealsTemplate = DealsPromotionTemplate::find($post['id_deals_promotion_template'][$key]);
					$dataDeals['deals_promo_id_type']	= $dealsTemplate['deals_promo_id_type'];

					if($post['voucher_type_autogenerated'][$key] != ""){
						$dataDeals['deals_voucher_type']	= "Auto generated";
						$dataDeals['deals_total_voucher']	= $post['voucher_type_autogenerated'][$key];
					}elseif($post['voucher_type_listvoucher'][$key] != ""){
						$dataDeals['deals_voucher_type']	= "List Vouchers";
						$ex	= explode(PHP_EOL,$post['voucher_type_listvoucher'][$key]);
						$total = count($ex);
						$dataDeals['deals_total_voucher']	= $total;
					}else{
						$dataDeals['deals_voucher_type']	= "Unlimited";
						$dataDeals['deals_total_voucher']	= 0;
					}

					$dataDeals['deals_promo_id_type']	= $dealsTemplate['deals_promo_id_type'];
					if($dealsTemplate['deals_promo_id_type'] == 'promoid'){
						$dataDeals['deals_promo_id']	= $dealsTemplate['deals_promo_id'];
					}else{
						$dataDeals['deals_promo_id']	= $dealsTemplate['deals_nominal'];
					}
					$dataDeals['deals_title'] = $dealsTemplate['deals_title'];
					$dataDeals['deals_second_title'] = $dealsTemplate['deals_second_title'];
					$dataDeals['deals_description'] = $dealsTemplate['deals_description'];
					$dataDeals['deals_short_description'] = $dealsTemplate['deals_short_description'];
					$dataDeals['deals_image'] = $dealsTemplate['deals_image'];
					$dataDeals['deals_start'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_start']));
					$dataDeals['deals_end'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_end']));
					if($dealsTemplate['deals_voucher_duration'] != null){
						$dataDeals['deals_voucher_duration'] = $dealsTemplate['deals_voucher_duration'];
						$dataDeals['deals_voucher_expired'] = null;
					}
					else {
						$dataDeals['deals_voucher_duration'] = null;
						$dataDeals['deals_voucher_expired'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_voucher_expired']));
					}
					// return response()->json($dataDeals);
					 // return response()->json($dataDeals);
					$mark = 'insert';
					if(isset($post['id_deals'][$key]) && $post['id_deals'][$key] != "") {
						$dealsQuery = Deal::where('id_deals','=',$post['id_deals'][$key])->update($dataDeals);
						$id_deals = $post['id_deals'][$key];
						$mark = 'update';
					} else {
						$dealsQuery = Deal::create($dataDeals);
						$id_deals = $dealsQuery->id_deals;
					}


					if($dealsQuery){
						if($post['voucher_type_listvoucher'][$key] != ""){
							$ex	= explode(PHP_EOL,$post['voucher_type_listvoucher'][$key]);
							if($mark == 'insert'){
								foreach($ex as $voucher){
									$dataDealsVoucher = [];
									$dataDealsVoucher['id_deals'] = $id_deals;
									$dataDealsVoucher['voucher_code'] = $voucher;
									$dataDealsVoucher['deals_voucher_status'] = "Available";

									$queryDealsVoucher = DealsVoucher::create($dataDealsVoucher);
									if(!$queryDealsVoucher){
										DB::rollBack();
										$result = [
											'status'	=> 'fail',
											'messages'	=> ['Update Deals Voucher Failed.']
										];
										return response()->json($result);
									}
								}
							} else {
								$vou = DealsVoucher::where('id_deals','=',$id_deals)->get()->toArray();

								$u = array();
								foreach($vou as $v){
									if(!in_array($v['voucher_code'],$ex)){
										DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
													->whereNull('deals_users.id_deals_voucher')
													->where('deals_vouchers.voucher_code','=',$v['voucher_code'])
													->delete();
									}
									array_push($u,$v['voucher_code']);
								}
								// return response()->json($ex);
								foreach($ex as $voucher){
									if(!in_array($voucher,$u)){
										$dataDealsVoucher = [];
										$dataDealsVoucher['id_deals'] = $id_deals;
										$dataDealsVoucher['voucher_code'] = $voucher;
										$dataDealsVoucher['deals_voucher_status'] = "Available";

										$queryDealsVoucher = DealsVoucher::create($dataDealsVoucher);
										if(!$queryDealsVoucher){
											DB::rollBack();
											$result = [
												'status'	=> 'fail',
												'messages'	=> ['Update Deals Voucher Failed.']
											];
											return response()->json($result);
										}
									}
								}
							}
						}

						if($post['voucher_type_autogenerated'][$key] != ""){
							DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
										->whereNull('deals_users.id_deals_voucher')
										->where('deals_vouchers.id_deals','=',$id_deals)
										->delete();

							$delsVoucherUser = DealsVoucher::where('id_deals','=',$id_deals)->get();
							$post['voucher_type_autogenerated'][$key]	= (int)$post['voucher_type_autogenerated'][$key] - count($delsVoucherUser);
							if($post['voucher_type_autogenerated'][$key] > 0){
								$save = app($this->dealsVoucher)->generateVoucher($id_deals, $post['voucher_type_autogenerated'][$key]);
							}
						}

						if($dataDeals['deals_voucher_type'] == 'Unlimited'){
							DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
										->whereNull('deals_users.id_deals_voucher')
										->where('deals_vouchers.id_deals','=',$id_deals)
										->delete();
						}

						if($dealsTemplate['deals_list_outlet'] != ""){
							$deleteDealsOutlet =  DealsOutlet::where('id_deals', $id_deals)->delete();
							if (in_array("all", explode(',',$dealsTemplate['deals_list_outlet']))) {
								/* SELECT ALL OUTLET */
								$post['id_outlet'][$key] = Outlet::select('id_outlet')->get()->toArray();

								if (empty($post['id_outlet'][$key])) {
									return false;
								}
								else {
									$post['id_outlet'][$key] = array_pluck($post['id_outlet'][$key], 'id_outlet');
								}
							}else{
								$post['id_outlet'][$key] = explode(',',$dealsTemplate['deals_list_outlet']);
							}

							foreach($post['id_outlet'][$key] as $id_outlet){
								$dataDealsOutlet = [];
								$dataDealsOutlet['id_deals'] = $id_deals;
								$dataDealsOutlet['id_outlet'] = $id_outlet;

								$queryDealsVoucher = DealsOutlet::create($dataDealsOutlet);
								if(!$queryDealsVoucher){
									DB::rollBack();
									$result = [
										'status'	=> 'fail',
										'messages'	=> ['Update Deals Voucher Failed.']
									];
									return response()->json($result);
								}
							}
						}

						$updatePromotion = PromotionContent::where('id_promotion_content','=',$id_promotion_content)->update(['id_deals' => $id_deals]);
						// if(!$updatePromotion){
						// 	DB::rollBack();
						// 	$result = [
						// 		'status'	=> 'fail',
						// 		'messages'	=> ['Update Promotion Content Deals Failed.']
						// 	];
						// 	return response()->json($result);
						// }
					}
					else{
						DB::rollBack();
						$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update Deals Failed.']
						];
						return response()->json($result);
					}

				}else{
					$promoContent = PromotionContent::where('id_promotion_content','=',$id_promotion_content)->first();
					if($promoContent->id_deals != null){
						$deal = Deal::where('id_deals', $promoContent->id_deals)->first();
						if($deal->total_claimed == 0 && $promoContent->promotion_count_voucher_give == 0){
							DealsOutlet::where('id_deals', $promoContent->id_deals)->delete();
							DealsVoucher::where('id_deals', $promoContent->id_deals)->delete();
							$delete = Deal::where('id_deals', $promoContent->id_deals)->delete();

							if($delete){
								$promoContent->update(['id_deals' => null]);
								if(!$promoContent){
									DB::rollBack();
									$result = [
										'status'	=> 'fail',
										'messages'	=> ['Update Promotion Content Deals Failed.']
									];
									return response()->json($result);
								}
							}
						}
					}
				}
			}
		} else {
			$data 							= [];
			$data['id_promotion']			= $id_promotion;
			$data['promotion_series_days']	= 0;

			if(isset($post['promotion_channel'][0]) && in_array('deals', $post['promotion_channel'][0])){
				//get deals template
				$dealsTemplate = DealsPromotionTemplate::find($post['id_deals_promotion_template'][0]);
				if(!$dealsTemplate){
					DB::rollBack();
					$result = [
						'status'	=> 'fail',
						'messages'	=> ['Update Promotion Content Failed.', 'Deals Not Found.']
					];
					return response()->json($result);
				}
				$data['voucher_value']			= $dealsTemplate['deals_voucher_value'];
				$data['voucher_given']			= (int)$post['voucher_given'][0];
				$data['id_deals_promotion_template'] = $post['id_deals_promotion_template'][0];
			}

			if(isset($post['promotion_channel'][0]) && in_array('email', $post['promotion_channel'][0])){
				$data['promotion_channel_email']	= '1';
				$data['promotion_email_subject']	= $post['promotion_email_subject'][0];
				$data['promotion_email_content']	= $post['promotion_email_content'][0];

				preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $post['promotion_email_content'][0], $match);
				if(count($match)>0){
					$match = array_unique($match[0]);
					foreach ($match as $value) {
						$cek = false;
						if(isset($post['id_promotion_content'])) {
							$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'])
																		->where('type', 'email')->where('original_link', $value)->first();
							if($getShortLink){
								$cek = true;
								$dataLink['original_link'] = $value;
								$dataLink['short_link'] = $getShortLink->short_link;
								$dataLink['type'] = 'email';
								array_push($arrayShorten, $dataLink);
							}
						}
						if($cek == false){
							$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
							if(isset($shortLink['short'])){
								$dataLink['original_link'] = $value;
								$dataLink['short_link'] = $shortLink['short'];
								$dataLink['type'] = 'email';
								array_push($arrayShorten, $dataLink);
							}
						}
					}
				}

			} else {
				$data['promotion_channel_email']	= '0';
			}

			if(isset($post['promotion_channel'][0]) && in_array('sms', $post['promotion_channel'][0])){
				$data['promotion_channel_sms']	= '1';
				$data['promotion_sms_content']	= $post['promotion_sms_content'][0];

				preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $post['promotion_sms_content'][0], $match);
				if(count($match)>0){
					$match = array_unique($match[0]);
					foreach ($match as $value) {
						$cek = false;
						if(isset($post['id_promotion_content'])) {
							$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'])
																		->where('type', 'sms')->where('original_link', $value)->first();
							if($getShortLink){
								$cek = true;
								$dataLink['original_link'] 	= $value;
								$dataLink['short_link'] 	= $getShortLink->short_link;
								$dataLink['type'] 			= 'sms';
								array_push($arrayShorten, $dataLink);
							}
						}
						if($cek == false){
							$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
							if(isset($shortLink['short'])){
								$dataLink['original_link'] = $value;
								$dataLink['short_link'] = $shortLink['short'];
								$dataLink['type'] = 'sms';
								array_push($arrayShorten, $dataLink);
							}
						}
					}
				}
			} else {
				$data['promotion_channel_sms']	= '0';
			}

			if(isset($post['promotion_channel'][0]) && in_array('push', $post['promotion_channel'][0])){
				$data['promotion_channel_push']	= '1';
				$data['promotion_push_subject']	= $post['promotion_push_subject'][0];
				$data['promotion_push_content']	= $post['promotion_push_content'][0];
				$data['promotion_push_clickto']	= $post['promotion_push_clickto'][0];
				if(isset($post['promotion_push_link'][0])){
					$data['promotion_push_link'] = $post['promotion_push_link'][0];
				}else{
					$data['promotion_push_link'] = null;
				}
				if(isset($post['promotion_push_id_reference'][0])){
					$data['promotion_push_id_reference'] = $post['promotion_push_id_reference'][0];
				}else{
					$data['promotion_push_id_reference'] = null;
				}
				if(isset($post['promotion_push_image'][0])){
					$data['promotion_push_image']	= $post['promotion_push_image'][0];
				}

			} else {
				$data['promotion_channel_push']	= '0';
			}

			if(isset($post['promotion_channel'][0]) && in_array('inbox', $post['promotion_channel'][0])){
				$data['promotion_channel_inbox']	= '1';
				$data['promotion_inbox_subject']	= $post['promotion_inbox_subject'][0];
				$data['promotion_inbox_clickto']	= $post['promotion_inbox_clickto'][0];
				if(isset($post['promotion_inbox_id_reference'][0])){
					$data['promotion_inbox_id_reference']	= $post['promotion_inbox_id_reference'][0];
				}else{
					$data['promotion_inbox_id_reference'] = null;
				}
				if(isset($post['promotion_inbox_link'][0])){
					$data['promotion_inbox_link']	= $post['promotion_inbox_link'][0];
				}else{
					$data['promotion_inbox_link'] = null;
				}
				if(isset($post['promotion_inbox_content'][0])){
					$data['promotion_inbox_content']	= $post['promotion_inbox_content'][0];
				}else{
					$data['promotion_inbox_content'] = null;
				}

			} else {
				$data['promotion_channel_inbox']	= '0';
			}

			if(isset($post['promotion_channel'][0]) && in_array('whatsapp', $post['promotion_channel'][0])){
				$data['promotion_channel_whatsapp']	= '1';
				$contentWa = $post['promotion_whatsapp_content'][0];
				//whatsapp contents
				if($contentWa){

					if(isset($post['id_promotion_content'])){
						//delete content
						$idOld = array_filter(array_pluck($contentWa,'id_whatsapp_content'));
						$contentOld = WhatsappContent::where('source', 'promotion')->where('id_reference', $post['id_promotion_content'])->whereNotIn('id_whatsapp_content', $idOld)->get();
						if(count($contentOld) > 0){
							foreach($contentOld as $old){
								if($old['content_type'] == 'image' || $old['content_type'] == 'file'){
									MyHelper::deletePhoto(str_replace(env('S3_URL_API'), '', $old['content']));
								}
							}

							$delete = WhatsappContent::where('source', 'promotion')->where('id_reference', $post['id_promotion_content'])->whereNotIn('id_whatsapp_content', $idOld)->delete();
							if(!$delete){
								DB::rollBack();
								$result = [
										'status'	=> 'fail',
										'messages'	=> ['Update WhatsApp Content Failed.3']
									];
								return response()->json($result);
							}
						}
					}


					//create or update content
					foreach($contentWa as $content){

						if($content['content']){
							//delete file if update
							if($content['id_whatsapp_content']){
								$waContent = WhatsappContent::find($content['id_whatsapp_content']);
								if($waContent && ($waContent->content_type == 'image' || $waContent->content_type == 'file')){
									MyHelper::deletePhoto(str_replace(env('S3_URL_API'), '', $waContent->content));
								}
							}

							if($content['content_type'] == 'image'){
								if (!file_exists('whatsapp/img/promotion/')) {
									mkdir('whatsapp/img/promotion/', 0777, true);
								}

								//upload file
								$upload = MyHelper::uploadPhoto($content['content'], $path = 'whatsapp/img/promotion/');
								if ($upload['status'] == "success") {
									$content['content'] = env('S3_URL_API').$upload['path'];
								} else{
									DB::rollBack();
									$result = [
											'status'	=> 'fail',
											'messages'	=> ['Update WhatsApp Content Image Failed.']
										];
									return response()->json($result);
								}
							}
							else if($content['content_type'] == 'file'){
								if (!file_exists('whatsapp/file/promotion/')) {
									mkdir('whatsapp/file/promotion/', 0777, true);
								}

								$i = 1;
								$filename = $content['content_file_name'];
								while (file_exists('whatsapp/file/promotion/'.$content['content_file_name'].'.'.$content['content_file_ext'])) {
									$content['content_file_name'] = $filename.'_'.$i;
									$i++;
								}

								$upload = MyHelper::uploadFile($content['content'], $path = 'whatsapp/file/promotion/', $content['content_file_ext'], $content['content_file_name']);
								if ($upload['status'] == "success") {
									$content['content'] = env('S3_URL_API').$upload['path'];
								} else{
									DB::rollBack();
									$result = [
											'status'	=> 'fail',
											'messages'	=> ['Update WhatsApp Content File Failed.']
										];
									return response()->json($result);
								}
							}
							else if($content['content_type'] == 'text'){
								preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $content['content'], $match);
								if(count($match)>0){
									$match = array_unique($match[0]);
									foreach ($match as $value) {
										$cek = false;
										if(isset($post['id_promotion_content'])) {
											$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'])
																						->where('type', 'whatsapp')->where('original_link', $value)->first();
											if($getShortLink){
												$cek = true;
												$dataLink['original_link'] 	= $value;
												$dataLink['short_link'] 	= $getShortLink->short_link;
												$dataLink['type'] 			= 'whatsapp';
												array_push($arrayShorten, $dataLink);
											}
										}
										if($cek == false){
											$shortLink = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$value);
											if(isset($shortLink['short'])){
												$dataLink['original_link'] 	= $value;
												$dataLink['short_link'] 	= $shortLink['short'];
												$dataLink['type'] 			= 'whatsapp';
												array_push($arrayShorten, $dataLink);
											}
										}
									}
								}
							}

							if(isset($post['id_promotion_content'])) {
								$dataContent['id_reference'] = $post['id_promotion_content'];
							}

							$dataContent['source'] 		 = 'promotion';
							$dataContent['content_type'] = $content['content_type'];
							$dataContent['content'] 	 = $content['content'];

							array_push($contentWhatsapp, $dataContent);
						}

					}
				}

			} else {
				$data['promotion_channel_whatsapp']	= '0';
			}

			if(isset($post['id_promotion_content'])) {
				$query = PromotionContent::where('id_promotion_content','=',$post['id_promotion_content'])->first();
				if(isset($post['promotion_push_image'][0]) && $query['promotion_push_image'] != null){
					unlink($query['promotion_push_image']);
				}

				$query = $query->update($data);

				$id_promotion_content = $post['id_promotion_content'];
				array_push($id_content, $id_promotion_content);

				if($query){
					$deleteShortLink = PromotionContentShortenLink::where('id_promotion_content', $id_promotion_content)->delete();
				}
			} else {
				$query = PromotionContent::create($data);
				$id_promotion_content = $query->id_promotion_content;
				array_push($id_content, $id_promotion_content);
			}

			if($query){
				if(count($arrayShorten) > 0){
					foreach ($arrayShorten as $key => $value) {
						$arrayShorten[$key]['id_promotion_content'] = $id_promotion_content;
					}

					$insertShorten = PromotionContentShortenLink::insert($arrayShorten);
				}

				//update or create whatsapp content
				foreach($contentWhatsapp as $waContent){
					if(!isset($waContent['id_reference'])){
						$waContent['id_reference'] = $id_promotion_content;
					}

					//update
					if(isset($waContent['id_whatsapp_content']) && $waContent['id_whatsapp_content']){
						$insertWhatsapp = WhatsappContent::where('id_whatsapp_content', $waContent['id_whatsapp_content'])->update($waContent);
					}
					//create
					else{
						$insertWhatsapp = WhatsappContent::create($waContent);
					}

					if(!$insertWhatsapp){
						DB::rollBack();
						$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update WhatsApp Content Failed.4']
						];
						return response()->json($result);
					}
				}
			}else{
				DB::rollBack();
				$result = [
					'status'	=> 'fail',
					'messages'	=> ['Update Promotion Content Failed.']
				];
				return response()->json($result);
			}

			if(isset($post['promotion_channel'][0]) && in_array('deals', $post['promotion_channel'][0])){
				//kalo ada deals
				$dataDeals 							= [];

				//get deals template
				$dealsTemplate = DealsPromotionTemplate::find($post['id_deals_promotion_template'][0]);
				$dataDeals['deals_type']			= "Promotion";
				$dataDeals['deals_promo_id_type']	= $dealsTemplate['deals_promo_id_type'];

				if($post['voucher_type_autogenerated'][0] != ""){
					$dataDeals['deals_voucher_type']	= "Auto generated";
					$dataDeals['deals_total_voucher']	= $post['voucher_type_autogenerated'][0];
				}elseif($post['voucher_type_listvoucher'][0] != ""){
					$dataDeals['deals_voucher_type']	= "List Vouchers";
					$ex	= explode(PHP_EOL,$post['voucher_type_listvoucher'][0]);
					$total = count($ex);
					$dataDeals['deals_total_voucher']	= $total;
				}else{
					$dataDeals['deals_voucher_type']	= "Unlimited";
					$dataDeals['deals_total_voucher']	= 0;
				}

				$dataDeals['deals_promo_id_type']	= $dealsTemplate['deals_promo_id_type'];
				if($dealsTemplate['deals_promo_id_type'] == 'promoid'){
					$dataDeals['deals_promo_id']	= $dealsTemplate['deals_promo_id'];
				}else{
					$dataDeals['deals_promo_id']	= $dealsTemplate['deals_nominal'];
				}

				$dataDeals['deals_title'] = $dealsTemplate['deals_title'];
				$dataDeals['deals_second_title'] = $dealsTemplate['deals_second_title'];
				$dataDeals['deals_description'] = $dealsTemplate['deals_description'];
				$dataDeals['deals_short_description'] = $dealsTemplate['deals_short_description'];
				$dataDeals['deals_image'] = $dealsTemplate['deals_image'];
				$dataDeals['deals_start'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_start']));
				$dataDeals['deals_end'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_end']));
				if($dealsTemplate['deals_voucher_duration'] != null){
					$dataDeals['deals_voucher_duration'] = $dealsTemplate['deals_voucher_duration'];
					$dataDeals['deals_voucher_expired'] = null;
				}
				else {
					$dataDeals['deals_voucher_duration'] = null;
					$dataDeals['deals_voucher_expired'] = date('Y-m-d H:i:s', strtotime($dealsTemplate['deals_voucher_expired']));
				}

				$mark = 'insert';
				if(isset($post['id_deals'][0]) && $post['id_deals'][0] != "") {
					$dealsQuery = Deal::where('id_deals','=',$post['id_deals'][0])->update($dataDeals);
					$id_deals = $post['id_deals'][0];
					$mark = 'update';
				} else {
					$dealsQuery = Deal::create($dataDeals);

					if(!$dealsQuery){
						DB::rollBack();
						$result = [
							'status'	=> 'fail',
							'messages'	=> ['Update Promotion Content Deals Failed.']
						];
						return response()->json($result);
					}

					$id_deals = $dealsQuery->id_deals;
				}


				if($dealsQuery){
					if($post['voucher_type_listvoucher'][0] != ""){
						$ex	= explode(PHP_EOL,$post['voucher_type_listvoucher'][0]);
						if($mark == 'insert'){
							foreach($ex as $voucher){
								$dataDealsVoucher = [];
								$dataDealsVoucher['id_deals'] = $id_deals;
								$dataDealsVoucher['voucher_code'] = $voucher;
								$dataDealsVoucher['deals_voucher_status'] = "Available";

								$queryDealsVoucher = DealsVoucher::create($dataDealsVoucher);
							}
						} else {
							$vou = DealsVoucher::where('id_deals','=',$id_deals)->get()->toArray();

							$u = array();
							foreach($vou as $v){
								if(!in_array($v['voucher_code'],$ex)){
									DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
												->whereNull('deals_users.id_deals_voucher')
												->where('deals_vouchers.voucher_code','=',$v['voucher_code'])
												->delete();
								}
								array_push($u,$v['voucher_code']);
							}
							// return response()->json($ex);
							foreach($ex as $voucher){
								if(!in_array($voucher,$u)){
									$dataDealsVoucher = [];
									$dataDealsVoucher['id_deals'] = $id_deals;
									$dataDealsVoucher['voucher_code'] = $voucher;
									$dataDealsVoucher['deals_voucher_status'] = "Available";

									$queryDealsVoucher = DealsVoucher::create($dataDealsVoucher);
									if(!$queryDealsVoucher){
										DB::rollBack();
										$result = [
											'status'	=> 'fail',
											'messages'	=> ['Update Promotion Content Deals Failed.']
										];
										return response()->json($result);
									}
								}
							}
						}
					}

					if($post['voucher_type_autogenerated'][0] != ""){
						DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
									->whereNull('deals_users.id_deals_voucher')
									->where('deals_vouchers.id_deals','=',$id_deals)
									->delete();

						$delsVoucherUser = DealsVoucher::where('id_deals','=',$id_deals)->get();
						$post['voucher_type_autogenerated'][0]	= (int)$post['voucher_type_autogenerated'][0] - count($delsVoucherUser);
						if($post['voucher_type_autogenerated'][0] > 0){
							$save = app($this->dealsVoucher)->generateVoucher($id_deals, $post['voucher_type_autogenerated'][0]);
						}
					}

					if($dataDeals['deals_voucher_type'] == 'Unlimited'){
						DealsVoucher::leftJoin('deals_users','deals_users.id_deals_voucher','=','deals_vouchers.id_deals_voucher')
									->whereNull('deals_users.id_deals_voucher')
									->where('deals_vouchers.id_deals','=',$id_deals)
									->delete();
					}

					if($dealsTemplate['deals_list_outlet'] != ""){
						$deleteDealsOutlet =  DealsOutlet::where('id_deals', $id_deals)->delete();
						if (in_array("all", explode(',',$dealsTemplate['deals_list_outlet']))) {
							/* SELECT ALL OUTLET */
							$post['id_outlet'][0] = Outlet::select('id_outlet')->get()->toArray();

							if (empty($post['id_outlet'][0])) {
								return false;
							}
							else {
								$post['id_outlet'][0] = array_pluck($post['id_outlet'][0], 'id_outlet');
							}

						}else{
							$post['id_outlet'][0] = explode(',',$dealsTemplate['deals_list_outlet']);
						}

						foreach($post['id_outlet'][0] as $id_outlet){
							$dataDealsOutlet = [];
							$dataDealsOutlet['id_deals'] = $id_deals;
							$dataDealsOutlet['id_outlet'] = $id_outlet;

							$queryDealsOutlet = DealsOutlet::create($dataDealsOutlet);
							if(!$queryDealsOutlet){
								DB::rollBack();
								$result = [
									'status'	=> 'fail',
									'messages'	=> ['Update Promotion Content Deals Failed.']
								];
								return response()->json($result);
							}
						}
					}
					$updatePromotion = PromotionContent::where('id_promotion_content','=',$id_promotion_content)->update(['id_deals' => $id_deals]);
				}
			}else{
				$promoContent = PromotionContent::where('id_promotion_content','=',$id_promotion_content)->first();
				if($promoContent->id_deals != null){
					$deal = Deal::where('id_deals', $promoContent->id_deals)->first();
					if($deal->total_claimed == 0 && $promoContent->promotion_count_voucher_give == 0){
						DealsOutlet::where('id_deals', $promoContent->id_deals)->delete();
						DealsVoucher::where('id_deals', $promoContent->id_deals)->delete();
						$delete = Deal::where('id_deals', $promoContent->id_deals)->delete();

						if($delete){
							$promoContent->update(['id_deals' => null]);
							if(!$promoContent){
								DB::rollBack();
								$result = [
									'status'	=> 'fail',
									'messages'	=> ['Update Promotion Content Deals Failed.']
								];
								return response()->json($result);
							}
						}
					}
				}
			}

		}

		if(isset($id_content)){
			$deleteDeals = Deal::whereIn('id_deals', function($query) use($id_promotion, $id_content){
								$query->from('promotion_contents')
									  ->where('id_promotion', $id_promotion)
									  ->whereNotIn('id_promotion_content', $id_content)
									  ->whereNotNull('id_deals')
									  ->select('id_deals');
							})->where('deals_total_claimed', 0)->delete();
			$deleteContent = PromotionContent::where('id_promotion', $id_promotion)->whereNotIn('id_promotion_content', $id_content)->delete();
		}

		DB::commit();
		$result = [
				'status'  => 'success',
				'result'  => $query
			];
		return response()->json($result);
	}

	public function addPromotionQueue(Request $request)
    {
		$timeNow = date('H:i:00');
		$timeNow2 = date('H:i:00', strtotime('-5 minutes', strtotime(date('Y-m-d H:i:00'))));

		$post = $request->json()->all();
		$countUser = 0;
		if(isset($post['id_promotion'])){
			$promotions = Promotion::where('id_promotion', $post['id_promotion'])->get();
			if(!$promotions){
				return response()->json([
					'status'  => 'fail',
					'messages'  => ['Promotion Not Found']
				]);
			}
		}else{
			$promotions = Promotion::join('promotion_schedules', 'promotions.id_promotion', 'promotion_schedules.id_promotion')
									->where('promotion_type', '!=', 'Instant Campaign')
									->where('schedule_time', '<=', $timeNow)
									->where('schedule_time', '>', $timeNow2)
									->where(function ($query) {
										$query->where('schedule_exact_date', '=', date('Y-m-d'))
										->orWhere('schedule_date_every_month', '=', date('d'))
										->orWhere('schedule_date_month', '=', date('d-m'))
										->orWhere(function ($q) {
											$q->where('schedule_day_every_week', '=', date('l'))
												->where('schedule_week_in_month', '=', 0);
										})
										->orWhere(function ($q) {
											$q->where('schedule_day_every_week', '=', date('l'))
												->where('schedule_week_in_month', '=', $this->getWeek());
										})
										->orWhere('schedule_everyday', '=', 'Yes');
									})
									->get();
									// return $promotions;

		}

		foreach ($promotions as $key => $promotion) {
			// check promotion content if exist
			if(count($promotion->contents) > 0){
				// get all users when there are no filters
				if(count($promotion->promotion_rule_parents) <= 0){
					$users = User::get();
				}
				// filter user
				else{
					$cond = Promotion::with(['promotion_rule_parents', 'promotion_rule_parents.rules'])->where('id_promotion','=',$promotion['id_promotion'])->first();
					$userFilter = app($this->user)->UserFilter($cond['promotion_rule_parents']);
					$users = [];
					if($userFilter){
						$users = $userFilter['result'];
						$exUserQueue = PromotionQueue::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();

						$idUsers = array_diff(array_pluck($users, 'id'), array_pluck($exUserQueue, 'id_user'));
						$users = User::whereIn('id', $idUsers)->get();
					}
					if($promotion['promotion_user_limit'] == '1' || $promotion['promotion_type'] == 'Campaign Series'){
						$exUser = PromotionSent::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();
						$exUserQueue = PromotionQueue::where('id_promotion_content', $promotion->contents[0]['id_promotion_content'])->select('id_user')->distinct()->get();

						$idUsers = array_diff(array_pluck($users, 'id'), array_pluck($exUser, 'id_user'));
						$idUsers = array_diff($idUsers, array_pluck($exUserQueue, 'id_user'));
						$users = User::whereIn('id', $idUsers)->get();
					}
				}

				$dataPromotionQueue = array();
				foreach($users as $key => $user){
					$queue['id_user'] = $user['id'];
					$queue['id_promotion_content'] = $promotion['contents'][0]['id_promotion_content'];
					$queue['send_at'] = date('Y-m-d').' '.$timeNow;
					$queue['created_at'] = date('Y-m-d H:i:s');
					$queue['updated_at'] = date('Y-m-d H:i:s');

					$dataPromotionQueue[] = $queue;
					$countUser++;
				}

				$insertPromotionQueue = PromotionQueue::insert($dataPromotionQueue);
			}

		}

		// send promotion series
		if(!isset($post['id_promotion'])){
			$promotionSeries = Promotion::join('promotion_schedules', 'promotions.id_promotion', 'promotion_schedules.id_promotion')
										->where('promotion_type', 'Campaign Series')
										->where('promotion_schedules.schedule_time', '<=', $timeNow)
										->where('promotion_schedules.schedule_time', '>=', $timeNow2)
										->get();
			// return $promotionSeries;
			foreach ($promotionSeries as $promotion) {
				// check promotion content if exist
				if(count($promotion->contents) > 0){
					foreach ($promotion->contents as $key => $content) {
						if($content['promotion_series_days'] > 0){
							$dateSend = date('Y-m-d', strtotime('-'.$content['promotion_series_days'].' days', strtotime(date('Y-m-d'))));
							$idUsers = PromotionSent::where('id_promotion_content', $promotion->contents[$key-1]['id_promotion_content'])
													->whereDate('send_at', $dateSend)
													->whereNotIn('id_user', function($q) use ($content){
														$q->select('id_user')->from('promotion_sents')->where('id_promotion_content', $content['id_promotion_content']);
													})
													->select('id_user')->distinct()->get();
							$users = User::whereIn('id', $idUsers)->get();

							if(count($users) > 0){
								$dataPromotionQueue = array();
								foreach($users as $user){
									$queue['id_user'] = $user['id'];
									$queue['id_promotion_content'] = $content['id_promotion_content'];
									$queue['send_at'] = date('Y-m-d').' '.$timeNow;
									$queue['created_at'] = date('Y-m-d H:i:s');
									$queue['updated_at'] = date('Y-m-d H:i:s');

									$dataPromotionQueue[] = $queue;
									$countUser++;
								}

								$insertPromotionQueue = PromotionQueue::insert($dataPromotionQueue);
							}
						}
					}
				}
			}
		}

		return ([
			'status'  => 'success',
			'result'  => 'Promotion queue has been added.',
			'count_user' => $countUser
		]);
	}

	public function sendPromotion(Request $request)
    {
		$now = date('Y-m-d H:i:s');
		$post = $request->json()->all();
		$countUser = 0;

		$queue = PromotionQueue::with(['content', 'content.promotion','user'])->where('send_at', '<=', $now)->orderBy('send_at', 'ASC')->limit(100)->get();

		$dataPromotionSent = array();
		foreach($queue as $key => $dataQueue){
			$idref = null;
			if($dataQueue['content']['id_deals'] != null){
				$sendDeals = $this->sendDeals($dataQueue['content']['id_promotion_content'],$dataQueue['user']);
				if(isset($sendDeals['status']) && $sendDeals['status'] == 'success'){
					$idref = $sendDeals['result'][0]['id_deals_voucher'];
					$dataVoucher = $sendDeals['result'];
					$idDealsVoucher = array_pluck($dataVoucher,'id_deals_voucher');
				}else{
					$deleteQueue = PromotionQueue::where('id_promotion_queue', $dataQueue['id_promotion_queue'])->delete();
					continue;
				}
			}

			if($dataQueue['content']['promotion_channel_email'] == '1'){
				$sendEmail = $this->sendEmail($dataQueue['content']['id_promotion_content'],$dataQueue['user'], $now);
				$channelEmail = '1';
			}else{
				$channelEmail = '0';
			}

			if($dataQueue['content']['promotion_channel_sms'] == '1'){
				$sendSms = $this->sendSms($dataQueue['content']['id_promotion_content'],$dataQueue['user']);
				$channelSms = '1';
			}else{
				$channelSms = '0';
			}

			if($dataQueue['content']['promotion_channel_push'] == '1'){
				$sendPush = $this->sendPush($dataQueue['content']['id_promotion_content'],$dataQueue['user']);
				$channelPush = '1';
			}else{
				$channelPush = '0';
			}

			if($dataQueue['content']['promotion_channel_inbox'] == '1'){
				$sendInbox = $this->sendInbox($dataQueue['content']['id_promotion_content'],$dataQueue['user'],$idref);
				$channelInbox = '1';
			}else{
				$channelInbox = '0';
			}

			if($dataQueue['content']['promotion_channel_whatsapp'] == '1'){
				$sendWhatsapp = $this->sendWhatsapp($dataQueue['content']['id_promotion_content'],$dataQueue['user']);
				$channelWhatsapp = '1';
			}else{
				$channelWhatsapp = '0';
			}

			if($dataQueue['content']['promotion']['promotion_type'] == 'Campaign Series'){ $seriesNo = 1;}
			else{ $seriesNo = 0;}

			$sent = [
				'id_promotion_content' 	=> $dataQueue['content']['id_promotion_content'],
				'id_user' 				=> $dataQueue['user']['id'],
				'send_at'				=> $now,
				'channel_email'			=> $channelEmail,
				'channel_sms'			=> $channelSms,
				'channel_push'			=> $channelPush,
				'channel_inbox'			=> $channelInbox,
				'channel_whatsapp'		=> $channelWhatsapp,
				'series_no'				=> $seriesNo,
				'created_at'			=> date('Y-m-d H:i:s'),
				'updated_at'			=> date('Y-m-d H:i:s')
			];

			if(isset($idDealsVoucher)){
				$sent['id_deals_voucher'] 	= implode(',',$idDealsVoucher);
			}

			$dataPromotionSent[] = $sent;

			$countUser++;
			$deleteQueue = PromotionQueue::where('id_promotion_queue', $dataQueue['id_promotion_queue'])->delete();

		}

		$insertPromotionSent = PromotionSent::insert($dataPromotionSent);
		return ([
			'status'  => 'success',
			'result'  => 'Promotion has been sent.',
			'count_user' => $countUser
		]);
	}

	public function sendEmail($id_promotion_content, $user, $time){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return ([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}
		$promotionContent = $promotionContent->toArray();

		if(!empty($user['email'])){
			if($user['name'] != "")
				$name	 = "";
			else
				$name	 = $user['name'];

			$to		 = $user['email'];

			preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $promotionContent['promotion_email_content'], $match);
			if(count($match)>0){
				$match = array_unique($match[0]);
				foreach ($match as $value) {
					$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $promotionContent['id_promotion_content'])
																->where('type', 'email')->where('original_link', $value)->first();
					if($getShortLink){
						$promotionContent['promotion_email_content'] = str_replace($getShortLink['original_link'], $getShortLink['short_link'], $promotionContent['promotion_email_content']);
					}
				}
			}
			$subject = app($this->autocrm)->TextReplace($promotionContent['promotion_email_subject'], $user['id'], null, 'id');

			$content =  app($this->autocrm)->TextReplace($promotionContent['promotion_email_content'], $user['id'], null, 'id');

			// get setting email
			$getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
			$setting = array();
			foreach ($getSetting as $key => $value) {
				$setting[$value['key']] = $value['value'];
			}

			$hash = base64_encode($user['id'].'|'.$promotionContent['id_promotion_content'].'|'.$time);
			$setting['email_logo'] = env('API_APP_URL').'api/promotion/display_logo/'.$hash;

			$data = array(
				'customer' => $name,
				'html_message' => $content,
				'setting' => $setting
			);

			Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
			{
				$message->to($to, $name)->subject($subject);
				if(env('MAIL_DRIVER') == 'mailgun'){
					$message->trackClicks(true)
							->trackOpens(true);
				}
				if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
					$message->from($setting['email_sender'], $setting['email_from']);
				}else if(!empty($setting['email_sender'])){
					$message->from($setting['email_sender']);
				}

				if(isset($setting['email_reply_to'])){
					$message->replyTo($setting['email_reply_to']);
				}

				if(isset($setting['email_cc'])){
					$message->cc($setting['email_cc']);
				}

				if(isset($setting['email_bcc'])){
					$message->bcc($setting['email_bcc']);
				}
			});
			$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_email_sent' => $promotionContent['promotion_count_email_sent']+1]);
			return ([
				'status'  => 'success',
				'result'  => 'Promotion Content Email Has Been Sent.'
			]);
		}

	}

	public function sendSMS($id_promotion_content, $user){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return response()->json([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}
		$promotionContent = $promotionContent->toArray();

		if(!empty($user['phone'])){
			$senddata = array(
				'apikey' => env('SMS_KEY'),
				'callbackurl' => env('APP_URL'),
				'datapacket'=>array()
			);

			preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $promotionContent['promotion_sms_content'], $match);
			if(count($match)>0){
				$match = array_unique($match[0]);
				foreach ($match as $value) {
					$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $promotionContent['id_promotion_content'])
																->where('type', 'email')->where('original_link', $value)->first();
					if($getShortLink){
						$promotionContent['promotion_sms_content'] = str_replace($getShortLink['original_link'], $getShortLink['short_link'], $promotionContent['promotion_sms_content']);
					}
				}
			}

			$content 	= app($this->autocrm)->TextReplace($promotionContent['promotion_sms_content'], $user['id'], null, 'id');

			switch (env('SMS_GATEWAY')) {
				case 'Jatis':
					$senddata = [
						'userid'	=> env('SMS_USER'),
						'password'	=> env('SMS_PASSWORD'),
						'msisdn'	=> '62'.substr($user['phone'],1),
						'sender'	=> env('SMS_SENDER'),
						'division'	=> env('SMS_DIVISION'),
						'batchname'	=> env('SMS_BATCHNAME'),
						'uploadby'	=> env('SMS_UPLOADBY'),
						'channel'   => env('SMS_CHANNEL')
					];

					$senddata['message'] = $content;

					$this->jatissms->setData($senddata);
					$send = $this->jatissms->send();

					break;
				case 'RajaSMS':
					$senddata = array(
						'apikey' => env('SMS_KEY'),
						'callbackurl' => env('APP_URL'),
						'datapacket'=>array()
					);

					array_push($senddata['datapacket'],array(
						'number' => trim($user['phone']),
						'message' => urlencode(stripslashes(utf8_encode($content))),
						'sendingdatetime' => ""));

					$this->rajasms->setData($senddata);
					$send = $this->rajasms->send();
					break;
				default:
					$senddata = array(
						'apikey' => env('SMS_KEY'),
						'callbackurl' => env('APP_URL'),
						'datapacket'=>array()
					);

					array_push($senddata['datapacket'],array(
						'number' => trim($user['phone']),
						'message' => urlencode(stripslashes(utf8_encode($content))),
						'sendingdatetime' => ""));

					$this->rajasms->setData($senddata);
					$send = $this->rajasms->send();
					break;
			}

			$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_sms_sent' => $promotionContent['promotion_count_sms_sent']+1]);

		}
	}

	public function sendPush($id_promotion_content, $user){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return response()->json([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}
		$promotionContent = $promotionContent->toArray();

		if(!empty($user['phone'])){
			try {
				$dataOptional          = [];
				$image = null;

				if (isset($promotionContent['promotion_push_clickto']) && $promotionContent['promotion_push_clickto'] != null) {
					$dataOptional['type'] = $promotionContent['promotion_push_clickto'];
				} else {
					$dataOptional['type'] = 'Home';
				}

				if (isset($promotionContent['promotion_push_link']) && $promotionContent['promotion_push_link'] != null) {
					if($dataOptional['type'] == 'Link')
						$dataOptional['link'] = $promotionContent['promotion_push_link'];
					else
						$dataOptional['link'] = null;
				} else {
					$dataOptional['link'] = null;
				}

				if (isset($promotionContent['promotion_push_id_reference']) && $promotionContent['promotion_push_id_reference'] != null) {
					$dataOptional['id_reference'] = (int)$promotionContent['promotion_push_id_reference'];
				} else{
					$dataOptional['id_reference'] = 0;
				}

				if($promotionContent['promotion_push_clickto'] == 'News' && $promotionContent['promotion_push_id_reference'] != null){
					$news = News::find($promotionContent['promotion_push_id_reference']);
					if($news){
						$dataOptional['news_title'] = $news->news_title;
					}
					$dataOptional['url'] = env('APP_URL').'news/webview/'.$promotionContent['promotion_push_id_reference'];
				}

				if($promotionContent['promotion_push_clickto']  == 'Order' && $promotionContent['promotion_push_id_reference'] != null){
					$outlet = Outlet::find($promotionContent['promotion_push_id_reference']);
					if($outlet){
						$dataOptional['news_title'] = $outlet->outlet_name;
					}
				}

				$deviceToken = PushNotificationHelper::searchDeviceToken("phone", $user['phone']);

				preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $promotionContent['promotion_push_content'], $match);
				if(count($match)>0){
					$match = array_unique($match[0]);
					foreach ($match as $value) {
						$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $promotionContent['id_promotion_content'])
																	->where('type', 'push')->where('original_link', $value)->first();
						if($getShortLink){
							$promotionContent['promotion_push_content'] = str_replace($getShortLink['original_link'], $getShortLink['short_link'], $promotionContent['promotion_push_content']);
						}
					}
				}

				$subject = app($this->autocrm)->TextReplace($promotionContent['promotion_push_subject'], $user['id'], null, 'id');
				$content = app($this->autocrm)->TextReplace($promotionContent['promotion_push_content'], $user['id'], null, 'id');
				$deviceToken = PushNotificationHelper::searchDeviceToken("phone", $user['phone']);

				if (!empty($deviceToken)) {
					if (isset($deviceToken['token']) && !empty($deviceToken['token'])) {
						$push = PushNotificationHelper::sendPush($deviceToken['token'], $subject, $content, $image, $dataOptional);
						$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_push' => $promotionContent['promotion_count_push']+1]);
					}
				}
				return true;
			} catch (\Exception $e) {
				return response()->json(MyHelper::throwError($e));
			}
		}
	}

	public function sendInbox($id_promotion_content, $user, $idref = null){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return response()->json([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}
		$promotionContent = $promotionContent->toArray();

		preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $promotionContent['promotion_inbox_content'], $match);
		if(count($match)>0){
			$match = array_unique($match[0]);
			foreach ($match as $value) {
				$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $promotionContent['id_promotion_content'])
															->where('type', 'email')->where('original_link', $value)->first();
				if($getShortLink){
					$promotionContent['promotion_inbox_content'] = str_replace($getShortLink['original_link'], $getShortLink['short_link'], $promotionContent['promotion_inbox_content']);
				}
			}
		}

		$inbox = [];
		$inbox['id_user'] 	  = $user['id'];
		$inbox['inboxes_subject'] = app($this->autocrm)->TextReplace($promotionContent['promotion_inbox_subject'], $user['id'], null, 'id');
		$inbox['inboxes_clickto'] = $promotionContent['promotion_inbox_clickto'];

		if($promotionContent['promotion_inbox_clickto'] == 'Content' && !empty($promotionContent['promotion_inbox_content'])){
			$inbox['inboxes_content'] = app($this->autocrm)->TextReplace($promotionContent['promotion_inbox_content'], $user['id'], null, 'id');
		}

		if($promotionContent['promotion_inbox_clickto'] == 'Voucher Detail' && !empty($idref)) {
			$inbox['inboxes_id_reference'] = $idref;
		}else{
			if(!empty($promotionContent['promotion_inbox_id_reference'])){
				$inbox['inboxes_id_reference'] = $promotionContent['promotion_inbox_id_reference'];
			}
		}

		if($promotionContent['promotion_inbox_clickto'] == 'Link' && !empty($promotionContent['promotion_inbox_link'])){
			$inbox['inboxes_link'] = $promotionContent['promotion_inbox_link'];
		}

		$inbox['inboxes_send_at'] = date("Y-m-d H:i:s");
		$inbox['created_at'] = date("Y-m-d H:i:s");
		$inbox['updated_at'] = date("Y-m-d H:i:s");

		$inboxQuery = UserInbox::insert($inbox);

		$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_inbox' => $promotionContent['promotion_count_inbox']+1]);
	}

	public function sendWhatsapp($id_promotion_content, $user){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return 'fail';
		}
		$promotionContent = $promotionContent->toArray();

		//cek api key for whatsapp
		$api_key = Setting::where('key', 'api_key_whatsapp')->first();
		if($api_key){
			if($api_key->value){
				if(!empty($user['phone'])){

					$contentWhatsapp = WhatsappContent::where('id_reference', $promotionContent['id_promotion_content'])->where('source', 'promotion')->get();
					if(count($contentWhatsapp) > 0){
						foreach($contentWhatsapp as $contentWa){

							if($contentWa['content_type'] == 'text'){
								preg_match_all('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',  $contentWa['content'], $match);
								if(count($match)>0){
									$match = array_unique($match[0]);
									foreach ($match as $value) {
										$getShortLink = PromotionContentShortenLink::where('id_promotion_content', $promotionContent['id_promotion_content'])
																					->where('type', 'whatsapp')->where('original_link', $value)->first();
										if($getShortLink){
											$contentWa['content'] = str_replace($getShortLink['original_link'], $getShortLink['short_link'], $contentWa['content']);
										}
									}
								}

								$content 	= app($this->autocrm)->TextReplace($contentWa['content'], $user['id'], null, 'id');
							}else{
								$content = $contentWa['content'];
							}


							// add country code in number
							$ptn = "/^0/";
							$rpltxt = "62";
							$receipient = preg_replace($ptn, $rpltxt, $user['phone']);

							$send = $this->apiwha->send($api_key->value, $receipient, $content);

							if(isset($send['result_code']) && $send['result_code'] == -1){
								return 'fail';
							}

						}
						$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_sms_sent' => $promotionContent['promotion_count_sms_sent']+1]);
					}
				}

			}else{
				return 'fail';
			}
		}else{
			return 'fail';
		}
	}

	public function sendDeals($id_promotion_content, $user){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return [
				'status'  => 'fail',
				'message'  => 'Promotion Content not found.'
			];
		}
		$promotionContent = $promotionContent->toArray();

		$dataDeals = Deal::find($promotionContent['id_deals']);
		if($dataDeals){
			$dataVoucher = null;
			if ($dataDeals->deals_voucher_type != "Unlimited") {
				// cek jumlah voucher dulu
				if(($dataDeals['deals_total_voucher'] - $dataDeals['deals_total_claimed']) >= $promotionContent['voucher_given']){
					for($i = 1; $i<= $promotionContent['voucher_given']; $i++){
						$dataDeals = Deal::find($promotionContent['id_deals']);
						$voucher = app($this->dealsClaim)->getVoucherFromTable($user, $dataDeals);
						if ($voucher) {
							$dataVoucher[] = $voucher;
							$promotionContent = PromotionContent::find($id_promotion_content);
							$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_voucher_give' => $promotionContent['promotion_count_voucher_give']+1]);
						}
					}
				}
			}else{
				for($i = 0; $i< $promotionContent['voucher_given']; $i++){
					$dataDeals = Deal::find($promotionContent['id_deals']);
					$voucher = app($this->dealsClaim)->getVoucherGenerate($user, $dataDeals);
					if ($voucher) {
						$dataVoucher[] = $voucher;
						$promotionContent = PromotionContent::find($id_promotion_content);
						$updateCountPromotion = PromotionContent::where('id_promotion_content', $promotionContent['id_promotion_content'])->update(['promotion_count_voucher_give' => $promotionContent['promotion_count_voucher_give']+1]);
					}
				}
			}

			if ($dataVoucher != null) {
				return ['status' =>'success', 'result' => $dataVoucher];
			}else{
				return [
					'status'   => 'fail',
					'message' => 'Voucher is runs out.'
				];
			}
		}else{
			return [
				'status' => 'fail',
				'message' => 'Deals not found.'
			];
		}

	}

	public function displayLogo(Request $request, $hash) {
		$hashdecode = explode('|', base64_decode($hash));
		$promotionSent = PromotionSent::where('id_user','=',$hashdecode[0])
								->where('id_promotion_content','=',$hashdecode[1])
								->where('send_at','=',$hashdecode[2])
								->first();
		if($promotionSent && $promotionSent->email_read == '0'){
			$content = PromotionContent::find($hashdecode[1]);
			$updateCountPromotion = PromotionContent::where('id_promotion_content', $hashdecode[1])->update(['promotion_count_email_read' => $content['promotion_count_email_read']+1]);
			PromotionSent::where('id_user','=',$hashdecode[0])
						->where('id_promotion_content','=',$hashdecode[1])
						->where('send_at','=',$hashdecode[2])
						->update(['email_read' => '1']);
		}


		$emailLogo = Setting::where('key', 'email_logo')->first();
		if($emailLogo){
			$imagenya = env('S3_URL_API').$emailLogo->value;
		}
		$img = Image::make($imagenya);
		$response = $img->response('png');
        // ob_end_clean(); // if I remove this, it does not work
		return $response;

    }

	public function getWeek(){
		$endDate = date('d');
		$i = 2;
		$week = 1;
		while($i<=$endDate){
			if( date('l',strtotime(date('Y-m-'.$i))) == 'Sunday'){
				$week++;
			}
			$i++;
		}
		return $week;
	}

	public function promotionSentList(Request $request)
	{
		$post = $request->json()->all();
		$data = PromotionSent::with('user')->where('id_promotion_content', $post['id_promotion_content'])->orderBy('send_at', 'DESC');
		if(isset($post['type'])){
			if($post['type'] != 'email_read') $post['type'] = 'channel'.'_'.$post['type'];
			$data = $data->where($post['type'], '1');
		}
		$data = $data->paginate(10)->toArray();
		return response()->json(MyHelper::checkGet($data));
	}

	public function promotionLinkClickedList(Request $request)
	{
		$post = $request->json()->all();
		$updateClick = $this->updateLinkClicked($post['id_promotion_content'], $post['type']);
		$link = PromotionContentShortenLink::where('id_promotion_content', $post['id_promotion_content'])->where('type', $post['type'])->paginate(10)->toArray();
		return response()->json(MyHelper::checkGet($link));
	}

	public function updateLinkClicked($id_promotion_content, $type){
		$link = PromotionContentShortenLink::where('id_promotion_content', $id_promotion_content)->where('type', $type)->get();
		$total = 0;
		foreach ($link as $value) {
			$getSsl = MyHelper::curl($value['short_link'].'+');
			$dataUpdate['link_clicked'] = (int)MyHelper::cut_str($getSsl, '<div class="col-sm-4 url-stats">', '<span>Clicks</span>');
			$dataUpdate['link_unique_clicked']  = (int)MyHelper::cut_str($getSsl, '<div class="col-sm-4 url-stats">', '<span>Unique Clicks</span>');
			$update = PromotionContentShortenLink::where('id_promotion_content_shorten_link', $value['id_promotion_content_shorten_link'])->update($dataUpdate);
			$total = $total + $dataUpdate['link_clicked'];
		}
		$updateClick = PromotionContent::where('id_promotion_content', $id_promotion_content)->update(['promotion_count_'.$type.'_link_clicked' => $total]);
	}

	public function promotionVoucherList(Request $request)
	{
		$post = $request->json()->all();
		$promotionContent = PromotionContent::find($post['id_promotion_content']);
		if(!$promotionContent){
			return response()->json([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}

		if(isset($post['given']) || isset($post['used'])){
			$voucher = DealsVoucher::rightJoin('deals_users', 'deals_vouchers.id_deals_voucher', 'deals_users.id_deals_voucher');
		}else{
			$voucher = DealsVoucher::leftJoin('deals_users', 'deals_vouchers.id_deals_voucher', 'deals_users.id_deals_voucher');
		}

		if(isset($post['used'])){
			$voucher = $voucher->whereNotNull('deals_users.used_at');
		}

		$voucher = $voucher->leftJoin('users', 'users.id', 'deals_users.id_user')
							->where('deals_vouchers.id_deals', $promotionContent->id_deals)
							->paginate(10)->toArray();
		return response()->json(MyHelper::checkGet($voucher));
	}

	public function promotionVoucherTrx(Request $request)
	{
		$post = $request->json()->all();
		$promotionContent = PromotionContent::find($post['id_promotion_content']);
		if(!$promotionContent){
			return response()->json([
				'status'  => 'fail',
				'messages'  => ['Promotion Content Not Found']
			]);
		}

		$transaction = Transaction::with(['user','outlet'])->join('deals_vouchers', 'transactions.id_deals_voucher', 'deals_vouchers.id_deals_voucher')
									->where('deals_vouchers.id_deals', $promotionContent['id_deals'])->paginate(10)->toArray();

		return response()->json(MyHelper::checkGet($transaction));
	}

	public function recipientPromotion(Request $request)
	{
		$post = $request->json()->all();
		$idPromotionContents = PromotionContent::select('id_promotion_content')->where('id_promotion', $post['id_promotion'])->get();
		$idUsers = PromotionSent::select('id_user')->whereIn('id_promotion_content', $idPromotionContents)->get();

		if(Promotion::find($post['id_promotion'])->promotion_type == 'Campaign Series'){
			$users = User::with(['promotionSents'=> function($query) use ($idPromotionContents) {
				$query->whereIn('id_promotion_content', $idPromotionContents);
			}])->whereIn('id', $idUsers)->paginate(10)->toArray();

			foreach ($users['data'] as $key => $user) {
				$voucherUsed = 0;
				$voucherGiven = 0;
				foreach ($user['promotion_sents'] as $i => $value) {
					if($value['id_deals_voucher']){
						$idDealsVoucher = explode(',',$value['id_deals_voucher']);
						$voucherGiven += count($idDealsVoucher);
						$voucherUsed += DealsUser::whereIn('id_deals_voucher', $idDealsVoucher)->whereNotNull('used_at')->count();
					}
				}
				$users['data'][$key]['voucher_given'] = $voucherGiven;
				$users['data'][$key]['voucher_used'] = $voucherUsed;

				usort($users['data'][$key]['promotion_sents'], function($a, $b){
					$t1 = strtotime($a['send_at']);
					$t2 = strtotime($b['send_at']);
					return $t1 - $t2;
				});
			}

		}else{
			$users = User::join('promotion_sents', 'users.id', 'promotion_sents.id_user')
			->whereIn('id_promotion_content', $idPromotionContents)
			->whereIn('id', $idUsers)
			->orderBy('promotion_sents.send_at', 'DESC')
			->paginate(10)->toArray();

			foreach ($users['data'] as $key => $value) {
				if($value['id_deals_voucher']){
					$idDealsVoucher = explode(',',$value['id_deals_voucher']);
					$voucherUsed = DealsUser::whereIn('id_deals_voucher', $idDealsVoucher)->whereNotNull('used_at')->count();
					$users['data'][$key]['voucher_given'] = count($idDealsVoucher);
					$users['data'][$key]['voucher_used'] = $voucherUsed;
				}
			}
		}

		return response()->json(MyHelper::checkGet($users));
	}

	public function delete(Request $request){
		$post = $request->json()->all();

		$cekSend = PromotionSent::whereIn('id_promotion_content', function($query) use ($post){
			$query->select('id_promotion_content')->from('promotion_contents')->where('id_promotion', $post['id_promotion']);
		})->get();

		DB::beginTransaction();
		if(count($cekSend) > 0){
			return response()->json($result = [
				'status'  => 'fail',
				'messages'  => ['Promotions that have been sent cannot be deleted']
			]);
		}else{
			$contents = PromotionContent::where('id_promotion', $post['id_promotion'])->get();
			foreach ($contents as $dataContent) {
				if($dataContent['id_deals'] != null){

					$check = app($this->deals)->checkDelete($dataContent['id_deals']);
					if ($check) {
						// delete image first
						app($this->deals)->deleteImage($dataContent['id_deals']);

						$deleteDeals = app($this->deals)->delete($dataContent['id_deals']);

						if (!$deleteDeals) {
							DB::rollBack();
							return response()->json(MyHelper::checkDelete($delete));
						}
					}
					else {
						return response()->json([
							'status'   => 'fail',
							'messages' => ['Deal already used.']
						]);
					}
				}
			}

			if(count($contents) > 0){
				$deleteContent = PromotionContent::where('id_promotion', $post['id_promotion'])->delete();
				if($deleteContent){
					$delete = Promotion::where('id_promotion', $post['id_promotion'])->delete();
					if ($delete) {
						DB::commit();
					}
					else {
						DB::rollBack();
					}

					return response()->json(MyHelper::checkDelete($delete));
				}else{
					DB::rollBack();
					return response()->json([
						'status'   => 'fail',
						'messages' => ['Failed Delete promotion.']
					]);
				}
			}else{
				$delete = Promotion::where('id_promotion', $post['id_promotion'])->delete();
				if ($delete) {
					DB::commit();
				}
				else {
					DB::rollBack();
				}
				return response()->json(MyHelper::checkDelete($delete));
			}
		}
	}
}
