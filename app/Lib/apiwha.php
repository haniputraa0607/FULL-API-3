<?php 

namespace App\Lib;

class apiwha {
	
	public function send($api_key, $number, $text) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://panel.apiwha.com/send_message.php?apikey=".$api_key."&number=".$number."&text=".urlencode($text),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		));

		$hasil = curl_exec($curl);
		$err = curl_error($curl);
		
		if ($err) {
			return json_encode($err);
		} else {
			return json_encode($hasil);
			
		}
	}
	public function balance() {	
		$dt=json_encode($this->data);
		$curlHandle = curl_init("http://162.211.84.203/sms/api_sms_masking_balance_json.php");
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