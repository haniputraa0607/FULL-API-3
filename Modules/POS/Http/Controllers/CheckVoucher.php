<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Http\Models\DealsUser;
use App\Http\Models\Deals;
use App\Http\Models\DealsOutlet;
use App\Http\Models\Outlet;
use App\Lib\MyHelper;

class CheckVoucher
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
	/* CHECK */
	static function check($post=[])
	{
        // declare
        $fail['status'] = "fail";

        // kalo input manwal
        if (isset($post['code'])) {
            $validQr = Self::newValidateQr('code', $post['code'], $post['store_code']);
        }

        if (isset($post['qrcode'])) {
            $validQr = Self::newValidateQr('qr', $post['qrcode'], $post['store_code']);
        }
// dd($validQr);
        // validate qr code
        if ($validQr) {

            //belum di invalidate
            if($validQr[0]['id_outlet'] == null){

                // validate store
                if ($validStore = Self::validateStore($post['store_code'], $validQr[0]['deal_voucher']['id_deals'])) {
                    
                    $get = Self::returnVoucher($validQr, $post['store_code']);
    
                    return response()->json(MyHelper::checkGet($get));
                }else{
                    $fail['messages'] = "The voucher cannot be redeem in this store.";
                }
            }else{
                // validate store voucher
                if ($validStore = Self::cekStore($validQr[0], $post['store_code'])) {
                    $get = Self::returnVoucher($validQr, $post['store_code']);
    
                    return response()->json(MyHelper::checkGet($get));            
                }else{
                    $fail['messages'] = "This voucher is not available at this outlet.";
                }
            }

        }
        else {
            $fail['messages'] = "QR Code isn't valid.";
        }

        return response()->json($fail);
    }

    /* VALIDATE QR */
    /*static function validateQr($qrcode) 
    {
        
        // $var = 'https://chart.googleapis.com/chart?chl='.MyHelper::encryptQRCode($qrcode."-".time()).'&chs=250x250&cht=qr&chld=H%7C0';
        // $var = 'https://chart.googleapis.com/chart?chl='.MyHelper::encryptQRCode($deals->id_deals_user.MyHelper::createRandomPIN(6)).'&chs=250x250&cht=qr&chld=H%7C0';
        // print_r($var); exit();
        $dec = MyHelper::decryptQRCode($qrcode);

        if (stristr($dec, "-")) {
            $idDealsUser = explode("-", $dec);

            if (isset($idDealsUser[0])) {
                $dealsUser = DealsUser::where('id_deals_user', $idDealsUser[0])
                                        ->whereNull('used_at')
                                        ->with(['dealVoucher', 'dealVoucher.deal' ])->get()->toArray();

                if ($dealsUser) {
                    return $dealsUser;
                }
            }
        }

        return false;
    }*/

    /* VALIDATE QR */
    static function newValidateQr($type, $qrcode, $storecode) 
    {
    	
    	// $var = 'https://chart.googleapis.com/chart?chl='.MyHelper::encryptQRCode($deals->id_deals_user.MyHelper::createRandomPIN(6)).'&chs=250x250&cht=qr&chld=H%7C0';
    	// print_r($var); exit();

        if ($type == "qr") {
    	   $qrcode = MyHelper::decryptQRCode($qrcode); 
        }

		$dealsUser = DealsUser::where('voucher_hash_code', $qrcode)
								->whereNull('used_at')
								->with(['dealVoucher', 'dealVoucher.deal'])->get()->toArray();
								
								
								
		

		if ($dealsUser) {
			return $dealsUser;
		}
    	
        return false;
    }

    /* CHECK DEALS */
    static function validateStore($store_code, $idDeals) 
    {
    	$valid  = DealsOutlet::with(['outlet' => function($q) use ($store_code){
    	    $q->where('outlet_code', $store_code);
    	}])->where('id_deals', $idDeals)->get()->toArray();

    	if ($valid) {
    		$outlet = array_filter(array_column($valid, 'outlet'));

    		if ($outlet) {
    			return true;
    		}
    	}

    	return false;
    }
    
    /* CHECK OUTLET VOUCHER */
    static function cekStore($dealsUser, $outlet_code) 
    {
        $outlet = Outlet::where('outlet_code', $outlet_code)->first();
        if($outlet){
            if ($dealsUser['id_outlet'] == $outlet->id_outlet) {
                return true;
            }
        }

    	return false;
    }

    /* RETURN */
    static function returnVoucher($deals, $storecode) {

    	if ($deals[0]['deal_voucher']['deal']['deals_promo_id_type'] == "nominal") {
			$type  = "voucher";
    	}
    	else {
			$type  = "promo";
    	}
        $value = $deals[0]['deal_voucher']['deal']['deals_promo_id'];

    	$voucher = [
    		'voucher' => [
				'code'  => $deals[0]['deal_voucher']['voucher_code'],
				'value' => $value,
				'type'  => $type
    		]
    	];
    	
    	$outlet = Outlet::where('outlet_code', $storecode)->first();
								
								if($outlet){
								    // foreach($dealsUser as $del){
                            		    $dealsUser = DealsUser::where('id_deals_user', $deals[0]['id_deals_user'])
                            								->update(['used_at' => date('Y-m-d H:i:s'), 'id_outlet' => $outlet['id_outlet']]);
                            // 		}
								}
    	

    	return $voucher;
    }

	
}
