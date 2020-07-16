<?php
namespace App\Lib;

/**
 * 
 */
class ValueFirst
{
	public static $obj = null;
	/**
	 * Create object from static function
	 * @return ValueFirst ValueFirst Instance
	 */
	public static function create() {
		if(!self::$obj){
			self::$obj = new self();
		}
		return self::$obj;
	}

	public function __construct()
	{
		$this->json_endpoint = env('VALUEFIRST_JSON_ENDPOINT','https://api.myvaluefirst.com/psms/servlet/psms.JsonEservice');
		$this->http_endpoint = env('VALUEFIRST_HTTP_ENDPOINT','http://www.myvaluefirst.com/smpp/sendsms');
		self::$obj = $this;
	}


    public function __get($key)
    {
        return env('VALUEFIRST_' . strtoupper($key));
    }

	public function send($data)
	{
		if(!$this->validate($data)) {
			return false;
		}
		if ($this->send_method == 'json') {
		} else {
			$data['username'] = $this->http_username;
			$data['password'] = $this->http_password;
			$res = MyHelper::getWithTimeout($this->http_endpoint, null, $data);
	        $log=[
	            'request_body'=>$data,
	            'request_url'=>$this->http_endpoint,
	            'response'=>json_encode($res),
	            'phone'=>$data['to']
	        ];
			MyHelper::logApiSMS($log);
			return (strpos(json_encode($res['response']??''),'Sent') !== false);
		}
	}

	public function validate(&$data)
	{
		if(!is_numeric($data['to']??false)) {
			return false;
		}
		if(!($data['text']??false)) {
			return false;
		}
		$data['from'] = $this->masking_number??'VFIRST';
		$data['dir-url'] = $this->dir_url;
		$data['udh'] = 0;
		return true;
	}
}