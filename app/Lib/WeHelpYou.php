<?php
namespace App\Lib;

use App\Http\Models\{
	LogApiWehelpyou,
	TransactionPickup,
	TransactionPickupWehelpyou,
	TransactionPickupWehelpyouUpdate,
	Transaction,
	Autocrm,
	Outlet,
	User
};
use Modules\Transaction\Http\Requests\CheckTransaction;
use Modules\Transaction\Http\Controllers\ApiOnlineTransaction;
use Modules\Outlet\Entities\DeliveryOutlet;
use Modules\Autocrm\Entities\AutoresponseCodeList;
use Illuminate\Support\Facades\Log;
/**
 * 
 */
class WeHelpYou
{
	private static function getBaseUrl()
	{
		if(config('app.env') == 'production'){
			$baseUrl = config('wehelpyou.url_prod');
        }else{
			$baseUrl = config('wehelpyou.url_sandbox');
        }

        return $baseUrl;
	}

	private static function getHeader($method, $request)
	{
		$time = date("D, d M Y H:i:s ", time()-7*3600).'GMT';
		$signatureString = $time . '|' . (($method == 'get') ? '' : json_encode($request));
		$signature = base64_encode(hash_hmac('sha256', $signatureString, config('wehelpyou.secret'), true));
		return [
			'time' => $time,
			'signature' => $signature,
			'Authorization' => 'Basic '.base64_encode(config('wehelpyou.client_id').':'.config('wehelpyou.pass')),
		];
	}

