<?php

namespace Modules\Promotion\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Promotion;
use App\Http\Models\PromotionRule;
use App\Http\Models\PromotionRuleParent;
use App\Http\Models\PromotionContent;
use App\Http\Models\PromotionContentShortenLink;
use App\Http\Models\PromotionSchedule;
use App\Http\Models\PromotionQueue;
use App\Http\Models\PromotionSent;
use App\Http\Models\Deal;
use App\Http\Models\DealsVoucher;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPromotionTemplate;
use App\Http\Models\Outlet;

use App\Lib\MyHelper;
use DB;

class ApiPromotionDeals extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
  
    public function list(Request $request)
    {
		$post = $request->json()->all();
		$deals = DealsPromotionTemplate::orderBy('updated_at', 'desc');
		if(isset($post['id_deals_promotion_template'])){
			$deals = $deals->where('id_deals_promotion_template', $post['id_deals_promotion_template']);
		}
		$deals = $deals->get();
		if(isset($post['id_deals_promotion_template'])){
			$deals['promotion'] = PromotionContent::join('promotions', 'promotions.id_promotion', 'promotion_contents.id_promotion')
													->where('id_deals_promotion_template', $post['id_deals_promotion_template'])
													->where('id_deals_promotion_template', $post['id_deals_promotion_template'])
													->select('promotions.*')
													->distinct()
													->orderBy('promotions.id_promotion', 'desc')
													->get();
		}
		return response()->json(MyHelper::checkGet($deals));
	}

    public function save(Request $request)
    {
		$post = $request->json()->all();
		
		if($post['deals_promo_id_type'] == 'promoid'){
			$post['deals_promo_id'] = $post['deals_promo_id_promoid'];
			$post['deals_nominal'] = null;
		}

		if($post['deals_promo_id_type'] == 'nominal'){
			$post['deals_nominal'] = $post['deals_promo_id_nominal'];
			$post['deals_promo_id'] = null;
		}

		unset($post['deals_promo_id_promoid']);
		unset($post['deals_promo_id_nominal']);

		$post['deals_start'] = date('Y-m-d H:i:s', strtotime($post['deals_start']));
		$post['deals_end'] = date('Y-m-d H:i:s', strtotime($post['deals_end']));

		$post['deals_list_outlet'] = implode(',', $post['id_outlet']);
		unset($post['id_outlet']);

		if($post['duration'] == 'dates'){
			$post['deals_voucher_expired'] = date('Y-m-d H:i:s', strtotime($post['deals_voucher_expired']));
			$post['deals_voucher_duration'] = null;
		}else{
			$post['deals_voucher_expired'] = null;
		}
		unset($post['duration']);

		if($post['deals_voucher_type'] == 'List Vouchers'){
			$post['deals_list_voucher'] = str_replace("\r\n", ',', $post['voucher_code']);
		}else{
			$post['deals_list_voucher'] = null;
		}
		unset($post['voucher_code']);

		if(isset($post['deals_image'])){
			if (!file_exists('img/promotion/deals')) {
				mkdir('img/promotion/deals', 0777, true);
			}
			$upload = MyHelper::uploadPhoto($post['deals_image'], $path = 'img/promotion/deals', 500);
			if ($upload['status'] == "success") {
				$post['deals_image'] = $upload['path'];
			} else{
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Save Promotion Deals Image failed.']
					];
				return response()->json($result);
			}
		}

		if(isset($post['id_deals_promotion_template'])){
			$deals = DealsPromotionTemplate::where('id_deals_promotion_template', $post['id_deals_promotion_template'])->update($post);
		}else{
			$deals = DealsPromotionTemplate::create($post);
		}

		return response()->json(MyHelper::checkCreate($deals));
    }
}
