<?php

namespace App\Lib;

class classJatisSMS {
	protected $data;
	protected $smsserverip;
	public function setData($data) {
		$this->data = $data;
	}
	public function send() {
		$dt=http_build_query($this->data);
		$curlHandle = curl_init(env('SMS_URL'));
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
            )
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
        if(isset($this->data['msisdn'])){
            if(substr($this->data['msisdn'], 0, 2) == '62'){
                $phone = '0'.substr($this->data['msisdn'],2);
            }else{
                $phone = $this->data['msisdn'];
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
}