	public static function sendRequest($method = 'GET', $url = null, $request = null, $logType = null, $orderId = null)
	{
		$method = strtolower($method);
		$headers = self::getHeader($method, $request);

		if ($method == 'get') {
			$response = MyHelper::getWithTimeout(self::getBaseUrl() . $url, null, $request, $headers, 65, $fullResponse);
		} else {
			$response = MyHelper::postWithTimeout(self::getBaseUrl() . $url, null, $request, 0, $headers, 65, $fullResponse);
		}

		try {
            LogApiWehelpyou::create([
                'type'              => $logType,
                'id_reference'      => $orderId,
                'request_url'       => self::getBaseUrl() . $url,
                'request_method'    => strtoupper($method),
                'request_parameter' => json_encode($request),
                'request_header'    => json_encode($headers),
                'response_body'     => json_encode($response),
                'response_header'   => json_encode($fullResponse->getHeaders()),
                'response_code'     => $fullResponse->getStatusCode()
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiWehelpyou: ' . $e->getMessage());
        }        

		return $response;
	}

	public static function getPriceInstant($request)
	{
		return self::sendRequest('POST', 'v2/price/instant', $request, 'get_price');
	}

	public static function getCredit()
	{
		$credit = self::sendRequest('GET', 'v1/credit/remaining', null, 'get_credit')['response']['data']['credit'] ?? 'IDR 0';
		$credit = self::formatPriceStringToInt($credit);
		self::sendBalanceNotification($credit);
		return $credit;
	}

	public static function isEnoughCredit(int $price)
	{
		$credit = self::getCredit();
		if ($credit <= 0) {
			return false;
		}

		return (($credit - $price) > 0) ? true : false;
	}

	public static function isNotEnoughCredit(int $price)
	{
		$credit = self::getCredit();
		if ($credit <= 0) {
			return true;
		}

		return (($credit - $price) > 0) ? false : true;
	}

	public static function getAvailableService(array $origin = [], array $destination = [])
	{
		$origin = implode(', ', $origin);
		$destination = implode(', ', $destination);
		return self::sendRequest('GET', 'v1/available/service?origin=['.$origin.']&destination=['.$destination.']', null, 'get_available_service');
	}

	public static function getOrderHistory($startDate = null, $endDate = null)
	{
		$startDate = $startDate ?? date("Y-m-d 00:00:00", strtotime('-7 days'));
		$endDate = $endDate ?? date("Y-m-d 23:59:00");
		return self::sendRequest('GET', 'v1/order/history?startDate='.$startDate.'&endDate='.$endDate, null, 'get_order_history');
	}

	public static function formatPriceStringToInt(string $price): int
	{
		$price = explode('.', str_replace("IDR ", '', $price))[0];
		return (int) str_replace(',', '', $price);
	}

	public static function getTrackingStatus($po_no)
	{
		return self::sendRequest('GET', 'v1/tracking/order/'.$po_no, null, 'get_tracking', $po_no);
	}

	public static function createOrder($request, $outlet)
	{
		$postRequest = self::formatPostRequest($request->user(), $outlet, $request['destination']);
		$postRequest['courier'] = $request->courier;

		return self::sendRequest('POST', 'v2/create/order/instant', $postRequest, 'create_order');
	}

	public static function cancelOrder($po_no)
	{
		return self::sendRequest('GET', 'v1/cancel/order/'.$po_no, null, 'cancel_order', $po_no);
	}

	public static function getListTransactionDelivery($request, $outlet, $totalProductQty = null, $additionalDelivery = [])
	{
		$postRequest = self::formatPostRequest($request->user(), $outlet, $request['destination'], $totalProductQty);
		if (!$postRequest) {
			return [];
		}
		$listDelivery = self::getPriceInstant($postRequest);
		if ($listDelivery['status_code'] != 200) {
			return [];
		}
		$listDelivery = self::formatListDelivery($listDelivery, self::getCredit(), $outlet, $additionalDelivery);
		
		return $listDelivery;
	}

	public static function formatPostRequest($user, $outlet, $destination, $totalProductQty)
	{
		$itemSpecification = self::getSettingItemSpecification();
		if (self::isNotValidDimension($itemSpecification, $totalProductQty)) {
			return false;
		}

		if (empty($destination['address']) 
			|| empty($destination['latitude'])
			|| empty($destination['longitude'])
		) {
			return false;
		}

		return [
			"vehicle_type" => "Motorcycle",
			"box" => false,
			"sender" => [
				"name" => $outlet->outlet_name,
				"phone" => $outlet->outlet_phone,
				"address" => $outlet->outlet_address,
				"latitude" => $outlet->outlet_latitude,
				"longitude" => $outlet->outlet_longitude,
				"notes" => ""
			],
			"receiver" => [
				"name" => $user->name,
				"phone" => $user->phone,
				"address" => $destination['address'],
				"notes" => $destination['description'] ?? null,
				"latitude" => $destination['latitude'],
				"longitude" => $destination['longitude']
			],
			"item_specification" => [
				"name" => $itemSpecification['package_name'],
				"item_description" => $itemSpecification['package_description'],
				"length" => 40,
				"width" => 40,
				"height" => 40,
				"weight" => 20,
				"remarks" => $itemSpecification['remarks'] ?? null
			]
		];
	}

	public static function formatListDelivery($listDelivery, $credit, $outlet, $additionalDelivery = [])
	{
		(new ApiOnlineTransaction)->mergeNewDelivery(json_encode($listDelivery['response']));

		$delivery_outlet = DeliveryOutlet::where('id_outlet', $outlet->id_outlet)->where('available_status', 1)->pluck('code')->toArray();
		$result = [];
		$deliverySetting = (new ApiOnlineTransaction)->listAvailableDelivery();
		$listDeliverySetting = $deliverySetting['result']['delivery'] ?? [];
		$defaultOrder = $deliverySetting['result']['default_delivery'] ?? null;
		foreach ($listDeliverySetting as $delivery) {
			$disable = 0;
			$delivery['courier'] = self::getSettingCourierName($delivery['code']);
			$delivery['price'] = self::getCourierPrice($listDelivery['response']['data']['partners'] ?? [], $delivery['courier']);
			if (empty($delivery['price'])) {
				$disable = 1;
			}

			if (isset($additionalDelivery[$delivery['code']])) {
				$delivery['price'] = $additionalDelivery[$delivery['code']];
				$disable = 0;
			}

			if ($delivery_outlet) {
				if (!in_array($delivery['code'], $delivery_outlet)) {
					$disable = 1;
				}
			} elseif ($delivery['show_status'] != 1 && $delivery['available_status'] != 1) {
				$disable = 1;
			}

			if ($credit <= 0) {
				$disable = 1;
			}
			$delivery['disable'] = $disable;
			$delivery['short_description'] = $delivery['short_description'] ?? null;
			unset($delivery['show_status'], $delivery['available_status']);
			$result[] = $delivery;
		}
		
		$result = self::formatDefaultOrder($result, $defaultOrder);
		return $result;
	}

	public static function getSettingCourierName($code)
	{
		$courier = $code;
		$courier = str_replace('wehelpyou_', '', $code);
		return $courier;
	}

	public static function getCourierPrice($listDelivery, $code)
	{
		foreach ($listDelivery as $delivery) {
			if (empty($delivery)) {
				continue;
			}
			if ($delivery['courier'] == $code) {
				return $delivery['price'];
			}
		}
		return 0;
	}

	public static function getCourier($requestCourier, $request, $outlet)
	{
		$courier = null;
		$listDelivery = self::getListTransactionDelivery($request, $outlet);
		foreach ($listDelivery as $val) {
			if ($val['disable'] == 0 && $val['courier'] == $requestCourier) {
				$courier = $val;
				break;
			}
		}
		return $courier;
	}

	public static function createTrxPickupWehelpyou($dataTrxPickup, $request, $outlet, $totalProductQty)
	{
		$itemSpecification = self::getSettingItemSpecification();
		if (self::isNotValidDimension($itemSpecification, $totalProductQty)) {
			return false;
		}

		return TransactionPickupWehelpyou::create([
			'id_transaction_pickup' => $dataTrxPickup->id_transaction_pickup,
			'vehicle_type' 			=> 'Motorcycle',
			'courier' 				=> $request->courier,
			'box' 					=> false,

			'sender_name' 			=> $outlet->outlet_name,
			'sender_phone' 			=> $outlet->outlet_phone,
			'sender_address' 		=> $outlet->outlet_address,
			'sender_latitude' 		=> $outlet->outlet_latitude,
			'sender_longitude' 		=> $outlet->outlet_longitude,
			'sender_notes' 			=> "NOTE: bila ada pertanyaan, mohon hubungi penerima terlebih dahulu untuk informasi. \nPickup Code ".$dataTrxPickup['order_id'],

			'receiver_name' 		=> $request->user()->name,
			'receiver_phone' 		=> $request->user()->phone,
			'receiver_address' 		=> $request['destination']['address'],
			'receiver_notes' 		=> $request['destination']['description'],
			'receiver_latitude' 	=> $request['destination']['latitude'],
			'receiver_longitude' 	=> $request['destination']['longitude'],

			'item_specification_name' 				=> $itemSpecification['package_name'],
			'item_specification_item_description' 	=> $itemSpecification['package_description'],
			'item_specification_length' 			=> 40,
			'item_specification_width' 				=> 40,
			'item_specification_height' 			=> 40,
			'item_specification_weight' 			=> 20, // kilogram
			'item_specification_remarks' 			=> $itemSpecification['remarks'] ?? null
		]);
	}

	public static function isNotValidDimension($itemSpecification, $totalProductQty)
	{
		$maxVolume = 40 * 40 * 40; // cm
		$totalVolume = $itemSpecification['length'] * $itemSpecification['width'] * $itemSpecification['height'] * $totalProductQty;

		if ($maxVolume < $totalVolume) {
			return true;
		}

		$maxWeight = 20000; //gram
		$totalWeight = $itemSpecification['weight'] * $totalProductQty;
		if ($maxWeight < $totalWeight) {
			return true;
		}

		return false;
	}

	public static function getSettingListDelivery(): array
	{
		return json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
	}

	public static function bookingDelivery(Transaction $trx)
	{
		$trx->load('transaction_pickup.transaction_pickup_wehelpyou');
		if (!empty($trx['transaction_pickup']['transaction_pickup_wehelpyou']['poNo'])) {
			return MyHelper::checkGet($trx['transaction_pickup']['transaction_pickup_wehelpyou']);
		}

		$trxPickup = $trx['transaction_pickup'];
		$trxPickupWHY = $trx['transaction_pickup']['transaction_pickup_wehelpyou'];
		$postRequest = [
			"vehicle_type" => $trxPickupWHY->vehicle_type,
			"courier" => $trxPickupWHY->courier,
			"box" => $trxPickupWHY->box,
			"sender" => [
				"name" => $trxPickupWHY->sender_name,
				"phone" => $trxPickupWHY->sender_phone,
				"address" => $trxPickupWHY->sender_address,
				"latitude" => $trxPickupWHY->sender_latitude,
				"longitude" => $trxPickupWHY->sender_longitude,
				"notes" => $trxPickupWHY->sender_notes,
			],
			"receiver" => [
				"name" => $trxPickupWHY->receiver_name,
				"phone" => $trxPickupWHY->receiver_phone,
				"address" => $trxPickupWHY->receiver_address,
				"notes" => $trxPickupWHY->receiver_notes,
				"latitude" => $trxPickupWHY->receiver_latitude,
				"longitude" => $trxPickupWHY->receiver_longitude
			],
			"item_specification" => [
				"name" => $trxPickupWHY->item_specification_name,
				"item_description" => $trxPickupWHY->item_specification_item_description,
				"length" => $trxPickupWHY->item_specification_length,
				"width" => $trxPickupWHY->item_specification_width,
				"height" => $trxPickupWHY->item_specification_height,
				"weight" => $trxPickupWHY->item_specification_weight,
				"remarks" => $trxPickupWHY->item_specification_remarks
			]
		];

		$priceInstant = self::getPriceInstant($postRequest);
		if ($priceInstant['status_code'] != 200) {
			return [
				'status' => 'fail',
				'messages' => ['Tidak dapat menemukan layanan delivery']
			];
		}

		$courier = null;
		foreach ($priceInstant['response']['data']['partners'] as $val) {
			if ($trxPickupWHY->courier == $val['courier']) {
				$courier = $val;
			}
		}

		if (empty($courier)) {
			return [
				'status' => 'fail',
				'messages' => ['Tidak dapat menemukan layanan delivery']
			];
		}

		if (self::isNotEnoughCredit($courier['price'])) {
			return [
				'status' => 'fail',
				'messages' => ['Tidak dapat membooking delivery, kredit tidak mencukupi']
			];
		}

		$createOrder = self::sendRequest('POST', 'v2/create/order/instant', $postRequest, 'create_order');
		$saveOrder = self::saveCreateOrderReponse($trxPickup, $createOrder);

		return $saveOrder;
	}

	public static function getSettingItemSpecification()
	{
		$settingItem = json_decode(MyHelper::setting('package_detail_delivery', 'value_text', '[]'), true) ?? [];

		$itemSpecification = [
			'package_name' 			=> $settingItem['package_name'],
			'package_description' 	=> $settingItem['package_description'],
			'length' 				=> $settingItem['length'],
			'width' 				=> $settingItem['width'],
			'height' 				=> $settingItem['height'],
			'weight' 				=> $settingItem['weight'],
			'remarks' 				=> $settingItem['remarks'] ?? null
		];

		return $itemSpecification;
	}

	public static function saveCreateOrderReponse($trxPickup, $orderResponse)
	{
		if (empty($orderResponse['response']['data']['poNo'])) {

			$deliverySetting = (new ApiOnlineTransaction)->listAvailableDelivery();
			$courier = $trxPickup['transaction_pickup_wehelpyou']['courier'];
			foreach ((new ApiOnlineTransaction)->listAvailableDelivery()['result']['delivery'] as $delivery) {
				if (strpos($delivery['code'], $courier)) {
					$courier = $delivery['delivery_name'];
					break;
				}
			}

			return [
				'status' => 'fail',
				'messages' => $orderResponse['response']['message'] ?? ['Failed booking '.$courier]
			];
		}

		$responseData = $orderResponse['response']['data'];

		TransactionPickupWehelpyou::where('id_transaction_pickup', $trxPickup->id_transaction_pickup)
		->update([
			"poNo"		 => $responseData['poNo'],
            "service"	 => $responseData['service'],
            "price"		 => $responseData['price'],
            "distance"	 => $responseData['distance'],
            "SLA"		 => $responseData['SLA'],

            "order_detail_id" 				=> $responseData['order_detail']['id'],
            "order_detail_po_no" 			=> $responseData['order_detail']['po_no'],
            "order_detail_feature_type_id" 	=> $responseData['order_detail']['feature_type_id'],
            "order_detail_awb_no" 			=> $responseData['order_detail']['awb_no'],
            "order_detail_order_date" 		=> $responseData['order_detail']['order_date'],
            "order_detail_delivery_type_id" => $responseData['order_detail']['delivery_type_id'],
            "order_detail_total_amount" 	=> $responseData['order_detail']['total_amount'],
            "order_detail_partner_id" 		=> $responseData['order_detail']['partner_id'],
            "order_detail_status_id" 		=> $responseData['order_detail']['status_id'],
            "order_detail_cancel_reason_id" => $responseData['order_detail']['cancel_reason_id'],
            "order_detail_cancel_detail" 	=> $responseData['order_detail']['cancel_detail'],
            "order_detail_gosend_code" 		=> $responseData['order_detail']['gosend_code'],
            "order_detail_alfatrex_code" 	=> $responseData['order_detail']['alfatrex_code'],
            "order_detail_lalamove_code" 	=> $responseData['order_detail']['lalamove_code'],
            "order_detail_speedy_code" 		=> $responseData['order_detail']['speedy_code'],
            "order_detail_is_multiple" 		=> $responseData['order_detail']['is_multiple'],
            "order_detail_distance" 		=> $responseData['order_detail']['distance'],
            "order_detail_createdAt" 		=> $responseData['order_detail']['createdAt'],
            "order_detail_updatedAt" 		=> $responseData['order_detail']['updatedAt']
		]);

		return MyHelper::checkGet($responseData);
	}

	public static function updateStatus($trx, $po_no)
	{
		if ($trx->fakeStatusId) {
			$trackOrder = self::fakeTracking($po_no, $trx->fakeStatusId);
		}else{
			$trackOrder = self::getTrackingStatus($po_no);
		}

		if ($trackOrder['status_code'] != '200') {
			return [
				'status' => 'fail',
				'messages' => $trackOrder['response']['message'] ?? ['PO number tidak ditemukan']
			];
		}

		if ($trx->transaction_pickup->transaction_pickup_wehelpyou->latest_status_id == 2) {
			return ['status' => 'success'];
		}

		$statusNew = $trackOrder['response']['data']['status_log'];
		$statusOld = TransactionPickupWehelpyouUpdate::where('poNo', $po_no)
					->where('id_transaction', $trx->id_transaction)
					->pluck('status_id')
					->toArray();
		$latestStatus = $trackOrder['response']['data']['status']['name'];
		$latestStatusId = $trackOrder['response']['data']['status_id'] ?? null;

		$id_transaction_pickup_wehelpyou = $trx->transaction_pickup->transaction_pickup_wehelpyou->id_transaction_pickup_wehelpyou;
		foreach ($statusNew as $status) {
			if (!in_array($status['status_id'], $statusOld)) {
				$statusName = self::getStatusById($status['status_id']) ?? $status['status'];
				TransactionPickupWehelpyouUpdate::create([
					'id_transaction' => $trx->id_transaction,
					'id_transaction_pickup_wehelpyou' => $trx->transaction_pickup->transaction_pickup_wehelpyou->id_transaction_pickup_wehelpyou,
					'poNo' => $po_no,
					'status' => $statusName,
					'status_id' => $status['status_id']
				]);
				$latestStatus = $statusName;
				$latestStatusId = $status['status_id'];

				if (in_array($latestStatusId, [9])) {
					$arrived_at = date('Y-m-d H:i:s', ($status['date']??false)?strtotime($status['date']):time());
                    TransactionPickup::where('id_transaction', $trx->id_transaction)->update(['arrived_at' => $arrived_at]);
				}
			}
		}

		TransactionPickupWehelpyou::where('id_transaction_pickup_wehelpyou', $id_transaction_pickup_wehelpyou)
		->update([
			'latest_status' 			=> $latestStatus,
			'latest_status_id' 			=> $latestStatusId,
			'tracking_driver_name' 		=> $trackOrder['response']['data']['tracking']['name'] ?? null,
			'tracking_driver_phone' 	=> $trackOrder['response']['data']['tracking']['phone'] ?? null,
			'tracking_live_tracking_url'=> $trackOrder['response']['data']['tracking']['live_tracking_url'] ?? null,
			'tracking_vehicle_number' 	=> $trackOrder['response']['data']['tracking']['vehicle_number'] ?? null,
			'tracking_photo' 			=> $trackOrder['response']['data']['tracking']['photo'] ?? null,
			'tracking_receiver_name' 	=> $trackOrder['response']['data']['tracking']['receiver_name'] ?? null,
			'tracking_driver_log' 		=> $trackOrder['response']['data']['tracking']['driver_log'] ?? null
		]);

		$outlet = Outlet::where('id_outlet', $trx->id_outlet)->first();
        $trx_pickup = TransactionPickup::where('id_transaction', $trx->id_transaction)->first();
		self::sendOutletNotif($trx, $outlet, $latestStatusId, $trx_pickup);
		self::sendUserNotif($trx, $outlet, $latestStatusId, $trx_pickup);

		return ['status' => 'success'];
	}

	public static function sendOutletNotif($trx, $outlet, $status_id, $trx_pickup)
	{
        if ($status_id == 2) {
            $trx_pickup->update(['show_confirm' => '1']);
            Transaction::where('id_transaction', $trx->id_transaction)->update(['show_rate_popup' => '1']);
        }

        $status = self::getStatusById($status_id);
        $message = self::getOutletMessageByStatusId($status_id) ?? $status;
		app("Modules\OutletApp\Http\Controllers\ApiOutletApp")->outletNotif(
			[
            'type' => 'trx',
            'subject' => 'Info Pesanan Delivery',
            'string_body' => $trx_pickup->order_id.' '.($message),
            'status' => $status,
            'id_transaction' => $trx->id_transaction,
            'id_reference' => $trx->id_transaction,
            'order_id' => $trx_pickup->order_id
        ], $outlet->id_outlet);
	}

	public static function sendUserNotif($trx, $outlet, $status_id, $trx_pickup)
	{
		$subject = self::getSubjectByStatusId($status_id);
		if (!$subject) {
			return true;
		}

		$content = self::getContentByStatusId($status_id);
		if (!$content) {
			return true;
		}

		$status = self::getStatusById($status_id);
		if (!$status) {
			return true;
		}

		$user = User::where('id', $trx->id_user)->first();
		if ($status_id != 2) {
			$autocrm = app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Delivery Status Update', $user->phone,
                [
                    'id_reference'              => $trx->id_transaction,
                    'id_transaction'            => $trx->id_transaction,
                    'receipt_number'            => $trx->transaction_receipt_number,
                    'outlet_code'               => $outlet->outlet_code,
                    'outlet_name'               => $outlet->outlet_name,
                    'delivery_status_title'     => $subject,
                    'delivery_status_content'   => $content,
                    'order_id'                  => $trx_pickup->order_id,
                    'name'                      => ucwords($user->name)
                ]
            );

            return true;
		}

		$getAvailableCodeCrm = Autocrm::where('autocrm_title', 'Order Taken With Code')->first();
        $code = null;
        $idCode = null;

        if( !empty($getAvailableCodeCrm) 
        	&& ($getAvailableCodeCrm['autocrm_email_toogle'] == 1 
        		|| $getAvailableCodeCrm['autocrm_sms_toogle'] == 1 
        		|| $getAvailableCodeCrm['autocrm_push_toogle'] == 1 
        		|| $getAvailableCodeCrm['autocrm_inbox_toogle'] == 1)
        ){
            $getAvailableCode = app('Modules\Autocrm\Http\Controllers\ApiAutoresponseWithCode')->getAvailableCode($trx->id_transaction);
            $code = $getAvailableCode['autoresponse_code'] ?? null;
            $idCode = $getAvailableCode['id_autoresponse_code_list'] ?? null;
        }

        if(!empty($code)) {
            $send = app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Order Taken Delivery With Code', $user->phone, [
                'id_reference'    => $trx->id_transaction,
                'id_transaction'  => $trx->id_transaction,
                'receipt_number'  => $trx->transaction_receipt_number,
                'outlet_code'     => $outlet->outlet_code,
                'outlet_name'     => $outlet->outlet_name,
                'delivery_status' => $status,
                'order_id'        => $trx_pickup->order_id,
                'code'            => $code
            ]);

            $updateCode = AutoresponseCodeList::where('id_autoresponse_code_list', $idCode)->update(['id_user' => $trx->id_user, 'id_transaction' => $trx->id_transaction]);
            if($updateCode){
                app('Modules\Autocrm\Http\Controllers\ApiAutoresponseWithCode')->stopAutoresponse($idCode);
            }
        }else{
            $autocrm = app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Delivery Status Update', $user->phone,
                [
                    'id_reference'              => $trx->id_transaction,
                    'id_transaction'            => $trx->id_transaction,
                    'receipt_number'            => $trx->transaction_receipt_number,
                    'outlet_code'               => $outlet->outlet_code,
                    'outlet_name'               => $outlet->outlet_name,
                    'delivery_status_title'     => $subject,
                    'delivery_status_content'   => $content,
                    'order_id'                  => $trx_pickup->order_id,
                    'name'                      => ucwords($user->name)
                ]
            );
        }

        return true;
	}

	public static function getStatusById($status_id)
	{
		$status = [
			1 	=> 'On progress', 
			2 	=> 'Completed', 
			99 	=> 'Order failed by system', 
			90 	=> 'Cancel order failed', 
			97 	=> 'Refund failed by system', 
			88 	=> 'Refunded by system', 
			91 	=> 'Cancelled by partner', 
			11 	=> 'Finding driver', 
			8 	=> 'Driver allocated', 
			32 	=> 'Item picked', 
			9 	=> 'Enroute drop', 
			98 	=> 'Order expired', 
			96 	=> 'Rejected', 
			89 	=> 'Cancelled, without refund'
		];
		
		return $status[$status_id] ?? null;
	}

	public static function getSubjectByStatusId($status_id)
	{
		$subject = [
            1 			=> 'Pesanan Diterima!',
            8			=> 'Mohon menunggu ya!',
            9 			=> 'Sudah siap menikmati pesananmu?',
            'cancelled' => 'Pesananmu tidak dapat diambil oleh driver',
            2   		=> 'Terima kasih sudah pesan di JIWA+!',
            'no_driver' => 'Driver belum berhasil ditemukan',
        ];

        return $subject[$status_id] ?? null;
	}

	public static function getContentByStatusId($status_id)
	{
        $content = [
            1			=> 'Mohon tunggu, pesanan sedang dipersiapkan',
            8			=> 'Driver-mu sedang menuju ke outlet',
            9			=> 'Driver sedang menuju ke tempatmu',
            'cancelled'	=> 'Mohon tunggu konfirmasi dari outlet',
            2			=> 'Selamat menikmati Kak %name%',
            'no_driver'	=> 'Mohon tunggu konfirmasi dari outlet',
        ];

        return $content[$status_id] ?? null;
	}

	public static function getOutletMessageByStatusId($status_id)
	{
		$outlet_message = [
            11 					=> 'Mencari Driver',
            8 					=> 'Driver ditemukan',
            'out_for_pickup' 	=> 'Driver menuju ke Outlet',
            32 					=> 'Driver mengambil Pesanan',
            9 					=> 'Driver menuju Alamat Tujuan',
            'cancelled' 		=> 'Driver batal mengambil Pesanan',
            2 					=> 'Pesanan sampai ke Alamat Tujuan',
            96 					=> 'Driver batal mengantar Pesanan',
            'no_driver' 		=> 'Driver tidak ditemukan',
            'on_hold' 			=> 'Driver terkendala saat pengantaran'
        ];

        return $outlet_message[$status_id] ?? null;
	}

	public static function driverNotFoundStatus(): array
	{
		return [
			'On progress', 
			'Completed', 
			'Order failed by system', 
			'Cancel order failed', 
			'Refund failed by system', 
			'Refunded by system', 
			'Cancelled by partner', 
			'Finding driver', 
			'Order expired', 
			'Rejected', 
			'Cancelled, without refund'
		];
	}

	public static function driverFoundStatus(): array
	{
		return [
			'Driver Allocated',
			'Enroute drop'
		];
	}

	public static function fakeTracking($poNo, $statusId = 1)
	{
		$now = date("Y-m-d H:i:s");
		$response = [
			'status_code' => 200,
			'response' => [
				'statusCode' => 200,
				'message' => 'TRACKING_ORDER_SUCCESS',
				'data' => [
					'po_no' => $poNo,
					'order_date' => '2021-06-18T10:19:49.945Z',
					'total_amount' => 10000,
					'status_log' => [
						0 => [
							'date' => $now,
							'status' => 'On Progress',
							'status_id' => 1,
						]
					],
					'distance' => 1.005,
					'status_id' => $statusId,
					'sender' => [
						'name' => 'outlet 103',
						'phone' => '0811223344',
						'address' => 'jalan outlet',
						'notes' => 'NOTE: bila ada pertanyaan, mohon hubungi penerima terlebih dahulu untuk informasi. 
						Pickup Code NV5M',
					],
					'receiver' => [
						'name' => 'admin super',
						'phone' => '0811223344',
						'address' => 'jl jalan',
						'notes' => 'deskripsi',
					],
					'tracking' => [
						'name' => 'Test-Driver',
						'phone' => '628882233',
						'vehicle_number' => 'TEST A 3333 SYY',
						'photo' => NULL,
						'live_tracking_url' => NULL,
					],
					'feature' => [
						'name' => NULL,
					],
					'status' => [
						'name' => 'Finished',
					],
				],
			],
		];

		if (in_array($statusId, [8,9,2])) {
			$response['response']['data']['status_log'] += [
				1 => [
					'date' => $now,
					'status' => 'Enroute Pickup',
					'status_id' => 8,
				]
			];
		}

		if (in_array($statusId, [9,2])) {
			$response['response']['data']['status_log'] += [
				2 => [
					'date' => $now,
					'status' => 'Enroute Drop',
					'status_id' => 9,
				],
				3 => [
					'date' => $now,
					'status' => 'Finished',
					'status_id' => 2,
				]
			];
		}

		return $response;
	}

	public static function sendBalanceNotification($balance)
	{
		$limit = MyHelper::setting('wehelpyou_limit_balance');
		if ($limit && $balance < $limit) {
			$lastSend = MyHelper::setting('wehelpyou_notif_last_send');
			if (date('Y-m-d') < $lastSend) {
				$send = app('\Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('WeHelpYou Low Balance', User::first()->phone, [
					'balance' => MyHelper::requestNumber($balance, '_CURRENCY'),
					'limit' => MyHelper::requestNumber($limit, '_CURRENCY'),
				], null, true);
				Setting::where('key', 'wehelpyou_notif_last_send')->update(['value' => date('Y-m-d')]);
			}
		}
	}

	public static function formatDefaultOrder($listDelivery, $defaultOrder)
	{
		if ($defaultOrder == 'price') {
			usort($listDelivery, function($a, $b) {
	            return $a['price'] - $b['price'];
	        });
		}else{
			usort($listDelivery, function($a, $b) {
	            return $a['position'] - $b['position'];
	        });
		}

		$listDisable = [];
		$listEnable	= [];
		foreach ($listDelivery as $delivery) {
			if ($delivery['disable'] == 1) {
				$listDisable[] = $delivery;
			} else {
				$listEnable[] = $delivery;
			}
		}

		return array_merge($listEnable, $listDisable);
		
	}
}
