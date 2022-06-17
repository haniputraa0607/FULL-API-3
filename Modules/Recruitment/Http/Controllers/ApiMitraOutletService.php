<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\LogOutletBox;

use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Http\Requests\DetailCustomerQueueRequest;
use Modules\Recruitment\Http\Requests\StartOutletServiceRequest;

use Modules\Outlet\Entities\OutletBox;

use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use DB;
use DateTime;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Outlet\Entities\OutletTimeShift;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Product;
use Modules\Product\Entities\ProductProductIcount;
use Modules\Product\Entities\UnitIcount;
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductIcountOutletStock;

class ApiMitraOutletService extends Controller
{
	public function __construct() {
		$this->mitra = "Modules\Recruitment\Http\Controllers\ApiMitra";
		$this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
		$this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
		$this->mitra_log_balance = "Modules\Recruitment\Http\Controllers\MitraLogBalance";
	}

	public function customerQueue(Request $request)
	{
		$user = $request->user();

		$queue = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
		->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
		->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
		->join('products', 'transaction_products.id_product', 'products.id_product')
		->where(function($q) {
			$q->whereNull('service_status');
			$q->orWhere('service_status', '!=', 'Completed');
		})
		->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
		->where(function($q) {
			$q->where('trasaction_payment_type', 'Cash')
			->orWhere('transaction_payment_status', 'Completed');
		})
		->where('transaction_payment_status', '!=', 'Cancelled')
		->wherenull('transaction_products.reject_at')
		->orderBy('schedule_date', 'asc')
		->orderBy('schedule_time', 'asc')
		->paginate(10)
		->toArray();

		$serviceInProgress = TransactionProductService::where('service_status', 'In Progress')
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->first();

		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->whereDate('date', date('Y-m-d'))
		->first();

		$resData = [];
		$dateNow = new DateTime("now");
		foreach ($queue['data'] ?? [] as $val) {
			$timerText = "";
			$dateSchedule = new DateTime($val['schedule_date'] . ' ' .$val['schedule_time']);
			$interval = $dateNow->diff($dateSchedule);
			$day = $interval->d;
			$hour = $interval->h;
			$minute = $interval->i;
			if ($day) {
				$timerText .= $day.' hari, '. $hour.' jam' ;
			} elseif ($hour) {
				$timerText .= $hour.' jam' ;
			} else {
				$timerText .= $minute.' menit' ;
			}

			$timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($val['schedule_date'] . ' ' .$val['schedule_time'])) ? ' lagi' : ' lalu';
			$timerTextColor = (strtotime(date('Y-m-d')) == strtotime($val['schedule_date'])) ? '#FF2424' : '#121212';

			$trx = Transaction::where('id_transaction', $val['id_transaction'])->first();
			$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
			$paymentMethod = null;
			foreach ($trxPayment['payment'] as $p) {
				$paymentMethod = $p['name'];
				if (strtolower($p['name']) != 'balance') {
					break;
				}
			}

			$buttonText = 'Layani';
			$paymentCash = 0;
			if ($val['transaction_payment_status'] == 'Pending' && $val['trasaction_payment_type'] == 'Cash') {
				$buttonText = 'Pembayaran';
				$paymentCash = 1;
			}

			$disable = 0;
			if ($serviceInProgress && $serviceInProgress['id_transaction_product_service'] != $val['id_transaction_product_service']) {
				$disable = 1;
			}

			$scheduleDate = app($this->mitra)->convertTimezoneMitra($val['schedule_date']);
			$scheduleDate = MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($scheduleDate)), 'j F Y');
			$scheduleTime = app($this->mitra)->convertTimezoneMitra($val['schedule_time']);
			$scheduleTime = date('H:i', strtotime($scheduleTime));

			$resData[] = [
				'id_transaction_product_service' => $val['id_transaction_product_service'],
				'order_id' => $val['order_id'] ?? null,
				'transaction_receipt_number' => $val['transaction_receipt_number'],
				'customer_name' => $val['customer_name'],
				'schedule_date' => $scheduleDate,
				'schedule_time' => $scheduleTime,
				'service_status' => $val['service_status'],
				'payment_method' => $paymentMethod,
				'product_name' => $val['product_name'],
				'price' => $val['transaction_product_net'],
				'timer_text' => $timerText,
				'timer_text_color' => $timerTextColor,
				'button_text' => $buttonText,
				'disable' => $disable,
				'id_outlet_box' => $schedule->id_outlet_box ?? null,
				'flag_update_schedule' => $val['flag_update_schedule'],
				'is_conflict' => $val['is_conflict'],
				'payment_cash' => $paymentCash 
			];
		}

		$res = $queue;
		$res['data'] = $resData;
		return MyHelper::checkGet($res);
	}

	public function customerQueueDetail(DetailCustomerQueueRequest $request)
	{
		$user = $request->user();

		$queue = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
		->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
		->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
		->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
		->where('id_transaction_product_service', $request->id_transaction_product_service)
		->where('transaction_payment_status' ,'Completed')
		->first();

		if (!$queue) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		$outlet = Outlet::with(['location_outlet'])->where('id_outlet', $user->id_outlet)->first();
		if (!$outlet) {
			return [
				'status' => 'fail',
				'messages' => ['Outlet tidak ditemukan']
			];
		}

		$product_name = Product::where('id_product',$queue['id_product'])->select('product_name')->first()['product_name'];
		$company_type = $outlet['location_outlet']['company_type'] == 'PT IMA' ? 'ima' : 'ims';
		$product_icounts = ProductProductIcount::with(['product_icounts' => function($pi){$pi->select('id_product_icount','name');}])->where('company_type', $company_type)->where('id_product', $queue['id_product'])->select('id_product_product_icount','id_product_icount','unit','qty','optional')->get()->toArray();
		foreach($product_icounts as $p => $product_icount){
			$cek_optional = false;
			if($product_icount['optional'] == 1){
				$product_icounts[$p]['max_qty'] = ProductIcountOutletStock::where('id_outlet',$user->id_outlet)->where('id_product_icount',$product_icount['id_product_icount'])->where('unit',$product_icount['unit'])->first()['stock'];
			}

			$product_icounts[$p]['name_product_icount'] = $product_icount['product_icounts']['name'];
			unset($product_icounts[$p]['product_icounts']);
		}


		$serviceInProgress = TransactionProductService::where('service_status', 'In Progress')
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->first();

		$disable = 0;
		if ($serviceInProgress) {
			$disable = 1;
		}

		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
		->whereDate('date', date('Y-m-d'))
		->first();

		$dateNow = new DateTime("now");
		$timerText = "";
		$dateSchedule = new DateTime($queue['schedule_date'] . ' ' .$queue['schedule_time']);
		$interval = $dateNow->diff($dateSchedule);
		$day = $interval->d;
		$hour = $interval->h;
		$minute = $interval->i;
		if ($day) {
			$timerText .= $day.' hari, '. $hour.' jam' ;
		} elseif ($hour) {
			$timerText .= $hour.' jam' ;
		} else {
			$timerText .= $minute.' menit' ;
		}

		$timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($queue['schedule_date'] . ' ' .$queue['schedule_time'])) ? ' lagi' : ' lalu';

		$trx = Transaction::where('id_transaction', $queue['id_transaction'])->first();
		$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
		$paymentMethod = null;
		foreach ($trxPayment['payment'] as $p) {
			$paymentMethod = $p['name'];
			if (strtolower($p['name']) != 'balance') {
				break;
			}
		}

		$box = OutletBox::where('id_outlet_box', $schedule['id_outlet_box'] ?? null)->first();

		$logService = TransactionProductServiceLog::whereIn('action', ['Start', 'Extend'])
		->where('id_transaction_product_service', $queue['id_transaction_product_service'])
		->get()->keyBy('action');

		$startTime = !empty($logService['Start']['created_at'])  ? date('Y-m-d H:i:s', strtotime($logService['Start']['created_at'])) : null;
		$extendTime = !empty($logService['Extend']['created_at'])  ? date('Y-m-d H:i:s', strtotime($logService['Extend']['created_at'])) : null;
		
    	$processingTime = ($queue['processing_time_service'] ?? 30) * 60; //second

    	$timeLeft = 0;
    	if ($startTime) {
    		$timeLeft = $processingTime - (strtotime(date('Y-m-d H:i:s')) - strtotime($startTime));
    	}

    	if ($extendTime) {
    		$timeLeft = $timeLeft + $processingTime;
    	}

    	$timeLeft = ($timeLeft >= 1) ? $timeLeft : 0;

    	$extendPopup = (Setting::where('key', 'outlet_service_extend_popup_time')->first()['value'] ?? 5) * 60;

    	$scheduleDate = app($this->mitra)->convertTimezoneMitra($queue['schedule_date']);
    	$scheduleDate = MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($scheduleDate)), 'j F Y');
    	$scheduleTime = app($this->mitra)->convertTimezoneMitra($queue['schedule_time']);
    	$scheduleTime = date('H:i', strtotime($scheduleTime));

    	$res = [
    		'id_transaction' => $queue['id_transaction'],
    		'id_transaction_product_service' => $queue['id_transaction_product_service'],
    		'order_id' => $queue['order_id'] ?? null,
    		'transaction_receipt_number' => $queue['transaction_receipt_number'] ?? null,
    		'customer_name' => $queue['customer_name'],
    		'schedule_date' => $scheduleDate,
    		'schedule_time' => $scheduleTime,
    		'service_status' => $queue['service_status'],
    		'payment_method' => $paymentMethod,
    		'product_name' => $queue['product_name'],
    		'timer_text' => $timerText,
    		'button_text' => 'Layani',
    		'disable' => $disable,
    		'id_outlet_box' => $schedule->id_outlet_box ?? null,
    		'flag_update_schedule' => $queue['flag_update_schedule'],
    		'is_conflict' => $queue['is_conflict'],
    		'outlet_name' => $outlet['outlet_name'],
    		'hairstylist_nickname' => $user['nickname'],
    		'hairstylist_fullname' => $user['fullname'],
    		'outlet_box_code' => $box['outlet_box_code'] ?? null,
    		'outlet_box_name' => $box['outlet_box_name'] ?? null,
    		'processing_time_service' => $timeLeft,
    		'extend_popup_time' => $extendPopup,
    		'product_name' => $product_name,
    		'product_icount_use' => $product_icounts
    	];

    	return MyHelper::checkGet($res);
    }

    public function availableBox(Request $request)
    {
    	$user = $request->user();
		$attendance = $this->checkAttendance($user);
		$box = [];
		if($attendance){
			$box = OutletBox::where([
				['id_outlet', $user->id_outlet],
				['outlet_box_status', 'Active']
			])->get();
		}
    	return MyHelper::checkGet($box);
    }

	public function checkAttendance($user){
		$date =  MyHelper::adjustTimezone(date('Y-m-d'), 7, 'Y-m-d', true);
		$attendance = HairstylistAttendance::where('id_user_hair_stylist', $user->id_user_hair_stylist)->where('id_outlet', $user->id_outlet)->whereDate('attendance_date', $date)->whereNotNull('clock_in')->first();
		if($attendance){
			return true;
		}
		return false;
	}

    public function startService(StartOutletServiceRequest $request)
    {
    	$user = $request->user();

    	$trxReceiptNumber = $request->transaction_receipt_number;
    	$checkQr = Transaction::where('transaction_receipt_number',$trxReceiptNumber)
    	->with('transaction_product_services')
    	->first();

    	if (!$checkQr) {
    		return [
    			'status' => 'fail',
    			'title' => 'QR code tidak terdaftar',
    			'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
    		];
    	}

    	$isNotValidQr = true;
    	foreach ($checkQr['transaction_product_services'] as $val) {
    		if ($val['id_transaction_product_service'] == $request->id_transaction_product_service) {
    			$isNotValidQr = false;
    			break;
    		}
    	}

    	if ($isNotValidQr) {
    		return [
    			'status' => 'fail',
    			'title' => 'QR code tidak sesuai',
    			'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
    		];
    	}   

    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->service_status == 'In Progress') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah dimulai']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	$schedule = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->first();

    	if (!$schedule) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Jadwal Hairstylist tidak ditemukan']
    		];
    	}

    	if (isset($schedule->id_outlet_box) && $schedule->id_outlet_box != $request->id_outlet_box) {
    		if (is_null($request->id_outlet_box)) {
	    		$request->merge(['id_outlet_box' => $schedule->id_outlet_box]);
    		}

    		if ($schedule->id_outlet_box != $request->id_outlet_box) {
	    		return [
	    			'status' => 'fail',
	    			'messages' => ['Tidak dapat menggunakan box yang berbeda']
	    		];	
    		}
    	}

    	$box = OutletBox::where('id_outlet_box', $request->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}

    	if ($box->outlet_box_status != 'Active') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak aktif']
    		];
    	}

    	if ($box->outlet_box_use_status != 0) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sedang digunakan']
    		];
    	}

    	$shift = app($this->mitra)->getOutletShift($user->id_outlet);
    	if (!$shift) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Shift outlet tidak ditemukan']
    		];
    	}

    	$usedBox = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', '!=', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->where('shift', $shift)
    	->where('id_outlet_box', $request->id_outlet_box)
    	->first();

    	if ($usedBox) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sudah dipilih oleh Hairstylist lain']
    		];
    	}

    	DB::beginTransaction();
    	try {
    		$action = ($service->service_status == 'Stopped') ? 'Resume' : 'Start';
    		TransactionProductServiceLog::create([
    			'id_transaction_product_service' => $request->id_transaction_product_service,
    			'action' => $action
    		]);

    		$service->update([
    			'service_status' => 'In Progress',
    			'id_outlet_box' => $request->id_outlet_box
    		]);

    		$box->update(['outlet_box_use_status' => 1]);

    		if (empty($schedule->id_outlet_box)) {
    			HairstylistScheduleDate::where('id_hairstylist_schedule_date', $schedule->id_hairstylist_schedule_date)
    			->update(['id_outlet_box' => $request->id_outlet_box]);
    		}


    		DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
    		DB::rollback();
    		return [
    			'status' => 'fail',
    			'messages' => ['Gagal memulai layanan']
    		];	
    	}

    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 1, 1, $service->transaction_product->product->processing_time_service], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' => [
    			'id_outlet_box' => $box->id_outlet_box,
    			'outlet_box_name' => $box->outlet_box_name,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }
    public function checkStartService(StartOutletServiceRequest $request)
    {
    	$user = $request->user();
    	$trxReceiptNumber = $request->transaction_receipt_number;
    	$checkQr = Transaction::where('transaction_receipt_number',$trxReceiptNumber)
    	->with('transaction_product_services')
    	->first();

    	if (!$checkQr) {
    		return [
    			'status' => 'fail',
    			'title' => 'QR code tidak terdaftar',
    			'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
    		];
    	}

    	$isNotValidQr = true;
    	foreach ($checkQr['transaction_product_services'] as $val) {
    		if ($val['id_transaction_product_service'] == $request->id_transaction_product_service) {
    			$isNotValidQr = false;
    			break;
    		}
    	}

    	if ($isNotValidQr) {
    		return [
    			'status' => 'fail',
    			'title' => 'QR code tidak sesuai',
    			'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
    		];
    	}

    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->service_status == 'In Progress') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah dimulai']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	$schedule = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->first();

    	if (!$schedule) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Jadwal Hairstylist tidak ditemukan']
    		];
    	}

    	if (isset($schedule->id_outlet_box) && $schedule->id_outlet_box != $request->id_outlet_box) {
    		if (is_null($request->id_outlet_box)) {
	    		$request->merge(['id_outlet_box' => $schedule->id_outlet_box]);
    		}

    		if ($schedule->id_outlet_box != $request->id_outlet_box) {
	    		return [
	    			'status' => 'fail',
	    			'messages' => ['Tidak dapat menggunakan box yang berbeda']
	    		];	
    		}
    	}

    	$box = OutletBox::where('id_outlet_box', $request->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}

    	if ($box->outlet_box_status != 'Active') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak aktif']
    		];
    	}

    	if ($box->outlet_box_use_status != 0) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sedang digunakan']
    		];
    	}

    	$shift = app($this->mitra)->getOutletShift($user->id_outlet);
    	if (!$shift) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Shift outlet tidak ditemukan']
    		];
    	}

    	$usedBox = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', '!=', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->where('shift', $shift)
    	->where('id_outlet_box', $request->id_outlet_box)
    	->first();

    	if ($usedBox) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sudah dipilih oleh Hairstylist lain']
    		];
    	}

    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 1, 1, $service->transaction_product->product->processing_time_service], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' => [
    			'id_outlet_box' => $box->id_outlet_box,
    			'outlet_box_name' => $box->outlet_box_name,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }
    public function stopService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->service_status == 'Stopped') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah dihentikan']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}

    	DB::beginTransaction();
    	try {
    		TransactionProductServiceLog::create([
    			'id_transaction_product_service' => $request->id_transaction_product_service,
    			'action' => 'Stop'
    		]);
    		
    		$service->update([
    			'service_status' => 'Stopped',
    			'id_outlet_box' => null
    		]);

    		$box->update(['outlet_box_use_status' => 0]);

    		DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
    		DB::rollback();
    		return [
    			'status' => 'fail',
    			'messages' => ['Gagal menghentikan layanan']
    		];	
    	}

    	return ['status' => 'success'];
    }
    
    public function checkExtendService(Request $request)
    {
    	$user = $request->user();

    	$service = TransactionProductService::where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    	->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    	->join('products', 'transaction_products.id_product', 'products.id_product')
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->flag_update_schedule) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu layanan sudah diperpanjang, tidak dapat memperpanjang waktu lebih dari sekali']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	if (empty($service->processing_time_service)) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu pemrosesan tidak ditemukan']
    		];
    	}

    	$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();
    	$processingTime = $service->processing_time_service ?? 30;
    	$startTime = TransactionProductServiceLog::where('action', 'Start')
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$startTime) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu layanan dimulai tidak ditemukan']
    		];
    	}

    	$timeLeft = ($processingTime * 60) -  (strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d H:i:s', strtotime($startTime->created_at))));
    	$newTime = ($processingTime * 60) + $timeLeft;
    	$newTime = ($newTime >= 1) ? $newTime : 0;

    	$extended = new DateTime("+".  $newTime ." seconds");
    	$extendedTime = $extended->format('H:i:s');

    	
    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 1, 1, $processingTime], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' =>[
    			'extended_time' => $newTime,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }
    public function extendService(Request $request)
    {
    	$user = $request->user();

    	$service = TransactionProductService::where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    	->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    	->join('products', 'transaction_products.id_product', 'products.id_product')
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->flag_update_schedule) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu layanan sudah diperpanjang, tidak dapat memperpanjang waktu lebih dari sekali']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	if (empty($service->processing_time_service)) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu pemrosesan tidak ditemukan']
    		];
    	}

    	$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();
    	$processingTime = $service->processing_time_service ?? 30;
    	$startTime = TransactionProductServiceLog::where('action', 'Start')
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$startTime) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Waktu layanan dimulai tidak ditemukan']
    		];
    	}

    	$timeLeft = ($processingTime * 60) -  (strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d H:i:s', strtotime($startTime->created_at))));
    	$newTime = ($processingTime * 60) + $timeLeft;
    	$newTime = ($newTime >= 1) ? $newTime : 0;

    	$extended = new DateTime("+".  $newTime ." seconds");
    	$extendedTime = $extended->format('H:i:s');

    	DB::beginTransaction();
    	try {

    		TransactionProductServiceLog::create([
    			'id_transaction_product_service' => $request->id_transaction_product_service,
    			'action' => 'Extend'
    		]);

    		$service->update(['flag_update_schedule' => 1]);

    		$conflictServices = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
    		->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
    		->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    		->join('products', 'transaction_products.id_product', 'products.id_product')
    		->whereNull('service_status')
    		->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    		->where('transaction_payment_status' ,'Completed')
    		->whereDate('schedule_date', date('Y-m-d'))
    		->where('schedule_time', '<', $extendedTime)
    		->orderBy('schedule_date', 'asc')
    		->orderBy('schedule_time', 'asc')
    		->get();

    		foreach ($conflictServices ?? [] as $conflict) {
    			$conflict->update(['is_conflict' => 1]);
    		}

    		DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
    		DB::rollback();
    		return [
    			'status' => 'fail',
    			'messages' => ['Gagal memperpanjang waktu layanan']
    		];	
    	}

    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 1, 1, $processingTime], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' =>[
    			'extended_time' => $newTime,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }
    public function checkCompleteService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}


    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 0, 0, 0], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' => [
    			'id_outlet_box' => $box->id_outlet_box,
    			'outlet_box_name' => $box->outlet_box_name,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }
    public function completeService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    	->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where('id_transaction_product_service', $request->id_transaction_product_service)
    	->first();

    	if (!$service) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan tidak ditemukan']
    		];
    	}

    	if ($service->service_status == 'Completed') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Layanan sudah selesai']
    		];
    	}

    	$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}

    	$outlet = Outlet::with(['location_outlet'])->where('id_outlet', $box->id_outlet)->first();
    	if (!$outlet) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Outlet tidak ditemukan']
    		];
    	}

    	DB::beginTransaction();
    	try {
    		$trx = Transaction::where('id_transaction', $service->id_transaction)->with('outlet', 'user')->first();
    		TransactionProductServiceLog::create([
    			'id_transaction_product_service' => $request->id_transaction_product_service,
    			'action' => 'Complete'
    		]);
    		
    		$service->update([
    			'service_status' => 'Completed',
    			'completed_at' => date('Y-m-d H:i:s')
    		]);

    		TransactionProduct::where('id_transaction_product', $service->id_transaction_product)
    		->update([
    			'transaction_product_completed_at' => date('Y-m-d H:i:s')
    		]);

    		$box->update(['outlet_box_use_status' => 0]);

            //remove hs from table not avilable
    		HairstylistNotAvailable::where('id_transaction_product_service', $service['id_transaction_product_service'])->delete();

            // log rating outlet
    		UserRatingLog::updateOrCreate([
    			'id_user' => $trx->id_user,
    			'id_transaction' => $trx->id_transaction,
    			'id_outlet' => $trx->id_outlet
    		],[
    			'refuse_count' => 0,
    			'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
    		]);

            // log rating hairstylist
    		UserRatingLog::updateOrCreate([
    			'id_user' => $trx->id_user,
    			'id_transaction' => $trx->id_transaction,
    			'id_transaction_product_service' => $request->id_transaction_product_service,
    			'id_user_hair_stylist' => $user->id_user_hair_stylist
    		],[
    			'refuse_count' => 0,
    			'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
    		]);

    		$trx->update(['show_rate_popup' => '1']);

    		if($request->product_icount_use){
                //cek 
                $company_type = $outlet['location_outlet']['company_type'] == 'PT IMA' ? 'ima' : 'ims';
                $product_icounts = ProductProductIcount::where('company_type', $company_type)->where('id_product', $service['id_product'])->select('id_product_product_icount','id_product_icount','unit','qty','optional')->get()->toArray();
                $product_product = [];
                foreach($product_icounts as $p){
                    $product_product[$p['id_product_icount'].'_'.$p['unit']] = $p;
                }
                foreach($request->product_icount_use as $key => $product_use){
					if($product_product[$product_use['id_product_icount'].'_'.$product_use['unit']]['optional'] == 1){
						$this_qty = ($product_use['qty'] - $product_product[$product_use['id_product_icount'].'_'.$product_use['unit']]['qty'])*-1;
						$product_icount = new ProductIcount();
						if($this_qty != 0){
							$update_stock = $product_icount->find($product_use['id_product_icount'])->addLogStockProductIcount($this_qty,$product_use['unit'],'Transaction Outlet Service',$service['id_transaction_product_service']);
							if(!$update_stock){
								DB::rollback();
							}
						}
					}
                }
            }
            
            // notif hairstylist
    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
    			'Mitra HS - Transaction Service Completed',
    			$user['phone_number'],
    			[
    				'date' => $trx['transaction_date'],
    				'outlet_name' => $trx['outlet']['outlet_name'],
    				'detail' => $detail ?? null,
    				'receipt_number' => $trx['transaction_receipt_number'],
    				'order_id' => $service['order_id']
    			], null, false, false, 'hairstylist'
    		);

            // notif user customer
    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
    			'Transaction Service Completed', 
    			$trx->user->phone, 
    			[
    				'date' => $trx['transaction_date'],
    				'outlet_name' => $trx['outlet']['outlet_name'],
    				'detail' => $detail ?? null,
    				'receipt_number' => $trx['transaction_receipt_number'],
    				'order_id' => $service['order_id']
    			]
    		);

    		$this->completeTransaction($service->id_transaction);

    		DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
    		DB::rollback();
    		return [
    			'status' => 'fail',
    			'messages' => ['Gagal menyelesaikan layanan']
    		];	
    	}

    	$box_url = str_replace(['%box_code%', '%command%', '%status%', '%time%'], [$box->outlet_box_code, 0, 0, 0], $box->outlet_box_url ?: MyHelper::setting('outlet_box_default_url'));

    	return [
    		'status' => 'success',
    		'result' => [
    			'id_outlet_box' => $box->id_outlet_box,
    			'outlet_box_name' => $box->outlet_box_name,
    			'outlet_box_url' => $box_url,
    		]
    	];
    }

    public function completeTransaction($id_transaction)
    {
    	$trxProducts = TransactionProduct::where('id_transaction', $id_transaction)
    	->whereNull('transaction_product_completed_at')
    	->first();

    	if (!$trxProducts) {
    		TransactionOutletService::where('id_transaction', $id_transaction)
    		->update(['completed_at' => date('Y-m-d H:i:s')]);

    		$trx = Transaction::with('outlet','user')->find($id_transaction);
    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
    			'Transaction Completed', 
    			$trx->user->phone, 
    			[
    				'date' => $trx['transaction_date'],
    				'outlet_name' => $trx['outlet']['outlet_name'],
    				'detail' => $detail ?? null,
    				'receipt_number' => $trx['transaction_receipt_number']
    			]
    		);
    	}

    	return true;
    }

    public function paymentCashDetail(Request $request){
    	$post = $request->json()->all();
    	$user = $request->user();
    	if(empty($post['order_id']) && empty($post['payment_code'])){
    		return ['status' => 'fail', 'messages' => ['Order ID and Payment code can not be empty']];
    	}

    	$trx = Transaction::join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
    	->where('transaction_receipt_number', $post['order_id'])->first();
    	if(empty($trx)){
    		return ['status' => 'fail', 'messages' => ['Transaction not found']];
    	}

    	$checkCode = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])
    	->where('payment_code', $post['payment_code'])->first();
    	if(empty($checkCode)){
    		return ['status' => 'fail', 'messages' => ['The code you entered is wrong']];
    	}

    	$trxProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
    	->leftJoin('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    	->where('transaction_products.id_transaction', $trx['id_transaction'])
    	->select('products.product_name', 'transaction_products.*', 'transaction_product_services.*')->get()->toArray();

    	if(empty($trxProduct)){
    		return ['status' => 'fail', 'messages' => ['Products not found']];
    	}

    	$products = [];
    	foreach ($trxProduct as $p){
    		if($p['type'] == 'Service'){
    			$check = array_search($p['id_product'], array_column($p, 'id_product'));
    			if($check !== false){
    				$products[$check]['qty'] = $products[$check]['qty'] + $p['transaction_product_qty'];
    				$products[$check]['product_subtotal'] = $products[$check]['product_subtotal'] + $p['transaction_product_subtotal'];
    				continue;
    			}
    		}

    		$products[] = [
    			'id_product' => $p['id_product'],
    			'product_name' => $p['product_name'],
    			'qty' => $p['transaction_product_qty'],
    			'product_subtotal' => $p['transaction_product_subtotal']
    		];
    	}

    	$result = [
    		'current_balance' => $user->balance??0,
    		'order_id' => $trx['transaction_receipt_number'],
    		'transaction_subtotal' => $trx['transaction_subtotal'],
    		'transaction_tax' => $trx['transaction_tax'],
    		'transaction_grandtotal' => $trx['transaction_grandtotal'],
    		'transaction_date' => MyHelper::dateFormatInd($trx['transaction_date'], true, false),
    		'customer_name' => $trx['customer_name'],
    		'customer_email' => $trx['customer_email'],
    		'currency' => 'Rp',
    		'products' => $products
    	];

    	return response()->json(MyHelper::checkGet($result));
    }

    public function paymentCashCompleted(Request $request){
    	$user = $request->user();
    	$post = $request->json()->all();
    	if(empty($post['order_id'])){
    		return ['status' => 'fail', 'messages' => ['Order ID can not be empty']];
    	}

    	$trx = Transaction::where('transaction_receipt_number', $post['order_id'])->first();
    	if(empty($trx)){
    		return ['status' => 'fail', 'messages' => ['Transaction not found']];
    	}

    	if($trx['transaction_payment_status'] == 'Completed'){
    		return ['status' => 'fail', 'messages' => ['This transaction has been paid']];
    	}

    	$update = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])
    	->update(['cash_received_by' => $user->id_user_hair_stylist]);

    	if($update){
    		$update = Transaction::where('id_transaction', $trx['id_transaction'])
    		->update(['transaction_payment_status' => 'Completed', 'completed_at' => date('Y-m-d H:i:s')]);

    		if($update){
    			$dt = [
    				'id_user_hair_stylist'    => $user->id_user_hair_stylist,
    				'balance'                 => $trx['transaction_grandtotal'],
    				'id_reference'            => $trx['id_transaction'],
    				'source'                  => 'Receive Payment'
    			];
    			app($this->mitra_log_balance)->insertLogBalance($dt);
    		}
    	}

    	return response()->json(MyHelper::checkUpdate($update));
    }

    public function outletServiceDetail(Request $request)
    {
    	$user = $request->user();
    	$user->load('outlet.brands');

    	$outlet = [
    		'id_outlet' => $user['outlet']['id_outlet'],
    		'outlet_code' => $user['outlet']['outlet_code'],
    		'outlet_name' => $user['outlet']['outlet_name'],
    		'outlet_address' => $user['outlet']['outlet_address'],
    		'outlet_latitude' => $user['outlet']['outlet_latitude'],
    		'outlet_longitude' => $user['outlet']['outlet_longitude']
    	];

    	$brand = [
    		'id_brand' => $user['outlet']['brands'][0]['id_brand'],
    		'brand_code' => $user['outlet']['brands'][0]['code_brand'],
    		'brand_name' => $user['outlet']['brands'][0]['name_brand'],
    		'brand_logo' => $user['outlet']['brands'][0]['logo_brand'],
    		'brand_logo_landscape' => $user['outlet']['brands'][0]['logo_landscape_brand']
    	];

    	$shift = app($this->mitra)->getOutletShift($user->id_outlet, null, true);

    	$schedule = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->whereIn('shift', $shift ?? [])
    	->first();
    	$overtime = HairstylistOverTime::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->wheredate('date', date('Y-m-d'))
    	->first();
    	$box = [];
    	if ($schedule) {
    		$shift = $schedule->shift;
    		$attendance = HairstylistAttendance::where('id_user_hair_stylist', '=', $user->id_user_hair_stylist)
    		->whereDate('attendance_date', date('Y-m-d'))
			->whereNotNull('clock_in')
    		->first();
    		if (!$attendance) {
    			$box = [];
    			$outlet_box = null;
    		}else{
    			$log = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$attendance->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    			if(optional($log)->type == 'clock_in'){
    				if ($schedule->id_outlet_box) {
    					$box = OutletBox::where([
    						['id_outlet', $user->id_outlet],
    						['id_outlet_box', $schedule->id_outlet_box],
    						['outlet_box_status', 'Active']
    					])->get();
    					$outlet_box = $schedule->id_outlet_box;
    				} else {
    					$boxs = OutletBox::where([
    						['id_outlet', $user->id_outlet],
    						['outlet_box_status', 'Active']
    					])->get();
    					$box = array();
    					foreach ($boxs as $value) {
    						$hs = HairstylistScheduleDate::whereDate('date', date('Y-m-d'))
    						->whereTime('time_start', '<=' ,date('H:i:s'))
    						->whereTime('time_end','>=',date('H:i:s'))
    						->where('id_outlet_box',$value['id_outlet_box'])
    						->join('hairstylist_attendances','hairstylist_attendances.id_hairstylist_schedule_date','hairstylist_schedule_dates.id_hairstylist_schedule_date')->first();
    						if(!$hs){
    							$box[] = $value;
    						}else{
    							$logs = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$hs->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    							if($logs->type != 'clock_in'){
    								$box[] = $value;
    							}
    						}
    					}
    					$outlet_box = null;
    				}
    			}else{
    				$box = [];
    				$outlet_box = null;
    			}       
    		}
    	}elseif($overtime){
    		$schedule = HairstylistSchedule::join(
    			'hairstylist_schedule_dates', 
    			'hairstylist_schedules.id_hairstylist_schedule', 
    			'hairstylist_schedule_dates.id_hairstylist_schedule'
    		)
    		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    		->whereDate('date', date('Y-m-d'))
    		->first(); 
    		$outlets = Outlet::find($user->id_outlet);
    		$timezone = $outlets->city->province->time_zone_utc;
    		$dateTime = $dateTime ?? date('Y-m-d H:i:s');
    		$curTime = date('H:i:s', strtotime($dateTime));
    		$day = MyHelper::indonesian_date_v2($dateTime, 'l');
    		$day = str_replace('Jum\'at', 'Jumat', $day);
			
			$time_start = MyHelper::adjustTimezone($schedule['time_start'], $timezone, 'H:i', true);
			$time_end = MyHelper::adjustTimezone($schedule['time_end'], $timezone, 'H:i', true);
			$approve_ovt = false;
			if($schedule['is_overtime'] == 1 && date('H:i') >= $time_start && date('H:i') <= $time_end){
				$approve_ovt = true;
			}
    		$outletSchedule = OutletSchedule::where('id_outlet', $user->id_outlet)->where('day', $day)->first();
    		$isHoliday = app('Modules\Outlet\Http\Controllers\ApiOutletController')->isHoliday($user->id_outlet);
    		$outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)->where('shift',$schedule->shift)->first();

    		if (!$outletSchedule || $outletSchedule->is_closed) {
    			$box = [];
    			$outlet_box = null;
    		}elseif ($isHoliday['status']) {
    			$box = [];
    			$outlet_box = null;
    		}elseif (!$approve_ovt) {
    			$box = [];
    			$outlet_box = null;
    		}elseif($overtime->time == "after"){
    			$str = explode(":",$overtime->duration);
    			$string = "+".(int)$str[0].' hours'.' '.(int)$str[1].' minutes'.' '. (int)$str[2].' seconds';
    			$start = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_start;
    			$end = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_end;
    			$end = date('Y-m-d H:i:s', strtotime($string. $end));
    			$now = date('Y-m-d H:i:s');
    			if($start <= $now && $end >= $now){
    				if ($schedule->id_outlet_box) {
    					$box = OutletBox::where([
    						['id_outlet', $user->id_outlet],
    						['id_outlet_box', $schedule->id_outlet_box],
    						['outlet_box_status', 'Active']
    					])->get();
    					$outlet_box = $schedule->id_outlet_box;
    				} else {
    					$boxs = OutletBox::where([
    						['id_outlet', $user->id_outlet],
    						['outlet_box_status', 'Active']
    					])->get();
    					$box = array();
    					foreach ($boxs as $value) {
    						$hs = HairstylistScheduleDate::whereDate('date', date('Y-m-d'))
    						->whereTime('time_start', '<=' ,date('H:i:s'))
    						->whereTime('time_end','>=',date('H:i:s'))
    						->where('id_outlet_box',$value['id_outlet_box'])
    						->join('hairstylist_attendances','hairstylist_attendances.id_hairstylist_schedule_date','hairstylist_schedule_dates.id_hairstylist_schedule_date')->first();
    						if(!$hs){
    							$box[] = $value;
    						}else{
    							$logs = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$hs->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    							if($logs->type != 'clock_in'){
    								$box[] = $value;
    							}
    						}
    					}
    					$outlet_box = null;
    				}
    			}
    		}elseif($overtime->time == "before"){
    			$str = explode(":",$overtime->duration);
    			$string = "-".(int)$str[0].' hours'.' -'.(int)$str[1].' minutes'.' -'. (int)$str[2].' seconds';
    			$start = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_start;
    			$end = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_end;
    			$start = date('Y-m-d H:i:s', strtotime($start . $string));
    			$now = date('Y-m-d H:i:s');
    			if($start <= $now && $schedule->time_end >= $now){
    				$attendance = HairstylistAttendance::where('id_user_hair_stylist', '=', $user->id_user_hair_stylist)
    				->whereDate('attendance_date', date('Y-m-d'))
    				->first();
    				if (!$attendance) {
    					$box = [];
    					$outlet_box = null;
    				}else{
    					$log = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$attendance->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    					if($log->type == 'clock_in'){
    						if ($schedule->id_outlet_box) {
    							$box = OutletBox::where([
    								['id_outlet', $user->id_outlet],
    								['id_outlet_box', $schedule->id_outlet_box],
    								['outlet_box_status', 'Active']
    							])->get();
    							$outlet_box = $schedule->id_outlet_box;
    						} else {
    							$boxs = OutletBox::where([
    								['id_outlet', $user->id_outlet],
    								['outlet_box_status', 'Active']
    							])->get();
    							$box = array();
    							foreach ($boxs as $value) {
    								$hs = HairstylistScheduleDate::whereDate('date', date('Y-m-d'))
    								->whereTime('time_start', '<=' ,date('H:i:s'))
    								->whereTime('time_end','>=',date('H:i:s'))
    								->where('id_outlet_box',$value['id_outlet_box'])
    								->join('hairstylist_attendances','hairstylist_attendances.id_hairstylist_schedule_date','hairstylist_schedule_dates.id_hairstylist_schedule_date')->first();
    								if(!$hs){
    									$box[] = $value;
    								}else{
    									$logs = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$hs->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    									if($logs->type != 'clock_in'){
    										$box[] = $value;
    									}
    								}
    							}
    							$outlet_box = null;
    						}
    					}else{
    						$box = [];
    						$outlet_box = null;
    					}       
    				}
    			}
    		}
    	}
    	$res = [
    		'id_outlet_box' => $outlet_box ?? null,
    		'outlet' => $outlet,
    		'brand' => $brand,
    		'box' => $box
    	];
    	return MyHelper::checkGet($res);
    }

    public function customerHistory(Request $request)
    {
    	$user = $request->user();
    	$tps = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
    	->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
    	->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
    	->join('products', 'transaction_products.id_product', 'products.id_product')
    	->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
    	->where(function($q) {
    		$q->whereNotNull('transaction_product_services.completed_at');		
    		$q->Where('service_status', '=', 'Completed');
    	})
    	->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    	->where(function($q) {
    		$q->where('trasaction_payment_type', 'Cash')
    		->orWhere('transaction_payment_status', 'Completed');
    	})
    	->where('transaction_payment_status',  'Completed');

    	if ($filter_range = $request->filter_range) {
    		if (is_array($filter_range)) {
    			$tps->whereDate('schedule_date', '>=', date('Y-m-d', strtotime($filter_range[0])));
    			$tps->whereDate('schedule_date', '<=', date('Y-m-d', strtotime($filter_range[1] ?? $filter_range[0])));
    		} elseif ($filter_range == 'last7days') {
    			$tps->whereDate('schedule_date', '>=', date('Y-m-d', strtotime('-7 days')));
    		} elseif ($filter_range == 'last30days') {
    			$tps->whereDate('schedule_date', '>=', date('Y-m-d', strtotime('-30 days')));
    		} elseif ($filter_range == 'this_month') {
    			$tps->whereDate('schedule_date', '>=', date('Y-m-01'));
    		} elseif ($filter_range == 'today'){
    			$tps->whereDate('schedule_date', date('Y-m-d'));
    		}
    	}

    	if((!empty($request->sort) && $request->sort == 'desc') || empty($request->sort)){
    		$tps = $tps->orderBy('schedule_date', 'desc')->orderBy('schedule_time', 'desc');
    	}elseif(!empty($request->sort) && $request->sort == 'asc'){
    		$tps = $tps->orderBy('schedule_date', 'asc')->orderBy('schedule_time', 'asc');
    	}

    	$tps = $tps->paginate(10);

    	$tps->transform(function($item) use ($user) {
    		$trx = Transaction::where('id_transaction', $item->id_transaction)->first();
    		$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
    		$paymentMethod = null;
    		foreach ($trxPayment['payment'] as $p) {
    			$paymentMethod = $p['name'];
    			if (strtolower($p['name']) != 'balance') {
    				break;
    			}
    		}

    		return [
    			'id_transaction_product_service' => $item->id_transaction_product_service,
    			'transaction_date' => MyHelper::indonesian_date_v2($item->schedule_date, 'd F Y'),
				'service_start' => date('H:i', strtotime($item->schedule_time)), //TODO update with service start
				'service_end' => date('H:i', strtotime($item->completed_at)),
				'customer_name' => $item->customer_name,
				'product_name' => $item->product_name,
				'order_id' => $item->order_id,
				'outlet_name' => $item->outlet_name,
				'hairstylist_name' => $user->fullname,
				'schedule_time' => date('H:i', strtotime($item->schedule_time)),
				'price' => $item->transaction_product_net,
				'payment_method' => $paymentMethod,
			];
		});

    	return MyHelper::checkGet($tps);
    }

    public function selectBox(Request $request)
    {
    	$user = $request->user();
    	$schedule = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->first();

    	if (!$schedule) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Jadwal Hairstylist tidak ditemukan']
    		];
    	}

    	if (isset($schedule->id_outlet_box) && $schedule->id_outlet_box != $request->id_outlet_box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Tidak dapat menggunakan box yang berbeda']
    		];	
    	}

    	$box = OutletBox::where('id_outlet_box', $request->id_outlet_box)->first();

    	if (!$box) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak ditemukan']
    		];
    	}

    	if ($box->outlet_box_status != 'Active') {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box tidak aktif']
    		];
    	}

    	if ($box->outlet_box_use_status != 0) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sedang digunakan']
    		];
    	}

    	$shift = app($this->mitra)->getOutletShift($user->id_outlet);
    	if (!$shift) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Shift outlet tidak ditemukan']
    		];
    	}

    	$usedBox = HairstylistSchedule::join(
    		'hairstylist_schedule_dates', 
    		'hairstylist_schedules.id_hairstylist_schedule', 
    		'hairstylist_schedule_dates.id_hairstylist_schedule'
    	)
    	->where('id_user_hair_stylist', '!=', $user->id_user_hair_stylist)
    	->whereDate('date', date('Y-m-d'))
    	->where('shift', $shift)
    	->where('id_outlet_box', $request->id_outlet_box)
    	->first();

    	if ($usedBox) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Box sudah dipilih oleh Hairstylist lain']
    		];
    	}
    	$overtime = HairstylistOverTime::where('id_user_hair_stylist', $user->id_user_hair_stylist)
    	->wheredate('date', date('Y-m-d'))
    	->first();
    	$attendance = HairstylistAttendance::where('id_user_hair_stylist', '=', $user->id_user_hair_stylist)
    	->whereDate('attendance_date', date('Y-m-d'))
