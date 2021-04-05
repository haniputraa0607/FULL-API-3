<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionVoucher;

use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;

use Modules\Subscription\Entities\SubscriptionUserVoucher;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiReportPromoController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

 	public function listPromo($promo, Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $select_trx = '
			COUNT(transactions.id_transaction) AS total_transaction, 
			SUM(transactions.transaction_gross) AS total_gross_sales, 
			SUM(
				CASE WHEN transactions.transaction_shipment IS NOT NULL AND transactions.transaction_shipment != 0 THEN transactions.transaction_shipment 
					WHEN transactions.transaction_shipment_go_send IS NOT NULL AND transactions.transaction_shipment_go_send != 0 THEN transactions.transaction_shipment_go_send
				ELSE 0 END
			) as total_delivery_fee,
			SUM(
				CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
					WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount)
					ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
			) as total_discount,
			COUNT(CASE WHEN transaction_pickups.pickup_by != "Customer" THEN 1 ELSE NULL END) as total_transaction_delivery,
			COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" THEN 1 ELSE NULL END) as total_transaction_pickup
		';

    	switch ($promo) {
    		case 'deals':
		        $list = TransactionVoucher::join('transactions', 'transactions.id_transaction', 'transaction_vouchers.id_transaction')
		        		->join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
		        		->join('deals', 'deals_vouchers.id_deals', 'deals.id_deals')
		    			->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
		        		->where('transactions.id_outlet', $request->id_outlet)
		    			->groupBy('deals_vouchers.id_deals')
		    			->where('transactions.transaction_payment_status', 'Completed')
		    			->whereNull('transaction_pickups.reject_at')
		        		->select(
		        			'deals.deals_title as title',
		        			'deals.promo_type',
		        			DB::raw('
		        				CASE WHEN deals.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN deals.promo_type = "Discount bill" THEN "bill discount"
									WHEN deals.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END as type
		        			'),
		        			DB::raw($select_trx)
		        		);
    			
    			break;

    		case 'promo-campaign':
    			$list = Transaction::join('promo_campaign_promo_codes', 'transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
    					->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign')
		    			->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
		        		->where('transactions.id_outlet', $request->id_outlet)
		    			->groupBy('promo_campaigns.id_promo_campaign')
		    			->where('transactions.transaction_payment_status', 'Completed')
		    			->whereNull('transaction_pickups.reject_at')
		        		->select(
		        			'promo_campaigns.promo_title as title',
		        			'promo_campaigns.promo_type',
		        			DB::raw('
		        				CASE WHEN promo_campaigns.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN promo_campaigns.promo_type = "Discount bill" THEN "bill discount"
									WHEN promo_campaigns.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END as type
		        			'),
		        			DB::raw($select_trx)
		        		);
    			break;

    		case 'subscription':
    			$list = SubscriptionUserVoucher::join('transactions', 'transactions.id_transaction', 'subscription_user_vouchers.id_transaction')
    					->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
    					->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
		    			->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
		        		->where('transactions.id_outlet', $request->id_outlet)
		    			->groupBy('subscriptions.id_subscription')
		    			->where('transactions.transaction_payment_status', 'Completed')
		    			->whereNull('transaction_pickups.reject_at')
		        		->select(
		        			'subscriptions.subscription_title as title',
		        			'subscriptions.subscription_discount_type',
		        			DB::raw('
		        				CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN "payment method"
									WHEN subscriptions.subscription_discount_type = "discount" THEN "bill discount"
									WHEN subscriptions.subscription_discount_type = "discount_delivery" THEN "delivery discount"
								ELSE NULL END as type
		        			'),
		        			DB::raw($select_trx)
		        		);
    			break;
    		
    		default:
            	return [
            		'status' => 'fail',
            		'messages' => [
            			'Promo tidak ditemukan'
            		]
            	];
    			break;
    	}


        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        }

        $order = $post['order'] ?? 'title';
        $orderType = $post['order_type'] ?? 'asc';
        $list = $list->orderBy($order, $orderType);

        $sub = $list;

        $query = DB::table(DB::raw('('.$sub->toSql().') as report_promo'))
		        ->mergeBindings($sub->getQuery());

		$this->filterPromoReport($query, $post);

        if($post['export'] == 1){
            $query = $query->get();
        }else{
            $query = $query->paginate(30);
        }

        if (!$query) {
        	return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        $result = $query->toArray();

        return MyHelper::checkGet($result);
    }

    function filterPromoReport($query, $filter)
    {
    	if (isset($filter['rule'])) {
            foreach ($filter['rule'] as $key => $con) {
            	if(is_object($con)){
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] != 'all_transaction') {
                    	$var = $con['subject'];
                    	if ($con['operator'] == 'like') {
                    		$con['parameter'] = '%'.$con['parameter'].'%';
                    	}

                        if ($filter['operator'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }
            }
        }

        return $query;
    }
}
