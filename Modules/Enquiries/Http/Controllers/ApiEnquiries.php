<?php

namespace Modules\Enquiries\Http\Controllers;

use App\Http\Models\Enquiry;
use App\Http\Models\EnquiriesPhoto;
use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use App\Lib\classMaskingJson;
use App\Lib\classJatisSMS;
use App\Lib\ValueFirst;
use Hash;
use App\Lib\PushNotificationHelper;
use DB;
use Mail;
use File;

use Modules\Enquiries\Http\Requests\Create;
use Modules\Enquiries\Http\Requests\Update;
use Modules\Enquiries\Http\Requests\Delete;
use Modules\Enquiries\Entities\EnquiriesFile;
use Modules\Brand\Entities\Brand;

class ApiEnquiries extends Controller
{

	public $saveImage 	= "img/enquiry/";
	public $saveFile 	= "files/enquiry/";
    public $endPoint;

	function __construct() {
		date_default_timezone_set('Asia/Jakarta');
		$this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		$this->rajasms = new classMaskingJson();
		$this->jatissms = new classJatisSMS();
		$this->endPoint = config('url.storage_url_api');
	}
    /* Cek inputan */
    function cekInputan($post = []) {
    	// print_r($post); exit();
        $data = [];

		if (isset($post['id_brand'])) {
            $data['id_brand'] = $post['id_brand'];
		}

		if (isset($post['id_outlet'])) {
			$data['id_outlet'] = $post['id_outlet'];
		} else {
			$data['id_outlet'] = null;
		}

        if (isset($post['enquiry_name'])) {
            $data['enquiry_name'] = $post['enquiry_name'];
        }else{
			$data['enquiry_name'] = null;
		}

        if (isset($post['enquiry_phone'])) {
            $data['enquiry_phone'] = $post['enquiry_phone'];
        }else{
			$data['enquiry_phone'] = null;
		}

        if (isset($post['enquiry_email'])) {
            $data['enquiry_email'] = $post['enquiry_email'];
        }else{
			$data['enquiry_email'] = null;
		}

        if (isset($post['enquiry_subject'])) {
            $data['enquiry_subject'] = $post['enquiry_subject'];
          if ($post['enquiry_subject'] == "Customer Feedback") {
            if (isset($post['visiting_time'])) {
              $data['visiting_time'] = $post['visiting_time'];
            }
          }

          if ($post['enquiry_subject'] == "Career") {
            if (isset($post['position'])) {
              $data['position'] = $post['position'];
            }
          }
        }

        if (isset($post['enquiry_content'])) {
            $data['enquiry_content'] = $post['enquiry_content'];
        }else{
			$data['enquiry_content'] = null;
		}

		if (isset($post['enquiry_device_token'])) {
            $data['enquiry_device_token'] = $post['enquiry_device_token'];
        }else{
			$data['enquiry_device_token'] = null;
		}

		if (isset($post['enquiry_file'])) {
        	$dataUploadFile = [];

			if (is_array($post['enquiry_file'])) {
				for ($i=0; $i < count($post['enquiry_file']); $i++) {
					$upload = MyHelper::uploadFile($post['enquiry_file'][$i], $this->saveFile, strtolower($post['ext'][$i]));

					if (isset($upload['status']) && $upload['status'] == "success") {
					    $data['enquiry_file'] = $upload['path'];

					    array_push($dataUploadFile, $upload['path']);
					}
					else {
					    $result = [
					        'error'    => 1,
					        'status'   => 'fail',
					        'messages' => ['fail upload file']
					    ];

					    return $result;
					}
				}
			}
			else {
				$ext = MyHelper::checkMime2Ext($post['enquiry_file']);

				$upload = MyHelper::uploadFile($post['enquiry_file'], $this->saveFile, $post['ext']);

				if (isset($upload['status']) && $upload['status'] == "success") {
				    $data['enquiry_file'] = $upload['path'];

				    array_push($dataUploadFile, $upload['path']);
				}
				else {
				    $result = [
				        'error'    => 1,
				        'status'   => 'fail',
				        'messages' => ['fail upload file']
				    ];

				    return $result;
				}
			}

			$data['many_upload_file'] = $dataUploadFile;
        }

        if (isset($post['enquiry_status'])) {
            $data['enquiry_status'] = $post['enquiry_status'];
        }

        return $data;
	}

