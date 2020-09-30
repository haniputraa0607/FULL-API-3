<?php
namespace App\Lib;

use App\Http\Models\LogTopup;
use App\Http\Models\Setting;
use Image;
use File;
use DB;
use Modules\Disburse\Entities\LogIRIS;
use Storage;
use App\Http\Models\Notification;
use App\Http\Models\Store;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\ProductVariant;
use App\Http\Models\LogPoint;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\UserNotification;
use App\Http\Models\AutocrmRule;
use App\Http\Models\AutocrmRuleParent;
use App\Http\Models\CampaignRule;
use App\Http\Models\CampaignRuleParent;
use App\Http\Models\PromotionRule;
use App\Http\Models\PromotionRuleParent;
use App\Http\Models\InboxGlobalRule;
use App\Http\Models\InboxGlobalRuleParent;
use App\Http\Models\LogTopupManual;
use App\Http\Models\LogApiSms;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\PointInjection\Entities\PointInjectionRule;
use Modules\PointInjection\Entities\PointInjectionRuleParent;

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
use Illuminate\Support\Facades\Crypt;

class MyHelper{
	public static function  checkGet($data, $message = null){
			if($data && !empty($data)) return ['status' => 'success', 'result' => $data];
			else if(empty($data)) {
				if($message == null){
					$message = 'empty';
				}
				return ['status' => 'fail', 'messages' => [$message]];
			}
			else return ['status' => 'fail', 'messages' => ['failed to retrieve data']];
	}

	// $messages = false ---> return cuma id
	// $messages = true ---> return seluruh data
	public static function  checkCreate($data, $returnAll = false){
			if($data) return ['status' => 'success', 'result' => $data];
			else return ['status' => 'fail', 'result' => ['failed to insert data.']];
	}

	public static function  checkUpdate($status){
			if($status) return ['status' => 'success'];
			else return ['status' => 'fail','messages' => ['failed to update data']];
	}

	public static function  checkDelete($status){
			if($status) return ['status' => 'success'];
			else return ['status' => 'fail', 'messages' => ['failed to delete data']];
	}

	public static function safe_b64encode($string) {
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='),array('-','_',''),$data);
		return $data;
	}

