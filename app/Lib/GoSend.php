<?php 

namespace App\Lib;

use App\Http\Models\Setting;
use App\Http\Models\LogApiGosend;

class GoSend {

	public function __construct() {
		date_default_timezone_set('Asia/Jakarta');
	}
	
	public static function getShipmentMethod() {
		return Setting::select('value')->where('key','gosend_use_sameday')->pluck('value')->first()?'SameDay':'Instant';
	}
	
	static function booking($origin, $destination, $item, $storeOrderId="", $insurance=null) {
		if(env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == ''){
			return [
				'status'=> 'fail',
				'messages' => ['GO-SEND key has not been set']
			];
		}
		
		$url = env('GO_SEND_URL').'gokilat/v10/booking';

		$header = [
			'Client-ID' => env('GO_SEND_CLIENT_ID'),
			'Pass-Key'  => env('GO_SEND_PASS_KEY')
		];

		$post['paymentType']	= 3;
		$post['deviceToken']	= "";
		$post['collection_location'] = "pickup";
		$post['shipment_method'] = self::getShipmentMethod();

		$post['routes'][0]['originName'] = ""; 
		$post['routes'][0]['originNote'] = $origin['note'];
		$post['routes'][0]['originContactName'] = $origin['name'];
		$post['routes'][0]['originContactPhone'] = $origin['phone'];
		$post['routes'][0]['originLatLong'] = $origin['latitude'].','.$origin['longitude'];
		$post['routes'][0]['originAddress'] = $origin['address'];

		$post['routes'][0]['destinationName'] = ""; 
		$post['routes'][0]['destinationNote'] = "";
		$post['routes'][0]['destinationContactName'] = $destination['name'];
		$post['routes'][0]['destinationContactPhone'] = $destination['phone'];
		$post['routes'][0]['destinationLatLong'] = $destination['latitude'].','.$destination['longitude'];
		$post['routes'][0]['destinationAddress'] = $destination['address'].', Note : '.$destination['note'];

		$post['routes'][0]['item'] = $item;

		$post['routes'][0]['storeOrderId'] = $storeOrderId;
		$post['routes'][0]['insuranceDetails'] = $insurance;
        $token = MyHelper::post($url, null, $post, 0, $header,$status_code,$response_header);
        try {
        	LogApiGosend::create([
        		'type' => 'booking',
		    	'id_reference' => $storeOrderId,
		    	'request_url' => $url,
		    	'request_method' => 'POST',
		        'request_parameter' => json_encode($post),
		        'request_header' => json_encode($header),
		    	'response_body' => json_encode($token),
		        'response_header' => json_encode($response_header),
		    	'response_code' => $status_code
        	]);
        } catch (\Exception $e) {
        	\Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: '.$e->getMessage());
        }
        if(!($token['id']??false)){
        	$token['messages'] = array_merge(($response_header['Error-Message']??[]),['Failed booking GO-SEND']);
        }
        return $token;
	}
	
	static function getStatus($storeOrderId){
		if(env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == ''){
			return [
				'status'=> 'fail',
				'messages' => ['GO-SEND key has not been set']
			];
		}

		$header = [
			'Client-ID' => env('GO_SEND_CLIENT_ID'),
			'Pass-Key'  => env('GO_SEND_PASS_KEY')
		];

		$url = env('GO_SEND_URL').'gokilat/v10/booking/storeOrderId/'.$storeOrderId;
		$token = MyHelper::get($url, null, $header,$status_code,$response_header);
        try {
        	LogApiGosend::create([
        		'type' => 'get_status',
		    	'id_reference' => $storeOrderId,
		    	'request_url' => $url,
		    	'request_method' => 'GET',
		    	'request_header' => json_encode($header),
		        'request_parameter' => null,
		    	'response_header' => json_encode($response_header),
		    	'response_body' => json_encode($token),
		    	'response_code' => $status_code
        	]);
        } catch (\Exception $e) {
        	\Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: '.$e->getMessage());
        }
        return $token;
	}

	static function getPrice($origin, $destination){
		if(env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == ''){
			return [
				'status'=> 'fail',
				'messages' => ['GO-SEND key has not been set']
			];
		}

		$header = [
			'Client-ID' => env('GO_SEND_CLIENT_ID'),
			'Pass-Key'  => env('GO_SEND_PASS_KEY')
		];

		$url = env('GO_SEND_URL').'gokilat/v10/calculate/price?origin='.$origin['latitude'].','.$origin['longitude'].'8&destination='.$destination['latitude'].','.$destination['longitude'].'&paymentType=3';
		$token = MyHelper::get($url, null, $header,$status_code,$response_header);

        try {
        	LogApiGosend::create([
        		'type' => 'get_status',
		    	'id_reference' => null,
		    	'request_url' => $url,
		    	'request_method' => 'GET',
		    	'request_header' => json_encode($header),
		        'request_parameter' => null,
		    	'response_header' => json_encode($response_header),
		    	'response_body' => json_encode($token),
		    	'response_code' => $status_code
        	]);
        } catch (\Exception $e) {
        	\Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: '.$e->getMessage());
        }

        return $token;
	}

	static function checkKey(){
		if(env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == ''){
			return [
				'status'=> 'fail',
				'messages' => ['GO-SEND key has not been set']
			];
		}
		return true;
	}
}