<?php

namespace App\Lib;

use GuzzleHttp\Client;
use Modules\Disburse\Entities\LogIRIS;

class classMaskingJson {
	protected $data;
	protected $smsserverip;
	public function setData($data) {
		$this->data = $data;
	}
	public function send() {
		$dt=json_encode($this->data);
		$curlHandle = curl_init(env('SMS_URL')."/sms/api_sms_masking_send_json.php");
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dt))
		);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 5);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);

		$hasil = curl_exec($curlHandle);
		$curl_response = $hasil;
		$curl_errno = curl_errno($curlHandle);
		$curl_error = curl_error($curlHandle);
		$http_code  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		$curl_info=json_encode(curl_getinfo($curlHandle));
		curl_close($curlHandle);
		if ($curl_errno > 0) {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil=json_encode($senddata);
		} else {
			if ($http_code<>"200") {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil= json_encode($senddata);
			}
		}

        $phone = null;
        if(isset($this->data['number'])){
            if(substr($this->data['number'], 0, 2) == '62'){
                $phone = '0'.substr($this->data['number'],2);
            }else{
                $phone = $this->data['number'];
            }
        }
		$log=[
			'request_body'=>$this->data,
			'request_url'=>env('SMS_URL'),
			'response'=>$curl_response,
			'phone'=>$phone
		];
		MyHelper::logApiSMS($log);

		return $hasil;
	}
	public function balance() {
		$dt=json_encode($this->data);
		$curlHandle = curl_init(env('SMS_URL')."/sms/api_sms_masking_balance_json.php");
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dt))
		);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 5);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);
		$hasil = curl_exec($curlHandle);
		$curl_errno = curl_errno($curlHandle);
		$curl_error = curl_error($curlHandle);
		$http_code  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);
		if ($curl_errno > 0) {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil=json_encode($senddata);
		} else {
			if ($http_code<>"200") {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil= json_encode($senddata);
			}
		}
		return $hasil;
	}

    public function sendSMS() {
	    if(env('SMS_OTP_TYPE') == 'MISSCALL'){
            ob_start();

            $phone = null;
            if(isset($this->data['datapacket'][0]['number'])){
                if(substr($this->data['datapacket'][0]['number'], 0, 2) == '62'){
                    $phone = '0'.substr($this->data['datapacket'][0]['number'],2);
                }else{
                    $phone = $this->data['datapacket'][0]['number'];
                }
            }

            // setting
            $urlendpoint = 'http://sms114.xyz/sms/api_misscall_otp_send_json.php'; // url endpoint api
            $apikey      = env('SMS114_API_KEY'); // api key
            $callbackurl = env('SMS141_URL_CALLBACK'); // url callback get status sms
            $number      = $phone; // destinationnumber
            $message     = $this->data['datapacket'][0]['otp']; // misscall number code otp

            // sending
            $senddata = array(
                'apikey' => $apikey,
                'callbackurl' => $callbackurl,
                'number' => $number,
                'message' => $message
            );

            // sending
            $data=json_encode($senddata);
            $curlHandle = curl_init($urlendpoint);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data))
            );
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
            $respon = curl_exec($curlHandle);
            curl_close($curlHandle);
            header('Content-Type: application/json');

            $log=[
                'request_body'=>$this->data,
                'request_url'=>$urlendpoint,
                'response'=>$respon,
                'phone'=>$phone
            ];
            MyHelper::logApiSMS($log);
            return $respon;
        }
    }
}