	public static function safe_b64decode($string)	{
		$data = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
		  $data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	public static function encryptQRCode($string) {
		$string = base64_encode($string);
		$string = str_replace(array('+','/','='),array('-','_',''),$string);
		$string = str_replace(array('A','a','0'),array('$','#','@'),$string);
		return $string;
	}

	public static function decryptQRCode($string)	{
		$string = str_replace(array('$','#','@'),array('A','a','0'),$string);
		$string = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($string) % 4;
		if ($mod4) {
		  $string .= substr('====', $mod4);
		}
		return base64_decode($string);
	}

	public static function checkStore($store_code){
			if($store_code) {
				$check = Store::where('store_code','=',$store_code)->get()->toArray();
				if($check) {
					return ['status' => 'success', 'result' => $check[0]];
				}
				else return ['status' => 'fail', 'messages' => ['Store not found.']];
			}
			else return ['status' => 'fail', 'messages' => ['Store not found.']];
	}

	public static function checkCustomer($phone){
			if($phone) {
				$check = Users::where('phone','=',$phone)->get()->toArray();
				if($check){
					return ['status' => 'success', 'result' => $check[0]];
				}
				else return ['status' => 'fail', 'messages' => ['Customer not found.']];
			}
			else return ['status' => 'fail', 'messages' => ['Customer not found.']];
	}

	public static function topProducts($phone){
			if($phone) {
				$check = Users::where('phone','=',$phone)->get()->toArray();
				if($check){
					$top_products = Transaksi::select('ProductVariant.plu_id',
														'Product.menu_name',
														'Product.group',
														DB::raw('SUM(TransaksiProduct.qty) AS qty_total'),
														DB::raw('SUM(TransaksiProduct.harga_total) AS spending_total'),
														DB::raw('SUM(if(TransaksiProduct.whipped_cream is null, 1, 0)) AS whipped_normal'),
														DB::raw('SUM(if(TransaksiProduct.whipped_cream = "less", 1, 0)) AS whipped_less'),
														DB::raw('SUM(if(TransaksiProduct.whipped_cream = "no", 1, 0)) AS whipped_no'),
														DB::raw('SUM(if(TransaksiProduct.sugar is null, 1, 0)) AS sugar_normal'),
														DB::raw('SUM(if(TransaksiProduct.sugar = "less", 1, 0)) AS sugar_less'),
														DB::raw('SUM(if(TransaksiProduct.sugar = "no", 1, 0)) AS sugar_no'),
														DB::raw('SUM(if(TransaksiProduct.ice is null, 1, 0)) AS ice_normal'),
														DB::raw('SUM(if(TransaksiProduct.ice = "less", 1, 0)) AS ice_less'),
														DB::raw('SUM(if(TransaksiProduct.ice = "no", 1, 0)) AS ice_no')
										)
										->join('TransaksiProduct','TransaksiProduct.id_transaksi','=','Transaksi.id_transaksi')
										->join('ProductPrice','TransaksiProduct.id_product_price','=','ProductPrice.id_product_price')
										->join('ProductVariant','ProductPrice.id_product_variant','=','ProductVariant.id_product_variant')
										->join('Product','Product.id_product','=','ProductVariant.id_product')
										->where('id_user','=',$check[0]['id_user'])
										->where('Transaksi.payment_status','=','success')
										->groupBy('Product.id_product')
										->orderBy('TransaksiProduct.harga_total','desc')
										->get()
										->toArray();
					if($top_products){
						return ['status' => 'success', 'result' => $top_products];
					} else {
						return ['status' => 'empty', 'messages' => []];
					}
				}
				else return ['status' => 'fail', 'messages' => ['Customer not found.']];
			}
			else return ['status' => 'fail', 'messages' => ['Customer not found.']];
	}

	public static function checkProduct($store_code, $plu_id){
			if($store_code) {
				$check = Store::where('store_code','=',$store_code)->get()->toArray();
				if($check){
					$produk = ProductVariant::join('ProductPrice','ProductVariant.id_product_variant','=','ProductPrice.id_product_variant')
											->join('Product','Product.id_product','=','ProductVariant.id_product')
											->where('ProductVariant.plu_id','=',$plu_id)
											->get()
											->toArray();
					if($produk){
						return ['status' => 'success', 'result' => $produk[0]];
					}
					else return ['status' => 'fail', 'messages' => ['Product not found.']];
				}
				else return ['status' => 'fail', 'messages' => ['Store not found.']];
			}
			else return ['status' => 'fail', 'messages' => ['Store not found.']];
	}

	public static function checkTransaction($store_code, $id_transaction, $receipt_number){
	if($store_code) {
		$check = Store::where('store_code','=',$store_code)->get()->toArray();
		if($check){
			$transaction = Transaksi::where('Transaksi.id_transaksi','=',$id_transaction)
									->where('Transaksi.receipt_number','=',$receipt_number)
									->get()
									->toArray();
			if($transaction){
				return ['status' => 'success', 'result' => $transaction[0]];
			}
			else return ['status' => 'fail', 'messages' => ['Product not found.']];
		}
		else return ['status' => 'fail', 'messages' => ['Store not found.']];
	}
	else return ['status' => 'fail', 'messages' => ['Store not found.']];
	}

	public static function  passwordkey($id_user){
			$key = md5("esemestester".$id_user."644", true);
			return $key;
	}

	public static function encryptkhusus($value) {
		if(!$value){return false;}
		$skey = self::getkey();
		$depan = substr($skey, 0, config('configs.ENC_DD'));
		$belakang = substr($skey, -config('configs.ENC_DB'), config('configs.ENC_DB'));
		$text = serialize($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $skey, $text, MCRYPT_MODE_ECB, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decryptkhusus($value) {
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, config('configs.ENC_DD'), $jumlah-config('configs.ENC_DD')-config('configs.ENC_DB'));
		$crypttext = self::safe_b64decode($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $skey, $crypttext, MCRYPT_MODE_ECB, $iv);
		return unserialize(trim($decrypttext));
	}

	// $skey wajib 16 char
	public static function encryptkhususpassword($value, $skey) {
		$keybaru = substr(hash('sha256', $skey), 0, 16);
		if(!$value){return false;}
		$text = serialize($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $keybaru, $text, MCRYPT_MODE_ECB, $iv);
		return trim(self::safe_b64encode($crypttext));
	}

	// $skey wajib 16 char
	public static function decryptkhususpassword($value, $skey) {
		$keybaru = substr(hash('sha256', $skey), 0, 16);
		if(!$value){return false;}
		$crypttext = self::safe_b64decode($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $keybaru, $crypttext, MCRYPT_MODE_ECB, $iv);
		return unserialize(trim($decrypttext));
	}

	public static function encryptkhususnew($value) {
		if(!$value){return false;}
		$skey = self::getkey();
		$depan = substr($skey, 0, config('configs.ENC_DD'));
		$belakang = substr($skey, -config('configs.ENC_DB'), config('configs.ENC_DB'));
		$ivlen = openssl_cipher_iv_length(config('configs.ENC_CM'));
		$iv = substr(hash('sha256', config('configs.ENC_SI')), 0, $ivlen);
		$crypttext = openssl_encrypt($value, config('configs.ENC_CM'), $skey, 0, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decryptkhususnew($value) {
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, config('configs.ENC_DD'), $jumlah-config('configs.ENC_DD')-config('configs.ENC_DB'));
		$crypttext = self::safe_b64decode($value);
		$ivlen = openssl_cipher_iv_length(config('configs.ENC_CM'));
		$iv = substr(hash('sha256', config('configs.ENC_SI')), 0, $ivlen);
		$decrypttext = openssl_decrypt($crypttext, config('configs.ENC_CM'), $skey, 0, $iv);
		return trim($decrypttext);
	}

	// terbaru, cuma nambah serialize + unserialize sih biar support array
	public static function encrypt2019($value) {
		if(!$value){return false;}
		// biar support array
		$text = serialize($value);
		$skey = self::getkey();
		$depan = substr($skey, 0, config('configs.ENC_DD'));
		$belakang = substr($skey, -config('configs.ENC_DB'), config('configs.ENC_DB'));
		$ivlen = openssl_cipher_iv_length(config('configs.ENC_CM'));
		$iv = substr(hash('sha256', config('configs.ENC_SI')), 0, $ivlen);
		$crypttext = openssl_encrypt($text, config('configs.ENC_CM'), $skey, 0, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decrypt2019($value) {
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, config('configs.ENC_DD'), $jumlah-config('configs.ENC_DD')-config('configs.ENC_DB'));
		$crypttext = self::safe_b64decode($value);
		$ivlen = openssl_cipher_iv_length(config('configs.ENC_CM'));
		$iv = substr(hash('sha256', config('configs.ENC_SI')), 0, $ivlen);
		$decrypttext = openssl_decrypt($crypttext, config('configs.ENC_CM'), $skey, 0, $iv);
		// dikembalikan ke format array sewaktu return
		return unserialize(trim($decrypttext));
	}

	public static function  createRandomPIN($digit, $mode = null) {
			if($mode != null)
			{
				if($mode == "angka")
				{
					$chars = "123456789";
				}
				elseif($mode == "huruf")
				{
					$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				}
				elseif($mode == "kecil")
				{
					$chars = "346789abcdefghjkmnpqrstuvwxy";
				}
			} else {
				$chars = "36789BCDEFGHJKMNPQRSTUVWXY";
			}

			srand((double)microtime()*1000000);
			$i = 0;
			$pin = '';

			while ($i < $digit) {
				$num = rand() % strlen($chars);
				$tmp = substr($chars, $num, 1);
				$pin = $pin . $tmp;
				$i++;
			}
			return $pin;
	}

	public static function encPIN ($pin)
	{
		$firstRand 	= self::createrandom(env('ENC_FIRST_PIN', 4), null, '12356789');
		$lastRand 	= self::createrandom(env('ENC_LAST_PIN', 3), null, '12356789');

		return implode('', [$firstRand, $pin, $lastRand]);
	}

	public static function  getIPAddress() {
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
				$ipAddress = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
			}

			return $ipAddress;
	}

	public static function  getUserAgent() {
			return $_SERVER['HTTP_USER_AGENT'];
	}

	public static function createrandom($digit, $custom = null, $chars = null) {
		if ($chars == null) {
			$chars = "abcdefghjkmnpqrstuvwxyzBCDEFGHJKLMNPQRSTUVWXYZ12356789";
		}
		if($custom != null){
			if($custom == 'Angka')
				$chars = "0123456789";
			if($custom == 'Besar Angka')
				$chars = "BCDEFGHJKLMNPQRSTUVWXYZ12356789";
			if($custom == 'Kecil Angka')
				$chars = "abcdefghjkmnpqrstuvwxyz123456789";
			if($custom == 'Kecil')
				$chars = "abcdefghjkmnpqrstuvwxyz";
			if($custom == 'Besar')
				$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ";
			if ($custom == 'PromoCode')
                $chars = "ABCDEFGHJKLMNPQRTUVWXY23456789";
		}
		$i = 0;
		$generatedstring = '';
		$tmp = '';

		while ($i < $digit) {
			$charsbaru = str_replace($tmp, "", $chars);
			$num = rand() % strlen($charsbaru);
			$tmp = substr($charsbaru, $num, 1);
			$generatedstring = $generatedstring . $tmp;
			$i++;
		}

		return $generatedstring;
	}

	public static function encSlug ($id)
	{
		// create random char awal 1-9
		$randfirst = self::createrandom(1, null, '123456789');
		// bikin switch case untuk char 1-9
		switch($randfirst) {
		  case 1: $firstRand  = self::createrandom(3, null, '123456789');
		      $lastRand   = self::createrandom(4, null, '123456789');
		      break;
		  case 2: $firstRand  = self::createrandom(2, null, '123456789');
		      $lastRand   = self::createrandom(3, null, '123456789');
		      break;
		  case 3: $firstRand  = self::createrandom(4, null, '123456789');
		      $lastRand   = self::createrandom(4, null, '123456789');
		      break;
		  case 4: $firstRand  = self::createrandom(1, null, '123456789');
		      $lastRand   = self::createrandom(4, null, '123456789');
		      break;
		  case 5: $firstRand  = self::createrandom(5, null, '123456789');
		      $lastRand   = self::createrandom(1, null, '123456789');
		      break;
		  case 6: $firstRand  = self::createrandom(3, null, '123456789');
		      $lastRand   = self::createrandom(3, null, '123456789');
		      break;
		  case 7: $firstRand  = self::createrandom(2, null, '123456789');
		      $lastRand   = self::createrandom(4, null, '123456789');
		      break;
		  case 8: $firstRand  = self::createrandom(3, null, '123456789');
		      $lastRand   = self::createrandom(2, null, '123456789');
		      break;
		  case 9: $firstRand  = self::createrandom(2, null, '123456789');
		      $lastRand   = self::createrandom(2, null, '123456789');
		      break;
		}
		// $firstRand   = self::createrandom(env('ENC_FIRST_SLUG', 4), null, '123456789');
		// $lastRand  = self::createrandom(env('ENC_LAST_SLUG', 3), null, '123456789');
		return $randfirst . implode('', [$firstRand, $id, $lastRand]);
	}
	public static function decSlug ($id)
	{
		// ambil char pertama
		$randfirst = substr($id, 0, 1);
		// hilangkan char pertama
		$id = substr($id, 1);
		// bikin switch case untuk char 1-9
		switch($randfirst) {
		  case 1: $firstString = substr($id, 3);
		      $string = substr($firstString, 0, -4);
		      break;
		  case 2: $firstString = substr($id, 2);
		      $string = substr($firstString, 0, -3);
		      break;
		  case 3: $firstString = substr($id, 4);
		      $string = substr($firstString, 0, -4);
		      break;
		  case 4: $firstString = substr($id, 1);
		      $string = substr($firstString, 0, -4);
		      break;
		  case 5: $firstString = substr($id, 5);
		      $string = substr($firstString, 0, -1);
		      break;
		  case 6: $firstString = substr($id, 3);
		      $string = substr($firstString, 0, -3);
		      break;
		  case 7: $firstString = substr($id, 2);
		      $string = substr($firstString, 0, -4);
		      break;
		  case 8: $firstString = substr($id, 3);
		      $string = substr($firstString, 0, -2);
		      break;
		  case 9: $firstString = substr($id, 2);
		      $string = substr($firstString, 0, -2);
		      break;
		}
		return $string;
	}

	public static function getkey() {
		$depan = self::createrandom(config('configs.ENC_DD'));
		$belakang = self::createrandom(config('configs.ENC_DB'));
		$skey = $depan . config('configs.ENC_FK') . $belakang;
		return $skey;
	}

	public static function parsekey($value) {
		$depan = substr($value, 0, config('configs.ENC_DD'));
		$belakang = substr($value, -config('configs.ENC_DB'), config('configs.ENC_DB'));
		$skey = $depan . config('configs.ENC_FK') . $belakang;
		return $skey;
	}

	public static function throwError($e){
		$error = $e->getFile().' line '.$e->getLine();
		$error = explode('\\', $error);
		$error = end($error);
		return ['status' => 'failed with exception', 'exception' => get_class($e),'error' => $error ,'message' => $e->getMessage()];
	}

	public static function checkExtensionImageBase64($imgdata){
		 $f = finfo_open();
		 $imagetype = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);

		 if(empty($imagetype)) return '.jpg';
		 switch($imagetype)
		 {
				case 'image/bmp': return '.bmp';
				case 'image/cis-cod': return '.cod';
				case 'image/gif': return '.gif';
				case 'image/ief': return '.ief';
				case 'image/jpeg': return '.jpg';
				case 'image/pipeg': return '.jfif';
				case 'image/tiff': return '.tif';
				case 'image/x-cmu-raster': return '.ras';
				case 'image/x-cmx': return '.cmx';
				case 'image/x-icon': return '.ico';
				case 'image/x-portable-anymap': return '.pnm';
				case 'image/x-portable-bitmap': return '.pbm';
				case 'image/x-portable-graymap': return '.pgm';
				case 'image/x-portable-pixmap': return '.ppm';
				case 'image/x-rgb': return '.rgb';
				case 'image/x-xbitmap': return '.xbm';
				case 'image/x-xpixmap': return '.xpm';
				case 'image/x-xwindowdump': return '.xwd';
				case 'image/png': return '.png';
				case 'image/x-jps': return '.jps';
				case 'image/x-freehand': return '.fh';
				default: return false;
		 }
	}

	public static function checkMime2Ext($value) {
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
		];

		$file_info = finfo_buffer(finfo_open(), base64_decode($value), FILEINFO_MIME_TYPE);

        return isset($mime_map[$file_info]) === true ? $mime_map[$file_info] : false;
	}

	public static function uploadPhoto($foto, $path, $resize=800, $name=null) {
			// kalo ada foto
			$decoded = base64_decode($foto);

			if (!file_exists($path)) {
			    mkdir($path, 0777, true);
			}

			// cek extension
			$ext = MyHelper::checkExtensionImageBase64($decoded);

			// set picture name
			if($name != null)
				$pictName = $name.$ext;
			else
				$pictName = mt_rand(0, 1000).''.time().''.$ext;

			// path
			$upload = $path.$pictName;

			$img    = Image::make($decoded);

			$width  = $img->width();
			$height = $img->height();

			// resize hanya height nya krn foto sekarang berdiri
			if($height > 800){
					$img->resize(null, 800, function ($constraint) {
							$constraint->aspectRatio();
							$constraint->upsize();
					});
			}
			// if($width > 1000){
			// 		$img->resize(1000, null, function ($constraint) {
			// 				$constraint->aspectRatio();
			// 				$constraint->upsize();
			// 		});
			// }

			// ga usah di resize kalau ga perlu
			if($resize){
				$img->resize($resize, null, function ($constraint) {
					$constraint->aspectRatio();
				});
			}

			if(env('STORAGE')){
				$resource = $img->stream()->detach();

				$save = Storage::disk(env('STORAGE'))->put($upload, $resource, 'public');
				if ($save) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}else{
				if ($img->save($upload)) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}


			return $result;
	}

	public static function uploadPhotoQuality($foto, $path, $resize=800, $quality, $name=null) {
			// kalo ada foto
			$decoded = base64_decode($foto);

			// cek extension
			$ext = MyHelper::checkExtensionImageBase64($decoded);

			// set picture name
			if($name != null)
				$pictName = $name.$ext;
			else
				$pictName = mt_rand(0, 1000).''.time().''.$ext;

			// path
			$upload = $path.$pictName;

			$img    = Image::make($decoded)->encode('jpg', $quality);

			$width  = $img->width();
			$height = $img->height();

			// resize hanya height nya krn foto sekarang berdiri
			if($height > 800){
					$img->resize(null, 800, function ($constraint) {
							$constraint->aspectRatio();
							$constraint->upsize();
					});
			}
			// if($width > 1000){
			// 		$img->resize(1000, null, function ($constraint) {
			// 				$constraint->aspectRatio();
			// 				$constraint->upsize();
			// 		});
			// }

			$img->resize($resize, null, function ($constraint) {
				$constraint->aspectRatio();
			});

			if(env('STORAGE')){
				$resource = $img->stream()->detach();

				$save = Storage::disk(env('STORAGE'))->put($upload, $resource, 'public');
				if ($save) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}else{
				if ($img->save($upload)) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}


			return $result;
	}

	public static function cekImageNews($type, $foto) {
			// kalo ada foto
			$decoded = base64_decode($foto);

			$img     = Image::make($decoded);

			// cek resolusi
			$width  = $img->width();
			$height = $img->height();

			switch ($type) {
				case 'square':
					$perbandingan = $width / $height;

					if ($width < 300 || $height < 300) {
						$result = [
							'status'   => 'fail',
							'messages' => ['photo width & height minimum 300 (square)']
						];
					}
					else {
						if ($perbandingan != 1) {
							if ($height > 500) {
								$result = [
									'status'   => 'success',
									'messages' => "notSquare",
									'height'   => 500
								];
							}
							else {
								$result = [
									'status'   => 'success',
									'messages' => "notSquare",
									'height'    => $height
								];
							}
						}
						else {
							if ($height > 500) {
								$result = [
									'status' => 'success',
									'width'  => 500,
									'height' => $height
								];
							}
							else {
								$result = [
									'status' => 'success',
									'width'  => $width
								];
							}
						}
					}

					break;

				case 'rectangle':
					if ($width < 600) {
						$result = [
							'status'   => 'fail',
							'messages' => ['photo width minimum 600']
						];
					}
					else {
						$result = [
							'status' => 'success',
							'width'  => $width
						];
					}
					break;

				default:
					$result = [
						'status' => 'fail'
					];
					break;
			}

			return $result;
	}

	public static function uploadPhotoStrict($foto, $path, $width=800, $height=800, $name=null, $forceextension=null) {
		// kalo ada foto1
		$decoded = base64_decode($foto);
		if($forceextension != null)
			$ext = $forceextension;
		else
			$ext = MyHelper::checkExtensionImageBase64($decoded);
		// set picture name
		if($name != null)
			$pictName = $name.$ext;
		else
			$pictName = mt_rand(0, 1000).''.time().''.$ext;

		// path
		$upload = $path.$pictName;

		if($ext=='.gif'){
			if(env('STORAGE')){
				$resource = $decoded;

				$save = Storage::disk(env('STORAGE'))->put($upload, $resource, 'public');
				if ($save) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}else{
				if (!file_exists($path)) {
					mkdir($path, 666, true);
				}
				if (file_put_contents($upload, $decoded)) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}
		}else{
			$img = Image::make($decoded);
			$imgwidth = $img->width();
			$imgheight = $img->height();

			/* if($width > 1000){
					$img->resize(1000, null, function ($constraint) {
							$constraint->aspectRatio();
							$constraint->upsize();
					});
			} */

			if($imgwidth < $imgheight){
				//potrait
				if($imgwidth < $width){
					$img->resize($width, null, function ($constraint) {
						$constraint->aspectRatio();
						$constraint->upsize();
					});
				}

				if($imgwidth > $width){
					$img->resize($width, null, function ($constraint) {
						$constraint->aspectRatio();
					});
				}
			} else {
				//landscape
				if($imgheight < $height){
					$img->resize(null, $height, function ($constraint) {
						$constraint->aspectRatio();
						$constraint->upsize();
					});
				}
				if($imgheight > $height){
					$img->resize(null, $height, function ($constraint) {
						$constraint->aspectRatio();
					});
				}

			}
			/* if($imgwidth < $width){
				$img->resize($width, null, function ($constraint) {
					$constraint->aspectRatio();
					$constraint->upsize();
				});
				$imgwidth = $img->width();
				$imgheight = $img->height();
			}

			if($imgwidth > $width){
				$img->resize($width, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$imgwidth = $img->width();
				$imgheight = $img->height();
			}

			if($imgheight < $height){
				$img->resize(null, $height, function ($constraint) {
					$constraint->aspectRatio();
					$constraint->upsize();
				});
			} */

			$img->crop($width, $height);

			if(env('STORAGE')){
				$resource = $img->stream()->detach();

				$save = Storage::disk(env('STORAGE'))->put($upload, $resource, 'public');
				if ($save) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}else{
				if ($img->save($upload)) {
						$result = [
							'status' => 'success',
							'path'  => $upload
						];
				}
				else {
					$result = [
						'status' => 'fail'
					];
				}
			}
		}


		return $result;
	}

	public static function uploadFile($file, $path, $ext="apk", $name=null) {
		// kalo ada file
		$decoded = base64_decode($file);

		// set picture name
		if($name != null)
			$pictName = $name.'.'.$ext;
		else
			$pictName = mt_rand(0, 1000).''.time().'.'.$ext;

		// path
		$upload = $path.$pictName;

		if(env('STORAGE')){
			$save = Storage::disk(env('STORAGE'))->put($upload, $decoded, 'public');
			if ($save) {
					$result = [
						'status' => 'success',
						'path'  => $upload
					];
			}
			else {
				$result = [
					'status' => 'fail'
				];
			}
		}else{
			$save = File::put($upload,$decoded);
			if ($save) {
					$result = [
						'status' => 'success',
						'path'  => $upload
					];
			}
			else {
				$result = [
					'status' => 'fail'
				];
			}
		}

		return $result;
	}

	public static function deletePhoto($path) {
		if(env('STORAGE')){
			if(Storage::disk(env('STORAGE'))->exists($path)) {
				if(Storage::disk(env('STORAGE'))->delete($path)){
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return true;
			}
		}else{
			if (file_exists($path)) {
				if (unlink($path)) {
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return true;
			}
		}

	}

    public static function createFile($content, $ext="json", $path, $name=null) {
        // kalo ada file
        $decoded = json_encode($content);

        // set picture name
        if($name != null)
            $pictName = $name.'.'.$ext;
        else
            $pictName = mt_rand(0, 1000).''.time().'.'.$ext;

        // path
        $upload = $path.$pictName;

        $save = Storage::disk(config('configs.STORAGE'))->put($upload, $decoded);

        if ($save) {
            $result = [
                'status' => 'success',
                'path'  => $upload
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return $result;
    }

    public static function deleteFile($path) {
        if(config('configs.STORAGE')){
            if(Storage::disk(config('configs.STORAGE'))->exists($path)) {
                if(Storage::disk(config('configs.STORAGE'))->delete($path)){
                    return true;
                }
                else {
                    return false;
                }
            }
            else {
                return true;
            }
        }else{
            if (File::delete($path)) {
                return true;
            }
            else {
                return false;
            }
        }

    }

	public static function sendNotification($id, $type){
			return true;
	}

	public static function cariOperator($phone){
		$prefix = $result = substr($phone, 0, 4);

		$telkomsel = ['0811','0812','0813','0821','0822','0823','0852','0853','0851','0813'];
		$indosat   = ['0814','0815','0816','0855','0856','0857','0858'];
		$XL        = ['0817','0818','0819','0859','0877','0878'];
		$tri       = ['0895','0896','0897','0898','0899'];
		$smart     = ['0881','0882','0883','0884','0885','0886','0887','0888','0889'];
		$ceria     = ['0828'];
		$axis      = ['0838','0831','0832','0833'];

		if(in_array($prefix, $telkomsel))
			return 'Telkomsel';
		elseif(in_array($prefix, $indosat))
			return 'Indosat';
		elseif(in_array($prefix, $XL))
			return 'XL';
		elseif(in_array($prefix, $tri))
			return 'Tri';
		elseif(in_array($prefix, $smart))
			return 'Smart';
		elseif(in_array($prefix, $ceria))
			return 'Ceria';
		elseif(in_array($prefix, $axis))
			return 'Axis';
		else
			return 'Unknown Provider';
	}

	public static function generateImage($character, $filename, $tipe) {

			switch ($tipe) {
				case 'QR':
					$QR = urlencode($character);

					$bar = 'https://chart.googleapis.com/chart?chl='.$QR.'&chs=250x250&cht=qr&chld=H%7C0';
					$url = html_entity_decode($bar);

					break;

				case 'maps':
					$maps = 'http://maps.googleapis.com/maps/api/staticmap?center='.$character.'&zoom=15&scale=false&size=200x350&maptype=roadmap&format=png&visual_refresh=true&markers=size:large%7Ccolor:0xff0000%7Clabel:toko%'.$character.'&key=AIzaSyCOHBNv3Td9_zb_7uW-AJDU6DHFYk-8e9Y';
					$url = html_entity_decode($maps);

					break;
				default:
					return false;

					break;
			}

			$ch = @curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);

			$page = curl_exec( $ch);
			curl_close($ch);

			if(file_exists($filename)) {
				return true;
			}

			$fp = fopen($filename, 'x');
			fwrite($fp, $page);
			fclose($fp);

			/*$imgRes = imagecreatefromstring(base64_decode($url));
			$imgRes = imagejpeg($imgRes, $filename, 70);

			return $imgRes;*/

			if(file_exists($filename)) {
				return true;
			} else{
				return false;
			}
	}

	public static function get($url, $bearer=null, $header=null,&$status_code=null,&$response_header=null){
		$client = new Client;

		$content = array(
			'headers' => [
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json'
			]
		);

		if (!is_null($bearer)) {
			$content['headers']['Authorization'] = $bearer;
		}

		if(!is_null($header)){
			if(is_array($header)){
				foreach($header as $key => $dataHeader){
					$content['headers'][$key] = $dataHeader;
				}
			}
		}

		try {
			$response =  $client->request('GET', $url, $content);
			$status_code = $response->getStatusCode();
			$response_header = $response->getHeaders();
			return json_decode($response->getBody(), true);
		}
		catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
						$response = $e->getResponse()->getBody()->getContents();
						$response_header = $e->getResponse()->getHeaders();
						$status_code = $e->getResponse()->getStatusCode();
						return json_decode($response, true);
				}
				else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

	public static function post($url, $bearer=null, $post, $form_type=0, $header=null,&$status_code = null,&$response_header = null){
		$client = new Client;

		$content = array(
			'headers' => [
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			]
		);

		// if form_type = 0
		if ($form_type == 0) {
			$content['json'] = (array)$post;
		}
		else {
			$content['form_params'] = $post;
		}

		// if null bearer
		if (!is_null($bearer)) {
			$content['headers']['Authorization'] = $bearer;
		}

		if(!is_null($header)){
			if(is_array($header)){
				foreach($header as $key => $dataHeader){
					$content['headers'][$key] = $dataHeader;
				}
			}
		}

		try {
			$response = $client->post($url, $content);
			$status_code = $response->getStatusCode();
			$response_header = $response->getHeaders();
			return json_decode($response->getBody(), true);
		}catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
					$response = $e->getResponse()->getBody()->getContents();
					$status_code = $e->getResponse()->getStatusCode();
					$response_header = $e->getResponse()->getHeaders();
					return json_decode($response, true);
				}
				else  return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

	public static function put($url, $bearer=null, $post, $form_type=0, $header=null,&$status_code = null,&$response_header = null){
		$client = new Client;

		$content = array(
			'headers' => [
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			]
		);

		// if form_type = 0
		if ($form_type == 0) {
			$content['json'] = (array)$post;
		}
		else {
			$content['form_params'] = $post;
		}

		// if null bearer
		if (!is_null($bearer)) {
			$content['headers']['Authorization'] = $bearer;
		}

		if(!is_null($header)){
			if(is_array($header)){
				foreach($header as $key => $dataHeader){
					$content['headers'][$key] = $dataHeader;
				}
			}
		}

		try {
			$response = $client->put($url, $content);
			$status_code = $response->getStatusCode();
			$response_header = $response->getHeaders();
			return json_decode($response->getBody(), true);
		}catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
					$response = $e->getResponse()->getBody()->getContents();
					$status_code = $e->getResponse()->getStatusCode();
					$response_header = $e->getResponse()->getHeaders();
					return json_decode($response, true);
				}
				else  return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

	public static function postWithTimeout($url, $bearer=null, $post, $form_type=0, $header=null, $timeout = 65){
		$client = new Client;

		$content = array(
			'headers' => [
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			]
		);

		// if form_type = 0
		if ($form_type == 0) {
			$content['json'] = (array)$post;
		}
		else {
			$content['form_params'] = $post;
		}

		// if null bearer
		if (!is_null($bearer)) {
			$content['headers']['Authorization'] = $bearer;
		}

		if(!is_null($header)){
			if(is_array($header)){
				foreach($header as $key => $dataHeader){
					$content['headers'][$key] = $dataHeader;
				}
			}
		}
		$content['timeout']=$timeout;

		try {
			$response = $client->post($url, $content);
			// return plain response if json_decode fail because response is plain text
			$return = json_decode($response->getBody()->getContents(), true)?:$response->getBody()->__toString();
			return [
				'status_code' => $response->getStatusCode(),
				'response' => $return
			];
		}catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
					$response = $e->getResponse()->getBody()->getContents();
					$return = json_decode($response, true);
					return [
						'status_code' => $e->getResponse()->getStatusCode(),
						'response' => $return
					];
				}
				else  return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

	public static function getWithTimeout($url, $bearer=null, $post = [], $header=null, $timeout = 65){
		$client = new Client;

		$content = array(
			'headers' => [
				'Accept'        => 'application/json'
			]
		);

		// if null bearer
		if (!is_null($bearer)) {
			$content['headers']['Authorization'] = $bearer;
		}

		if(!is_null($header)){
			if(is_array($header)){
				foreach($header as $key => $dataHeader){
					$content['headers'][$key] = $dataHeader;
				}
			}
		}

		if ($post) {
			$params = http_build_query($post);
			if (strpos($url,'?')) {
				$url .= '&' . $params;
			} else {
				$url .= '?' . $params;
			}
		}
		$content['timeout']=$timeout;

		try {
			$response = $client->get($url, $content);
			// return plain response if json_decode fail because response is plain text
			$return = json_decode($response->getBody()->getContents(), true)?:$response->getBody()->__toString();
			return [
				'status_code' => $response->getStatusCode(),
				'response' => $return
			];
		}catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
					$response = $e->getResponse()->getBody()->getContents();
					$return = json_decode($response, true);
					return [
						'status_code' => $e->getResponse()->getStatusCode(),
						'response' => $return
					];
				}
				else  return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

    public static function getBearerToken() {
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
	}

	public static function curl($url, $cookies=0, $post=0, $referrer=0, $XMLRequest=0, $header=1, $proxyport=0) {
		global $_GET;

		$url = html_entity_decode($url);

		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		if ($cookies) {
			if (is_array($cookies)) {
				$cookies = CookiesToStr($cookies);
			}
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.1) Presto/2.12.388 Version/12.16');

		if($XMLRequest) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
		}

		curl_setopt($ch, CURLOPT_REFERER, $referrer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($post){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 60);
		$page = curl_exec( $ch);
		curl_close($ch);
		// return $page;
		if(stristr($page, "HTTP/1.1 5") || stristr($page, "HTTP/1.0 5")) {
			if(stristr($page, "HTTP/1.1 509 Bandwidth Error") && stristr($page, "dropbox")) {
				html_error("Link dropbox yg anda masukkan tidak dapat didownload, silakan cek linknya.<BR/><BR/><BR/>Link yg anda inputkan = <BR/><BR/><BR/><a href='".$link."' target=_blank>".substr($link, 0, 50)."</a>");
			}

			$filehostingdomain = preg_replace("/www\./", "", parse_url($url, PHP_URL_HOST));

			if($filehostingdomain == "ryushare.com") {
				html_error("Server Ryushare sedang error dari sananya, Silakan cek / buka sendiri linknya.<BR/><BR/><BR/><a href='". $link ."' target='_blank'>". substr($link, 0, 50) ." ... [KLIK DISINI]</a>");
			}

            if($filehostingdomain == 'uploadboy.com') {
                $this->html_error_key("limit", $page, 'You downloaded 15 file in last 1 day(s)', $link, 'You downloaded 15 file in last 1 day(s)');
            }

			$isi['link'] = $url;
			$isi['page'] = $page;
			$isi['tipe'] = "http 50x";

			//$this->sendemailauto($isi);

			html_error("Server ".$filehostingdomain." sedang error, silakan coba lagi nanti / besok untuk link ini.<BR/><BR/><BR/>Pesan error aslinya = <BR/><BR/><BR/>HTTP/1.1 502 Bad Gateway");
		}

		return $page;
	}

	public static function urlTransaction($url, $method, $data, $content) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => array(
			"content-type: ".$content,
			"accept: application/json",
			"key: 39555583a3816088cb1e32ab2dcda012"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		return json_decode($response);
	}

	public static function sendPush ($tokens, $subject, $messages, $image=null, $dataOptional=[]) {

        $optionBuiler = new OptionsBuilder();
        $optionBuiler->setTimeToLive(60*200);
        $optionBuiler->setContentAvailable(true);
        $optionBuiler->setPriority("high");

        /* SEMENTARA PAKE INI UNTUK TESTING MAS JENGGOT APPS */
        $notificationBuilder = new PayloadNotificationBuilder($subject);
        $notificationBuilder->setBody($messages)
                            ->setSound('default')
                            ->setClickAction($dataOptional['type']);
        // kalo ada image
        // if (!is_null($image) || $image != null) {
        //     $notificationBuilder->setIcon($image);
        // }

        /* INI YANG PERMINTAANNYA DARI ANDOID BIAR BISA DICUTOM KATANYA */
   		/* YANG ATASNYA NNTI DI COMMENT AJA, YANG INI DIAKTIFKAN */
        // $notificationBuilder = new PayloadNotificationBuilder("");

        // data - data yang dikirimkan
        $dataBuilder = new PayloadDataBuilder();
        // ini untuk yang push notif di android yang katanya ceritanya panjang
        // data push masuk dalam subject
        $dataOptional['title']             = $subject;
        $dataOptional['body']              = $messages;
        // $dataOptional['content_available'] = true;
        // $dataOptional['priority']          = "high";

        $dataBuilder->addData($dataOptional);

        // build semua
        $option       = $optionBuiler->build();
        $notification = $notificationBuilder->build();
        $data         = $dataBuilder->build();

        // print_r($option);
        // print_r($notification);
        // print_r($data);
        // exit();

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

        // var_dump($downstreamResponse); exit();

        // print_r($downstreamResponse);
        $success = $downstreamResponse->numberSuccess();
        $fail    = $downstreamResponse->numberFailure();

        if ($fail != 0) {
            // return Array (key:token, value:errror) - in production you should remove from your database the tokens present in this array
            $error = $downstreamResponse->tokensWithError();
            // print_r($error);
        }

        // $downstreamResponse->numberModification();;

        //return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        //return Array (key : oldToken, value : new token - you must change the token in your database )
        $downstreamResponse->tokensToModify();

        //return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        $result = [
            'success' => $success,
            'fail'    => $fail
        ];

        return $result;
    }

	// based on field Users Table
    public static function searchDeviceToken($type, $value) {
        $result = [];

        $devUser = User::leftjoin('user_devices', 'user_devices.id_user', '=', 'users.id')
            ->select('user_devices.id_device_user', 'users.id', 'user_devices.device_token', 'user_devices.device_id', 'users.phone');

        if (is_array($value)) {
            $devUser->whereIn('users.'.$type, $value);
        }
        else {
            $devUser->where('users.'.$type, $value);
        }

        $devUser = $devUser->get()->toArray();
        if (!empty($devUser)) {
            // if phone
            if ($type == "phone") {
                if (is_array($value)) {
                    $phone = implode(",", $value);
                }
                else {
                    $phone = $value;
                }

                $result['phone'] = $phone;
            }

            $token             = array_pluck($devUser, 'device_token');
            $id_user           = array_pluck($devUser, 'id');
            $result['token']   = $token;
            $result['id_user'] = $devUser[0]['id'];
            $result['mphone']  = array_pluck($devUser, 'phone');
        }

        return $result;
    }

    public static function curlData($url, $data) {
		$options = array('http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query($data)
		));

		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);

		return $result;
    }

    public static function logCount($phone, $key) {
    	$user = User::where('phone', $phone)->first();

    	if (empty($user)) {
    		return [
    			'status'	=> 'fail',
    			'messages'	=> 'User Not Found'
    		];
    	}

    	if ($key == 'point') {
			$min  = LogPoint::where('id_user', $user->id)->where('source', 'voucher')->sum('point');
			$plus = LogPoint::where('id_user', $user->id)->where('source', 'transaction')->sum('point');
			$field = 'points';
		} else {
			$min  = LogBalance::where('id_user', $user->id)->where('source', 'transaction')->sum('point');
			$plus = LogBalance::where('id_user', $user->id)->where('source', 'topup')->orWhere('source', 'cashback')->sum('point');
			$field = 'balance';
    	}

    	$total = $plus-$min;

    	$user->$field = $total;
    	$user->save();

    	if (!$user) {
    		return [
    			'status'	=> 'fail',
    			'messages'	=> 'Something Went Wrong'
    		];
    	}

    	$result = [
    		'status' => 'success',
    		'total'	 => $plus-$min
    	];

    	return $result;

	}

	public static function parseYoutube($url) {
        if (($cek = strpos($url, "youtu.be")) !== FALSE) {
            $parse = strpos($url, '/', $cek+1);
            $key = substr($url, $parse+1);
        } else{
            if (($parse = strpos($url, "v=")) !== FALSE) {
                if(($index = strpos($url, '&', $parse)) !== FALSE){
					$key = substr($url, $parse+2, $index - 2 - $parse );
                }else{
                    $key = substr($url, $parse+2);
                }
            }
		}
		if(isset($key)){
			$result = [
				'status' => 'success',
				'data'	 => 'https://youtube.com/watch?v='.$key
			];
		}else{
			$result = [
				'status' => 'failed',
			];
		}
		return $result;
	}

	public static function manualPayment($data, $type) {
		if ($type == 'transaction') {
			$insert = TransactionPaymentManual::create($data);
		} elseif ('logtopup') {
			$insert = LogTopupManual::create($data);
		} else {
			$insert = DealsPaymentManual::create($data);
		}

		if (!$insert) {
			DB::rollback();
			return 'fail';
		} else {
			return 'success';
		}
	}

	public static function addUserNotification($id_user, $type){
		if(!in_array($type, ['inbox', 'voucher', 'history'])){
			return $result = [
						'status' => 'fail',
						'messages'	 => 'Type must be one of inbox / voucher / history.'
					];
		}
		$userNotification = UserNotification::where('id_user', $id_user)->first();
		if(empty($userNotification)){
			$data['id_user'] = $id_user;
			$data[$type] 	 = 1;
			$createNotif = UserNotification::create($data);
			if($createNotif){
				return $result = [
						'status' => 'success'
					];
			}else{
				return $result = [
					'status' => 'fail',
					'messages'	 => 'Failed create user notification.'
				];
			}
		}else{
			$userNotification = $userNotification->toArray();
			$newNotif = $userNotification[$type] + 1;
			$updateNotif = UserNotification::where('id_user', $id_user)->update([$type => $newNotif]);
			if($updateNotif){
				return $result = [
					'status' => 'success'
				];
			}else{
				return $result = [
					'status' => 'fail',
					'messages'	 => 'Failed update user notification.'
				];
			}
		}
	}

	public static function insertCondition($type, $id, $conditions){
		if($type == 'autocrm'){
			$deleteRuleParent = AutocrmRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = AutocrmRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = AutocrmRuleParent::where('id_'.$type, $id)->delete();
			}
		}
		elseif($type == 'campaign'){
			$deleteRuleParent = CampaignRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = CampaignRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = CampaignRuleParent::where('id_'.$type, $id)->delete();
			}
		}
		elseif($type == 'promotion'){
			$deleteRuleParent = PromotionRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = PromotionRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = PromotionRuleParent::where('id_'.$type, $id)->delete();
			}
		}
		elseif($type == 'inbox_global'){
			$deleteRuleParent = InboxGlobalRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = InboxGlobalRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = InboxGlobalRuleParent::where('id_'.$type, $id)->delete();
			}
		}
		elseif ($type == 'point_injection') {
			$deleteRuleParent = PointInjectionRuleParent::where('id_' . $type, $id)->get();
			if (count($deleteRuleParent) > 0) {
				foreach ($deleteRuleParent as $key => $value) {
					$delete = PointInjectionRule::where('id_' . $type . '_rule_parent', $value['id_' . $type . '_rule_parent'])->delete();
				}
				$deleteRuleParent = PointInjectionRuleParent::where('id_' . $type, $id)->delete();
			}
		}

		$operatorexception = ['gender',
							'birthday_month',
							'city_name',
							'city_postal_code',
							'province_name',
							'provider',
							'birthday_month',
							'phone_verified',
							'email_verified',
							'email_unsubscribed',
							'level',
							'device',
							'is_suspended',
							'trx_type',
							'trx_shipment_courier',
							'trx_payment_type',
							'trx_payment_status',
							'trx_outlet',
							'trx_outlet_not',
							'trx_product',
							'trx_product_not',
							'trx_product_tag',
							'trx_product_tag_not',
							'birthday_today',
							'register_today'
							];

		$data_rule = array();

		foreach ($conditions as $key => $ruleParent) {
			$dataRuleParent['id_'.$type] = $id;
			$dataRuleParent[$type.'_rule'] = $ruleParent['rule'];
			$dataRuleParent[$type.'_rule_next'] = $ruleParent['rule_next'];

			unset($ruleParent['rule']);
			unset($ruleParent['rule_next']);

			if($type == 'autocrm'){
				$createRuleParent = AutocrmRuleParent::create($dataRuleParent);
			}
			elseif($type == 'campaign'){
				$createRuleParent = CampaignRuleParent::create($dataRuleParent);
			}
			elseif($type == 'promotion'){
				$createRuleParent = PromotionRuleParent::create($dataRuleParent);
			}
			elseif($type == 'inbox_global'){
				$createRuleParent = InboxGlobalRuleParent::create($dataRuleParent);
			}
			elseif ($type == 'point_injection') {
				$createRuleParent = PointInjectionRuleParent::create($dataRuleParent);
			}

			if(!$createRuleParent){
				DB::rollBack();
				return ['status' => 'fail'];
			}
			foreach ($ruleParent as $i => $row) {
				$condition['id_'.$type.'_rule_parent'] = $createRuleParent['id_'.$type.'_rule_parent'];
				$condition[$type.'_rule_subject'] = $row['subject'];

				if($row['subject'] == 'all_user'){
					$condition[$type.'_rule_operator'] = "";
				}elseif($row['subject'] == 'trx_product' || $row['subject'] == 'trx_outlet'){
                    $condition[$type.'_rule_operator'] = $row['operatorSpecialCondition'];
                }elseif(in_array($row['subject'], $operatorexception)){
					$condition[$type.'_rule_operator'] = '=';
				} else {
					$condition[$type.'_rule_operator'] = $row['operator'];
				}

                $condition[$type.'_rule_param_id'] = NULL;
				if($row['subject'] == 'all_user'){
					$condition[$type.'_rule_param'] = "";
				}elseif($row['subject'] == 'trx_product' || $row['subject'] == 'trx_outlet'){
                    $condition[$type.'_rule_param'] = $row['parameterSpecialCondition'];
                    $condition[$type.'_rule_param_id'] = $row['id'];
                }elseif(in_array($row['subject'], $operatorexception)){
					$condition[$type.'_rule_param'] = $row['operator'];
				} else {
					$condition[$type.'_rule_param'] = $row['parameter'];
				}

				$condition['created_at'] =  date('Y-m-d H:i:s');
				$condition['updated_at'] =  date('Y-m-d H:i:s');

				array_push($data_rule, $condition);
			}
		}

		if($type == 'autocrm'){
			$insert = AutocrmRule::insert($data_rule);
		}
		elseif($type == 'campaign'){
			$insert = CampaignRule::insert($data_rule);
		}
		elseif($type == 'promotion'){
			$insert = PromotionRule::insert($data_rule);
		}
		elseif($type == 'inbox_global'){
			$insert = InboxGlobalRule::insert($data_rule);
		}
		elseif ($type == 'point_injection') {
			$insert = PointInjectionRule::insert($data_rule);
		}

		if($insert){
			return ['status' => 'success', 'data' =>  $data_rule];
		}else{
			DB::rollBack();
			return ['status' => 'fail'];
		}
	}
	public static function logApiSMS($arr){
    	if(!is_array($arr)){return false;}
		$trace=array_slice((new \Exception)->getTrace(),1,6);
		$log=[
		    'request_body'=>null,
		    'request_url'=>null,
		    'response'=>null,
		    'phone'=>null
		];
		$log=array_merge($log,$arr);
		array_walk($log, function(&$data){if(is_array($data)){$data=json_encode($data);}});
		LogApiSms::create($log);
    }
	public static function cut_str($str, $left, $right) {
		$str = substr ( stristr ( $str, $left ), strlen ( $left ) );
		$leftLen = strlen ( stristr ( $str, $right ) );
		$leftLen = $leftLen ? - ($leftLen) : strlen ( $str );
		$str = substr ( $str, 0, $leftLen );
		return $str;
	}

	public static function createQR($timestamp, $phone, $useragent = null){
		$arrtime = str_split($timestamp);

		$arrphone = str_split($phone);

		$qr[] = rand(0,9);
		$qr[] = rand(0,9);
		$qr[] = (int)$arrtime[0];
		$qr[] = (int)$arrtime[1];
		$qr[] = (int)$arrtime[2];
		$qr[] = rand(0,9);
		$qr[] = rand(0,9);
		$qr[] = rand(0,9);
		$qr[] = (int)$arrtime[3];
		$qr[] = (int)$arrtime[4];
		$qr[] = (int)$arrtime[5];
		$qr[] = (int)$arrtime[6];
		$qr[] = rand(0,9);
		$qr[] = (int)$arrtime[7];
		$qr[] = (int)$arrtime[8];
		$qr[] = (int)$arrtime[9];
		$qr[] = rand(0,9);
		$qr[] = (int)$arrphone[0];
		$qr[] = (int)$arrphone[1];
		$qr[] = rand(0,9);
		$qr[] = rand(0,9);
		$qr[] = (int)$arrphone[2];
		$qr[] = (int)$arrphone[3];
		$qr[] = (int)$arrphone[4];
		$qr[] = rand(0,9);
		$qr[] = rand(0,9);
		$qr[] = (int)$arrphone[5];
		$qr[] = (int)$arrphone[6];
		$qr[] = (int)$arrphone[7];
		$qr[] = rand(0,9);

		for($i = 8; $i < count($arrphone); $i++){
			$qr[] = $arrphone[$i];
		}

		for($i = 0; $i < 5; $i++){
			$qr[] = rand(0,9);
		}

		if($useragent == "Android"){
			$qr[] = 2;
		}elseif($useragent == "iOS"){
			$qr[] = 1;
		}else{
			$qr[] = 0;
		}

		$qr = implode('', $qr);

		return $qr;
	}

	public static function readQR($qrcode){
		$useragent = substr($qrcode, -1);
		if($useragent == 1){
			$device = 'IOS';
		}elseif($useragent == 2){
			$device = "Android";
		}else{
			$device = null;
		}

		//remove 1 digit terakhir
		$qrcode = substr($qrcode, 0, -1);

		//remove 5 digit terakhir
		$qrcode = substr($qrcode, 0, -5);

		//remove 2 digit pertama
		$qrcode = substr($qrcode, 2);

		$qrcode = str_split($qrcode);

		$arrtimestamp[] = $qrcode[0];
		$arrtimestamp[] = $qrcode[1];
		$arrtimestamp[] = $qrcode[2];
		$arrtimestamp[] = $qrcode[6];
		$arrtimestamp[] = $qrcode[7];
		$arrtimestamp[] = $qrcode[8];
		$arrtimestamp[] = $qrcode[9];
		$arrtimestamp[] = $qrcode[11];
		$arrtimestamp[] = $qrcode[12];
		$arrtimestamp[] = $qrcode[13];

		$arrphone[] = $qrcode[15];
		$arrphone[] = $qrcode[16];
		$arrphone[] = $qrcode[19];
		$arrphone[] = $qrcode[20];
		$arrphone[] = $qrcode[21];
		$arrphone[] = $qrcode[24];
		$arrphone[] = $qrcode[25];
		$arrphone[] = $qrcode[26];

		for($i = 28; $i < count($qrcode); $i++){
			$arrphone[] = $qrcode[$i];
		}

		$result['timestamp'] = implode('', $arrtimestamp);
		$result['phone'] = implode('', $arrphone);
		$result['device'] = $device;

		return $result;
	}

	public static function dateFormatInd($date,$full=true,$clock=true,$hari=false){
		if($hari){
			$days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jum\'at','Sabtu'];
		}
		if($full){
			$bulan = ['','Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
		}else{
			$bulan = ['','Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
		}

		return trim(($hari?$days[date('w', strtotime($date))].', ':'').date('d', strtotime($date)).' '.$bulan[date('n', strtotime($date))].' '.date('Y', strtotime($date)).($clock?date(' H:i', strtotime($date)):''));
	}

	public static function indonesian_date_v2($timestamp = '', $date_format = 'l, d F Y H:i') {
		if (trim ($timestamp) == '')
		{
				$timestamp = time ();
		}
		elseif (!ctype_digit ($timestamp))
		{
			$timestamp = strtotime ($timestamp);
		}
		# remove S (st,nd,rd,th) there are no such things in indonesia :p
		$date_format = preg_replace ("/S/", "", $date_format);
		$pattern = array (
			'/Mon[^day]/','/Tue[^sday]/','/Wed[^nesday]/','/Thu[^rsday]/',
			'/Fri[^day]/','/Sat[^urday]/','/Sun[^day]/','/Monday/','/Tuesday/',
			'/Wednesday/','/Thursday/','/Friday/','/Saturday/','/Sunday/',
			'/Jan[^uary]/','/Feb[^ruary]/','/Mar[^ch]/','/Apr[^il]/','/May/',
			'/Jun[^e]/','/Jul[^y]/','/Aug[^ust]/','/Sep[^tember]/','/Oct[^ober]/',
			'/Nov[^ember]/','/Dec[^ember]/','/January/','/February/','/March/',
			'/April/','/June/','/July/','/August/','/September/','/October/',
			'/November/','/December/',
		);
		$replace = array ( 'Sen','Sel','Rab','Kam','Jum','Sab','Min',
			'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu',
			'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des',
			'Januari','Februari','Maret','April','Juni','Juli','Agustus','September',
			'Oktober','November','Desember',
		);
		$date = date ($date_format, $timestamp);
		$date = preg_replace ($pattern, $replace, $date);
		$date = "{$date}";
		return $date;
	}

	public static function isJoined($query, $table){
        $joins = $query->getQuery()->joins;
        if($joins == null) {
            return false;
        }

        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }

        return false;
    }
    //replace text dengan array
    public static function simpleReplace($string,$replacers,$symbol='%'){
    	foreach ($replacers as $key => $to) {
    		$string=str_replace($symbol.$key.$symbol, $to,$string);
    	}
    	return $string;
	}

	public static function postCURLWithBearer($url, $data, $bearer) {
		$uri = config('url.app_api_url');
        $ch = curl_init($uri.$url);
        $data = json_encode($data);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: '. $bearer,
					'X-Forwarded-For: ' . MyHelper::get_client_ip(),
					'REMOTE_ADDR: ' . MyHelper::get_client_ip(),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	}

	public static function get_client_ip() {
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}

    public static function count_distance($lat1, $lon1, $lat2, $lon2, $unit = 'K', $convert = false) {
        $theta = $lon1 - $lon2;
        $lat1=floatval($lat1);
        $lat2=floatval($lat2);
        $lon1=floatval($lon1);
        $lon2=floatval($lon2);
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);

        if ($unit == "K") {
            $hasil = ($miles * 1.609344);
        } else if ($unit == "N") {
            $hasil = ($miles * 0.8684);
        } else {
            $hasil = $miles;
        }

        if($convert){
        	return number_format((float)$hasil, 2, '.', '').' km';
        }else{
        	return $hasil;
        }
    }
    /**
     * Get max min latitude based on radius. Hanya perhitngan kasar,
     * @param  Float  $lat    user latitude
     * @param  Float  $lon    user longitude
     * @param  Float  $radius    radius in meter
     * @return Array    ['latitude'=>['max'=>xxx,'min'=>xxx],'longitude'=>['max'=>xxx,'min'=>xxx]]
     */
    public static function getRadius($lat, $lon, $radius) {
        $distance = (float) $radius / 111319.5;
        $result = [
        	'latitude' => [
        		'min' => $lat - $distance,
        		'max' => $lat + $distance
        	],
        	'longitude' => [
        		'min' => $lon - $distance,
        		'max' => $lon + $distance
        	],
        ];
        return $result;
    }
    /**
     * Group some array based on a column
     * @param  array        $array        data
     * @param  string       $col          column as key for grouping
     * @param  function     $modifier     function to modify key value
     * @return array                      grouped array
     */
    public static function groupIt($array,$col,$col_modifier=null,$key_modifier=null) {
        $newArray=[];
        foreach ($array as $old => $value) {
            if($col_modifier!==null){
                $key = $col_modifier($value[$col],$value,$old);
            }else{
            	if(is_array($value)){
	                $key = $value[$col];
            	}else{
	                $key = $value->$col;
            	}
            }
            $newArray[$key][]=$value;
        }
        if($key_modifier!==null){
        	$arrNew=[];
            foreach ($newArray as $key => $value) {
                $new_key=$key_modifier($key,$value);
                $arrNew[$new_key]=$value;
            }
            $newArray = $arrNew;
        }
        return $newArray;
    }

	/**
	* Convert csv string or file to PHP array
	* @param  String  $string    String of csv content or path to csv file
	* @param  boolean $isPath    Set to true if $string is csv's path, leave it false otherwise
	* @param  String  $delimiter Set the delimiter of csv column
	* @return Array          php array of the csv
	*/
	public static function csvToArray($string,$isPath=false,$delimiter=','){
		if($isPath){
			try{
				$string=file_get_contents($string);
			}catch(Exception $e){
				return array();
			}
		}
		$first=explode(PHP_EOL, $string);
		$second=array_map(function($x) use ($delimiter){
			return explode($delimiter,str_replace("\r", '', $x));
		},$first);
		return $second;
	}

	/**
	 * Return int/float based on requested type
	 * @param  numeric 		$number Number to convert, can be numeric string, integer or anything
	 * @param  string 		$type   'int' , 'float' , 'double' or 'custom' for custom number format
	 * @param  $custom 		parameter suplied for customize number
	 * @return float/int    converted number
	 */
	public static function requestNumber($number,$type='int',$custom=[]) {
		if($type === '_CURRENCY'){$type = env('CURRENCY_FORMAT');}
		elseif($type === '_POINT'){$type = env('POINT_FORMAT');}
		switch ($type) {
			case 'int':
				return (int) $number;
				break;

			case 'float':
				return (float) $number;
				break;

			case 'double':
				return (double) $number;
				break;

			case 'rupiah':
				return 'Rp'.number_format($number,0,',','.');
				break;

			case 'dollar':
				return '$'.number_format($number,2,'.',',');
				break;

			case 'thousand_id':
				return number_format($number,0,',','.');
				break;

			case 'thousand_sg':
				return number_format($number,2,'.',',');
				break;

			case 'custom':
				return number_format($number,...$custom);
				break;

			case 'short':
				if ($number < 1000) {
				    // Anything less than a million
				    $n_format = number_format($number,0);
				} elseif ($number < 1000000) {
				    // Anything less than a billion
				    $n_format = number_format($number / 1000, 0) . 'K';
				} elseif ($number < 1000000000) {
				    // Anything less than a billion
				    $n_format = number_format($number / 1000000, 0) . 'M';
				} else {
				    // At least a billion
				    $n_format = number_format($number / 1000000000, 0) . 'B';
				}
				return $n_format;
				break;

			default:
				return $number;
				break;
		}
	}

	/**
	 * Create slug for resource based on id and created_at parameter
	 * @param  String $id         id of resource
	 * @param  String $created_at created_at value of item
	 * @return String             slug result
	 */
	public static function createSlug($id,$created_at){
		$combined = $id.'.'.$created_at;
		$result = self::encrypt2019($combined);
		return $result;
	}

	/**
	 * get id and created at from slug
	 * @param  String $slug given slug
	 * @return Array       id and created at or empty array if invalid slug
	 */
	public static function explodeSlug($slug) {
		$decripted = self::decrypt2019($slug);
		$result = explode('.',$decripted);
		if(!$result || (count($result) == 1 && empty($result[0]))){
			return [];
		}
		return $result;
	}

    public static function phoneCheckFormat($phone) {
        $phoneSetting = Setting::where('key', 'phone_setting')->first()->value_text;
        $phoneSetting = json_decode($phoneSetting);
        $codePhone = config('countrycode.country_code.'.env('COUNTRY_CODE').'.code');
        $min = $phoneSetting->min_length_number;
        $max = $phoneSetting->max_length_number;

        if(substr($phone, 0, 1) == '0'){
            $phone = $codePhone.substr($phone,1);
        }elseif(substr($phone, 0, 2) == $codePhone){
            $phone = $codePhone.substr($phone,2);
        }elseif(substr($phone, 0, 3) == '+'.$codePhone){
            $phone = $codePhone.substr($phone,3);
        }else{
            return [
                'status' => 'fail',
                'messages' => [$phoneSetting->message_failed]
            ];
        }

        if(strlen($phone) >= $min && strlen($phone) <= $max){
            return [
                'status' => 'success',
                'phone' => $phone
            ];
        }else{
            return [
                'status' => 'fail',
                'messages' => [$phoneSetting->message_failed]
            ];
        }
    }

    public static function connectIris($subject, $method, $url, $body, $approver = null){
        $baseUrl = env('URL_IRIS');
        //$apiKey = MyHelper::decrypt2019(env('API_KEY_IRIS'));
        $urlApi = $baseUrl.$url;
        if($approver == 1){
            $base64 = base64_encode(env('API_KEY_IRIS_APPROVER').':');
        }else{
            $base64 = base64_encode(env('API_KEY_IRIS').':');
        }

        $jsonBody = json_encode($body);

        $header = [
            'Content-Type'  => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic '.$base64
        ];
        $client = new Client([
            'headers' => $header
        ]);

        try {
            $output = $client->request($method, $urlApi, ['body' => $jsonBody]);
            $output = json_decode($output->getBody(), true);

            $dataLog= [
                'subject' => $subject,
                'request_header'=> json_encode($header),
                'request' => $jsonBody,
                'request_url' => $urlApi,
                'response' => json_encode($output)
            ];
            LogIRIS::create($dataLog);
            return ['status' => 'success', 'response' => $output];
        }catch (\GuzzleHttp\Exception\RequestException $e) {
            $dataLog= [
                'subject' => $subject,
                'request_header'=> json_encode($header),
                'request' => $jsonBody,
                'request_url' => $urlApi
            ];

            try{
                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();
                    $dataLog['response'] = $response;
                    LogIRIS::create($dataLog);
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                $dataLog['response'] = 'Check your internet connection.';
                LogIRIS::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
            catch(Exception $e){
                $dataLog['response'] = 'Check your internet connection.';
                LogIRIS::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }
    /**
     * Get setting value from setting table
     * @param string $key setting key
     * @param string $column value column [value|value_text]
     * @param string default value if setting not exist
     * @return  string/array result
     */
    public static function setting($key,$column = 'value',$default = '')
    {
    	return Setting::select($column)->where('key',$key)->pluck($column)->first()??$default;
    }

    public static function checkRuleForRequestOTP($data_user, $check = 0){
        //get setting rule for request otp
        $setting = Setting::where('key', 'otp_rule_request')->first();
        /*
          note : hold time in seconds. if the user has requested otp exceeds the
          maximum number then the user cannot make an otp request.
        */

        $holdTime = 60;//set default hold time if setting not exist
        $maxValueRequest = 10;//set default max value for request if setting not exist
        if($setting){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
            $maxValueRequest = (int)$setting->max_value_request;
        }
        $folder1 = 'otp';
        $file = $data_user[0]['id'].'.json';

        //check flag first in database
        if(isset($data_user[0]['otp_request_status']) && $data_user[0]['otp_request_status'] == 'Can Not Request'){
            return [
                'status'=>'fail',
                'otp_check'=> 1,
                'messages'=> ["OTP request has passed the limit, please contact our customer service at ".config('configs.EMAIL_ADDRESS_ADMIN')]
            ];
        }

        //check folder
        if(env('STORAGE') == 'local'){
            if(!Storage::disk(env('STORAGE'))->exists($folder1)){
                Storage::makeDirectory($folder1);
            }
        }

        if(Storage::disk(env('STORAGE'))->exists($folder1.'/'.$file)){
            $readContent = Storage::disk(env('STORAGE'))->get($folder1.'/'.$file);
            $content = json_decode($readContent);
            $currentTime = date('Y-m-d H:i:s');
            $count = $content->count_request + 1;

            $different = strtotime($content->available_request_time) - strtotime($currentTime);
            $different = (int)date('s', $different);

            if($different > $holdTime){
                $different = -1;
            }

            if(strtotime($currentTime) < strtotime($content->available_request_time)){
                return [
                    'status'=>'success',
                    'otp_check'=> 1,
                    'otp_timer' => $different
                ];
            } elseif($count > $maxValueRequest){
                $updateFlag = User::where('id', $data_user[0]['id'])->update(['otp_request_status' => 'Can Not Request']);
                MyHelper::deleteFile($folder1.'/'.$file);
                return [
                    'status'=>'fail',
                    'otp_check'=> 1,
                    'messages'=> ["OTP request has passed the limit, please contact our customer service at ".config('configs.EMAIL_ADDRESS_ADMIN')]
                ];
            } elseif($check == 0){
                $availebleTime = date('Y-m-d H:i:s',strtotime('+'.$holdTime.' seconds',strtotime(date('Y-m-d H:i:s'))));
                $contentFile = [
                    'available_request_time' => $availebleTime,
                    'count_request' => 1 + $content->count_request
                ];
                $createFile = MyHelper::createFile($contentFile, 'json', 'otp/', $data_user[0]['id']);
                return true;
            }
        }elseif($check == 0){
            $availebleTime = date('Y-m-d H:i:s',strtotime('+'.$holdTime.' seconds',strtotime(date('Y-m-d H:i:s'))));
            $contentFile = [
                'available_request_time' => $availebleTime,
                'count_request' => 1
            ];
            $createFile = MyHelper::createFile($contentFile, 'json', 'otp/', $data_user[0]['id']);
            return true;
        }
    }

    public static function checkRuleForRequestEmailVerify($data_user){
        //get setting rule for request email verify
        $setting = Setting::where('key', 'email_verify_rule_request')->first();
        /*
          note : hold time in seconds. if the user has requested email verify exceeds the
          maximum number then the user cannot make an email verify request.
        */

        $holdTime = 60;//set default hold time if setting not exist
        $maxValueRequest = 10;//set default max value for request if setting not exist
        if($setting){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
            $maxValueRequest = (int)$setting->max_value_request;
        }
        $folder1 = 'emailverify';
        $file = $data_user[0]['id'].'.json';

        //check flag first in database
        if(isset($data_user[0]['email_verify_request_status']) && $data_user[0]['email_verify_request_status'] == 'Can Not Request'){
            return [
                'status'=>'fail',
                'email_verify_check'=> 1,
                'messages'=> ["Email Verify request has passed the limit, please contact our customer service at ".config('configs.EMAIL_ADDRESS_ADMIN')]
            ];
        }

        //check folder
        if(env('STORAGE') == 'local'){
            if(!Storage::disk(env('STORAGE'))->exists($folder1)){
                Storage::makeDirectory($folder1);
            }
        }

        if(Storage::disk(env('STORAGE'))->exists($folder1.'/'.$file)){
            $readContent = Storage::disk(env('STORAGE'))->get($folder1.'/'.$file);
            $content = json_decode($readContent);
            $currentTime = date('Y-m-d H:i:s');
            $count = $content->count_request + 1;

            if(strtotime($currentTime) < strtotime($content->available_request_time)){
                return [
                    'status'=>'fail',
                    'email_verify_check'=> 1,
                    'messages'=> ["Can't request email verify, please request again after ".floor($holdTime/60)." minutes"]
                ];
            } elseif($count > $maxValueRequest){
                $updateFlag = User::where('id', $data_user[0]['id'])->update(['email_verify_request_status' => 'Can Not Request']);
                MyHelper::deleteFile($folder1.'/'.$file);
                return [
                    'status'=>'fail',
                    'email_verify_check'=> 1,
                    'messages'=> ["Email Verify request has passed the limit, please contact our customer service at ".config('configs.EMAIL_ADDRESS_ADMIN')]
                ];
            } else{
                $availebleTime = date('Y-m-d H:i:s',strtotime('+'.$holdTime.' seconds',strtotime(date('Y-m-d H:i:s'))));
                $contentFile = [
                    'available_request_time' => $availebleTime,
                    'count_request' => 1 + $content->count_request
                ];
                $createFile = MyHelper::createFile($contentFile, 'json', 'emailverify/', $data_user[0]['id']);
                return true;
            }
        }else{
            $availebleTime = date('Y-m-d H:i:s',strtotime('+'.$holdTime.' seconds',strtotime(date('Y-m-d H:i:s'))));
            $contentFile = [
                'available_request_time' => $availebleTime,
                'count_request' => 1
            ];
            $createFile = MyHelper::createFile($contentFile, 'json', 'emailverify/', $data_user[0]['id']);
            return true;
        }
    }
    /**
     * update flag transaction online (flag ini digunakan untuk menandai user pernah transaksi online atau belum (digunakan di referral))
     * @param  array/model 	$trx 	 	  	Transacction model
     * @param  string 		$status 		"pending" / "cancel" / "success"
     * @param  model 		$user   		User model or leave it empty
     * @return boolean
     */
    public static function updateFlagTransactionOnline($trx, $status = 'pending', $user = null)
    {
    	if (!$user) {
	        $user = User::where('id',$trx['id_user'])->first();
    	}
    	if ($status == 'success') {
    		if ($user['transaction_online_status'] == 'success') {
    			return true;
    		}
    		if ($user['transaction_online'] != $trx['id_transaction']) {
	    		$user->update(['transaction_online' => $trx['id_transaction'], 'transaction_online_status' => 'success']);
    			return true;
    		}
    	} elseif ($status == 'cancel') {
	        // check flag transaction_online == id_transaction
	        if($user['transaction_online'] == $trx['id_transaction']) {
	        	// find other pending transaction
	        	$id_pending_trx = Transaction::select('id_transaction')->where('id_user',$trx['id_user'])->where('transaction_payment_status','Pending')->where('id_transaction', '<>', $trx['id_transaction'])->pluck('id_transaction')->first();
	        	if ($id_pending_trx) {
	        		$user->update(['transaction_online' => $id_pending_trx, 'transaction_online_status' => 'pending']);
	        	} else {
	        		$user->update(['transaction_online' => null, 'transaction_online_status' => null]);
	        	}
	        };
	        return true;
    	} else {
    		if (!$user['transaction_online']) {
	    		$user->update(['transaction_online' => $trx['id_transaction'], 'transaction_online_status' => 'pending']);
    		}
    	}
    	return true;
	}
	
	/**
	 * Calculates the great-circle distance between two points, with
	 * the Vincenty formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 */
	public static function getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
	{
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);
	
		$lonDelta = $lonTo - $lonFrom;
		$a = pow(cos($latTo) * sin($lonDelta), 2) +
		pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
	
		$angle = atan2(sqrt($a), $b);
		return $angle * $earthRadius;
	}

	/**
	 * Update data data_outlet.json
	 * @param  array $data      data id_outlet and data to save, [['id_outlet' => xx, 'data' => 'xxxxxx'],['id_outlet' => xx, 'data' => 'xxxxxx']]
	 * @return void
	 */
	public static function updateOutletFile($data)
	{
		$filename = storage_path('data_outlet.json');
        if (is_file($filename)) {
            $filecontent = file_get_contents($filename);
            $data_outlet = json_decode($filecontent,true)?:[];
        } else {
            $data_outlet = [];
        }
        foreach ($data as $item) {
        	$data_outlet[$item['id_outlet']] = self::encrypt2019($item['data']);
        }
        try {
	        file_put_contents($filename, json_encode($data_outlet));
	    } catch (\Exception $e) {
	    	\Log::error("Failed to save data outlet. Please save this data manually in the $filename file", $data_outlet);
	    }
	}

	/**
	 * Get outlet data from data_outlet.json
	 * @param  integer $id_outlet      id_outlet, leave null for retrive all data
	 * @return void
	 */
	public static function getOutletFile($id_outlet = null)
	{
		$filename = storage_path('data_outlet.json');
        if (is_file($filename)) {
            $filecontent = file_get_contents($filename);
            $data_outlet = json_decode($filecontent,true)?:[];
        } else {
            $data_outlet = [];
        }

        if($id_outlet) {
        	$data = $data_outlet[$id_outlet] ?? null;
        	return self::decrypt2019($data);
        }

        return array_map(function ($data) {
        	return self::decrypt2019($data);
        }, $data_outlet);
	}

	/**
	 * Create cron's log
	 * @param  string Cron Name cron name
	 * @return Model           LogCron Eloquent Model
	 */
	public static function logCron($cronName)
	{
		$log = new \App\Http\Models\LogCron;
		$log->cron = $cronName;
		$log->status = 'onprocess';
		$log->start_date = date('Y-m-d H:i:s');
		$log->save();

		return $log;
	}

	public static function decryptPIN($encrypted, $phone)
	{
		$user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();
		if (!$user) {
			return false;
		}
		$challengeKey = $user->challenge_key;
		try {
			$newEncrypter = new \Illuminate\Encryption\Encrypter( $challengeKey, config( 'app.cipher' ) );
			$decrypted = $newEncrypter->decrypt( $encrypted );
			return $decrypted;
		} catch (\Exceprion $e) {
			return false;
		}
	}
}
