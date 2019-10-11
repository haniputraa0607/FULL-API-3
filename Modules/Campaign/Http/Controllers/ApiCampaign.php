<?php

namespace Modules\Campaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Http\Models\Campaign;
use App\Http\Models\CampaignRule;
use App\Http\Models\CampaignEmailSent;
use App\Http\Models\CampaignEmailQueue;
use App\Http\Models\CampaignSmsSent;
use App\Http\Models\CampaignSmsQueue;
use App\Http\Models\CampaignPushSent;
use App\Http\Models\CampaignPushQueue;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Treatment;
use App\Http\Models\Setting;
use App\Http\Models\CampaignRuleParent;
use App\Http\Models\WhatsappContent;
use App\Http\Models\CampaignWhatsappQueue;
use App\Http\Models\CampaignWhatsappSent;
use App\Http\Models\CampaignWhatsappSentContent;
use App\Http\Models\News;
use App\Http\Models\OauthAccessToken;

//use Modules\Campaign\Http\Requests\campaign_list;
//use Modules\Campaign\Http\Requests\campaign_create;
//use Modules\Campaign\Http\Requests\campaign_update;
//use Modules\Campaign\Http\Requests\campaign_delete;

use App\Jobs\SendCampaignJob;
use App\Jobs\GenerateCampaignRecipient;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;
use Validator;
use Hash;
use DB;
use Mailgun;

