<?php
namespace App\Lib;

use App\Http\Models\{
	LogApiWehelpyou,
	TransactionPickupWehelpyou,
	TransactionPickupWehelpyouUpdate,
	Transaction,
	User,
};
use Modules\Transaction\Http\Requests\CheckTransaction;
use Modules\Transaction\Http\Controllers\ApiOnlineTransaction;
use Modules\Outlet\Entities\DeliveryOutlet;
/**
 * 
 */
class WeHelpYou
{
	private static function getBaseUrl()
	{
		if(config('env') == 'production'){
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
			return false;
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

	public static function getListTransactionDelivery($request, $outlet)
	{
		$listDelivery = self::getPriceInstant(self::formatPostRequest($request->user(), $outlet, $request['destination']));
		$listDelivery = self::disableListDelivery($listDelivery, self::getCredit(), $outlet);
		
		return $listDelivery;
	}

	public static function formatPostRequest($user, $outlet, $destination)
	{
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
				"notes" => $destination['description'],
				"latitude" => $destination['latitude'],
				"longitude" => $destination['longitude']
			],
			"item_specification" => [
				"name" => "name of goods",
				"item_description" => "description of goods",
				"length" => 1,
				"width" => 1,
				"height" => 1,
				"weight" => 1,
				"remarks" => "notes of goods"
			]
		];
	}

	public static function disableListDelivery($listDelivery, $credit, $outlet)
	{
		(new ApiOnlineTransaction)->mergeNewDelivery(json_encode($listDelivery['response']));

		$delivery_outlet = DeliveryOutlet::where('id_outlet', $outlet->id_outlet)->pluck('code')->toArray();
		$result = [];
		foreach (self::getSettingListDelivery() as $delivery) {
			$disable = 0;
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

			$delivery['courier'] = self::getSettingCourierName($delivery['code']);
			$delivery['price'] = self::getCourierPrice($listDelivery['response']['data']['partners'] ?? [], $delivery['courier']);
			if ($delivery['price'] == 0) {
				$disable = 1;
			}

			$delivery['disable'] = $disable;
			$result[] = $delivery;
		}
		
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

	public static function createTrxPickupWehelpyou($dataTrxPickup, $request, $outlet)
	{
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

			'item_specification_name' 				=> 'name of goods',
			'item_specification_item_description' 	=> 'description of goods',
			'item_specification_length' 			=> 1,
			'item_specification_width' 				=> 1,
			'item_specification_height' 			=> 1,
			'item_specification_weight' 			=> 1,
			'item_specification_remarks' 			=> 'notes of goods',
		]);
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

		$createOrder = self::sendRequest('POST', 'v2/create/order/instant', $postRequest, 'create_order');
		$saveOrder = self::saveCreateOrderReponse($trxPickup, $createOrder);

		return $saveOrder;
	}

	public static function saveCreateOrderReponse($trxPickup, $orderResponse)
	{
		if (empty($orderResponse['response']['data']['poNo'])) {
			return [
				'status' => 'fail',
				'messages' => $orderResponse['response']
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
		$trackOrder = self::getTrackingStatus($po_no);
		if ($trackOrder['status_code'] != '200') {
			return [
				'status' => 'fail',
				'messages' => $orderResponse['response'] ?? 'PO number tidak ditemukan'
			];
		}

		$statusNew = $trackOrder['response']['data']['status_log'];
		$statusOld = TransactionPickupWehelpyouUpdate::where('poNo', $po_no)
					->where('id_transaction', $trx->id_transaction)
					->pluck('status')
					->toArray();

		$id_transaction_pickup_wehelpyou = $trx->transaction_pickup->transaction_pickup_wehelpyou->id_transaction_pickup_wehelpyou;
		TransactionPickupWehelpyou::where('id_transaction_pickup_wehelpyou', $id_transaction_pickup_wehelpyou)
		->update(['latest_status' => $trackOrder['response']['data']['status']['name']]);

		foreach ($statusNew as $status) {
			if (!in_array($status['status'], $statusOld)) {
				TransactionPickupWehelpyouUpdate::create([
					'id_transaction' => $trx->id_transaction,
					'id_transaction_pickup_wehelpyou' => $trx->transaction_pickup->transaction_pickup_wehelpyou->id_transaction_pickup_wehelpyou,
					'poNo' => $po_no,
					'status' => $status['status'],
					'status_id' => $status['status_id']
				]);
			}
		}

		return ['status' => 'success'];
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
}