//    	->wherenotnull('clock_in')
    	// ->wherenull('clock_out')
    	->first();
    	if($overtime){

    		$schedule = HairstylistSchedule::join(
    			'hairstylist_schedule_dates', 
    			'hairstylist_schedules.id_hairstylist_schedule', 
    			'hairstylist_schedule_dates.id_hairstylist_schedule'
    		)
    		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    		->whereDate('date', date('Y-m-d'))
    		->first(); 
    		$outlets = Outlet::find($user->id_outlet);
    		$timezone = $outlets->city->province->time_zone_utc;
    		$dateTime = $dateTime ?? date('Y-m-d H:i:s');
    		$curTime = date('H:i:s', strtotime($dateTime));
    		$day = MyHelper::indonesian_date_v2($dateTime, 'l');
    		$day = str_replace('Jum\'at', 'Jumat', $day);

    		$outletSchedule = OutletSchedule::where('id_outlet', $user->id_outlet)->where('day', $day)->first();
    		$isHoliday = app('Modules\Outlet\Http\Controllers\ApiOutletController')->isHoliday($user->id_outlet);
    		$outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)->where('shift',$schedule->shift)->first();

    		if (!$outletSchedule || $outletSchedule->is_closed) {
    			return [
    				'status' => 'fail',
    				'messages' => ['Diluar jam kerja hairstylist']
    			];
    		}elseif ($isHoliday['status']) {
    			return [
    				'status' => 'fail',
    				'messages' => ['Diluar jam kerja hairstylist']
    			];
    		}elseif($overtime->time == "after"){
    			$str = explode(":",$overtime->duration);
    			$string = "+".(int)$str[0].' hours'.' '.(int)$str[1].' minutes'.' '. (int)$str[2].' seconds';
    			$start = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_start;
    			$end = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_end;
    			$end = date('Y-m-d H:i:s', strtotime($string. $end));
    			$now = date('Y-m-d H:i:s');
    			if($start >= $now && $end <= $now){
    				return [
    					'status' => 'fail',
    					'messages' => ['Diluar jam kerja hairstylist']
    				];
    			}
    		}elseif($overtime->time == "before"){
    			$str = explode(":",$overtime->duration);
    			$string = "-".(int)$str[0].' hours'.' -'.(int)$str[1].' minutes'.' -'. (int)$str[2].' seconds';
    			$start = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_start;
    			$end = date('Y-m-d',strtotime($schedule->date))." ".$outletShift->shift_time_end;
    			$start = date('Y-m-d H:i:s', strtotime($start . $string));
    			$now = date('Y-m-d H:i:s');
    			if($start >= $now && $end <= $now){
    				return [
    					'status' => 'fail',
    					'messages' => ['Diluar jam kerja hairstylist 4; ' . "$start ; $now ; $end ; $start $string"]
    				];
    			}
    		}
    	}elseif(!$attendance) {
    		$log = HairstylistAttendanceLog::where(array('id_hairstylist_attendance'=>$attendance->id_hairstylist_attendance))->orderby('id_hairstylist_attendance_log','desc')->first();
    		if($log->type != 'clock_in'){
    			return [
    				'status' => 'fail',
    				'messages' => ['Tidak ada kehadiran dibutuhkan untuk hari ini']
    			];
    		}
    	}
    	DB::beginTransaction();
    	try {

    		HairstylistScheduleDate::where('id_hairstylist_schedule_date', $schedule->id_hairstylist_schedule_date)
    		->update(['id_outlet_box' => $request->id_outlet_box]);

    		$createLog = LogOutletBox::create([
    			'id_user_hair_stylist' => $user->id_user_hair_stylist,
    			'assigned_by' => null,
    			'id_outlet_box' => $request->id_outlet_box,
    			'note' => null
    		]);

    		DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
    		DB::rollback();
    		return [
    			'status' => 'fail',
    			'messages' => ['Gagal memilih box']
    		];	
    	}


    	return ['status' => 'success'];	
    }

    public function shiftBox($id_outlet)
    {
		$shift = app($this->mitra)->getOutletShift($id_outlet);
		$shift_2 = HairStylistScheduleDate::join('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
					->whereDate('hairstylist_schedule_dates.date',date('Y-m-d'))
					->where('hairstylist_schedule_dates.is_overtime', 1)
					->get()->toArray();
    	$box = OutletBox::where('id_outlet', $id_outlet)->with([
    		'hairstylist_schedule_dates.hairstylist_schedule.user_hair_stylist',
    		'hairstylist_schedule_dates' => function($q) use ($shift, $shift_2) {
    			$q->where('date', date('Y-m-d'))->where(function($q2) use($shift, $shift_2){
					$q2->where('shift', $shift);
					if($shift_2){
						foreach($shift_2 as $s2){
							$q2->orWhere(function($q3) use($s2){
								$q3->where('shift',$s2['shift']);
								$q3->where('is_overtime', 1);
							});
						}
					}
				});
    		}
    	])->get();
    	return $box;
    }
}
