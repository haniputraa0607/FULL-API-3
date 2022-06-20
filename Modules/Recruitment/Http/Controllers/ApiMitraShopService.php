<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

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

use Modules\Outlet\Entities\OutletBox;

use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use DB;
use DateTime;
use UserHairStylist as GlobalUserHairStylist;
use App\Http\Models\Outlet;

class ApiMitraShopService extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->mitra = "Modules\Recruitment\Http\Controllers\ApiMitra";
        $this->mitra_outlet_service = "Modules\Recruitment\Http\Controllers\ApiMitraOutletService";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
    }

    public function detailShopService(Request $request)
    {
    	$user = $request->user();

        $trxReceiptNumber = $request->transaction_receipt_number;
    	$trx = Transaction::where('transaction_receipt_number', $trxReceiptNumber)->first();
    	if (!$trx) {
    		return ['status' => 'fail', 'messages' => ['Transaksi tidak ditemukan']];
    	}

    	if ($trx->transaction_payment_status == 'Pending' && $trx->trasaction_payment_type != 'Cash') {
    		return ['status' => 'fail', 'messages' => ['Proses pembayaran belum selesai']];
    	}

    	if ($trx->id_outlet != $user->id_outlet) {
    		$outlet = Outlet::where('id_outlet', $trx->id_outlet)->first();
    		return ['status' => 'fail', 'messages' => ['Pengambilan barang hanya dapat dilakukan di outlet ' .$outlet->outlet_name]];
    	}

    	$paymentCash = 0;
    	if ($trx->transaction_payment_status == 'Pending' && $trx->trasaction_payment_type == 'Cash') {
    		$paymentCash = 1;
    	}

    	$trx->load([
    		'user',
    		'transaction_products.product.photos',
    		'transaction_products' => function($q) {
    			$q->where('type', 'Product');
    		}
    	]);
    	$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
    	$paymentMethod = null;
    	foreach ($trxPayment['payment'] as $val) {
    		$paymentMethod = $val['name'];
    		if (strtolower($val['name']) != 'balance') {
    			break;
    		}
    	}

    	$trxProduct = $trx->transaction_products;
        if(empty($trxProduct) || $trxProduct->isEmpty()){
            return ['status' => 'fail', 'messages' => ['Barang tidak ditemukan']];
        }

        $products = [];
        $subtotalProduct = 0;
        foreach ($trxProduct as $product){
        	$productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
            $products[] = [
                'id_product' => $product['id_product'],
                'product_name' => $product['product']['product_name'],
				'qty' => $product['transaction_product_qty'],
				'price' => $product['transaction_product_price'],
				'subtotal' => $product['transaction_product_subtotal'],
				'photo' => $productPhoto
            ];
            $subtotalProduct += abs($product['transaction_product_subtotal']);
        }

    	$res = [
    		'transaction_receipt_number' => $trx['transaction_receipt_number'],
    		'transaction_date' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($trx['transaction_date'])), 'j F Y'),
    		'name' => $trx['user']['name'],
    		'payment_method' => $paymentMethod,
    		'transaction_payment_status' => $trx['transaction_payment_status'],
    		'payment_cash' => $paymentCash,
    		'product_subtotal' => $subtotalProduct,
    		'products' => $products
    	];

    	return MyHelper::checkGet($res);
    }

    public function confirmShopService(Request $request)
    {
    	$user = $request->user();

        $trxReceiptNumber = $request->transaction_receipt_number;
    	$trx = Transaction::where('transaction_receipt_number', $trxReceiptNumber)->first();
    	if (!$trx) {
    		return ['status' => 'fail', 'messages' => ['Transaksi tidak ditemukan']];
    	}

    	if ($trx->transaction_payment_status != 'Completed') {
    		return ['status' => 'fail', 'messages' => ['Proses pembayaran belum selesai']];
    	}

    	if ($trx->id_outlet != $user->id_outlet) {
    		$outlet = Outlet::where('id_outlet', $trx->id_outlet)->first();
    		return ['status' => 'fail', 'messages' => ['Pengambilan barang hanya dapat dilakukan di outlet ' .$outlet->outlet_name]];
    	}

    	$trxProducts = TransactionProduct::where('id_transaction', $trx->id_transaction)
						->where('type', 'Product')
						->get();

		if (empty($trxProducts) || $trxProducts->isEmpty()) {
			return ['status' => 'fail', 'messages' => ['Barang tidak ditemukan']];
		}

    	DB::beginTransaction();
    	foreach ($trxProducts as $product) {
    		$product->update([
    			'transaction_product_completed_at' => date('Y-m-d H:i:s'),
    			'id_user_hair_stylist' => $user->id_user_hair_stylist
    		]);
    	}

    	// log rating outlet
        UserRatingLog::updateOrCreate([
            'id_user' => $trx->id_user,
            'id_transaction' => $trx->id_transaction,
            'id_outlet' => $trx->id_outlet
        ],[
            'refuse_count' => 0,
            'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
        ]);

        $trx->update(['show_rate_popup' => '1']);

    	app($this->mitra_outlet_service)->completeTransaction($trx->id_transaction);

    	// notif hairstylist
        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
            'Mitra SPV - Transaction Product Taken',
            $user['phone_number'],
            [
            	'date' => $trx['transaction_date'],
            	'outlet_name' => $trx['outlet']['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $trx['transaction_receipt_number']
            ], null, false, false, 'hairstylist'
        );
        if($user['level']!='Supervisor'){
            $spv = UserHairStylist::where('id_outlet',$user['id_outlet'])->where('level','Supervisor')->first();
            app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                'Mitra SPV - Transaction Product Taken',
                $spv['phone_number'],
                [
                    'date' => $trx['transaction_date'],
                    'outlet_name' => $trx['outlet']['outlet_name'],
                    'detail' => $detail ?? null,
                    'receipt_number' => $trx['transaction_receipt_number']
                ], null, false, false, 'hairstylist'
            );
        }

        // notif user customer
        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
        	'Transaction Product Taken', 
        	$trx->user->phone, 
        	[
	            'date' => $trx['transaction_date'],
            	'outlet_name' => $trx['outlet']['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $trx['transaction_receipt_number']
	        ]
	    );

    	DB::commit();

    	return ['status' => 'success'];
    }

    public function historyShopService(Request $request)
    {
    	$user = $request->user();
    	$id_user_hair_stylist = $user->id_user_hair_stylist;

    	$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
    	$dateStart = $thisYear . '-' . $thisMonth . '-01';
		$dateEnd   = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($dateStart));

    	$monthInfo = [
			'prev_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			],
			'this_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime($thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime($thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime($thisYear . '-' . $thisMonth . '-01'))
			],
			'next_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			]
		];

		if (strtotime($thisYear . '-' . $thisMonth . '-01') == strtotime(date('Y-n-01'))) {
			$monthInfo['next_month'] = null;
		}
    	$trxs = Transaction::whereHas('transaction_products', function($q) use ($id_user_hair_stylist) {
    		$q->where('id_user_hair_stylist', $id_user_hair_stylist);
    	})
    	->whereBetween('transaction_date', [$dateStart, $dateEnd])
    	->with([
    		'user',
    		'transaction_products.product.photos',
    		'transaction_products' => function($q) {
    			$q->where('type', 'Product');
    		}
    	])
    	->get();

    	$histories = [];
    	foreach ($trxs as $trx) {
	        $products = [];
	        $subtotalProduct = 0;
	        $trxProduct = $trx->transaction_products;
	        foreach ($trxProduct as $product){
	        	$productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
	            $products[] = [
	                'id_product' => $product['id_product'],
	                'product_name' => $product['product']['product_name'],
					'qty' => $product['transaction_product_qty'],
					'price' => $product['transaction_product_price'],
					'subtotal' => $product['transaction_product_subtotal'],
					'photo' => $productPhoto
	            ];
	            $subtotalProduct += abs($product['transaction_product_subtotal']);
	        }
    		$histories[] = [
    			'transaction_receipt_number' => $trx['transaction_receipt_number'],
    			'transaction_date' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($trx['transaction_date'])), 'j F Y'),
    			'name' => $trx['user']['name'],
    			'product' => $products,
    		];
    	}
    	
    	$res = [
    		'month' => $monthInfo,
    		'histories' => $histories
    	];

    	return MyHelper::checkGet($res);
    }
}
