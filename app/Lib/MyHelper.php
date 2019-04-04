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

class MyHelper{
	private static $config = array(
						'digitdepan' => 7,
						'digitbelakang' => 5,
						'keyutama' => 'kf8jaJKH8a3',
						'secret_iv' => 'PKs84j9kkk63',
						'ciphermode' => 'AES-256-CBC'
					);
	
	public static function  checkGet($data){
			if($data && !empty($data)) return ['status' => 'success', 'result' => $data];
			else if(empty($data)) return ['status' => 'fail', 'messages' => ['empty']];
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
		$config = static::$config;
		if(!$value){return false;}
		$skey = self::getkey();
		$depan = substr($skey, 0, $config['digitdepan']);
		$belakang = substr($skey, -$config['digitbelakang'], $config['digitbelakang']);
		$text = serialize($value);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $skey, $text, MCRYPT_MODE_ECB, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decryptkhusus($value) {
		$config = static::$config;
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, $config['digitdepan'], $jumlah-$config['digitdepan']-$config['digitbelakang']);
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
		$config = static::$config;
		if(!$value){return false;}
		$skey = self::getkey();
		$depan = substr($skey, 0, $config['digitdepan']);
		$belakang = substr($skey, -$config['digitbelakang'], $config['digitbelakang']);
		$ivlen = openssl_cipher_iv_length($config['ciphermode']);
		$iv = substr(hash('sha256', $config['secret_iv']), 0, $ivlen);
		$crypttext = openssl_encrypt($value, $config['ciphermode'], $skey, 0, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decryptkhususnew($value) {
		$config = static::$config;
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, $config['digitdepan'], $jumlah-$config['digitdepan']-$config['digitbelakang']);
		$crypttext = self::safe_b64decode($value);
		$ivlen = openssl_cipher_iv_length($config['ciphermode']);
		$iv = substr(hash('sha256', $config['secret_iv']), 0, $ivlen);
		$decrypttext = openssl_decrypt($crypttext, $config['ciphermode'], $skey, 0, $iv);
		return trim($decrypttext);
	}
	
	// terbaru, cuma nambah serialize + unserialize sih biar support array
	public static function encrypt2019($value) {
		$config = static::$config;
		if(!$value){return false;}
		// biar support array
		$text = serialize($value);
		$skey = self::getkey();
		$depan = substr($skey, 0, $config['digitdepan']);
		$belakang = substr($skey, -$config['digitbelakang'], $config['digitbelakang']);
		$ivlen = openssl_cipher_iv_length($config['ciphermode']);
		$iv = substr(hash('sha256', $config['secret_iv']), 0, $ivlen);
		$crypttext = openssl_encrypt($text, $config['ciphermode'], $skey, 0, $iv);
		return trim($depan . self::safe_b64encode($crypttext) . $belakang);
	}

	public static function decrypt2019($value) {
		$config = static::$config;
		if(!$value){return false;}
		$skey = self::parsekey($value);
		$jumlah = strlen($value);
		$value = substr($value, $config['digitdepan'], $jumlah-$config['digitdepan']-$config['digitbelakang']);
		$crypttext = self::safe_b64decode($value);
		$ivlen = openssl_cipher_iv_length($config['ciphermode']);
		$iv = substr(hash('sha256', $config['secret_iv']), 0, $ivlen);
		$decrypttext = openssl_decrypt($crypttext, $config['ciphermode'], $skey, 0, $iv);
		// dikembalikan ke format array sewaktu return
		return unserialize(trim($decrypttext));
	}

	public static function  createRandomPIN($digit, $mode = null) {
			if($mode != null)
			{
				if($mode == "angka")
				{
					$chars = "1234567890";
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
				$chars = "346789ABCDEFGHJKMNPQRSTUVWXY";
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

	public static function createrandom($digit, $custom = null) {
		$chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789";
		if($custom != null){
			if($custom == 'Angka')
				$chars = "0123456789";
			if($custom == 'Besar Angka')
				$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";
			if($custom == 'Kecil Angka')
				$chars = "abcdefghjkmnpqrstuvwxyz123456789";
			if($custom == 'Kecil')
				$chars = "abcdefghjkmnpqrstuvwxyz";
			if($custom == 'Besar')
				$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ";
		}
		$i = 0;
		$generatedstring = '';

		while ($i < $digit) {
			$num = rand() % strlen($chars);
			$tmp = substr($chars, $num, 1);
			$generatedstring = $generatedstring . $tmp;
			$i++;
			// supaya char yg sudah tergenerate tidak akan dipakai lagi
			$chars = str_replace($tmp, "", $chars);
		}

		return $generatedstring;
	}

	public static function getkey() {
		$config = static::$config;
		$depan = self::createrandom($config['digitdepan']);
		$belakang = self::createrandom($config['digitbelakang']);
		$skey = $depan . $config['keyutama'] . $belakang;
		return $skey;
	}

	public static function parsekey($value) {
		$config = static::$config;
		$depan = substr($value, 0, $config['digitdepan']);
		$belakang = substr($value, -$config['digitbelakang'], $config['digitbelakang']);
		$skey = $depan . $config['keyutama'] . $belakang;
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

	public static function uploadPhoto($foto, $path, $resize=1000, $name=null) {
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
			
			$img    = Image::make($decoded);
			
			$width  = $img->width();
			$height = $img->height();


			if($width > 1000){
					$img->resize(1000, null, function ($constraint) {
							$constraint->aspectRatio();
							$constraint->upsize();
					});
			} 
			
			$img->resize($resize, null, function ($constraint) {
				$constraint->aspectRatio();
			});
			
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

	public static function uploadPhotoStrict($foto, $path, $width=1000, $height=1000, $name=null, $forceextension=null) {
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

		return $result;
	}

	public static function deletePhoto($path) {
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

	public static function get($url, $bearer=null, $header=null){
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
			return json_decode($response->getBody(), true);
		}
		catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
						$response = $e->getResponse()->getBody()->getContents();
						return json_decode($response, true);
				}
				else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
			catch(Exception $e){
				return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
			}
		}
	}

	public static function post($url, $bearer=null, $post, $form_type=0, $header=null){
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
			return json_decode($response->getBody(), true);
		}catch (\GuzzleHttp\Exception\RequestException $e) {
			try{
				if($e->getResponse()){
					$response = $e->getResponse()->getBody()->getContents();
					return json_decode($response, true);
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
			
			if(!$createRuleParent){
				DB::rollBack();
				return ['status' => 'fail'];
			}
			foreach ($ruleParent as $i => $row) {
				$condition['id_'.$type.'_rule_parent'] = $createRuleParent['id_'.$type.'_rule_parent']; 
				$condition[$type.'_rule_subject'] = $row['subject']; 
			
				if($row['subject'] == 'all_user'){
					$condition[$type.'_rule_operator'] = "";
				}elseif(in_array($row['subject'], $operatorexception)){
					$condition[$type.'_rule_operator'] = '=';
				} else {
					$condition[$type.'_rule_operator'] = $row['operator'];
				}

				if($row['subject'] == 'all_user'){
					$condition[$type.'_rule_param'] = "";
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
		
		if($insert){
			return ['status' => 'success', 'data' =>  $data_rule]; 
		}else{
			DB::rollBack();
			return ['status' => 'fail'];
		}
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

	public static function dateFormatInd($date){
		$bulan = ['','Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

		return date('d', strtotime($date)).' '.$bulan[date('n', strtotime($date))].' '.date('Y', strtotime($date)).' '.date('H:i', strtotime($date));
	}
}
?>
