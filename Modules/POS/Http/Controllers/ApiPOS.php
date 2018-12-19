<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductPhoto;
use App\Http\Models\ProductModifier;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\DealsUser;
use App\Http\Models\Deal;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\SpecialMembership;
use App\Http\Models\DealsVoucher;
use App\Lib\MyHelper;
use Mailgun;

use Modules\POS\Http\Requests\reqMember;
use Modules\POS\Http\Requests\reqVoucher;
use Modules\POS\Http\Requests\voidVoucher;
use Modules\POS\Http\Requests\reqMenu;
use Modules\POS\Http\Requests\reqOutlet;
use Modules\POS\Http\Requests\reqTransaction;
use Modules\POS\Http\Requests\reqTransactionRefund;
use Modules\POS\Http\Requests\reqPreOrderDetail;

use Modules\POS\Http\Controllers\CheckVoucher;

use DB;
class ApiPOS extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->balance    = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
    }
	
    public function transactionDetail(reqPreOrderDetail $request){
		$post = $request->json()->all();
		
		$api = $this->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', $post['store_code'])->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 
		
		$check = Transaction::join('transaction_pickups','transactions.id_transaction','=','transaction_pickups.id_transaction')
							->with(['products','product_detail','vouchers','productTransaction.modifiers'])
							->where('order_id','=',$post['order_id'])
							->where('transactions.created_at','>=',date("Y-m-d")." 00:00:00")
							->where('transactions.created_at','<=',date("Y-m-d")." 23:59:59")
							->first()
							->toArray();

		if($check){
			$user = User::where('id','=',$check['id_user'])->first()->toArray();
			
			$qrCode = 'https://chart.googleapis.com/chart?chl='.$check['order_id'].'&chs=250x250&cht=qr&chld=H%7C0';
			$qrCode = html_entity_decode($qrCode);
			
			$expired = Setting::where('key', 'qrcode_expired')->first();
            if(!$expired || ($expired && $expired->value == null)){
                $expired = '10';
            }else{
                $expired = $expired->value;
            }

            $timestamp = strtotime('+'.$expired.' minutes');
            $memberUid = MyHelper::createQR($timestamp, $user['phone']);
            
			$transactions = [];
			$transactions['member_uid'] = $memberUid;
			$transactions['trx_id_behave'] = $check['transaction_receipt_number'];
			$transactions['trx_date_time'] = $check['created_at'];
			$transactions['qrcode'] = $qrCode;
			$transactions['order_id'] = $check['order_id'];
			$transactions['process_at'] = $check['pickup_type'];
			$transactions['process_date_time'] = $check['pickup_at'];
			$transactions['accepted_date_time'] = $check['receive_at'];
			$transactions['ready_date_time'] = $check['ready_at'];
			$transactions['taken_date_time'] = $check['taken_at'];
			$transactions['total'] = $check['transaction_subtotal'];
			$transactions['sevice'] = $check['transaction_service'];
			$transactions['tax'] = $check['transaction_tax'];
			$transactions['discount'] = $check['transaction_discount'];
			$transactions['grand_total'] = $check['transaction_grandtotal'];
			$transactions['payment_type'] = null;
			$transactions['payment_code'] = null;
			$transactions['payment_nominal'] = null;
			$transactions['menu'] = [];
			foreach($check['products'] as $key => $menu){
				$val = [];
				$val['plu_id'] = $menu['product_code'];
				$val['name'] = $menu['product_name'];
				$val['price'] = $menu['pivot']['transaction_product_price'];
				$val['qty'] = $menu['pivot']['transaction_product_qty'];
				$val['category'] = $menu['product_category_name'];
				$val['modifiers'] = $check['product_transaction'][$key]['modifiers'];
				
				array_push($transactions['menu'], $val);
			}
			return response()->json(['status' => 'success', 'result' => $transactions]); 
		} else {
			return response()->json(['status' => 'fail', 'messages' => ['Invalid Order ID']]); 
		}
		return response()->json(['status' => 'success', 'message' => 'API is not ready yet. Stay tuned!','result' => $post]); 
	}
	
    public function checkMember(reqMember $request){
        $post = $request->json()->all(); 
         
        $api = $this->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', $post['store_code'])->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 
 
        $qr = MyHelper::readQR($post['uid']);
		$timestamp = $qr['timestamp'];
		$phoneqr = $qr['phone'];
        if(date('Y-m-d H:i:s') < $timestamp){
            return response()->json(['status' => 'fail', 'messages' => ['Mohon refresh qrcode dan ulangi scan member']]); 
        }

        $user = User::where('phone', $phoneqr)->first(); 
        if(empty($user)){ 
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]); 
        } 
 
        $result['uid'] = $post['uid']; 
        $result['name'] = $user->name; 
 
        $voucher = DealsUser::with('dealVoucher', 'dealVoucher.deal')->where('id_user', $user->id) 
                            ->where(function ($query) use ($outlet) { 
                                $query->where('id_outlet', $outlet->id_outlet) 
                                    ->orWhereNull('id_outlet'); 
                            }) 
                            ->whereNull('used_at')->whereDate('voucher_expired_at', '>=', date("Y-m-d")) 
                            ->where('paid_status', 'Completed')->get(); 
        if(count($voucher) <= 0){ 
            $result['vouchers'] = []; 
        }else{ 
			// $arr = [];
            $voucher_name = []; 
            foreach($voucher as $index => $vou){ 
				array_push($voucher_name, ['name' => $vou->dealVoucher->deal->deals_title]);
				
                /* if($index > 0){ 
                    $voucher_name[0] = $voucher_name[0]."\n".$vou->dealVoucher->deal->deals_title; 
                }else{ 
                   $voucher_name[0] = $vou->dealVoucher->deal->deals_title; 
                }  */
            } 
			
			
			// array_push($arr, $voucher_name);
			
            $result['vouchers'] = $voucher_name; 
        } 
         
        $membership = UsersMembership::where('id_user', $user->id)->orderBy('id_log_membership', 'DESC')->first();
        if(empty($membership)){ 
            $result['customer_level'] = ""; 
            $result['promo_id'] = ""; 
        }else{ 
            $result['customer_level'] = $membership->membership_name; 
            $result['promo_id'] = $membership->benefit_promo_id; 
        } 

        $result['saldo'] = $user->balance; 
         
        return response()->json(['status' => 'success', 'result' => $result]); 
    }
	
	public function checkVoucher(reqVoucher $request){
		$post = $request->json()->all();

		$api = $this->checkApi($post['api_key'], $post['api_secret']);
		if ($api['status'] != 'success') {
		    return response()->json($api);
		}

		return CheckVoucher::check($post);
    }
    
	public function voidVoucher(voidVoucher $request){
		$post = $request->json()->all();

		$api = $this->checkApi($post['api_key'], $post['api_secret']);
		if ($api['status'] != 'success') {
		    return response()->json($api);
		}

        DB::beginTransaction();

        $voucher = DealsVoucher::with('deals_user')->where('voucher_code', $post['voucher_code'])->first();
        if(!$voucher){
            return response()->json(['status' => 'fail', 'messages' => ['Voucher tidak ditemukan']]); 
        }

        if($voucher['deals_user'][0]['used_at']){
            return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher '.$post['voucher_code'].'. Voucher sudah digunakan.']]); 
        }

        //update voucher redeem
        foreach($voucher['deals_user'] as $dealsUser){
            $dealsUser->redeemed_at = null;
            $dealsUser->voucher_hash = null;
            $dealsUser->voucher_hash_code = null;
            $dealsUser->id_outlet = null;
            $dealsUser->update();
    
            if(!$dealsUser){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher '.$post['voucher_code'].'. Segera hubungi team support']]); 
            }
        }
        
        //update count deals
        $deals = Deal::find($voucher['id_deals']);
        $deals->deals_total_redeemed = $deals->deals_total_redeemed - 1;
        $deals->update();
        if(!$deals){
            DB::rollBack();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal void voucher '.$post['voucher_code'].'. Segera hubungi team support']]); 
        }

        DB::commit();
        return response()->json(['status' => 'success', 'messages' => ['Void Voucher '.$post['voucher_code'].' telah berhasil']]); 
        
    }
	
	public function syncOutlet(reqOutlet $request){
		$outlet = $request->json('store');

        foreach ($outlet as $key => $value) {
          
            $data['outlet_name'] = $value['store_name'];
            $data['outlet_status'] = $value['store_status'];

            $save = Outlet::updateOrCreate(['outlet_code' => $value['store_code']], $data);

            if (!$save) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to sync']
                ]);
            }    
        }
        // return success
        return response()->json([
            'status' => 'success'
        ]);
	}
	
	public function syncMenu(reqMenu $request){
		$post = $request->json()->all();

        $syncDatetime = date('d F Y h:i');

		$apikey = Setting::where('key', 'api_key')->first()->value;
		$apisecret = Setting::where('key', 'api_secret')->first()->value;
		if($post['api_key'] != $apikey){
			return response()->json([
				'status'    => 'fail',
				'messages'  => ['Api key doesn\'t match.']
			]);
		}
		if($post['api_secret'] != $apisecret){
			return response()->json([
				'status'    => 'fail',
				'messages'  => ['Api secret doesn\'t match.']
			]);
		}

		$outlet = Outlet::where('outlet_code', $post['store_code'])->first();
		if($outlet){
			DB::beginTransaction();
			$countInsert = 0;
            $countUpdate = 0;
            $rejectedProduct = [];
            $updatedProduct = [];
            $insertedProduct = [];
			
			foreach($post['menu'] as $key => $menu){
                $product = Product::where('product_code', $menu['plu_id'])->first();
                // return response()->json($menu);
                // update product
				if($product){
                    // cek allow sync, jika 0 product tidak di update
                    if($product->product_allow_sync == '1'){

                        // cek name pos, jika beda product tidak di update
                        if(empty($product->product_name_pos) || $product->product_name_pos == $menu['name']){
                            $update = $product->update(['product_name_pos' => $menu['name']]);
                            if($update){
								// update modifiers 
								if(isset($menu['modifiers'])){
									if(!empty($menu['modifiers'])){
										foreach($menu['modifiers'] as $mod){
											$dataProductMod['type'] = $mod['type'];
											if(isset($mod['text']))
												$dataProductMod['text'] = $mod['text'];
											else
												$dataProductMod['text'] = null;
											
											$updateProductMod = ProductModifier::updateOrCreate([
														'id_product' => $product->id_product, 
														'code'  => $mod['code']
														], $dataProductMod);
										}
									}
								}
								
                                // update price 
                                $productPrice = ProductPrice::where('id_product', $product->id_product)->where('id_outlet', $outlet->id_outlet)->first();
                                if($productPrice){
                                    $oldPrice =  $productPrice->product_price;
                                    $oldUpdatedAt =  $productPrice->updated_at->format('Y-m-d H:i:s');
                                }else{
                                    $oldPrice = null;
                                    $oldUpdatedAt = null;
                                }
    
                                $dataProductPrice['product_price'] = (int)$menu['price'];
                                $dataProductPrice['product_status'] = $menu['status'];
                                
                                $updateProductPrice = ProductPrice::updateOrCreate([
                                                    'id_product' => $product->id_product, 
                                                    'id_outlet'  => $outlet->id_outlet
                                                    ], $dataProductPrice); 
        
                                if(!$updateProductPrice){
                                    DB::rollBack();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Something went wrong.']
                                    ]);
                                }else{

                                    //upload photo
                                    $imageUpload = [];
                                    if(isset($menu['photo'])){
                                        foreach($menu['photo'] as $photo){
                                            $image = file_get_contents($photo['url']);
                                            $img = base64_encode($image);
                                            if (!file_exists('img/product/item/')) {
                                                mkdir('img/product/item/', 0777, true);
                                            }

                                            $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);
    
                                            if (isset($upload['status']) && $upload['status'] == "success") {
                                                $orderPhoto = ProductPhoto::where('id_product', $product->id_product)->orderBy('product_photo_order', 'desc')->first();
                                                if($orderPhoto){
                                                    $orderPhoto = $orderPhoto->product_photo_order + 1;
                                                }else{
                                                    $orderPhoto = 1;
                                                }
                                                $dataPhoto['id_product'] = $product->id_product;
                                                $dataPhoto['product_photo'] = $upload['path'];
                                                $dataPhoto['product_photo_order'] = $orderPhoto;

                                                $photo = ProductPhoto::create($dataPhoto);
                                                if(!$photo){
                                                    DB::rollBack();
                                                    $result = [
                                                        'status'   => 'fail',
                                                        'messages' => ['fail upload image']
                                                    ];
        
                                                    return response()->json($result);
                                                }

                                                //add in array photo
                                                $imageUpload[] = $photo['product_photo'];  
                                            }else{
                                                DB::rollBack();
                                                $result = [
                                                    'status'   => 'fail',
                                                    'messages' => ['fail upload image']
                                                ];
    
                                                return response()->json($result);
                                            }
                                        }
                                    }

                                    $countUpdate++;
                                    
                                    // list updated product utk data log
                                    $newProductPrice = ProductPrice::where('id_product', $product->id_product)->where('id_outlet', $outlet->id_outlet)->first();
                                    $newUpdatedAt =  $newProductPrice->updated_at->format('Y-m-d H:i:s');
    
                                    $updateProd['id_product'] = $product['id_product']; 
                                    $updateProd['plu_id'] = $product['product_code']; 
                                    $updateProd['product_name'] = $product['product_name']; 
                                    $updateProd['old_price'] = $oldPrice;
                                    $updateProd['new_price'] = (int)$menu['price'];
                                    $updateProd['old_updated_at'] = $oldUpdatedAt;
                                    $updateProd['new_updated_at'] = $newUpdatedAt;
                                    if(count($imageUpload) > 0){
                                        $updateProd['new_photo'] = $imageUpload;
                                    }

                                    $updatedProduct[] = $updateProd;
                                }
                            }else{
                                DB::rollBack();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  =>  ['Something went wrong.']
                                ]);
                            }
                        }else{
                            // Add product to rejected product
                            $productPrice = ProductPrice::where('id_outlet', $outlet->id_outlet)->where('id_product', $product->id_product)->first();
                            
                            $dataBackend['plu_id'] = $product->product_code;
                            $dataBackend['name'] = $product->product_name_pos;
                            if(empty($productPrice)){
                                $dataBackend['price'] = '';
                            }else{
                                $dataBackend['price'] = number_format($productPrice->product_price,0,',','.');
                            }
    
                            $dataRaptor['plu_id'] = $menu['plu_id'];
                            $dataRaptor['name'] = $menu['name'];
                            $dataRaptor['price'] = number_format($menu['price'],0,',','.');
                            array_push($rejectedProduct, ['backend' => $dataBackend, 'raptor' => $dataRaptor]);
                        }
                    }
                }
                
                // insert product
                else{
					$create = Product::create([ 'product_code' => $menu['plu_id'], 'product_name_pos' => $menu['name'], 'product_name' => $menu['name']]);
					if($create){
                        // update price
						$dataProductPrice['product_price'] = (int)$menu['price'];
                        $dataProductPrice['product_status'] = $menu['status'];
                       
						$updateProductPrice = ProductPrice::updateOrCreate([
												'id_product' => $create->id_product, 
												'id_outlet'  => $outlet->id_outlet
											  ], $dataProductPrice); 
						
						if(!$updateProductPrice){
							DB::rollBack();
							return response()->json([
								'status'    => 'fail',
								'messages'  => ['Something went wrong.']
							]);
						}else{

                             //upload photo
                             $imageUpload = [];
                             if(isset($menu['photo'])){
                                 foreach($menu['photo'] as $photo){
                                     $image = file_get_contents($photo['url']);
                                     $img = base64_encode($image);
                                     if (!file_exists('img/product/item/')) {
                                         mkdir('img/product/item/', 0777, true);
                                     }

                                     $upload = MyHelper::uploadPhotoStrict($img, 'img/product/item/', 300, 300);

                                     if (isset($upload['status']) && $upload['status'] == "success") {
                                         $dataPhoto['id_product'] = $product->id_product;
                                         $dataPhoto['product_photo'] = $upload['path'];
                                         $dataPhoto['product_photo_order'] = 1;

                                         $photo = ProductPhoto::create($dataPhoto);
                                         if(!$photo){
                                             DB::rollBack();
                                             $result = [
                                                 'status'   => 'fail',
                                                 'messages' => ['fail upload image']
                                             ];
 
                                             return response()->json($result);
                                         }

                                         //add in array photo
                                         $imageUpload[] = $photo['product_photo']; 
                                     }else{
                                         DB::rollBack();
                                         $result = [
                                             'status'   => 'fail',
                                             'messages' => ['fail upload image']
                                         ];

                                         return response()->json($result);
                                     }
                                 }
                             }

                            $countInsert++;

                            // list new product utk data log
                            $insertProd['id_product'] = $create['id_product']; 
                            $insertProd['plu_id'] = $create['product_code']; 
                            $insertProd['product_name'] = $create['product_name']; 
                            $insertProd['price'] = (int)$menu['price'];
                            if(count($imageUpload) > 0){
                                $updateProd['new_photo'] = $imageUpload;
                            }

                            $insertedProduct[] = $insertProd;
						}
					}
				}
			}
            DB::commit();

            // send email rejected product
            if(count($rejectedProduct) > 0){

                $emailSync = Setting::where('key', 'email_sync_menu')->first();
                if(!empty($emailSync) && $emailSync->value != null){
                    $emailSync = explode(',', $emailSync->value);
                    foreach ($emailSync as $key => $to) {
    
                        $subject = 'Rejected product form menu sync raptor';
                        
                        $content['sync_datetime'] = $syncDatetime;
                        $content['outlet_code'] = $outlet->outlet_code;
                        $content['outlet_name'] = $outlet->outlet_name;
                        $content['total_rejected'] = count($rejectedProduct);
                        $content['rejected_menu'] = $rejectedProduct;
    
                        // get setting email
                        $setting = array();
                        $set = Setting::where('key', 'email_from')->first();
                        if(!empty($set)){
                            $setting['email_from'] = $set['value'];
                        }else{
                            $setting['email_from'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_sender')->first();
                        if(!empty($set)){
                            $setting['email_sender'] = $set['value'];
                        }else{
                            $setting['email_sender'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_reply_to')->first();
                        if(!empty($set)){
                            $setting['email_reply_to'] = $set['value'];
                        }else{
                            $setting['email_reply_to'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_reply_to_name')->first();
                        if(!empty($set)){
                            $setting['email_reply_to_name'] = $set['value'];
                        }else{
                            $setting['email_reply_to_name'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_cc')->first();
                        if(!empty($set)){
                            $setting['email_cc'] = $set['value'];
                        }else{
                            $setting['email_cc'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_cc_name')->first();
                        if(!empty($set)){
                            $setting['email_cc_name'] = $set['value'];
                        }else{
                            $setting['email_cc_name'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_bcc')->first();
                        if(!empty($set)){
                            $setting['email_bcc'] = $set['value'];
                        }else{
                            $setting['email_bcc'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_bcc_name')->first();
                        if(!empty($set)){
                            $setting['email_bcc_name'] = $set['value'];
                        }else{
                            $setting['email_bcc_name'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_logo')->first();
                        if(!empty($set)){
                            $setting['email_logo'] = $set['value'];
                        }else{
                            $setting['email_logo'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_logo_position')->first();
                        if(!empty($set)){
                            $setting['email_logo_position'] = $set['value'];
                        }else{
                            $setting['email_logo_position'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_copyright')->first();
                        if(!empty($set)){
                            $setting['email_copyright'] = $set['value'];
                        }else{
                            $setting['email_copyright'] = null;
                        }
                        
                        $set = Setting::where('key', 'email_disclaimer')->first();
                        if(!empty($set)){
                            $setting['email_disclaimer'] = $set['value'];
                        }else{
                            $setting['email_disclaimer'] = null;
                        }
                            
                        $set = Setting::where('key', 'email_contact')->first();
                        if(!empty($set)){
                            $setting['email_contact'] = $set['value'];
                        }else{
                            $setting['email_contact'] = null;
                        }
    
                        $data = array(
                            'content' => $content,
                            'setting' => $setting
                        );
                        
                        Mailgun::send('pos::email_sync_menu', $data, function($message) use ($to,$subject,$setting)
                        {
                            $message->to($to)->subject($subject)
                                            ->trackClicks(true)
                                            ->trackOpens(true);
                            if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                                $message->from($setting['email_from'], $setting['email_sender']);
                            }else if(!empty($setting['email_from'])){
                                $message->from($setting['email_from']);
                            }
    
                            if(!empty($setting['email_reply_to'])){
                                $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                            }
    
                            if(!empty($setting['email_cc']) && !empty($setting['email_cc_name'])){
                                $message->cc($setting['email_cc'], $setting['email_cc_name']);
                            }
    
                            if(!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])){
                                $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                            }
                        });
                    }
                }
            }
					
			$hasil['new_product']['total'] = (String)$countInsert;
			$hasil['new_product']['list_product'] = $insertedProduct;
            $hasil['updated_product']['total'] = (String)$countUpdate;
            $hasil['updated_product']['list_product'] = $updatedProduct;
            
            return response()->json([
				'status'    => 'success',
                'result'  => $hasil,
			]);
		}else{
			return response()->json([
				'status'    => 'fail',
				'messages'  => ['store_code isn\'t match']
			]);
		}
    }

    public function syncMenuReturn(reqMenu $request){
        // call function syncMenu
        $url = env('API_URL').'/api/v1/pos/menu/sync';
        $syncMenu = MyHelper::post($url, MyHelper::getBearerToken(), $request->json()->all());

        // return sesuai api raptor
        if(isset($syncMenu['status']) && $syncMenu['status'] == 'success'){
            $hasil['inserted'] = $syncMenu['result']['new_product']['total'];
            $hasil['updated'] = $syncMenu['result']['updated_product']['total'];
            return response()->json([
				'status'    => 'success',
                'result'  => [$hasil]
			]);
        }
        return $syncMenu;
    }

	public function transaction(reqTransaction $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            DB::rollback();
            return response()->json($api);
        }

        $checkOutlet = Outlet::where('outlet_code', $post['store_code'])->first();
        if (empty($checkOutlet)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]);
        }
		$result = array();
        foreach ($post['transactions'] as $key => $trx) {
			if(isset($trx['order_id'])){
				if(!empty($trx['order_id'])){
					$trx = Transaction::join('transaction_pickups','transactions.id_transaction','=','transaction_pickups.id_transaction')
										->where('transaction_pickups.order_id','=',$trx['order_id'])
										->where('transactions.created_at','=',date("Y-m-d H:i:s"))
										->first();
					if($trx){
						$r = ['id_transaction'    => $trx['id_transaction']
							];
						array_push($result, $r);
					} else {
						if(count($post['transactions']) == 1)
							return response()->json(['status' => 'fail', 'messages' => ['Order ID not found']]);
					}
				}
			} else {
				if (isset($trx['member_uid'])) {
					$qr = MyHelper::readQR($trx['member_uid']);
					$timestamp = $qr['timestamp'];
					$phoneqr = $qr['phone'];
					$user      = User::where('phone', $phoneqr)->with('memberships')->first();
					if (empty($user)) {
						DB::rollback();
						return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
					}

					if (count($user['memberships']) > 0) {
						$post['membership_level']    = $user['memberships'][0]['membership_name'];
						$post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
					} else {
						$post['membership_level']    = null;
						$post['membership_promo_id'] = null;
					}
				} else {
					$user['id'] = null;
					$post['membership_level']    = null;
					$post['membership_promo_id'] = null;
				}
				
				$dataTrx = [
					'id_outlet'                   => $checkOutlet['id_outlet'],
					'id_user'                     => $user['id'],
					'transaction_date'            => date('Y-m-d H:i:s', strtotime($trx['date_time'])),
					'transaction_receipt_number'  => $trx['trx_id'],
					'trasaction_type'             => 'Offline',
					'sales_type'            	  => $trx['sales_type'],
					'transaction_subtotal'        => $trx['total'],
					'transaction_service'         => $trx['service'],
					'transaction_discount'        => $trx['discount'],
					'transaction_tax'             => $trx['tax'],
					'transaction_grandtotal'      => $trx['grand_total'],
					'transaction_point_earned'    => null,
					'transaction_cashback_earned' => null,
					'membership_level'            => $post['membership_level'],
					'membership_promo_id'         => $post['membership_promo_id'],
					'trasaction_payment_type'     => 'Offline',
					'transaction_payment_status'  => 'Completed'
				];

				$createTrx = Transaction::updateOrCreate(['transaction_receipt_number' => $trx['trx_id']], $dataTrx);

				if (!$createTrx) {
					DB::rollback();
					return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
				}

				$checkPay = TransactionPaymentOffline::where('id_transaction', $createTrx['id_transaction'])->get();
				if (count($checkPay) > 0) {
					$deletePay = TransactionPaymentOffline::where('id_transaction', $createTrx['id_transaction'])->delete();
					if (!$deletePay) {
						DB::rollback();
						return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
					}
				}

				$statusGet = 0;

				foreach ($trx['payments'] as $col => $pay) {
					$paymentSpecial = SpecialMembership::where('payment_method', $pay['name'])->first();
					if (!empty($paymentSpecial)) {
						$paymentUse = $paymentSpecial;
						$statusGet = 1;
					}

					$dataPay = [
						'id_transaction' => $createTrx['id_transaction'],
						'payment_type'   => $pay['type'],
						'payment_bank'   => $pay['name'],
						'payment_amount' => $pay['nominal']
					];

					$createPay = TransactionPaymentOffline::create($dataPay);
					if (!$createPay) {
						DB::rollback();
						return response()->json(['status' => 'fail', 'messages' => ['Transaction sync failed']]);
					}
				}

				foreach ($trx['menu'] as $row => $menu) {
					$checkProduct = Product::where('product_code', $menu['plu_id'])->first();
					if (empty($checkProduct)) {
						DB::rollback();
						return response()->json(['status' => 'fail', 'messages' => ['Menu not found']]);
					}
					
					$dataProduct = [
						'id_transaction'               => $createTrx['id_transaction'],
						'id_product'                   => $checkProduct['id_product'],
						'id_outlet'                    => $checkOutlet['id_outlet'],
						'id_user'                      => $createTrx['id_user'],
						'transaction_product_qty'      => $menu['qty'],
						'transaction_product_price'    => $menu['price'],
						'transaction_product_subtotal' => $menu['qty'] * $menu['price']
					];

					$createProduct = TransactionProduct::updateOrCreate(['id_transaction' => $createTrx['id_transaction'], 'id_product' => $checkProduct['id_product']], $dataProduct);
					
					// update modifiers 
					if(isset($menu['modifiers'])){
						if(!empty($menu['modifiers'])){
							foreach($menu['modifiers'] as $mod){
								$detailMod = ProductModifier::where('id_product','=',$checkProduct['id_product'])
															->where('code','=',$mod['code'])
															->first();
								if($detailMod){
									$id_product_modifier = $detailMod['id_product_modifier'];
									$type = $detailMod['type'];
									$text = $detailMod['text'];
								} else {
									ProductModifier::create(['id_product' => $checkProduct['id_product'],
															 'type' => null,
															 'code' => $mod['code'],
															 'text' => null
															]);
									$id_product_modifier = null;
									$type = null;
									$text = null;
								}
								$dataProductMod['id_transaction_product'] = $createProduct['id_transaction_product'];
								$dataProductMod['id_transaction'] = $createTrx['id_transaction'];
								$dataProductMod['id_product'] = $checkProduct['id_product'];
								$dataProductMod['id_product_modifier'] = $id_product_modifier;
								$dataProductMod['id_outlet'] = $checkOutlet['id_outlet'];
								$dataProductMod['id_user'] = $createTrx['id_user'];
								$dataProductMod['type'] = $type;
								$dataProductMod['code'] = $mod['code'];
								$dataProductMod['text'] = $text;
								$dataProductMod['qty'] = $menu['qty'];
								$dataProductMod['datetime'] = $createTrx['created_at'];
								$dataProductMod['trx_type'] = $createTrx['trasaction_type'];
								$dataProductMod['sales_type'] = $createTrx['sales_type'];
								
								$updateProductMod = TransactionProductModifier::updateOrCreate([
											'id_transaction' => $createTrx['id_transaction'], 
											'code'  => $mod['code']
											], $dataProductMod);
							}
						}
					}
					if (!$createProduct) {
						DB::rollback();
						return response()->json(['status' => 'fail', 'messages' => ['Transaction product sync failed']]);
					}
				}

				$pointBefore = 0;
				$pointValue = 0;

				if (isset($trx['member_uid'])) {
					//insert voucher
					$idDealVouUsed = [];
					if (!empty($trx['voucher'])) {
						foreach ($trx['voucher'] as $keyV => $valueV) {
							$checkUsed = DealsVoucher::where('voucher_code', $valueV['voucher_code'])->with(['deals_user', 'deal'])->first();
							if (empty($checkUsed)) {
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Voucher not found']
								]);
							}

							//cek voucher sudah di invalidate
							foreach ($checkUsed['deals_user'] as $valueDealUser) {
								if($valueDealUser->redeemed_at == null){
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Voucher '.$valueV['voucher_code'].' not valid']
									]);
								}
							}

							//cek voucher outlet
							foreach ($checkUsed['deals_user'] as $valueDealUser) {
								if($valueDealUser->id_outlet !=  $checkOutlet['id_outlet']){
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Voucher '.$valueV['voucher_code']. ' cannot be used at this outlet.']
									]);
								}
							}

							$checkVoucherUsed = TransactionVoucher::whereNotIn('id_transaction', [$createTrx->id_transaction])->where('id_deals_voucher', $checkUsed['id_deals_voucher'])->first();
							if($checkVoucherUsed){
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Voucher '.$valueV['voucher_code'].' has been used']
								]);
							}

							//create transaction voucher
							$trxVoucher['id_transaction'] = $createTrx->id_transaction;
							$trxVoucher['id_deals_voucher'] =  $checkUsed['id_deals_voucher'];

							$insertTrxVoucher = TransactionVoucher::updateOrCreate(['id_transaction' => $createTrx->id_transaction, 'id_deals_voucher' => $checkUsed['id_deals_voucher']],$trxVoucher);
							if (!$insertTrxVoucher) {
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Create voucher transaction failed']
								]);
							}

							$idDealVouUsed[] = $insertTrxVoucher->id_deals_voucher;

							foreach ($checkUsed['deals_user'] as $keyU => $valueU) {
								$valueU->used_at = $createTrx->transaction_date;
								$valueU->update();
								if (!$valueU) {
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Voucher not valid']
									]);
								}
							}

							$checkUsed['deal']->deals_total_used =  $checkUsed['deal']->deals_total_used + 1;
							$checkUsed['deal']->update();
							if (!$checkUsed['deal']) {
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Voucher not valid']
								]);
							}
						}
						
					}

					//delete voucher not used if transction update
					$trxVouNotUsed = TransactionVoucher::where('id_transaction', $createTrx->id_transaction)->whereNotIn('id_deals_voucher', $idDealVouUsed)->get();
					if(count($trxVouNotUsed) > 0){
						foreach($trxVouNotUsed as $notUsed){

							$notUsed['deals_voucher']['deals_user'][0]->used_at = null;
							$notUsed['deals_voucher']['deals_user'][0]->save();
							if(!$notUsed['deals_voucher']['deals_user'][0]){
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Update voucher transaction failed']
								]); 
							}

							$notUsed['deals_voucher']['deal']->deals_total_used = $notUsed['deals_voucher']['deal']->deals_total_used - 1;
							$notUsed['deals_voucher']['deal']->save();
							if(!$notUsed['deals_voucher']['deal']){
								DB::rollback();
								return response()->json([
									'status'    => 'fail',
									'messages'  => ['Update voucher transaction failed']
								]); 
							}

						}

						$delTrxVou = TransactionVoucher::where('id_transaction', $createTrx->id_transaction)->whereNotIn('id_deals_voucher', $idDealVouUsed)->delete();
						if(!$delTrxVou){
							DB::rollback();
							return response()->json([
								'status'    => 'fail',
								'messages'  => ['Update voucher transaction failed']
							]); 
						}
					}

					if ($createTrx['transaction_payment_status'] == 'Completed') {
						 //get last point 
						 $pointBefore = LogPoint::where('id_user', $user['id'])->whereNotIn('id_log_point', function($q) use($createTrx){
							$q->from('log_points')
							  ->where('source', 'Transaction')
							  ->where('id_reference', $createTrx->id_transaction)
							  ->select('id_log_point');
						})->sum('point');

						 //cek jika menggunakan voucher tidak dapat point / cashback
						 if (count($idDealVouUsed) > 0) {
							$point = null;
							$cashback = null;

							//delete log point
							$delLog = LogPoint::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->get();
							if(count($delLog) > 0){
								$delLog = LogPoint::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->delete();
								if (!$delLog) {
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Update point failed']
									]);
								}
							}

							//delete log balance
							$delLog = LogBalance::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->get();
							if(count($delLog) > 0){
								$delLog = LogBalance::where('id_reference', $createTrx->id_transaction)->where('source', 'Transaction')->delete();
								if (!$delLog) {
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Update cashback failed']
									]);
								}
							}

						 }else{
							 if ($statusGet == 1) {
								 if (!empty($user['memberships'][0]['membership_name'])) {
									 $level = $paymentUse->special_membership_name;
									 $percentageP = $paymentUse->benefit_point_multiplier / 100;
									 $percentageB = $paymentUse->benefit_cashback_multiplier / 100;
									 $cashMax = $paymentUse->cashback_maximum;
								 } else {
									 $level = null;
									 $percentageP = 1;
									 $percentageB = 1;
		 
									 $getSet = Setting::where('key', 'cashback_maximum')->first();
									 if($getSet){
										 $cashMax = (int)$getSet->value;
									 }
								 }
								
								 $point = floor($this->count('point', $trx) * $percentageP);
								 $cashback = floor($this->count('cashback', $trx) * $percentageB);
		 
								 if(isset($cashMax) && $cashback > $cashMax){
									 $cashback = $cashMax;
								 }
		 
								 //update point & cashback earned
								 $createTrx->transaction_point_earned = $point;
								 $createTrx->transaction_cashback_earned = $cashback;
								 $createTrx->update();
								 if (!$createTrx) {
									 DB::rollback();
									 return response()->json([
										 'status'    => 'fail',
										 'messages'  => ['Insert Point Failed']
									 ]);
								 }
		 
								if ($createTrx['transaction_point_earned']) {
									 $settingPoint = Setting::where('key', 'point_conversion_value')->first();
		 
									 $dataLog = [
										 'id_user'                     => $createTrx['id_user'],
										 'point'                       => $createTrx['transaction_point_earned'],
										 'id_reference'                => $createTrx['id_transaction'],
										 'source'                      => 'Transaction',
										 'grand_total'                 => $createTrx['transaction_grandtotal'],
										 'point_conversion'            => $settingPoint['value'],
										 'membership_level'            => $level,
										 'membership_point_percentage' => $percentageP * 100
									 ];
		 
									 $insertDataLog = LogPoint::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLog);
									 if (!$insertDataLog) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Point Failed']
										 ]);
									 }
		 
									 $pointValue = $insertDataLog->point;
		 
									 //update user point
									 $user->points = $pointBefore + $pointValue;
									 $user->update();
									 if (!$user) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Point Failed']
										 ]);
									 }
								}
		 
								if ($createTrx['transaction_cashback_earned']) {
									$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();

									$dataLogCash = [
										'id_user'                        => $createTrx['id_user'],
										'balance'                        => $createTrx['transaction_cashback_earned'],
										'id_reference'                   => $createTrx['id_transaction'],
										'source'                         => 'Transaction',
										'grand_total'                    => $createTrx['transaction_grandtotal'],
										'ccashback_conversion'           => $settingCashback['value'],
										'membership_level'               => $level,
										'membership_cashback_percentage' => $percentageB * 100
									];

									$insertDataLogCash = LogBalance::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLogCash);
									if (!$insertDataLogCash) {
										DB::rollback();
										return response()->json([
											'status'    => 'fail',
											'messages'  => ['Insert Cashback Failed']
										]);
									}

									//update user balance
									$sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
									$user->balance = $sumBalance;
									$user->update();
									if (!$user) {
										DB::rollback();
										return response()->json([
											'status'    => 'fail',
											'messages'  => ['Insert Cashback Failed']
										]);
									}
								}
		 
								$createTrx->special_memberships = 1;
								$createTrx->save();
								if (!$createTrx) {
									DB::rollback();
									return response()->json([
										'status'    => 'fail',
										'messages'  => ['Transaction sync failed']
									]);
								}
							 } else {
								 if (!empty($user['memberships'][0]['membership_name'])) {
									 $level = $user['memberships'][0]['membership_name'];
									 $percentageP = $user['memberships'][0]['benefit_point_multiplier'] / 100;
									 $percentageB = $user['memberships'][0]['benefit_cashback_multiplier'] / 100;
									 $cashMax = $user['memberships'][0]['cashback_maximum'];
								 } else {
									 $level = null;
									 $percentageP = 0;
									 $percentageB = 0;
		 
									 $getSet = Setting::where('key', 'cashback_maximum')->first();
									 if($getSet){
										 $cashMax = (int)$getSet->value;
									 }
								 }
		 
								 $point = floor($this->count('point', $trx) * $percentageP);
								 $cashback = floor($this->count('cashback', $trx) * $percentageB);
		 
								 if(isset($cashMax) && $cashback > $cashMax){
									 $cashback = $cashMax;
								 }
		 
								 //update point & cashback earned
								 $createTrx->transaction_point_earned = $point;
								 $createTrx->transaction_cashback_earned = $cashback;
								 $createTrx->update();
								 if (!$createTrx) {
									 DB::rollback();
									 return response()->json([
										 'status'    => 'fail',
										 'messages'  => ['Insert Point Failed']
									 ]);
								 }
		 
								 if ($createTrx['transaction_point_earned']) {
									 $settingPoint = Setting::where('key', 'point_conversion_value')->first();
		 
									 $dataLog = [
										 'id_user'                     => $createTrx['id_user'],
										 'point'                       => $createTrx['transaction_point_earned'],
										 'id_reference'                => $createTrx['id_transaction'],
										 'source'                      => 'Transaction',
										 'grand_total'                 => $createTrx['transaction_grandtotal'],
										 'point_conversion'            => $settingPoint['value'],
										 'membership_level'            => $level,
										 'membership_point_percentage' => $percentageP * 100
									 ];
		 
									 $insertDataLog = LogPoint::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLog);
									 if (!$insertDataLog) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Point Failed']
										 ]);
									 }
		 
									 $pointValue = $insertDataLog->point;
		 
									 //update user point
									 $user->points = $pointBefore + $pointValue;
									 $user->update();
									 if (!$user) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Point Failed']
										 ]);
									 }
		 
								 }
		 
								 if ($createTrx['transaction_cashback_earned']) {
									 $settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
		 
									 $dataLogCash = [
										 'id_user'                        => $createTrx['id_user'],
										 'balance'                        => $createTrx['transaction_cashback_earned'],
										 'id_reference'                   => $createTrx['id_transaction'],
										 'source'                         => 'Transaction',
										 'grand_total'                    => $createTrx['transaction_grandtotal'],
										 'ccashback_conversion'           => $settingCashback['value'],
										 'membership_level'               => $level,
										 'membership_cashback_percentage' => $percentageB * 100
									 ];
		 
									 $insertDataLogCash = LogBalance::updateOrCreate(['id_user' => $createTrx['id_user'], 'id_reference' => $createTrx['id_transaction']], $dataLogCash);
									 if (!$insertDataLogCash) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Cashback Failed']
										 ]);
									 }
		 
									 //update user balance
									 $sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
									 $user->balance = $sumBalance;
									 $user->update();
									 if (!$user) {
										 DB::rollback();
										 return response()->json([
											 'status'    => 'fail',
											 'messages'  => ['Insert Cashback Failed']
										 ]);
									 }
								 }
		 
								 $checkMembership = app($this->membership)->calculateMembership($user['phone']);
							 }
						 }
					}
				}
				
				$r = ['id_transaction'    => $createTrx->id_transaction,
							'point_before'      => (int)$pointBefore,
							'point_after'       => $pointBefore + $pointValue,
							'point_value'       => $pointValue
						];
				array_push($result, $r);
			}
		}
        DB::commit();
        return response()->json([
            'status'    => 'success',
            'result'    => $result
        ]);
    }
    
    public function transactionRefund(reqTransactionRefund $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            DB::rollback();
            return response()->json($api);
        }

        $checkTrx = Transaction::where('transaction_receipt_number', $post['trx_id'])->first();
        if (empty($checkTrx)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        //if use voucher, cannot refund
        $trxVou = TransactionVoucher::where('id_transaction', $checkTrx->id_transaction)->first();
        if($trxVou){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction cannot be refund. This transaction use voucher']]);
        }

        $user = User::where('id', $checkTrx->id_user)->first();
        if (empty($user)) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
        }

        $checkTrx->transaction_payment_status = 'Cancelled';
        $checkTrx->void_date = date('Y-m-d H:i:s');
        $checkTrx->transaction_notes = $post['reason'];
        $checkTrx->update();
        if (!$checkTrx) {
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed1']]);
        }

        $point = LogPoint::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
        if (!empty($point)) {
            $point->delete();
            if (!$point) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed2']]);
            }

             //update user point
             $sumPoint = LogPoint::where('id_user', $user['id'])->sum('point');
             $user->points = $sumPoint;
             $user->update();
             if (!$user) {
                 DB::rollback();
                 return response()->json([
                     'status'    => 'fail',
                     'messages'  => ['Update point failed']
                 ]);
             }
        }

        $balance = LogBalance::where('id_reference', $checkTrx->id_transaction)->where('source', 'Transaction')->first();
        if (!empty($balance)) {
            $balance->delete();
            if (!$balance) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Transaction refund sync failed3']]);
            }

             //update user balance
             $sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
             $user->balance = $sumBalance;
             $user->update();
             if (!$user) {
                 DB::rollback();
                 return response()->json([
                     'status'    => 'fail',
                     'messages'  => ['Update cashback failed']
                 ]);
             }
        }

        DB::commit();

        $checkMembership = app($this->membership)->calculateMembership($user['phone']);
        return response()->json(['status' => 'success']);
    }

    function checkApi($key, $secret)
    {
        $api_key = Setting::where('key', 'api_key')->first();
        if (empty($api_key)) {
            return ['status' => 'fail', 'messages' => ['api_key not found']];
        }

        $api_key = $api_key['value'];
        if ($api_key != $key) {
            return ['status' => 'fail', 'messages' => ['api_key isn\’t match']];
        }

        $api_secret = Setting::where('key', 'api_secret')->first();
        if (empty($api_secret)) {
            return ['status' => 'fail', 'messages' => ['api_secret not found']];
        }

        $api_secret = $api_secret['value'];
        if ($api_secret != $secret) {
            return ['status' => 'fail', 'messages' => ['api_secret isn\’t match']];
        }

        return ['status' => 'success'];

    }

    function count($value, $data)
    {
        if ($value == 'point') {
            $subtotal     = $data['total'];
            $service      = $data['service'];
            $discount     = $data['discount'];
            $tax          = $data['tax'];
            $pointFormula = $this->convertFormula('point');
            $value        = $this->pointValue();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $pointFormula) . ';'));
            return $count;

        }

        if ($value == 'cashback') {
            $subtotal        = $data['total'];
            $service         = $data['service'];
            $discount        = $data['discount'];
            $tax             = $data['tax'];
            $cashbackFormula = $this->convertFormula('cashback');
            $value           = $this->cashbackValue();
            // $max             = $this->cashbackValueMax();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $cashbackFormula) . ';'));

            // if ($count >= $max) {
            //     return $max;
            // } else {
                return $count;
            // }

        }
    }

    public function convertFormula($value) 
    {
        $convert = $this->$value();
        return $convert;
    }

    public function point() 
    {
        $point = $this->setting('point_acquisition_formula');

        $point = preg_replace('/\s+/', '', $point);
        return $point;
    }

    public function cashback() 
    {
        $cashback = $this->setting('cashback_acquisition_formula');

        $cashback = preg_replace('/\s+/', '', $cashback);
        return $cashback;
    }

    public function setting($value) 
    {
        $setting = Setting::where('key', $value)->first();
        
        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

    public function pointCount() 
    {
        $point = $this->setting('point_acquisition_formula');
        return $point;
    }

    public function cashbackCount() 
    {
        $cashback = $this->setting('cashback_acquisition_formula');
        return $cashback;
    }

    public function pointValue() 
    {
        $point = $this->setting('point_conversion_value');
        return $point;
    }

    public function cashbackValue() 
    {
        $cashback = $this->setting('cashback_conversion_value');
        return $cashback;
    }

    public function cashbackValueMax() 
    {
        $cashback = $this->setting('cashback_maximum');
        return $cashback;
    }
}
