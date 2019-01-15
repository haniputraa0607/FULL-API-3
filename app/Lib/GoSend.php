<?php 

namespace App\Lib;

class GoSend {

	public function __construct() {
		date_default_timezone_set('Asia/Jakarta');
	}
	
	
	static function booking($origin, $destination, $item, $storeOrderId="", $insurance=null) {
		if(env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == ''){
			return [
				'status'=> 'fail',
				'messages' => ['GO-SEND key has not been set']
			];
		}
		
		$url = env('GO_SEND_URL').'/gokilat/v10/booking';

		$header = [
			'Client-ID' => env('GO_SEND_CLIENT_ID'),
			'Pass-Key'  => env('GO_SEND_PASS_KEY')
		];

		$post['paymentType']	= 3;
		$post['deviceToken']	= "";
		$post['collection_location'] = "pickup";
		$post['shipment_method'] = "Instant";

		$post['routes'][0]['originName'] = ""; 
		$post['routes'][0]['originNote'] = $origin['note'];
		$post['routes'][0]['originContactName'] = $origin['name'];
		$post['routes'][0]['originContactPhone'] = $origin['phone'];
		$post['routes'][0]['originLatLong'] = $origin['latitude'].','.$origin['longitude'];

		$post['routes'][0]['destinationName'] = ""; 
		$post['routes'][0]['destinationNote'] = "";
		$post['routes'][0]['destinationContactName'] = $destination['name'];
		$post['routes'][0]['destinationContactPhone'] = $destination['phone'];
		$post['routes'][0]['destinationLatLong'] = $destination['latitude'].','.$destination['longitude'];
		$post['routes'][0]['destinationAddress'] = $destination['address'].', Note : '.$destination['note'];

		$post['item'] = $item;
		$post['storeOrderId'] = $storeOrderId;
		$post['insuranceDetails'] = $insurance;
        $token = MyHelper::post($url, null, $post, 0, $header);

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

		$url = env('GO_SEND_URL').'/gokilat/v10/booking/storeOrderId/'.$storeOrderId;
		$token = MyHelper::get($url, null, $header);

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

		$url = env('GO_SEND_URL').'/gokilat/v10/calculate/price?origin='.$origin['latitude'].','.$origin['longitude'].'8&destination='.$destination['latitude'].','.$destination['longitude'].'&paymentType=3';
		$token = MyHelper::get($url, null, $header);
		
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