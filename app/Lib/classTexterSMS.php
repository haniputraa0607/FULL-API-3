<?php 

namespace App\Lib;

class classTexterSMS {
	protected $to;
	protected $text;
	
	public function setTo($to) {
		$this->to = $to;
	}

	public function setText($text) {
		$this->text = $text;
	}
	
	private function curlpage($url,$cookies=0,$post=0,$header=0,$referrer=0){
		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if($header){
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		}
		if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
		curl_setopt($ch, CURLOPT_REFERER, $referrer); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($post){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
		$page = curl_exec( $ch);
		curl_close($ch); 
		return $page;
	}
	
	public function send(){
		if (!$this->to) {
			trigger_error('Error: Phone required!');
			exit();			
		}

		if (!$this->text)  {
			trigger_error('Error: Text Message required!');
			exit();					
		}
		
		$sms = array();
		$url = "http://api.textersms.com/oauth/token";
		$post = array();
		$post['grant_type'] = "password";
		$post['client_id'] = "1";
		$post['client_secret'] = "DEOTsx9waH8jbtVRSgru0UeMf3okPZan3vwnhBuS";
		$post['username'] = "ivankp@technopartner.id";
		$post['password'] = "ddtzddtz";
		$post['scope'] = "*";
		
		$page = json_decode($this->curlpage($url,0,$post), true);
		
		$header = array();
		$header[] = 'Accept: application/json';
		$header[] = 'Content-type: application/json';
		$header[] = 'Authorization: Bearer '.$page['access_token'];
		
		date_default_timezone_set('Asia/Jakarta');
		
		$url = "http://api.textersms.com/api/sms/send"; 
		$post = array();
		$post['id_company_modem'] = "112";
		$post['no_hp'] = $this->to;
		$post['message']= $this->text;
		$post['tipe'] = "2 way SMS";
		$post['category'] = "Customer SMS";
		
		$page = json_decode($this->curlpage($url,0,json_encode($post),$header), true);
		
		return $page;
	}
}