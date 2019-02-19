<?php
namespace App\Lib;

use Image;
use File;
use DB;
use App\Http\Models\Notification;
use App\Http\Models\Store;
use App\Http\Models\User;
use App\Http\Models\Transaksi;
use App\Http\Models\ProductVariant;
use App\Http\Models\LogPoint;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\Lib\MyHelper;

class Midtrans {

    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    static function bearer() {
        return 'Basic ' . base64_encode(env('MIDTRANS_SANDBOX_BEARER'));
    }
    
    static function token($receipt, $grandTotal, $user=null, $shipping=null, $product=null) {
        $url    = env('MIDTRANS_SANDBOX');

        $transaction_details = array(
            'order_id'      => $receipt,
            'gross_amount'  => $grandTotal
        );

        $dataMidtrans = array(
            'transaction_details' => $transaction_details,
        );

        if (!is_null($user)) {
            $dataMidtrans['customer_details'] = $user;
        }

        if (!is_null($shipping)) {
            $dataMidtrans['shipping_address'] = $shipping;
        }

        if (!is_null($product)) {
            $dataMidtrans['item_details'] = $product;
        }

        $token = MyHelper::post($url, Self::bearer(), $dataMidtrans);

        return $token;
    }

    static function expire($order_id)
    {
        $url    = env('BASE_MIDTRANS_SANDBOX').'/v2/'.$order_id.'/expire';
        $status = MyHelper::post($url, Self::bearer(), ['data' => 'expired']);

        return $status;
    }

    static function checkStatus($orderId) {
        $url = 'https://api.sandbox.midtrans.com/v2/'.$orderId.'/status';
        
        $status = MyHelper::get($url, Self::bearer());

        return $status;
    }
}
?>
