<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Transaction;
use Modules\Franchise\Entities\TransactionVoucher;
use Modules\Franchise\Entities\TransactionProduct;
use Modules\Franchise\Entities\TransactionProductModifier;
use Modules\Franchise\Entities\Deal;
use Modules\Franchise\Entities\PromoCampaign;
use Modules\Franchise\Entities\Subscription;
use Modules\Franchise\Entities\Bundling;
use Modules\Franchise\Entities\SubscriptionUserVoucher;
use Modules\Franchise\Entities\TransactionBundlingProduct;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;
use Modules\PromoCampaign\Lib\PromoCampaignTools;

class ApiReportPromoController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function listPromo($promo, Request $request)
    {
        $post = $request->json()->all();
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $select_trx = '
			COUNT(transactions.id_transaction) AS total_transaction, 
			SUM(transactions.transaction_gross) AS total_gross_sales, 
			SUM(
				CASE WHEN transactions.transaction_shipment IS NOT NULL AND transactions.transaction_shipment != 0 THEN transactions.transaction_shipment 
					WHEN transactions.transaction_shipment_go_send IS NOT NULL AND transactions.transaction_shipment_go_send != 0 THEN transactions.transaction_shipment_go_send
				ELSE 0 END
			) AS total_delivery_fee,
			COUNT(CASE WHEN transaction_pickups.pickup_by != "Customer" THEN 1 ELSE NULL END) AS total_transaction_delivery,
			COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" THEN 1 ELSE NULL END) AS total_transaction_pickup
		';

        $total_discount_promo = '
			SUM(
				CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
			) AS total_discount,
			SUM(
				CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
			)/COUNT(transactions.id_transaction) AS average_discount
		';

        switch ($promo) {
            case 'deals':
                $list = TransactionVoucher::join('transactions', 'transactions.id_transaction', 'transaction_vouchers.id_transaction')
                        ->join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                        ->join('deals', 'deals_vouchers.id_deals', 'deals.id_deals')
                        ->groupBy('deals_vouchers.id_deals')
                        ->select(
                            'deals_vouchers.id_deals AS id_promo',
                            'deals.deals_title AS title',
                            'deals.promo_type',
                            DB::raw('
		        				CASE WHEN deals.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN deals.promo_type = "Discount bill" THEN "bill discount"
									WHEN deals.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END AS type
		        			'),
                            DB::raw($select_trx),
                            DB::raw($total_discount_promo)
                        );

                break;

            case 'promo-campaign':
                $list = Transaction::join('promo_campaign_promo_codes', 'transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
                        ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign')
                        ->groupBy('promo_campaigns.id_promo_campaign')
                        ->select(
                            'promo_campaigns.id_promo_campaign AS id_promo',
                            'promo_campaigns.promo_title AS title',
                            'promo_campaigns.promo_type',
                            DB::raw('
		        				CASE WHEN promo_campaigns.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN promo_campaigns.promo_type = "Discount bill" THEN "bill discount"
									WHEN promo_campaigns.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END AS type
		        			'),
                            DB::raw($select_trx),
                            DB::raw($total_discount_promo)
                        );
                break;

            case 'subscription':
                $list = SubscriptionUserVoucher::join('transactions', 'transactions.id_transaction', 'subscription_user_vouchers.id_transaction')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                        ->leftJoin('transaction_payment_subscriptions', 'transaction_payment_subscriptions.id_transaction', 'transactions.id_transaction')
                        ->groupBy('subscriptions.id_subscription')
                        ->select(
                            'subscriptions.id_subscription AS id_promo',
                            'subscriptions.subscription_title AS title',
                            'subscriptions.subscription_discount_type',
                            DB::raw('
		        				CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN "payment method"
									WHEN subscriptions.subscription_discount_type = "discount" THEN "bill discount"
									WHEN subscriptions.subscription_discount_type = "discount_delivery" THEN "delivery discount"
								ELSE NULL END AS type,

								CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN 
									SUM(transaction_payment_subscriptions.subscription_nominal)
								ELSE
									SUM(
										CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
									)
								END AS total_discount,

								CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN 
									SUM(transaction_payment_subscriptions.subscription_nominal)
								ELSE
									SUM(
										CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_bill != 0 THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
									)
								END / COUNT(transactions.id_transaction) AS average_discount
		        			'),
                            DB::raw($select_trx)
                            // DB::raw($total_discount_promo)
                        );
                break;

            case 'bundling':
                $list = TransactionBundlingProduct::join('transactions', 'transactions.id_transaction', 'transaction_bundling_products.id_transaction')
                        ->join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
                        ->groupBy('transaction_bundling_products.id_bundling')
                        ->select(
                            'transaction_bundling_products.id_bundling AS id_promo',
                            'bundling.bundling_name AS title',
                            DB::raw('"product discount" AS type'),
                            DB::raw($select_trx),
                            DB::raw('
		        				SUM(transaction_bundling_products.transaction_bundling_product_total_discount) AS total_discount,
		        				SUM(transaction_bundling_products.transaction_bundling_product_total_discount)/COUNT(transactions.id_transaction) AS average_discount
		        			')
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

        $list = $list->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('transaction_pickups.reject_at')
                ->where('transactions.id_outlet', $request->id_outlet);

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $list = $list->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        $order = $post['order'] ?? 'title';
        $orderType = $post['order_type'] ?? 'asc';
        $list = $list->orderBy($order, $orderType);

        $sub = $list;

        $query = DB::table(DB::raw('(' . $sub->toSql() . ') AS report_promo'))
                ->mergeBindings($sub->getQuery());

        $this->filterPromoReport($query, $post);

        if ($post['export'] == 1) {
            $query = $query->get();
        } else {
            $query = $query->paginate(30);
        }

        if (!$query) {
            return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        $result = $query->toArray();

        if (isset($result['data'])) {
            foreach ($result['data'] as &$value) {
                $value->id_promo = MyHelper::encSlug($value->id_promo);
            }
        }

        return MyHelper::checkGet($result);
    }

    public function filterPromoReport($query, $filter)
    {
        if (isset($filter['rule'])) {
            foreach ($filter['rule'] as $key => $con) {
                if (is_object($con)) {
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] != 'all_transaction') {
                        $var = $con['subject'];
                        if ($con['operator'] == 'like') {
                            $con['parameter'] = '%' . $con['parameter'] . '%';
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

    public function detailPromo(Request $request)
    {
        $post = $request->json()->all();
        $promo = $request->promo;
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }
        $id_promo = MyHelper::decSlug($request->id_promo);

        $detail = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                    ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->where('transactions.id_outlet', $request->id_outlet)
                    ->with('product_variant_group.product_variant_pivot_simple')
                    ->select(
                        DB::raw('
			        		transaction_products.id_product,
			        		transaction_products.id_brand,
			        		products.product_code,
			        		products.product_name,
			        		transaction_products.id_product_variant_group,
			        		transaction_products.type,
			        		SUM(transaction_products.transaction_product_qty) AS sold_qty,
			        		SUM( (transaction_products.transaction_product_price+transaction_products.transaction_variant_subtotal)  * transaction_products.transaction_product_qty) AS total_gross_sales
			        	')
                    )
                    ->groupBy('transaction_products.id_product', 'transaction_products.id_product_variant_group');

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $detail = $detail->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $detail = $detail->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $detail = $detail->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        switch ($promo) {
            case 'deals':
                $data_promo = Deal::where('id_deals', $id_promo)
                                ->select(DB::raw('
    								deals.deals_title AS promo_title,
			        				CASE WHEN deals.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
										WHEN deals.promo_type = "Discount bill" THEN "bill discount"
										WHEN deals.promo_type = "Discount delivery" THEN "delivery discount"
									ELSE NULL END AS type
			        			'))
                                ->first();

                $detail->join('transaction_vouchers', 'transactions.id_transaction', 'transaction_vouchers.id_transaction')
                        ->join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                        ->where('deals_vouchers.id_deals', $id_promo);
                break;

            case 'promo-campaign':
                $data_promo = PromoCampaign::where('id_promo_campaign', $id_promo)
                                ->select(DB::raw('
    								promo_campaigns.promo_title AS promo_title,
			        				CASE WHEN promo_campaigns.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
										WHEN promo_campaigns.promo_type = "Discount bill" THEN "bill discount"
										WHEN promo_campaigns.promo_type = "Discount delivery" THEN "delivery discount"
									ELSE NULL END AS type
			        			'))
                                ->first();

                $detail->join('promo_campaign_promo_codes', 'transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
                        ->where('promo_campaign_promo_codes.id_promo_campaign', $id_promo);
                break;

            case 'subscription':
                $data_promo = Subscription::where('id_subscription', $id_promo)
                                ->select(
                                    'subscriptions.subscription_title AS promo_title',
                                    DB::raw('
				        				CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN "payment method"
											WHEN subscriptions.subscription_discount_type = "discount" THEN "bill discount"
											WHEN subscriptions.subscription_discount_type = "discount_delivery" THEN "delivery discount"
										ELSE NULL END AS type
				        			')
                                )->first();

                $detail->join('subscription_user_vouchers', 'transactions.id_transaction', 'subscription_user_vouchers.id_transaction')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->where('subscription_users.id_subscription', $id_promo);
                break;

            case 'bundling':
                $data_promo = Bundling::where('id_bundling', $id_promo)
                                ->select(
                                    'bundling.bundling_name AS promo_title',
                                    DB::raw('"bundling" AS type')
                                )->first();

                $detail->leftJoin('transaction_bundling_products', 'transactions.id_transaction', 'transaction_bundling_products.id_transaction')
                        ->where('transaction_bundling_products.id_bundling', $id_promo)
                        ->whereNotNull('transaction_products.id_bundling_product');
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

        if (!$data_promo) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Promo tidak ditemukan'
                ]
            ];
        }

        switch ($data_promo['type']) {
            case 'product discount':
                $detail->where('transaction_products.transaction_product_discount', '!=', 0)
                        ->addSelect(
                            DB::raw('
		    					SUM(transaction_products.transaction_product_discount) AS total_discount,
		    					SUM(transaction_products.transaction_product_discount)/SUM(transaction_products.transaction_product_qty) AS average_discount
		    				')
                        );
                break;

            case 'bill discount':
                /*$sub = TransactionProduct::groupBy('id_transaction')
                        ->select(DB::raw('
                            id_transaction,
                            count(id_transaction) AS trx_qty
                        '));

                $detail->joinSub($sub, 'sub_trx_product', function ($join) {
                            $join->on('transaction_products.id_transaction', 'sub_trx_product.id_transaction');
                        })*/
                $detail->whereNotNull('transactions.transaction_discount_bill')
                        ->where('transactions.transaction_discount_bill', '!=', 0)
                        ->addSelect(
                            DB::raw('
		    					transaction_products.id_transaction,
		    					SUM(transactions.transaction_discount_bill) AS total_discount,
		    					COUNT(transaction_products.id_transaction) AS total_unit_trx,
		    					SUM( 
		    						( (transaction_products.transaction_product_price+transaction_products.transaction_variant_subtotal) *transaction_products.transaction_product_qty)
		    						/transactions.transaction_subtotal * transactions.transaction_discount_bill 
		    					) AS total_discount,

		    					SUM( 
		    						( (transaction_products.transaction_product_price+transaction_products.transaction_variant_subtotal) *transaction_products.transaction_product_qty)
		    						/transactions.transaction_subtotal * transactions.transaction_discount_bill 
		    					)/ SUM(transaction_products.transaction_product_qty) AS average_discount

		    				')
                        );
                break;

            case 'delivery discount':
                return [
                    'status' => 'fail',
                    'messages' => [
                        'Promo tidak ditemukan'
                    ]
                ];
                break;

            case 'payment method':
                $detail->join('transaction_payment_subscriptions', 'transactions.id_transaction', 'transaction_payment_subscriptions.id_transaction')
                        ->addSelect(
                            DB::raw('
		    					SUM( 
		    						( (transaction_products.transaction_product_price+transaction_products.transaction_variant_subtotal) *transaction_products.transaction_product_qty)
		    						/transactions.transaction_subtotal * transaction_payment_subscriptions.subscription_nominal 
		    					) AS total_discount,

		    					SUM( 
		    						( (transaction_products.transaction_product_price+transaction_products.transaction_variant_subtotal) *transaction_products.transaction_product_qty)
		    						/transactions.transaction_subtotal * transaction_payment_subscriptions.subscription_nominal 
		    					)/ SUM(transaction_products.transaction_product_qty) AS average_discount

		    				')
                        );
                break;

            case 'bundling':
                $detail->addSelect(
                    DB::raw('
		    					SUM(transaction_products.transaction_product_discount_all) AS total_discount,
		    					SUM(transaction_products.transaction_product_discount_all)/SUM(transaction_products.transaction_product_qty) AS average_discount
		    				')
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

        $order = $post['order'] ?? 'product_code';
        $orderType = $post['order_type'] ?? 'asc';
        $detail = $detail->orderBy($order, $orderType);

        $detail = $detail->get()->toArray();

        if ($detail) {
            $pct = new PromoCampaignTools();
            foreach ($detail as &$value) {
                $variant_name = null;
                if (isset($value['product_variant_group']['product_variant_pivot_simple'])) {
                    $variant_name = implode(',', array_column($value['product_variant_group']['product_variant_pivot_simple'], 'product_variant_name'));
                }
                $value['variant_group'] = $variant_name;
                $value['price_now'] = ($pct->getProductPrice($request->id_outlet, $value['id_product'], $value['id_product_variant_group'])['product_price'] ?? null);
                unset($value['product_variant_group']);
            }
        }

        $result['data'] = $detail;

        if ($data_promo['type'] == "bill discount" || $data_promo['type'] == "payment method") {
            $data_mod = $this->detailPromoModifier($request);
            $result['data'] = array_merge($result['data'], $data_mod['result']['data'] ?? []);
        }

        $result['data_promo'] = $data_promo;
        $result = MyHelper::checkGet($result);

        return $result;
    }

    public function detailPromoModifier(Request $request)
    {
        $post = $request->json()->all();
        $promo = $request->promo;
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }
        $id_promo = MyHelper::decSlug($request->id_promo);

        $detail = TransactionProductModifier::join('transaction_products', 'transaction_product_modifiers.id_transaction_product', 'transaction_products.id_transaction_product')
                    ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                    ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                    ->where('transaction_product_modifiers.transaction_product_modifier_price', '!=', 0)
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('transaction_pickups.reject_at')
                    ->where('transactions.id_outlet', $request->id_outlet)
                    ->select(
                        DB::raw('
			        		transaction_product_modifiers.id_transaction_product,
			        		transaction_product_modifiers.id_product_modifier,
			        		transaction_products.id_product,
			        		transaction_products.id_brand,
			        		transaction_product_modifiers.code as product_code,
			        		transaction_product_modifiers.text as product_name,
			        		transaction_products.id_product_variant_group,
			        		CASE WHEN transaction_product_modifiers.id_product_modifier_group IS NOT NULL THEN "Variant Non SKU" 
			        		ELSE "Topping" END as type,
			        		SUM(transaction_products.transaction_product_qty) AS sold_qty,
			        		SUM( (transaction_product_modifiers.transaction_product_modifier_price) * transaction_product_modifiers.qty) AS total_gross_sales
			        	')
                    )
                    ->groupBy('transaction_product_modifiers.id_product_modifier');

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $detail = $detail->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $detail = $detail->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $detail = $detail->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        switch ($promo) {
            case 'deals':
                $data_promo = Deal::where('id_deals', $id_promo)
                                ->select(DB::raw('
    								deals.deals_title AS promo_title,
			        				CASE WHEN deals.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
										WHEN deals.promo_type = "Discount bill" THEN "bill discount"
										WHEN deals.promo_type = "Discount delivery" THEN "delivery discount"
									ELSE NULL END AS type
			        			'))
                                ->first();

                $detail->join('transaction_vouchers', 'transactions.id_transaction', 'transaction_vouchers.id_transaction')
                        ->join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                        ->where('deals_vouchers.id_deals', $id_promo);
                break;

            case 'promo-campaign':
                $data_promo = PromoCampaign::where('id_promo_campaign', $id_promo)
                                ->select(DB::raw('
    								promo_campaigns.promo_title AS promo_title,
			        				CASE WHEN promo_campaigns.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
										WHEN promo_campaigns.promo_type = "Discount bill" THEN "bill discount"
										WHEN promo_campaigns.promo_type = "Discount delivery" THEN "delivery discount"
									ELSE NULL END AS type
			        			'))
                                ->first();

                $detail->join('promo_campaign_promo_codes', 'transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
                        ->where('promo_campaign_promo_codes.id_promo_campaign', $id_promo);
                break;

            case 'subscription':
                $data_promo = Subscription::where('id_subscription', $id_promo)
                                ->select(
                                    'subscriptions.subscription_title AS promo_title',
                                    DB::raw('
				        				CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN "payment method"
											WHEN subscriptions.subscription_discount_type = "discount" THEN "bill discount"
											WHEN subscriptions.subscription_discount_type = "discount_delivery" THEN "delivery discount"
										ELSE NULL END AS type
				        			')
                                )->first();

                $detail->join('subscription_user_vouchers', 'transactions.id_transaction', 'subscription_user_vouchers.id_transaction')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->where('subscription_users.id_subscription', $id_promo);
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

        if (!$data_promo) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Promo tidak ditemukan'
                ]
            ];
        }

        switch ($data_promo['type']) {
            case 'bill discount':
                $detail->whereNotNull('transactions.transaction_discount_bill')
                        ->where('transactions.transaction_discount_bill', '!=', 0)
                        ->addSelect(
                            DB::raw('
		    					transaction_products.id_transaction,
		    					SUM( 
		    						( (transaction_product_modifiers.transaction_product_modifier_price) * transaction_product_modifiers.qty)
		    						/transactions.transaction_subtotal * transactions.transaction_discount_bill 
		    					) AS total_discount,

		    					SUM( 
		    						( (transaction_product_modifiers.transaction_product_modifier_price) * transaction_product_modifiers.qty)
		    						/transactions.transaction_subtotal * transactions.transaction_discount_bill 
		    					)/ SUM(transaction_product_modifiers.qty) AS average_discount

		    				')
                        );
                break;

            case 'payment method':
                $detail->join('transaction_payment_subscriptions', 'transactions.id_transaction', 'transaction_payment_subscriptions.id_transaction')
                        ->addSelect(
                            DB::raw('
		    					SUM( 
		    						( (transaction_product_modifiers.transaction_product_modifier_price) * transaction_product_modifiers.qty)
		    						/transactions.transaction_subtotal * transaction_payment_subscriptions.subscription_nominal 
		    					) AS total_discount,

		    					SUM( 
		    						( (transaction_product_modifiers.transaction_product_modifier_price) * transaction_product_modifiers.qty)
		    						/transactions.transaction_subtotal * transaction_payment_subscriptions.subscription_nominal 
		    					)/ SUM(transaction_product_modifiers.qty) AS average_discount

		    				')
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

        $order = $post['order'] ?? 'product_code';
        $orderType = $post['order_type'] ?? 'asc';
        $detail = $detail->orderBy($order, $orderType);

        $detail = $detail->get()->toArray();

        if ($detail) {
            $pct = new PromoCampaignTools();
            foreach ($detail as &$value) {
                $value['variant_group'] = null;
                $value['price_now'] = ($pct->getProductModifierPrice($request->id_outlet, $value['id_product_modifier']) ?? null);
            }
        }

        $result['data'] = $detail;
        $result['data_promo'] = $data_promo;
        $result = MyHelper::checkGet($result);

        return $result;
    }

    public function listPromoV2($promo, Request $request)
    {
        $post = $request->json()->all();
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $select_trx = '
			COUNT(transactions.id_transaction) AS total_transaction, 
			SUM(transactions.transaction_gross) AS total_gross_sales, 
			SUM(
				CASE WHEN transactions.transaction_shipment IS NOT NULL AND transactions.transaction_shipment != 0 THEN transactions.transaction_shipment 
					WHEN transactions.transaction_shipment_go_send IS NOT NULL AND transactions.transaction_shipment_go_send != 0 THEN transactions.transaction_shipment_go_send
				ELSE 0 END
			) AS total_delivery_fee,
			COUNT(CASE WHEN transaction_pickups.pickup_by != "Customer" THEN 1 ELSE NULL END) AS total_transaction_delivery,
			COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" THEN 1 ELSE NULL END) AS total_transaction_pickup
		';

        $total_discount_promo = '
			SUM(
				CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
			) AS total_discount,
			SUM(
				CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
				+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
			)/COUNT(transactions.id_transaction) AS average_discount
		';

        switch ($promo) {
            case 'deals':
                $list = TransactionVoucher::join('transactions', 'transactions.id_transaction', 'transaction_vouchers.id_transaction')
                        ->join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
                        ->join('deals', 'deals_vouchers.id_deals', 'deals.id_deals')
                        ->groupBy('deals_vouchers.id_deals')
                        ->select(
                            'deals_vouchers.id_deals AS id_promo',
                            'deals.deals_title AS title',
                            'deals.promo_type',
                            DB::raw('
		        				CASE WHEN deals.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN deals.promo_type = "Discount bill" THEN "bill discount"
									WHEN deals.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END AS type
		        			'),
                            DB::raw($select_trx),
                            DB::raw($total_discount_promo)
                        );

                break;

            case 'promo-campaign':
                $list = Transaction::join('promo_campaign_promo_codes', 'transactions.id_promo_campaign_promo_code', 'promo_campaign_promo_codes.id_promo_campaign_promo_code')
                        ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign')
                        ->groupBy('promo_campaigns.id_promo_campaign')
                        ->select(
                            'promo_campaigns.id_promo_campaign AS id_promo',
                            'promo_campaigns.promo_title AS title',
                            'promo_campaigns.promo_type',
                            DB::raw('
		        				CASE WHEN promo_campaigns.promo_type IN ("Product discount","Tier discount","Buy X Get Y") THEN "product discount"
									WHEN promo_campaigns.promo_type = "Discount bill" THEN "bill discount"
									WHEN promo_campaigns.promo_type = "Discount delivery" THEN "delivery discount"
								ELSE NULL END AS type
		        			'),
                            DB::raw($select_trx),
                            DB::raw($total_discount_promo)
                        );
                break;

            case 'subscription':
                $list = SubscriptionUserVoucher::join('transactions', 'transactions.id_transaction', 'subscription_user_vouchers.id_transaction')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                        ->leftJoin('transaction_payment_subscriptions', 'transaction_payment_subscriptions.id_transaction', 'transactions.id_transaction')
                        ->groupBy('subscriptions.id_subscription')
                        ->select(
                            'subscriptions.id_subscription AS id_promo',
                            'subscriptions.subscription_title AS title',
                            'subscriptions.subscription_discount_type',
                            DB::raw('
		        				CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN "payment method"
									WHEN subscriptions.subscription_discount_type = "discount" THEN "bill discount"
									WHEN subscriptions.subscription_discount_type = "discount_delivery" THEN "delivery discount"
								ELSE NULL END AS type,

								CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN 
									SUM(transaction_payment_subscriptions.subscription_nominal)
								ELSE
									SUM(
										CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
									)
								END AS total_discount,

								CASE WHEN subscriptions.subscription_discount_type = "payment_method" THEN 
									SUM(transaction_payment_subscriptions.subscription_nominal)
								ELSE
									SUM(
										CASE WHEN transactions.transaction_discount != 0 THEN ABS(transactions.transaction_discount) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_delivery != 0 THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
										+ CASE WHEN transactions.transaction_discount_bill != 0 THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
									)
								END / COUNT(transactions.id_transaction) AS average_discount
		        			'),
                            DB::raw($select_trx)
                        );
                break;

            case 'bundling':
                $list = TransactionBundlingProduct::join('transactions', 'transactions.id_transaction', 'transaction_bundling_products.id_transaction')
                        ->join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
                        ->groupBy('transaction_bundling_products.id_bundling')
                        ->select(
                            'transaction_bundling_products.id_bundling AS id_promo',
                            'bundling.bundling_name AS title',
                            DB::raw('"product discount" AS type'),
                            DB::raw($select_trx),
                            DB::raw('
		        				SUM(transaction_bundling_products.transaction_bundling_product_total_discount) AS total_discount,
		        				SUM(transaction_bundling_products.transaction_bundling_product_total_discount)/COUNT(transactions.id_transaction) AS average_discount
		        			')
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

        $list = $list->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('transaction_pickups.reject_at')
                ->where('transactions.id_outlet', $request->id_outlet);

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $list = $list->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        $sub = $list;
        $query = DB::table(DB::raw('(' . $sub->toSql() . ') AS report_promo'))
                ->mergeBindings($sub->getQuery());

        $this->filterPromoReport($query, $post);

        if ($request->export) {
            $query = $query->get();
        } else {
            if (is_array($orders = $request->order)) {
                $columns = [
                    'title',
                    'type',
                    'total_transaction',
                    'total_gross_sales',
                    'total_delivery_fee',
                    'total_discount',
                    'total_transaction_delivery',
                    'total_transaction_pickup',
                    'average_discount'
                ];
                foreach ($orders as $column) {
                    if ($colname = ($columns[$column['column']] ?? false)) {
                        $query->orderBy($colname, $column['dir']);
                    }
                }
            }

            $query = $query->paginate($request->length ?: 10);
        }

        if (!$query) {
            return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        $result = $query->toArray();

        if (isset($result['data'])) {
            foreach ($result['data'] as &$value) {
                $value->id_promo = MyHelper::encSlug($value->id_promo);
            }
        }

        return MyHelper::checkGet($result);
    }
}