    /* CREATE */
    function create(Create $request) {
        $data = $this->cekInputan($request->json()->all());

        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
		}

		//cek brand
		if(isset($data['brand'])){
			$brand = Brand::find($data['id_brand']);
			if(!$brand){
				// return response()->json([
				// 	'status' => 'fail',
				// 	'messages' => ['Brand not found']
				// ]);
				$brand = null;
			}
		}else{
			$brand = null;
		}

		$save = Enquiry::create($data);

        // jika berhasil maka ngirim" ke crm
        if ($save) {

			$data['attachment'] = [];
			// save many file
        	if (isset($data['many_upload_file'])) {
        		$files = $this->saveFiles($save->id_enquiry, $data['many_upload_file']);
				$enquiryFile = EnquiriesFile::where('id_enquiry', $save->id_enquiry)->get();
				foreach($enquiryFile as $dataFile){
					$data['attachment'][] = $dataFile->url_enquiry_file;
				}
				unset($data['enquiry_file']);
        	}
			// send CRM
			$data['brand'] = $brand;
			$goCrm = $this->sendCrm($data);
			$data['id_enquiry'] = $save->id_enquiry;
		}
        return response()->json(MyHelper::checkCreate($data));
    }

	/* SAVE FILE BANYAK */
    function saveFiles($id, $file)
    {
    	$data = [];

    	foreach ($file as $key => $value) {
    		$temp = [
				'enquiry_file' 	=> $value,
				'id_enquiry'    => $id,
				'created_at'    => date('Y-m-d H:i:s'),
				'updated_at'    => date('Y-m-d H:i:s')
    		];
    		array_push($data, $temp);
    	}

    	if (!empty($data)) {
    		if (!EnquiriesFile::insert($data)) {
    			return false;
    		}
    	}

    	return true;
	}

	/* REPLY */
    function reply(Request $request) {
		$post = $request->json()->all();
		// return $post;
		$id_enquiry = $post['id_enquiry'];
		$check = Enquiry::where('id_enquiry', $id_enquiry)->first();

		$aditionalVariabel = [
            'enquiry_subject' => $check['enquiry_subject'],
            'enquiry_message' => $check['enquiry_content'],
            'enquiry_phone'   => $check['enquiry_phone'],
            'enquiry_name'    => $check['enquiry_name'],
            'enquiry_email'   => $check['enquiry_email'],
            'visiting_time'   => isset($check['visiting_time'])?$check['visiting_time']:""];

		if(isset($post['reply_email_subject']) && $post['reply_email_subject'] != ""){
			if($check['reply_email_subject'] == null && $check['enquiry_email'] != null){
				$to = $check['enquiry_email'];
				if($check['enquiry_name'] != "")
					$name = $check['enquiry_name'];
				else $name = "Customer";

                $subject = app($this->autocrm)->TextReplace($post['reply_email_subject'], $check['enquiry_phone'], $aditionalVariabel);
                $content = app($this->autocrm)->TextReplace($post['reply_email_content'], $check['enquiry_phone'], $aditionalVariabel);

				// get setting email
				$setting = array();
				$set = Setting::where('key', 'email_from')->first();
				if(!empty($set)){
					$setting['email_from'] = $set['value'];
				}else{
					$setting['email_from'] = null;
				}
				$set = Setting::where('key', 'email_sender')->first();
				if(!empty($set)){
					$setting['email_sender'] = $set['value'];
				}else{
					$setting['email_sender'] = null;
				}
				$set = Setting::where('key', 'email_reply_to')->first();
				if(!empty($set)){
					$setting['email_reply_to'] = $set['value'];
				}else{
					$setting['email_reply_to'] = null;
				}
				$set = Setting::where('key', 'email_reply_to_name')->first();
				if(!empty($set)){
					$setting['email_reply_to_name'] = $set['value'];
				}else{
					$setting['email_reply_to_name'] = null;
				}
				$set = Setting::where('key', 'email_cc')->first();
				if(!empty($set)){
					$setting['email_cc'] = $set['value'];
				}else{
					$setting['email_cc'] = null;
				}
				$set = Setting::where('key', 'email_cc_name')->first();
				if(!empty($set)){
					$setting['email_cc_name'] = $set['value'];
				}else{
					$setting['email_cc_name'] = null;
				}
				$set = Setting::where('key', 'email_bcc')->first();
				if(!empty($set)){
					$setting['email_bcc'] = $set['value'];
				}else{
					$setting['email_bcc'] = null;
				}
				$set = Setting::where('key', 'email_bcc_name')->first();
				if(!empty($set)){
					$setting['email_bcc_name'] = $set['value'];
				}else{
					$setting['email_bcc_name'] = null;
				}
				$set = Setting::where('key', 'email_logo')->first();
				if(!empty($set)){
					$setting['email_logo'] = $set['value'];
				}else{
					$setting['email_logo'] = null;
				}
				$set = Setting::where('key', 'email_logo_position')->first();
				if(!empty($set)){
					$setting['email_logo_position'] = $set['value'];
				}else{
					$setting['email_logo_position'] = null;
				}
				$set = Setting::where('key', 'email_copyright')->first();
				if(!empty($set)){
					$setting['email_copyright'] = $set['value'];
				}else{
					$setting['email_copyright'] = null;
				}
				$set = Setting::where('key', 'email_contact')->first();
				if(!empty($set)){
					$setting['email_contact'] = $set['value'];
				}else{
					$setting['email_contact'] = null;
				}

				$data = array(
					'customer' => $name,
					'html_message' => $content,
					'setting' => $setting
				);
				// return $data;
				Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
				{
					$message->to($to, $name)->subject($subject);
					if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
						$message->from($setting['email_sender'], $setting['email_from']);
					}else if(!empty($setting['email_sender'])){
						$message->from($setting['email_sender']);
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
			}
		}

		if(isset($post['reply_sms_content'])){
			if($check['reply_sms_content'] == null && $check['enquiry_phone'] != null){
                $content = app($this->autocrm)->TextReplace($post['reply_sms_content'], $check['enquiry_phone'], $aditionalVariabel);
				switch (env('SMS_GATEWAY')) {
					case 'Jatis':
						$senddata = [
							'userid'	=> env('SMS_USER'),
							'password'	=> env('SMS_PASSWORD'),
							'msisdn'	=> '62'.substr($check['enquiry_phone'],1),
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
                    case 'ValueFirst':
                        $sendData = [
                            'to' => trim($check['enquiry_phone']),
                            'text' => $content
                        ];

                        ValueFirst::create()->send($sendData);
                        break;
					default:
						$senddata = array(
								'apikey' => env('SMS_KEY'),
								'callbackurl' => config('url.app_url'),
								'datapacket'=>array()
							);
						array_push($senddata['datapacket'],array(
											'number' => trim($check['enquiry_phone']),
											'message' => urlencode(stripslashes(utf8_encode($content))),
											'sendingdatetime' => ""));

						$this->rajasms->setData($senddata);

						$send = $this->rajasms->send();
						break;
				}
			}
		}

		if(isset($post['reply_push_subject'])){
			if(!empty($post['reply_push_subject'])){
				try {
					$dataOptional          = [];
					$image = null;

					if (isset($post['reply_push_image'])) {
						$upload = MyHelper::uploadPhoto($post['reply_push_image'], $path = 'img/push/', 600);

						if ($upload['status'] == "success") {
							$post['reply_push_image'] = $upload['path'];
						} else{
							$result = [
									'status'	=> 'fail',
									'messages'	=> ['Update Push Notification Image failed.']
								];
							return response()->json($result);
						}
					}

					if (isset($post['reply_push_image']) && $post['reply_push_image'] != null) {
						$dataOptional['image'] = config('url.storage_url_api').$post['reply_push_image'];
						$image = config('url.storage_url_api').$post['reply_push_image'];
					}

					if (isset($post['reply_push_clickto']) && $post['reply_push_clickto'] != null) {
						$dataOptional['type'] = $post['reply_push_clickto'];
					} else {
						$dataOptional['type'] = 'Home';
					}

					if (isset($post['reply_push_link']) && $post['reply_push_link'] != null) {
						if($dataOptional['type'] == 'Link')
							$dataOptional['link'] = $post['reply_push_link'];
						else
							$dataOptional['link'] = null;
					} else {
						$dataOptional['link'] = null;
					}

                    if (isset($post['reply_push_id_reference']) && $post['reply_push_id_reference'] != null) {
                        if($dataOptional['type'] !== 'Home'){
                            $dataOptional['type'] = 'Detail '.$dataOptional['type'];
                        }
                        $dataOptional['id_reference'] = (int)$post['reply_push_id_reference'];
                    } else{
                        if($dataOptional['type'] !== 'Home'){
                            $dataOptional['type'] = 'List '.$dataOptional['type'];
                        }
                        $dataOptional['id_reference'] = 0;
                    }
					// return $dataOptional;

					$deviceToken = array($check['enquiry_device_token']);


                    $subject = app($this->autocrm)->TextReplace($post['reply_push_subject'], $check['enquiry_phone'], $aditionalVariabel);
                    $content = app($this->autocrm)->TextReplace($post['reply_push_content'], $check['enquiry_phone'], $aditionalVariabel);

					if (!empty($deviceToken)) {
							$push = PushNotificationHelper::sendPush($deviceToken, $subject, $content, $image, $dataOptional);
							// return $push;
					}
				} catch (\Exception $e) {
					return response()->json(MyHelper::throwError($e));
				}
			}
		}

		unset($post['id_enquiry']);
		$post['enquiry_status'] = 'Read';
		// return $post;
		$update = Enquiry::where('id_enquiry', $id_enquiry)->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /* UPDATE */
    function update(Update $request) {
        $data = $request->json()->all();

        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }

        $update = Enquiry::where('id_enquiry', $request->json('id_enquiry'))->update($data);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /* DELETE */
    function delete(Delete $request) {
        $delete = Enquiry::where('id_enquiry', $request->json('id_enquiry'))->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* LIST */
    function index(Request $request) {
        $post = $request->json()->all();

        $data = Enquiry::with(['brand', 'outlet', 'files']);

        if (isset($post['id_enquiry'])) {
            $data->where('id_enquiry', $post['id_enquiry']);
        }

        if (isset($post['enquiry_phone'])) {
            $data->where('enquiry_phone', $post['enquiry_phone']);
        }

        if (isset($post['enquiry_subject'])) {
            $data->where('enquiry_subject', $post['enquiry_subject']);
        }

        $data = $data->orderBy('id_enquiry','desc')->get()->toArray();

        return response()->json(MyHelper::checkGet($data));
    }

    /* SEND CRM */
    function sendCrm($data) {
		$outlet_name = "";
		$outlet_code = "";
		if($data['id_outlet']){
			$outlet = Outlet::find($data['id_outlet']);
			if(isset($outlet['outlet_name'])){
				$outlet_name = $outlet['outlet_name'];
				$outlet_code = $outlet['outlet_code'];
			}
		}
		if(!isset($data['brand']['name_brand'])){
			$data['brand']['name_brand']="";
		}
        $send = app($this->autocrm)->SendAutoCRM('Enquiry '.$data['enquiry_subject'], $data['enquiry_phone'], [
                                                                'enquiry_subject' => $data['enquiry_subject'],
                                                                'enquiry_message' => $data['enquiry_content'],
                                                                'enquiry_phone'   => $data['enquiry_phone'],
                                                                'enquiry_name'    => $data['enquiry_name'],
																'enquiry_email'   => $data['enquiry_email'],
																'outlet_name'     => $outlet_name,
																'outlet_code'     => $outlet_code,
																'brand'     	  => $data['brand']['name_brand'],
																'visiting_time'   => isset($data['visiting_time'])?$data['visiting_time']:"",
																'position'   	  => isset($data['position'])?$data['position']:"",
																'attachment' 	  => $data['attachment']
                                                            ]);
		// print_r($send);exit;
        return $send;
    }

	function listEnquirySubject(){
		$list = Setting::where('key', 'enquiries_subject_list')->get()->first();

		$result = ['text' => $list['value'], 'value' => explode('| ' ,$list['value_text'])];
		foreach ($result['value'] as $key => $value) {
			$result['value'][$key] = explode('* ' ,$value);
		}
		return response()->json(MyHelper::checkGet($result));
	}

	function listEnquiryPosition(){
		$list = Setting::where('key', 'enquiries_position_list')->get()->first();

		$result = ['text' => $list['value'], 'value' => explode(', ' ,$list['value_text'])];
		return response()->json(MyHelper::checkGet($result));
	}
}