class ApiCampaign extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->user     = "Modules\Users\Http\Controllers\ApiUser";
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->rajasms = new classMaskingJson();
		$this->apiwha = new apiwha();
    }

    public function campaignList(Request $request){
		$post = $request->json()->all();

		$query = Campaign::orderBy('id_campaign', 'Desc');
		$count = Campaign::get();

		if(isset($post['campaign_title']) && $post['campaign_title'] != ""){
			$query = $query->where('campaign_title','like','%'.$post['campaign_title'].'%');
			$count = $count->where('campaign_title','like','%'.$post['campaign_title'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign']
				];
		}
		return response()->json($result);
	}
	public function CreateCampaign(Request $request){
		$post = $request->json()->all();
		$user = $request->user();

		$data 						= [];
		$data['campaign_title'] 	= $post['campaign_title'];
		$data['id_user'] 			= $user['id'];

		if(!empty($post['campaign_send_at'])){
			$datetimearr 				= explode(' - ',$post['campaign_send_at']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['campaign_send_at'] 	= $date." ".$datetimearr[1].":00";
		} else $data['campaign_send_at'] = null;

		// $data['campaign_rule'] 		= $post['rule'];

		if(in_array('Email', $post['campaign_media']))
			$data['campaign_media_email'] = "Yes";
		else
			$data['campaign_media_email'] = "No";

		if(in_array('SMS', $post['campaign_media']))
			$data['campaign_media_sms'] = "Yes";
		else
			$data['campaign_media_sms'] = "No";

		if(in_array('Push Notification', $post['campaign_media']))
			$data['campaign_media_push'] = "Yes";
		else
			$data['campaign_media_push'] = "No";

		if(in_array('Inbox', $post['campaign_media']))
			$data['campaign_media_inbox'] = "Yes";
		else
			$data['campaign_media_inbox'] = "No";

		if(in_array('Whatsapp', $post['campaign_media']))
			$data['campaign_media_whatsapp'] = "Yes";
		else
			$data['campaign_media_whatsapp'] = "No";

		$data['campaign_generate_receipient'] =  $post['campaign_generate_receipient'];

		DB::beginTransaction();
		if(isset($post['id_campaign']))
			$queryCampaign = Campaign::where('id_campaign','=',$post['id_campaign'])->update($data);
		else
			$queryCampaign = Campaign::create($data);

		if($queryCampaign){
			$data = [];

			if(isset($post['id_campaign'])){
				$deleteRuleParent = CampaignRuleParent::where('id_campaign','=',$post['id_campaign'])->get();
				foreach ($deleteRuleParent as $key => $value) {
					$value->rules()->delete();
				}
				$deleteRuleParent = CampaignRuleParent::where('id_campaign','=',$post['id_campaign'])->delete();
			}

			if(isset($post['id_campaign'])) $data['id_campaign'] = $post['id_campaign'];
				else $data['id_campaign'] = $queryCampaign->id_campaign;

			$queryCampaignRule = MyHelper::insertCondition('campaign', $data['id_campaign'], $post['conditions']);
			if(isset($queryCampaignRule['status']) && $queryCampaignRule['status'] == 'success'){
				$resultrule = $queryCampaignRule['data'];
			}else{
				DB::rollBack();
				$result = [
					'status'  => 'fail',
					'messages'  => ['Create Campaign Failed']
				];
			}

			$result = [
					'status'  => 'success',
					'result'  => 'Set Campaign Information & Rule Success',
					'campaign'  => $queryCampaign,
					'rule'  => $resultrule
				];
		} else {
			DB::rollBack();
			$result = [
					'status'  => 'fail',
					'messages'  => ['Create Campaign Failed']
				];
		}

		DB::commit();
		return response()->json($result);
    }

    public function ShowCampaignStep1(Request $request){
		$post = $request->json()->all();
		$user = $request->user();

		$campaign = Campaign::with(['user', 'campaign_rule_parents', 'campaign_rule_parents.rules'])->where('id_campaign','=',$post['id_campaign'])->get()->toArray();
		if($campaign){
			$result = [
					'status'  => 'success',
					'result'  => $campaign
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign Not Found']
				];
		}
		return response()->json($result);
    }

	public function ShowCampaignStep2(Request $request){
		$post = $request->json()->all();
		$user = $request->user();

		$campaign = Campaign::with(['user', 'campaign_rule_parents', 'campaign_rule_parents.rules', 'whatsapp_content'])->where('id_campaign','=',$post['id_campaign'])->get()->first();
		if($campaign){
			$campaign['campaign_push_name_reference'] = "";

			if($campaign['campaign_media_push'] == "Yes"){
				if($campaign['campaign_push_clickto'] == "Product"){
					if($campaign['campaign_push_id_reference'] != 0){
						$q = Product::where('id_product','=',$campaign['campaign_push_id_reference'])->get()->first();
						$campaign['campaign_push_name_reference'] = $q['product_name'];
					}
				}

				if($campaign['campaign_push_clickto'] == "Outlet"){
					if($campaign['campaign_push_id_reference'] != 0){
						$q = Outlet::where('id_outlet','=',$campaign['campaign_push_id_reference'])->get()->first();
						$campaign['campaign_push_name_reference'] = $q['outlet_name'];
					}
				}
			}

			if($campaign['campaign_media_inbox'] == "Yes"){
				if($campaign['campaign_inbox_clickto'] == "Product"){
					if($campaign['campaign_inbox_id_reference'] != 0){
						$q = Product::where('id_product','=',$campaign['campaign_inbox_id_reference'])->get()->first();
						$campaign['campaign_inbox_name_reference'] = $q['product_name'];
					}
				}

				if($campaign['campaign_inbox_clickto'] == "Outlet"){
					if($campaign['campaign_inbox_id_reference'] != 0){
						$q = Outlet::where('id_outlet','=',$campaign['campaign_inbox_id_reference'])->get()->first();
						$campaign['campaign_inbox_name_reference'] = $q['outlet_name'];
					}
				}
			}

			$result = [
				'status'  => 'success',
				'result'  => $campaign
			];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign Not Found']
				];
		}

		return response()->json($result);
    }

	public function showRecipient(Request $request){
		$post=$request->json()->all();
		$limiter=[];
		$column=['id','name','email','phone','gender','city_name','birthday'];
		$limiter=[
			$column[$post['order'][0]['column']??0]??'id',
			$post['order'][0]['dir']??'asc',
			$post['start']??0,
			$post['length']??99999999,
			$post['search']['value']??null,
		];
		$cond = Campaign::with(['campaign_rule_parents', 'campaign_rule_parents.rules'])->where('id_campaign','=',$post['id_campaign'])->first();
		if(!$cond){
			return [
					'status'  => 'fail',
					'messages'  => ['Campaign Not Found']
				];
		}
		// UserFilter($conditions = null, $order_field='id', $order_method='asc', $skip=0, $take=99999999999,$keyword=null)
		$users = app($this->user)->UserFilter($cond['campaign_rule_parents'],...$limiter);
		if($users['status'] == 'success') $cond['users'] = $users['result'];
		$result = [
				'status'  => 'success',
				'result'  => $cond,
				'recordsFiltered' => $users['recordsFiltered']??0,
				'recordsTotal' => $users['recordsTotal']??0
			];
		return $result;
	}

	public function SendCampaign(Request $request){
		$post = $request->json()->all();
		$user = $request->user();

		$campaign = Campaign::where('id_campaign','=',$post['id_campaign'])->first();

		if($campaign){
			if($campaign['campaign_is_sent'] == 'Yes'){
				$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign already sent']
				];
				return response()->json($result);
			}

			if($campaign['campaign_send_at'] == null){
				//Kirimnya NOW
				if($campaign['campaign_media_email'] == "Yes"){
					$receipient_email = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_email_receipient'])));
					$data['campaign'] = $campaign;
					$data['type'] = 'email';
					foreach (array_chunk($receipient_email,10) as $recipients) {
						$data['recipient']=array_filter($recipients,function($var){return !empty($var);});
						SendCampaignJob::dispatch($data)->allOnConnection('database');
					}
				}

				if($campaign['campaign_media_sms'] == "Yes"){
					$receipient_sms = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_sms_receipient'])));

					$data['campaign'] = $campaign;
					$data['type'] = 'sms';
					foreach (array_chunk($receipient_sms,10) as $recipients) {
						$data['recipient']=array_filter($recipients,function($var){return !empty($var);});
						SendCampaignJob::dispatch($data)->allOnConnection('database');
					}
				}

				if($campaign['campaign_media_push'] == "Yes"){
					$receipient_push = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_push_receipient'])));

					$data['campaign'] = $campaign;
					$data['type'] = 'push';
					foreach (array_chunk($receipient_push,10) as $recipients) {
						$data['recipient']=array_filter($recipients,function($var){return !empty($var);});
						SendCampaignJob::dispatch($data)->allOnConnection('database');
					}
				}

				if($campaign['campaign_media_inbox'] == "Yes"){
					$receipient_inbox = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_inbox_receipient'])));

					$data['campaign'] = $campaign;
					$data['type'] = 'inbox';
					foreach (array_chunk($receipient_inbox,10) as $recipients) {
						$data['recipient']=array_filter($recipients,function($var){return !empty($var);});
						SendCampaignJob::dispatch($data)->allOnConnection('database');
					}
				}

				if($campaign['campaign_media_whatsapp'] == "Yes"){
					$sendAt = date('Y-m-d H:i:s', strtotime("+ 5 minutes"));

					$receipient_whatsapp = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_whatsapp_receipient'])));

					$data['campaign'] = $campaign;
					$data['type'] = 'whatsapp';
					foreach (array_chunk($receipient_whatsapp,10) as $recipients) {
						$data['recipient']=array_filter($recipients,function($var){return !empty($var);});
						SendCampaignJob::dispatch($data)->allOnConnection('database');
					}

				}
				$update = Campaign::where('id_campaign','=',$campaign['id_campaign'])->update(['campaign_is_sent' => 'Yes']);

				$result = [
					'status'  => 'success',
					'result'  => $campaign
				];
			} else {
				$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign Will be automatically sent at '.date("d F Y - H:i", strtotime($campaign['campaign_send_at']))]
				];
			}

		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign Not Found']
				];
		}
		return response()->json($result);
	}

	public function insertQueue(){
		$now = date('Y-m-d H:i:00');
		$now2 = date('Y-m-d H:i:00', strtotime('-5 minutes'));

		$campaigns = Campaign::where('campaign_send_at', '>=', $now2)->where('campaign_send_at', '<=', $now)->where('campaign_is_sent', 'No')->where('campaign_complete', '1')->get();
		foreach ($campaigns as $i => $campaign) {
			if($campaign['campaign_generate_receipient'] == 'Send At Time'){
				$cond = Campaign::with(['campaign_rule_parents', 'campaign_rule_parents.rules'])->where('id_campaign','=',$campaign['id_campaign'])->first();
				$userFilter = app($this->user)->UserFilter($cond['campaign_rule_parents']);
				$receipient = [];
				if($userFilter){
					$receipient = $userFilter['result'];

					//update email receipient
					if($campaign['campaign_media_email'] == "Yes"){
						$emailReceipient = $campaign['campaign_email_receipient'];
						if(count($receipient) > 0){
							if($emailReceipient != null){
								$emailReceipient = $emailReceipient.','.implode(',', array_pluck($receipient,'email'));
							}else{
								$emailReceipient = implode(',', array_pluck($receipient,'email'));
							}

							DB::table('campaigns')
							->where('id_campaign', $campaign['id_campaign'])
							->update([
								'campaign_email_receipient' => $emailReceipient,
								'campaign_email_count_all' => count(explode(',',$emailReceipient))
							]);
						}
					}
					//update sms receipient
					if($campaign['campaign_media_sms'] == "Yes"){
						$smsReceipient = $campaign['campaign_sms_receipient'];
						if(count($receipient) > 0){
							if($smsReceipient != null){
								$smsReceipient = $smsReceipient.','.implode(',', array_pluck($receipient,'phone'));
							}else{
								$smsReceipient = implode(',', array_pluck($receipient,'phone'));
							}

							DB::table('campaigns')
							->where('id_campaign', $campaign['id_campaign'])
							->update([
								'campaign_sms_receipient' => $smsReceipient,
								'campaign_sms_count_all' => count(explode(',',$smsReceipient))
							]);
						}
					}
					//update push receipient
					if($campaign['campaign_media_push'] == "Yes"){
						$pushReceipient = $campaign['campaign_push_receipient'];
						if(count($receipient) > 0){
							if($pushReceipient != null){
								$pushReceipient = $pushReceipient.','.implode(',', array_pluck($receipient,'phone'));
							}else{
								$pushReceipient = implode(',', array_pluck($receipient,'phone'));
							}

							DB::table('campaigns')
							->where('id_campaign', $campaign['id_campaign'])
							->update([
								'campaign_push_receipient' => $pushReceipient,
								'campaign_push_count_all' => count(explode(',',$pushReceipient))
							]);
						}
					}
					//update inbox receipient
					if($campaign['campaign_media_inbox'] == "Yes"){
						$inboxReceipient = $campaign['campaign_inbox_receipient'];
						if(count($receipient) > 0){
							if($inboxReceipient != null){
								$inboxReceipient = $inboxReceipient.','.implode(',', array_pluck($receipient,'phone'));
							}else{
								$inboxReceipient = implode(',', array_pluck($receipient,'phone'));
							}

							DB::table('campaigns')
							->where('id_campaign', $campaign['id_campaign'])
							->update([
								'campaign_inbox_receipient' => $inboxReceipient,
								'campaign_inbox_count' => count(explode(',',$inboxReceipient))
							]);
						}
					}
					//update whatsapp receipient
					if($campaign['campaign_media_whatsapp'] == "Yes"){
						$whatsappReceipient = $campaign['campaign_whatsapp_receipient'];
						if(count($receipient) > 0){
							if($whatsappReceipient != null){
								$whatsappReceipient = $whatsappReceipient.','.implode(',', array_pluck($receipient,'phone'));
							}else{
								$whatsappReceipient = implode(',', array_pluck($receipient,'phone'));
							}

							DB::table('campaigns')
							->where('id_campaign', $campaign['id_campaign'])
							->update([
								'campaign_whatsapp_receipient' => $whatsappReceipient,
								'campaign_whatsapp_count_all' => count(explode(',',$whatsappReceipient))
							]);
						}
					}

				}
			}
			$campaign = Campaign::find($campaign['id_campaign']);
			// add email queue
			if($campaign['campaign_media_email'] == "Yes"){
				$receipient_email = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_email_receipient'])));
				foreach($receipient_email as $key => $receipient){
					//masuk queue
					$subject = app($this->autocrm)->TextReplace($campaign['campaign_email_subject'], $receipient, null, 'email');
					$content = app($this->autocrm)->TextReplace($campaign['campaign_email_content'], $receipient, null, 'email');

					$queue = [];
					$queue['id_campaign'] = $campaign['id_campaign'];
					$queue['email_queue_to'] = $receipient;
					$queue['email_queue_subject'] = $subject;
					$queue['email_queue_content'] = $content;
					$queue['email_queue_send_at'] = date('Y-m-d H:i:s');

					$logs = CampaignEmailQueue::create($queue);

					DB::table('campaigns')
						->where('id_campaign', $campaign['id_campaign'])
						->update([
							'campaign_email_count_queue' => DB::raw('campaign_email_count_queue + 1')
						]);
				}

			}
			// add sms queue
			if($campaign['campaign_media_sms'] == "Yes"){
				$receipient_sms = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_sms_receipient'])));

				foreach($receipient_sms as $key => $receipient){
					$content = app($this->autocrm)->TextReplace($campaign['campaign_sms_content'], $receipient);

					$queue = [];
					$queue['id_campaign'] = $campaign['id_campaign'];
					$queue['sms_queue_to'] = $receipient;
					$queue['sms_queue_content'] = $content;
					$queue['sms_queue_send_at'] = date('Y-m-d H:i:s');

					$logs = CampaignSmsQueue::create($queue);

					DB::table('campaigns')
						->where('id_campaign', $campaign['id_campaign'])
						->update([
							'campaign_sms_count_queue' => DB::raw('campaign_sms_count_queue + 1')
						]);
				}
			}

			// add push queue
			if($campaign['campaign_media_push'] == "Yes"){
				$receipient_push = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_push_receipient'])));

				foreach($receipient_push as $key => $receipient){
					$push_subject = app($this->autocrm)->TextReplace($campaign['campaign_push_subject'], $receipient, null, 'phone');
					$push_content = app($this->autocrm)->TextReplace($campaign['campaign_push_content'], $receipient, null, 'phone');

					$push = [];
					$push['id_campaign'] = $campaign['id_campaign'];
					$push['push_queue_to'] = $receipient;
					$push['push_queue_subject'] = $push_subject;
					$push['push_queue_content'] = $push_content;
					$push['push_queue_send_at'] = date('Y-m-d H:i:s');

					$logs = CampaignPushQueue::create($push);

					DB::table('campaigns')
						->where('id_campaign', $campaign['id_campaign'])
						->update([
							'campaign_push_count_queue' => DB::raw('campaign_push_count_queue + 1')
						]);
				}
			}

			// add inbox queue
			if($campaign['campaign_media_inbox'] == "Yes"){
				$receipient_inbox = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_inbox_receipient'])));

				$user = User::whereIn('phone',$receipient_inbox)->get()->toArray();

				foreach($user as $key => $receipient){

					$inbox = [];
					$inbox['id_campaign'] = $campaign['id_campaign'];
					$inbox['id_user'] 	  = $receipient['id'];
					$inbox['inboxes_subject'] = app($this->autocrm)->TextReplace($campaign['campaign_inbox_subject'], $receipient['id'], null, 'id');
					$inbox['inboxes_clickto'] = $campaign['campaign_inbox_clickto'];

					if($campaign['campaign_inbox_clickto'] == 'Content'){
						$inbox['inboxes_content'] = app($this->autocrm)->TextReplace($campaign['campaign_inbox_content'], $receipient['id'], null, 'id');
					}

					if($campaign['campaign_inbox_clickto'] == 'Link'){
						$inbox['inboxes_link'] = $campaign['campaign_inbox_link'];
					}

					if(!empty($campaign['campaign_inbox_id_reference'])){
						$inbox['inboxes_id_reference'] = $campaign['campaign_inbox_id_reference'];
					}
					$inbox['inboxes_send_at'] = date("Y-m-d H:i:s");
					$inbox['created_at'] = date("Y-m-d H:i:s");
					$inbox['updated_at'] = date("Y-m-d H:i:s");

					$inboxQuery = UserInbox::insert($inbox);
				}
			}

			// add whatsapp queue
			if($campaign['campaign_media_whatsapp'] == "Yes"){
				$receipient_Whatsapp = explode(',', str_replace(' ', ',', str_replace(';', ',', $campaign['campaign_push_receipient'])));

				foreach($receipient_Whatsapp as $key => $receipient){

					$whatsapp = [];
					$whatsapp['id_campaign'] = $campaign['id_campaign'];
					$whatsapp['whatsapp_queue_to'] = $receipient;
					$whatsapp['whatsapp_queue_send_at'] = date('Y-m-d H:i:s');

					$logs = CampaignWhatsappQueue::create($whatsapp);

					DB::table('campaigns')
						->where('id_campaign', $campaign['id_campaign'])
						->update([
							'campaign_push_count_queue' => DB::raw('campaign_push_count_queue + 1')
						]);
				}
			}
			$update = Campaign::where('id_campaign','=',$campaign['id_campaign'])->update(['campaign_is_sent' => 'Yes']);
		}

		return response()->json([
			'status' => 'success',
			'result' => count($campaigns).' campaign has been insert to queue'
		]);

	}


	public function sendCampaignCron(){
		$now = date('Y-m-d H:i:00');

		// send 10 email queue
		$emailQueues = CampaignEmailQueue::where('email_queue_send_at', '<=', $now)->orderBy('email_queue_send_at', 'ASC')->limit(10)->get();
		foreach ($emailQueues as $key => $emailQueue) {

			$to = $emailQueue['email_queue_to'];
			$user = User::where('email', $to)->first();
			if($user && $user['name'] != null){
				$name = $user['name'];
			}else{
				$name = ucwords(str_replace("_"," ", str_replace("-"," ", str_replace("."," ", $to))));
			}

			$subject = $emailQueue['email_queue_subject'];
			$content = $emailQueue['email_queue_content'];

			// get setting email
			$getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
			$setting = array();
			foreach ($getSetting as $key => $value) {
				$setting[$value['key']] = $value['value'];
			}

			$data = array(
				'customer' => $name,
				'html_message' => $content,
				'setting' => $setting
			);

			Mailgun::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
			{

				if(stristr($to, 'gmail.con')){
					$to = str_replace('gmail.con', 'gmail.com', $to);
				}

				$message->to($to, $name)->subject($subject)
								->trackClicks(true)
								->trackOpens(true);
				if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
					$message->from($setting['email_from'], $setting['email_sender']);
				}else if(!empty($setting['email_from'])){
					$message->from($setting['email_from']);
				}

				if(!empty($setting['email_reply_to'])){
					$message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
				}

				if(!empty($setting['email_cc']) && !empty($setting['email_cc_name'])){
					$message->cc($setting['email_cc'], $setting['email_cc_name']);
				}

				if(!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])){
					$message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
				}
			});

			// insert to email sent
			$outbox = [];
			$outbox['id_campaign'] = $emailQueue['id_campaign'];
			$outbox['email_sent_to'] = $to;
			$outbox['email_sent_subject'] = $subject;
			$outbox['email_sent_message'] = $content;
			$outbox['email_sent_send_at'] = date("Y-m-d H:i:s");
			$logs = CampaignEmailSent::create($outbox);

			// update count campaign
			DB::table('campaigns')
				->where('id_campaign', $emailQueue['id_campaign'])
				->update([
					'campaign_email_count_sent' => DB::raw('campaign_email_count_sent + 1'),
					'campaign_email_count_queue' => DB::raw('campaign_email_count_queue - 1')
				]);

			// delete from email queue
			$deleteQueue = CampaignEmailQueue::where('id_campaign_email_queue', $emailQueue['id_campaign_email_queue'])->delete();

		}

		// send 10 sms queue
		$smsQueues = CampaignSmsQueue::where('sms_queue_send_at', '<=', $now)->orderBy('sms_queue_send_at', 'ASC')->limit(10)->get();
		foreach ($smsQueues as $key => $smsQueue) {
			$senddata = array(
				'apikey' => env('SMS_KEY'),
				'callbackurl' => env('APP_URL'),
				'datapacket'=>array()
			);

			$receipient = $smsQueue['sms_queue_to'];
			$content 	= $smsQueue['sms_queue_content'];

			array_push($senddata['datapacket'],array(
					'number' => trim($receipient),
					'message' => urlencode(stripslashes(utf8_encode($content))),
					'sendingdatetime' => ""));

			$this->rajasms->setData($senddata);
			$send = $this->rajasms->send();

			// insert to sms sent
			$outbox = [];
			$outbox['id_campaign'] = $smsQueue['id_campaign'];
			$outbox['sms_sent_to'] = $receipient;
			$outbox['sms_sent_content'] = $content;
			$outbox['sms_sent_send_at'] = date("Y-m-d H:i:s");
			$logs = CampaignSmsSent::create($outbox);

			//update count campaign
			DB::table('campaigns')
				->where('id_campaign', $smsQueue['id_campaign'])
				->update([
					'campaign_sms_count_sent' => DB::raw('campaign_sms_count_sent + 1'),
					'campaign_sms_count_queue' => DB::raw('campaign_sms_count_queue - 1')
				]);

			// delete from sms queue
			$deleteQueue = CampaignSmsQueue::where('id_campaign_sms_queue', $smsQueue['id_campaign_sms_queue'])->delete();

		}

		// send 100 push queue
		$pushQueues = CampaignPushQueue::with('campaign')->where('push_queue_send_at', '<=', $now)->orderBy('push_queue_send_at', 'ASC')->limit(100)->get();
		foreach ($pushQueues as $key => $pushQueue) {
			$receipient 	= $pushQueue['push_queue_to'];
			$dataOptional	= [];
			$image 			= null;

			if (isset($pushQueue['campaign']['campaign_push_image']) && $pushQueue['campaign']['campaign_push_image'] != null) {
				$dataOptional['image'] = env('S3_URL_API').$pushQueue['campaign']['campaign_push_image'];
				$image = env('S3_URL_API').$pushQueue['campaign']['campaign_push_image'];
			}

			if (isset($pushQueue['campaign']['campaign_push_clickto']) && $pushQueue['campaign']['campaign_push_clickto'] != null) {
				$dataOptional['type'] = $pushQueue['campaign']['campaign_push_clickto'];
			} else {
				$dataOptional['type'] = 'Home';
			}

			if (isset($pushQueue['campaign']['campaign_push_link']) && $pushQueue['campaign']['campaign_push_link'] != null) {
				if($dataOptional['type'] == 'Link')
					$dataOptional['link'] = $pushQueue['campaign']['campaign_push_link'];
				else
					$dataOptional['link'] = null;
			} else {
				$dataOptional['link'] = null;
			}

			if (isset($pushQueue['campaign']['campaign_push_id_reference']) && $pushQueue['campaign']['campaign_push_id_reference'] != null) {
				$dataOptional['id_reference'] = (int)$pushQueue['campaign']['campaign_push_id_reference'];
			} else{
				$dataOptional['id_reference'] = 0;
			}

			if($pushQueue['campaign']['campaign_push_clickto'] == 'News' && $pushQueue['campaign']['campaign_push_id_reference'] != null){
				$news = News::find($pushQueue['campaign_push_id_reference']);
				if($news){
					$dataOptional['news_title'] = $news->news_title;
				}
				$dataOptional['url'] = env('APP_URL').'news/webview/'.$pushQueue['campaign_push_id_reference'];
			}

			if($pushQueue['campaign_push_clickto'] == 'Order' && $pushQueue['campaign_push_id_reference'] != null){
				$outlet = Outlet::find($pushQueue['campaign_push_id_reference']);
				if($outlet){
					$dataOptional['news_title'] = $outlet->outlet_name;
				}
			}

			//push notif logout
			if($pushQueue['campaign_push_clickto'] == 'Logout'){
				$user = User::where('phone', $receipient)->first();
				if($user){
					//delete token
                			$del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
               		 			->where('oauth_access_tokens.user_id', $user['id'])->where('oauth_access_token_providers.provider', 'users')->delete();

				}

			}


			$deviceToken = PushNotificationHelper::searchDeviceToken("phone", $receipient);

			$subject = $pushQueue['push_queue_subject'];
			$content = $pushQueue['push_queue_content'];
			$deviceToken = PushNotificationHelper::searchDeviceToken("phone", $receipient);

			if (!empty($deviceToken)) {
				if (isset($deviceToken['token']) && !empty($deviceToken['token'])) {
					$push = PushNotificationHelper::sendPush($deviceToken['token'], $subject, $content, $image, $dataOptional);

					// insert push sent
					if (isset($push['success']) && $push['success'] > 0) {
						$push = [];
						$push['id_campaign'] = $pushQueue['id_campaign'];
						$push['push_sent_to'] = $receipient;
						$push['push_sent_subject'] = $subject;
						$push['push_sent_content'] = $content;
						$push['push_sent_send_at'] = date('Y-m-d H:i:s');

					$logs = CampaignPushSent::create($push);
					}
				}
			}

			//update count campaign
			DB::table('campaigns')
			->where('id_campaign', $pushQueue['id_campaign'])
			->update([
				'campaign_push_count_sent' => DB::raw('campaign_push_count_sent + 1'),
				'campaign_push_count_queue' => DB::raw('campaign_push_count_queue - 1')
				]);

			// delete from push queue
			$deleteQueue = CampaignPushQueue::where('id_campaign_push_queue', $pushQueue['id_campaign_push_queue'])->delete();
		}

		// send 10 whatsapp queue
		$whatsappQueues = CampaignWhatsappQueue::where('whatsapp_queue_send_at', '<=', $now)->orderBy('whatsapp_queue_send_at', 'ASC')->limit(10)->get();
		foreach ($whatsappQueues as $key => $whatsappQueue) {

			//cek api key whatsapp
			$api_key = Setting::where('key', 'api_key_whatsapp')->first();
			if($api_key){
				if($api_key->value){

					$receipient = $whatsappQueue['whatsapp_queue_to'];

					//send every content whatsapp
					$contentWaSent = [];

					$contentWhatsapps = WhatsappContent::where('source', 'campaign')->where('id_reference', $whatsappQueue['id_campaign'])->get();
					foreach($contentWhatsapps as $contentWhatsapp){
						if($contentWhatsapp['content_type'] == 'text'){
							$content = app($this->autocrm)->TextReplace($contentWhatsapp['content'], $receipient);
						}else{
							$content = $contentWhatsapp['content'];
						}
						// add country code in number
						$ptn = "/^0/";
						$rpltxt = "62";
						$phone = preg_replace($ptn, $rpltxt, $receipient);

						$send = $this->apiwha->send($api_key->value, $phone, $content);

						// //api key whatsapp not valid
						if(isset($send['result_code']) && $send['result_code'] == -1){
							break 2;
						}

						$dataContent['content'] = $content;
						$dataContent['content_type'] = $contentWhatsapp['content_type'];
						array_push($contentWaSent, $dataContent);

					}

					// insert to whatsapp sent
					$outbox = [];
					$outbox['id_campaign'] = $whatsappQueue['id_campaign'];
					$outbox['whatsapp_sent_to'] = $receipient;
					$outbox['whatsapp_sent_send_at'] = date("Y-m-d H:i:s");

					$logs = CampaignWhatsappSent::create($outbox);
					if($logs){
						// insert to campaign whatsapp content
						foreach($contentWaSent as $data){
							$dataContentWhatsapp['content'] = $data['content'];
							$dataContentWhatsapp['content_type'] = $data['content_type'];
							$dataContentWhatsapp['id_campaign_whatsapp_sent'] =  $logs['id_campaign_whatsapp_sent'];
							$create = CampaignWhatsappSentContent::create($dataContentWhatsapp);
						}
					}

					//update count campaign
					DB::table('campaigns')
					->where('id_campaign', $whatsappQueue['id_campaign'])
					->update([
						'campaign_whatsapp_count_all' => DB::raw('campaign_whatsapp_count_all + 1'),
						'campaign_whatsapp_count_queue' => DB::raw('campaign_whatsapp_count_queue - 1')
					]);

					// delete from sms queue
					$deleteQueue = CampaignWhatsappQueue::where('id_campaign_whatsapp_queue', $whatsappQueue['id_campaign_whatsapp_queue'])->delete();

				}
			}

		}

		return response()->json([
			'status' => 'success',
			'result' => count($emailQueues).' email, '.count($smsQueues).' sms, '.count($pushQueues).' push notification, '.count($whatsappQueues).' whatsapp has been sent.'
		]);
	}

    public function update(Request $request){
		$post = $request->json()->all();

		$id_campaign = $post['id_campaign'];
		if(isset($post['campaign_email_receipient']) && $post['campaign_email_receipient'] != "")
			$post['campaign_email_count_all'] = count(explode(',',$post['campaign_email_receipient']));
		if(isset($post['campaign_sms_receipient']) && $post['campaign_sms_receipient'] != "")
			$post['campaign_sms_count_all'] = count(explode(',',$post['campaign_sms_receipient']));
		if(isset($post['campaign_push_receipient']) && $post['campaign_push_receipient'] != "")
			$post['campaign_push_count_all'] = count(explode(',',$post['campaign_push_receipient']));
		if(isset($post['campaign_inbox_receipient']) && $post['campaign_inbox_receipient'] != "")
			$post['campaign_inbox_count'] = count(explode(',',$post['campaign_inbox_receipient']));
		if(isset($post['campaign_whatsapp_receipient']) && $post['campaign_whatsapp_receipient'] != "")
			$post['campaign_whatsapp_count_all'] = count(explode(',',$post['campaign_whatsapp_receipient']));

		if (isset($post['campaign_push_image'])) {
			$upload = MyHelper::uploadPhoto($post['campaign_push_image'], $path = 'img/push/', 600);

			if ($upload['status'] == "success") {
				$post['campaign_push_image'] = $upload['path'];
			} else{
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Update Push Notification Image failed.']
					];
				return response()->json($result);
			}
		}

		$campaign=Campaign::where('id_campaign',$id_campaign)->first();
		DB::beginTransaction();
		if($campaign->campaign_generate_receipient=='Now'){
			GenerateCampaignRecipient::dispatch($post)->allOnConnection('database');
		}
		if($campaign->campaign_send_at&&$campaign->campaign_send_at<date('Y-m-d H:i:s')){
			$post['campaign_send_at']=date('Y-m-d H:i:s');
		}
		unset($post['id_campaign']);
		$post['campaign_complete']=1;

		$contentWa = null;
		if(isset($post['campaign_whatsapp_content'])){
			$contentWa = $post['campaign_whatsapp_content'];
			unset($post['campaign_whatsapp_content']);
		}

		$query = Campaign::where('id_campaign','=',$id_campaign)->update($post);

		if($query){
			//whatsapp contents
			if($contentWa){

				//delete content
				$idOld = array_filter(array_pluck($contentWa,'id_whatsapp_content'));
				$contentOld = WhatsappContent::where('source', 'campaign')->where('id_reference', $id_campaign)->whereNotIn('id_whatsapp_content', $idOld)->get();
				if(count($contentOld) > 0){
					foreach($contentOld as $old){
						if($old['content_type'] == 'image' || $old['content_type'] == 'file'){
							$del = MyHelper::deletePhoto(str_replace(env('S3_URL_API'), '', $old['content']));
						}
					}

					$delete =  WhatsappContent::where('source', 'campaign')->where('id_reference', $id_campaign)->whereNotIn('id_whatsapp_content', $idOld)->delete();
					if(!$delete){
						DB::rollBack();
						$result = [
								'status'	=> 'fail',
								'messages'	=> ['Update WhatsApp Content Failed.']
							];
						return response()->json($result);
					}
				}

				//create or update content
				foreach($contentWa as $content){

					if($content['content']){
						//delete file if update
						if($content['id_whatsapp_content']){
							$whatsappContent = WhatsappContent::find($content['id_whatsapp_content']);
							if($whatsappContent && ($whatsappContent->content_type == 'image' || $whatsappContent->content_type == 'file')){
								MyHelper::deletePhoto($whatsappContent->content);
							}
						}

						if($content['content_type'] == 'image'){
							if (!file_exists('whatsapp/img/campaign/')) {
								mkdir('whatsapp/img/campaign/', 0777, true);
							}

							//upload file
							$upload = MyHelper::uploadPhoto($content['content'], $path = 'whatsapp/img/campaign/');
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
							if (!file_exists('whatsapp/file/campaign/')) {
								mkdir('whatsapp/file/campaign/', 0777, true);
							}

							$i = 1;
							$filename = $content['content_file_name'];
							while (file_exists('whatsapp/file/campaign/'.$content['content_file_name'].'.'.$content['content_file_ext'])) {
								$content['content_file_name'] = $filename.'_'.$i;
								$i++;
							}

							$upload = MyHelper::uploadFile($content['content'], $path = 'whatsapp/file/campaign/', $content['content_file_ext'], $content['content_file_name']);
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

						$dataContent['source'] 		 = 'campaign';
						$dataContent['id_reference'] = $id_campaign;
						$dataContent['content_type'] = $content['content_type'];
						$dataContent['content'] 	 = $content['content'];

						//for update
						if($content['id_whatsapp_content']){
							$whatsappContent = WhatsappContent::where('id_whatsapp_content',$content['id_whatsapp_content'])->update($dataContent);
						}
						//for create
						else{
							$whatsappContent = WhatsappContent::create($dataContent);
						}

						if(!$whatsappContent){
							DB::rollBack();
							$result = [
									'status'	=> 'fail',
									'messages'	=> ['Update WhatsApp Content Failed.']
								];
							return response()->json($result);
						}
					}

				}
			}

			DB::commit();
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			DB::rollBack();
			$result = [
					'status'  => 'fail',
					'messages'  => ['Campaign Update Failed']
				];
		}
		return response()->json($result);
    }

    public function campaignEmailOutboxList(Request $request){
		$post = $request->json()->all();

		$query = CampaignEmailSent::join('campaigns','campaigns.id_campaign','=','campaign_email_sents.id_campaign')
									->orderBy('id_campaign_email_sent', 'Desc');
		$count = CampaignEmailSent::join('campaigns','campaigns.id_campaign','=','campaign_email_sents.id_campaign')->get();

		if(isset($post['email_sent_subject']) && $post['email_sent_subject'] != ""){
			$query = $query->where('email_sent_subject','like','%'.$post['email_sent_subject'].'%');
			$count = $count->where('email_sent_subject','like','%'.$post['email_sent_subject'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Email Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignEmailOutboxDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignEmailSent::join('campaigns','campaigns.id_campaign','=','campaign_email_sents.id_campaign')
								->where('id_campaign_email_sent',$post['id_campaign_email_sent'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Email Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignEmailQueueList(Request $request){
		$post = $request->json()->all();

		$query = CampaignEmailQueue::join('campaigns','campaigns.id_campaign','=','campaign_email_queues.id_campaign')
									->orderBy('id_campaign_email_queue', 'Desc');
		$count = CampaignEmailQueue::join('campaigns','campaigns.id_campaign','=','campaign_email_queues.id_campaign')->get();

		if(isset($post['email_queue_subject']) && $post['email_queue_subject'] != ""){
			$query = $query->where('email_queue_subject','like','%'.$post['email_queue_subject'].'%');
			$count = $count->where('email_queue_subject','like','%'.$post['email_queue_subject'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Email Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignEmailQueueDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignEmailQueue::join('campaigns','campaigns.id_campaign','=','campaign_email_queues.id_campaign')
								->where('id_campaign_email_queue',$post['id_campaign_email_queue'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Email Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignSmsOutboxList(Request $request){
		$post = $request->json()->all();

		$query = CampaignSmsSent::join('campaigns','campaigns.id_campaign','=','campaign_sms_sents.id_campaign')
									->orderBy('id_campaign_sms_sent', 'Desc');
		$count = CampaignSmsSent::join('campaigns','campaigns.id_campaign','=','campaign_sms_sents.id_campaign')->get();

		if(isset($post['sms_sent_content']) && $post['sms_sent_content'] != ""){
			$query = $query->where('sms_sent_content','like','%'.$post['sms_sent_content'].'%');
			$count = $count->where('sms_sent_content','like','%'.$post['sms_sent_content'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign SMS Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignSmsOutboxDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignSmsSent::join('campaigns','campaigns.id_campaign','=','campaign_sms_sents.id_campaign')
								->where('id_campaign_sms_sent',$post['id_campaign_sms_sent'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Sms Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignSmsQueueList(Request $request){
		$post = $request->json()->all();

		$query = CampaignSmsQueue::join('campaigns','campaigns.id_campaign','=','campaign_sms_queues.id_campaign')
									->orderBy('id_campaign_sms_queue', 'Desc');
		$count = CampaignSmsQueue::join('campaigns','campaigns.id_campaign','=','campaign_sms_queues.id_campaign')->get();

		if(isset($post['sms_queue_content']) && $post['sms_queue_content'] != ""){
			$query = $query->where('sms_queue_content','like','%'.$post['sms_queue_content'].'%');
			$count = $count->where('sms_queue_content','like','%'.$post['sms_queue_content'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign SMS Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignSmsQueueDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignSmsQueue::join('campaigns','campaigns.id_campaign','=','campaign_sms_queues.id_campaign')
								->where('id_campaign_sms_queue',$post['id_campaign_sms_queue'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Sms Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignPushOutboxList(Request $request){
		$post = $request->json()->all();

		$query = CampaignPushSent::join('campaigns','campaigns.id_campaign','=','campaign_push_sents.id_campaign')
									->orderBy('id_campaign_push_sent', 'Desc');
		$count = CampaignPushSent::join('campaigns','campaigns.id_campaign','=','campaign_push_sents.id_campaign')->get();

		if(isset($post['push_sent_subject']) && $post['push_sent_subject'] != ""){
			$query = $query->where('push_sent_subject','like','%'.$post['push_sent_subject'].'%');
			$count = $count->where('push_sent_subject','like','%'.$post['push_sent_subject'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Push Notification Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignPushOutboxDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignPushSent::join('campaigns','campaigns.id_campaign','=','campaign_push_sents.id_campaign')
								->where('id_campaign_push_sent',$post['id_campaign_push_sent'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Push Notification Outbox']
				];
		}
		return response()->json($result);
	}
	public function campaignPushQueueList(Request $request){
		$post = $request->json()->all();

		$query = CampaignPushQueue::join('campaigns','campaigns.id_campaign','=','campaign_push_queues.id_campaign')
									->orderBy('id_campaign_push_queue', 'Desc');
		$count = CampaignPushQueue::join('campaigns','campaigns.id_campaign','=','campaign_push_queues.id_campaign')->get();

		if(isset($post['push_queue_subject']) && $post['push_queue_subject'] != ""){
			$query = $query->where('push_queue_subject','like','%'.$post['push_queue_subject'].'%');
			$count = $count->where('push_queue_subject','like','%'.$post['push_queue_subject'].'%');
		}

		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Push Notification Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignPushQueueDetail(Request $request){
		$post = $request->json()->all();

		$query = CampaignPushQueue::join('campaigns','campaigns.id_campaign','=','campaign_push_queues.id_campaign')
								->where('id_campaign_push_queue',$post['id_campaign_push_queue'])
								->first();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Push Notification Queue']
				];
		}
		return response()->json($result);
	}

	public function campaignWhatsappOutboxList(Request $request){
		$post = $request->json()->all();

		$query = CampaignWhatsappSent::join('campaigns','campaigns.id_campaign','=','campaign_whatsapp_sents.id_campaign')
									->orderBy('campaign_whatsapp_sents.id_campaign_whatsapp_sent', 'Desc');
		$count = CampaignWhatsappSent::join('campaigns','campaigns.id_campaign','=','campaign_whatsapp_sents.id_campaign')->get();

		if(isset($post['id_campaign_whatsapp_sent'])){
			$query = $query->where('campaign_whatsapp_sents.id_campaign_whatsapp_sent', $post['id_campaign_whatsapp_sent'])
						   ->with('campaign_whatsapp_sent_content');
		}else{
			$query = $query->skip($post['skip'])->take($post['take']);
		}

		$query = $query->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Campaign Whatsapp Outbox']
				];
		}
		return response()->json($result);
	}

	public function campaignWhatsappQueueList(Request $request){
		$post = $request->json()->all();

		$query = CampaignWhatsappQueue::join('campaigns','campaigns.id_campaign','=','campaign_whatsapp_queues.id_campaign')
									->orderBy('id_campaign_whatsapp_queue', 'Desc');
		$count = CampaignWhatsappQueue::join('campaigns','campaigns.id_campaign','=','campaign_whatsapp_queues.id_campaign')->get();

		if(isset($post['id_campaign_whatsapp_queue'])){
			$query = $query->where('id_campaign_whatsapp_queue', $post['id_campaign_whatsapp_queue']);
		}else{
			$query = $query->skip($post['skip'])->take($post['take']);
		}

		$query = $query->get()->toArray();
		$count = $count->count();

		if(isset($query) && !empty($query)) {
			if(isset($post['id_campaign_whatsapp_queue'])){
				$query[0]['campaign_whatsapp_queue_content'] = WhatsappContent::where('source', 'campaign')->where('id_reference', $query[0]['id_campaign'])->get()->toArray();
			}
		}

		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Whatsapp Email Queue']
				];
		}
		return response()->json($result);
	}
}
