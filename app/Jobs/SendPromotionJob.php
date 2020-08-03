<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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

use App\Http\Models\OauthAccessToken;
//use Modules\Campaign\Http\Requests\campaign_list;
//use Modules\Campaign\Http\Requests\campaign_create;
//use Modules\Campaign\Http\Requests\campaign_update;
//use Modules\Campaign\Http\Requests\campaign_delete;

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

class SendPromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $user, $autocrm, $dealsVoucher, $dealsClaim, $deals, $rajasms, $jatissms, $apiwha;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
    	date_default_timezone_set('Asia/Jakarta');

        $this->data 		= $data;

        $this->user     	= "Modules\Users\Http\Controllers\ApiUser";
		$this->autocrm  	= "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->dealsVoucher = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
		$this->dealsClaim  	= "Modules\Deals\Http\Controllers\ApiDealsClaim";
		$this->deals  		= "Modules\Deals\Http\Controllers\ApiDeals";

		$this->rajasms 		= new classMaskingJson();
		$this->jatissms 	= new classJatisSMS();
		$this->apiwha 		= new apiwha();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
    	$now			= date('Y-m-d H:i:s');
    	$dataQueue 		= $this->data;
		$idref = null;
		if($dataQueue['content']['id_deals'] != null){
			$sendDeals = $this->sendDeals($dataQueue['content']['id_promotion_content'],$dataQueue['user']);

			if(isset($sendDeals['status']) && $sendDeals['status'] == 'success'){
				$idref = $sendDeals['result'][0]['id_deals_voucher'];
				$dataVoucher = $sendDeals['result'];
				$idDealsVoucher = array_pluck($dataVoucher,'id_deals_voucher');
			}else{
				$deleteQueue = PromotionQueue::where('id_promotion_queue', $dataQueue['id_promotion_queue'])->delete();
				return true;
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

		$deleteQueue = PromotionQueue::where('id_promotion_queue', $dataQueue['id_promotion_queue'])->delete();
		$insertPromotionSent = PromotionSent::insert($dataPromotionSent);

        return true;
    }

    function sendEmail($id_promotion_content, $user, $time){
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

	function sendSMS($id_promotion_content, $user){
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

	function sendPush($id_promotion_content, $user){
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

	function sendInbox($id_promotion_content, $user, $idref = null){
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

	function sendWhatsapp($id_promotion_content, $user){
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

	function sendDeals($id_promotion_content, $user){
		$promotionContent = PromotionContent::find($id_promotion_content);
		if(!$promotionContent){
			return [
				'status'  => 'fail',
				'message'  => 'Promotion Content not found.'
			];
		}
		$promotionContent = $promotionContent->toArray();
		$dataDeals = Deal::find($promotionContent['id_deals']);
		if (!$promotionContent['send_deals_expired']) {
			if ( !empty($dataDeals['deals_voucher_expired']) && $dataDeals['deals_voucher_expired'] < date('Y-m-d')) {
				return [
					'status'  => 'fail',
					'message'  => 'Deals Voucher is expired.'
				];
			}
		}

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
					// $dataDeals = Deal::find($promotionContent['id_deals']);
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
}
