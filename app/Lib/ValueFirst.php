<?php
namespace App\Lib;

use App\Http\Models\Setting;

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
    public static function create()
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }

    public function __construct()
    {
        $this->json_endpoint = env('VALUEFIRST_JSON_ENDPOINT', 'https://api.myvaluefirst.com/psms/servlet/psms.JsonEservice');
        $this->http_endpoint = env('VALUEFIRST_HTTP_ENDPOINT', 'http://www.myvaluefirst.com/smpp/sendsms');
        self::$obj           = $this;
    }

    public function __get($key)
    {
        return env('VALUEFIRST_' . strtoupper($key));
    }

    public function getSEQ()
    {
        $seq = MyHelper::setting('value_first_seq') ?: 1;
        Setting::updateOrCreate(['key' => 'value_first_seq'], ['value' => ($seq + 1)]);
        return $seq;
    }

    public function send($data)
    {
        if (!$this->validate($data)) {
            return false;
        }
        if (strtolower($this->send_method) == 'json') {
            $sendData = [
                '@VER' => '1.2',
                'USER' => [
                    '@USERNAME'      => $this->json_username,
                    '@PASSWORD'      => $this->json_password,
                    '@UNIXTIMESTAMP' => (string) time(),
                ],
                'DLR'  => [
                    '@URL' => urlencode($data['dir-url']),
                ],
                'SMS'  => [
                    [
                        '@UDH'      => '0',
                        '@CODING'   => '1',
                        '@TEXT'     => urlencode($data['text']),
                        '@PROPERTY' => '0',
                        '@ID'       => time() . rand(1000, 9999),
                        'ADDRESS'   => [
                            [
                                '@FROM' => urlencode($data['from']),
                                '@TO'   => $data['to'],
                                '@SEQ'  => $this->getSEQ(),
                            ],
                        ],
                    ],
                ],
            ];
            $res = MyHelper::postWithTimeout($this->json_endpoint, null, $sendData);
            $log = [
                'request_body' => $sendData,
                'request_url'  => $this->json_endpoint,
                'response'     => json_encode($res),
                'phone'        => $data['to'],
            ];
            MyHelper::logApiSMS($log);
            if (!($res['response']['MESSAGEACK']['GUID']??false) || ($res['response']['MESSAGEACK']['ERROR']??false)) {
            	return false;
            }
            return true;
        } else {
            $data['username'] = $this->http_username;
            $data['password'] = $this->http_password;
            $res              = MyHelper::getWithTimeout($this->http_endpoint, null, $data);
            $log              = [
                'request_body' => $data,
                'request_url'  => $this->http_endpoint,
                'response'     => json_encode($res),
                'phone'        => $data['to'],
            ];
            MyHelper::logApiSMS($log);
            return (strpos(json_encode($res['response'] ?? ''), 'Sent') !== false);
        }
    }

    public function validate(&$data)
    {
        if (!is_numeric($data['to'] ?? false)) {
            return false;
        }
        if (!($data['text'] ?? false)) {
            return false;
        }
        if(substr($data['to'], 0, 1) == '0'){
            $phone = '62'.substr($data['to'],1);
        }else{
            $phone = $data['to'];
        }
        $data['to'] 	 = $phone;
        $data['from']    = $this->masking_number ?? 'VFIRST';
        $data['dir-url'] = $this->dir_url;
        $data['udh']     = 0;
        return true;
    }
}
