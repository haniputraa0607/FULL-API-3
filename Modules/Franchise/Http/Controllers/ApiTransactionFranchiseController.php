<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Deal;
use Modules\Franchise\Entities\TransactionProductModifier;
use Modules\Franchise\Entities\Configs;
use Modules\Franchise\Entities\Transaction;
use Modules\Franchise\Entities\TransactionProduct;
use Modules\Franchise\Entities\Product;
use Modules\Franchise\Entities\Setting;
use Modules\Franchise\Entities\TransactionPaymentManual;
use Modules\Franchise\Entities\TransactionPaymentOffline;
use Modules\Franchise\Entities\TransactionPaymentBalance;
use Modules\Franchise\Entities\MDR;
use Modules\Franchise\Entities\TransactionPaymentIpay88;
use Modules\Franchise\Entities\TransactionMultiplePayment;
use Modules\Franchise\Entities\OutletConnection3;
use Modules\Franchise\Entities\LogBalance;
use Modules\Franchise\Entities\TransactionShipment;
use Modules\Franchise\Entities\TransactionPickup;
use Modules\Franchise\Entities\TransactionPaymentMidtran;
use Modules\Franchise\Entities\ProductVariant;
use Modules\Franchise\Entities\TransactionProductVariant;
use Modules\Franchise\Entities\TransactionPaymentShopeePay;
use Modules\Franchise\Entities\DealsUser;
use Modules\Franchise\Entities\DealsPaymentMidtran;
use Modules\Franchise\Entities\DealsPaymentManual;
use Modules\Franchise\Entities\DealsPaymentIpay88;
use Modules\Franchise\Entities\DealsPaymentShopeePay;
use Modules\Franchise\Entities\Brand;
use Modules\Franchise\Entities\BrandOutlet;
use Modules\Franchise\Entities\BrandProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\SubscriptionUserVoucher;
use Modules\Franchise\Entities\LogInvalidTransaction;
use Modules\Franchise\Entities\TransactionBundlingProduct;
use Modules\Transaction\Http\Requests\TransactionDetail;
use Modules\Transaction\Http\Requests\TransactionFilter;
use Modules\Franchise\Entities\ProductVariantGroup;
use Modules\Franchise\Entities\DisburseOutletTransaction;
use App\Jobs\ExportFranchiseJob;
use App\Lib\MyHelper;
use DB;
use App\Exports\MultipleSheetExport;
use Illuminate\Support\Facades\Storage;
use File;
use Modules\Franchise\Entities\ExportFranchiseQueue;

class ApiTransactionFranchiseController extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->disburse = "Modules\Disburse\Http\Controllers\ApiDisburseController";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiTransaction";
    }

    /**
     * Display list of transactions
     * @param Request $request
     * return Response
     */
    public function transactionFilter(TransactionFilter $request)
    {
        $post = $request->json()->all();

        $conditions = [];
        $rule = '';
        $search = '';
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $delivery = false;
        if (strtolower($post['key']) == 'delivery') {
            $post['key'] = 'pickup order';
            $delivery = true;
        }

        $query = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->select(
            'transactions.*',
            'transaction_pickups.*',
            'transaction_pickup_go_sends.*',
            'transaction_pickup_wehelpyous.*',
            'transaction_products.*',
            'users.*',
            'products.*',
            'product_categories.*',
            'outlets.outlet_code',
            'outlets.outlet_name'
        )
            ->join('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
            ->leftJoin('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
            ->leftJoin('transaction_pickup_go_sends', 'transaction_pickups.id_transaction_pickup', '=', 'transaction_pickup_go_sends.id_transaction_pickup')
            ->leftJoin('transaction_pickup_wehelpyous', 'transaction_pickups.id_transaction_pickup', '=', 'transaction_pickup_wehelpyous.id_transaction_pickup')
            ->leftJoin('transaction_products', 'transactions.id_transaction', '=', 'transaction_products.id_transaction')
            ->leftJoin('users', 'transactions.id_user', '=', 'users.id')
            ->leftJoin('products', 'products.id_product', '=', 'transaction_products.id_product')
            // ->leftJoin('brand_product','brand_product.id_product','=','transaction_products.id_product')
            ->leftJoin('product_categories', 'products.id_product_category', '=', 'product_categories.id_product_category')
            ->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end)
            ->where('transactions.transaction_payment_status', 'Completed')
            // ->whereNull('transaction_pickups.reject_at')
            ->with('user')
            // ->orderBy('transactions.id_transaction', 'DESC')
            ->groupBy('transactions.id_transaction');

        if (isset($post['id_outlet'])) {
            $query->where('transactions.id_outlet', $post['id_outlet']);
        }

        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if ($delivery) {
                $query->where('pickup_by', '<>', 'Customer');
            } else {
                $query->where('pickup_by', 'Customer');
            }
        }

        $query = $this->filterTransaction($query, $post);

        if (isset($post['conditions'])) {
            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'transaction_date',
                // 'outlet_code',
                'trasaction_type',
                'transaction_receipt_number',
                'transaction_grandtotal',
                null,
                null,
                null,
            ];
            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $query->orderBy($colname, $column['dir']);
                }
            }
        } else {
            $query->orderBy('transactions.id_transaction', 'DESC');
        }

        $akhir = $query->paginate($request->length ?: 10);

        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    /**
     * Create a new export queue
     * @param  Request $request
     * @return Response
     */
    public function newExport(Request $request)
    {
        $post = $request->json()->all();
        unset($post['filter']['_token']);

        $insertToQueue = [
            'id_user_franchise' => $request->user()->id_user_franchise,
            'id_outlet' => $post['id_outlet'],
            'filter' => json_encode($post['filter']),
            'report_type' => 'Transaction',
            'status_export' => 'Running'
        ];

        $create = ExportFranchiseQueue::create($insertToQueue);
        if ($create) {
            ExportFranchiseJob::dispatch($create)->allOnConnection('export_franchise_queue');
        }
        return response()->json(MyHelper::checkCreate($create));
    }

    /**
     * Display list of exported transaction
     * @param Request $request
     * return Response
     */
    public function listExport(Request $request)
    {
        $result = ExportFranchiseQueue::where('report_type', 'Transaction');
        if ($request->id_outlet) {
            $result->where('id_outlet', $request->id_outlet);
        } else {
            $result->where('id_user_franchise', $request->user()->id_user_franchise);
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'created_at',
                 null,
                'status_export',
            ];
            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->orderBy('id_export_franchise_queue', 'DESC');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            $countTotal = $result['total'];
            // needed for datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroyExport(ExportQueue $export_queue)
    {
        $filename = str_replace([env('STORAGE_URL_API') . 'download/', env('STORAGE_URL_API')], '', $export_queue->url_export);
        $delete = Storage::delete($filename);
        if ($delete) {
            $export_queue->status_export = 'Deleted';
            $export_queue->save();
        }
        return MyHelper::checkDelete($delete);
    }

    // public function exportExcel($post){
    public function exportExcel($queue)
    {

        $queue = ExportFranchiseQueue::where('id_export_franchise_queue', $queue->id_export_franchise_queue)->where('status_export', 'Running')->first();

        if (!$queue) {
            return false;
        } else {
            $queue = $queue->toArray();
        }

        $filter = (array)json_decode($queue['filter'], true);

        $data['date_start'] = $filter['rule']['9998']['parameter'] ?? date('Y-m-01');
        $data['date_end'] = $filter['rule']['9999']['parameter'] ?? date('Y-m-d');
        $data['rule'] = $filter['operator'] ?? 'and';
        $data['conditions'] = $filter['conditions'] ?? [];
        $data['key'] = 'all';

        if (isset($filter['rule']['9998'])) {
            unset($filter['rule']['9998'], $filter['rule']['9999']);
            $data['conditions'] = $filter['rule'];
        }

        $post = $data;

        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));

        $getOutlet = OutletConnection3::where('id_outlet', $queue['id_outlet'])->first();

        if ($getOutlet && !empty($getOutlet['outlet_email'])) {
            $filter['date_start'] = $start;
            $filter['date_end'] = $end;
            $filter['detail'] = 1;
            $filter['key'] = 'all';
            $filter['rule'] = 'and';
            $filter['conditions'] = [
                [
                    'subject' => 'id_outlet',
                    'operator' => $getOutlet['id_outlet'],
                    'parameter' => null
                ],
                [
                    'subject' => 'status',
                    'operator' => 'Completed',
                    'parameter' => null
                ]
            ];

            $filter['conditions'] = array_merge($filter['conditions'], $post['conditions']);

            $summary = app('Modules\Disburse\Http\Controllers\ApiDisburseController')->summaryCalculationFee(null, $start, $end, $getOutlet['id_outlet'], 0, $post);
            $generateTrx = app('Modules\Transaction\Http\Controllers\ApiTransaction')->exportTransaction($filter, 1, 'franchise');
            $dataDisburse = app('Modules\Disburse\Http\Controllers\ApiDisburseController')->summaryDisburse(null, $start, $end, $getOutlet['id_outlet'], 0, $post);

            if (!empty($generateTrx['list'])) {
                $excelFile = 'Transaction_[' . $start . '_' . $end . '][' . $getOutlet['outlet_code'] . ']_' . mt_rand(0, 1000) . time() . '.xlsx';
                $directory = 'franchise/report/transaction/' . $excelFile;

                $store  = (new MultipleSheetExport([
                    "Summary" => $summary,
                    "Calculation Fee" => $dataDisburse,
                    "Detail Transaction" => $generateTrx
                ]))->store($directory);


                if ($store) {
                    $path = storage_path('app/' . $directory);
                    $contents = File::get($path);
                    if (config('configs.STORAGE') != 'local') {
                        $store = Storage::disk(config('configs.STORAGE'))->put($directory, $contents, 'public');
                    }
                    $delete = File::delete($path);
                    ExportFranchiseQueue::where('id_export_franchise_queue', $queue['id_export_franchise_queue'])->update(['url_export' => $directory, 'status_export' => 'Ready']);
                }
            }

            return 'success';
        } else {
            return 'Outlet Not Found';
        }
    }

    public function listProduct(Request $request)
    {
        $post = $request->json()->all();
        $id_brand = BrandOutlet::where('id_outlet', $post['id_outlet'])->pluck('id_brand');

        $product = BrandProduct::whereIn('id_brand', $id_brand)
                    ->join('products', 'products.id_product', '=', 'brand_product.id_product')
                    ->whereNotNull('brand_product.id_product_category')
                    ->get()
                    ->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    public function listProductCategory(Request $request)
    {
        $post = $request->json()->all();
        $id_brand = BrandOutlet::where('id_outlet', $post['id_outlet'])->pluck('id_brand');

        $product = BrandProduct::whereIn('id_brand', $id_brand)
                    ->join('product_categories', 'product_categories.id_product_category', '=', 'brand_product.id_product_category')
                    ->whereNotNull('brand_product.id_product_category')
                    ->groupBy('brand_product.id_product_category')
                    ->get()
                    ->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    public function summaryCalculationFee($date_start, $date_end, $id_outlet = null, $filter = [])
    {

        $summaryFee = [];
        $summaryFee = DisburseOutletTransaction::join('transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_subscriptions as tps', 'tps.id_transaction', 'transactions.id_transaction')
            ->whereDate('transactions.transaction_date', '>=', $date_start)
            ->whereDate('transactions.transaction_date', '<=', $date_end)
            ->where('transactions.transaction_payment_status', 'Completed')
            // ->whereNull('transaction_pickups.reject_at')
            ->selectRaw('COUNT(transactions.id_transaction) total_trx, SUM(transactions.transaction_grandtotal) as total_gross_sales,
                        SUM(tps.subscription_nominal) as total_subscription, 
                        SUM(bundling_product_total_discount) as total_discount_bundling,
                        SUM(transactions.transaction_subtotal) as total_sub_total, 
                        SUM(transactions.transaction_shipment_go_send) as total_delivery, SUM(transactions.transaction_discount) as total_discount, 
                        SUM(fee_item) total_fee_item, SUM(payment_charge) total_fee_pg, SUM(income_outlet) total_income_outlet,
                        SUM(discount_central) total_income_promo, SUM(subscription_central) total_income_subscription, SUM(bundling_product_fee_central) total_income_bundling_product,
                        SUM(transactions.transaction_discount_delivery) total_discount_delivery');

        if ($id_outlet) {
            $summaryFee = $summaryFee->where('transactions.id_outlet', $id_outlet);
        }

        $summaryFee = $this->filterTransaction($summaryFee, $filter);

        $summaryFee = $summaryFee->first()->toArray();

        $config = Configs::where('config_name', 'show or hide info calculation disburse')->first();

        $summaryProduct = TransactionProduct::join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->join('products as p', 'p.id_product', 'transaction_products.id_product')
            ->where('transaction_payment_status', 'Completed')
            // ->whereNull('reject_at')
            ->whereDate('transaction_date', '>=', $date_start)
            ->whereDate('transaction_date', '<=', $date_end)
            ->groupBy('transaction_products.id_product_variant_group')
            ->groupBy('transaction_products.id_product')
            ->selectRaw("p.product_name as name, SUM(transaction_products.transaction_product_qty) as total_qty,
                        p.product_type as type,
                        (SELECT GROUP_CONCAT(pv.`product_variant_name` SEPARATOR ',') FROM `product_variant_groups` pvg
                        JOIN `product_variant_pivot` pvp ON pvg.`id_product_variant_group` = pvp.`id_product_variant_group`
                        JOIN `product_variants` pv ON pv.`id_product_variant` = pvp.`id_product_variant`
                        WHERE pvg.`id_product_variant_group` = transaction_products.id_product_variant_group) as variants");

        if ($id_outlet) {
            $summaryProduct = $summaryProduct->where('transactions.id_outlet', $id_outlet);
        }

        $summaryProduct = $this->filterTransaction($summaryProduct, $filter);

        $summaryProduct = $summaryProduct->get()->toArray();

        $summaryModifier = TransactionProductModifier::join('transactions', 'transactions.id_transaction', 'transaction_product_modifiers.id_transaction')
            ->join('transaction_products as tp', 'tp.id_transaction_product', 'transaction_product_modifiers.id_transaction_product')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->join('product_modifiers as pm', 'pm.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
            ->where('transaction_payment_status', 'Completed')
            // ->whereNull('reject_at')
            ->whereNull('transaction_product_modifiers.id_product_modifier_group')
            ->whereDate('transaction_date', '>=', $date_start)
            ->whereDate('transaction_date', '<=', $date_end)
            ->groupBy('transaction_product_modifiers.id_product_modifier')
            ->selectRaw("pm.text as name, 'Modifier' as type, SUM(transaction_product_modifiers.qty * tp.transaction_product_qty) as total_qty,
                        NULL as variants");

        if ($id_outlet) {
            $summaryModifier = $summaryModifier->where('transactions.id_outlet', $id_outlet);
        }

        $summaryModifier = $this->filterTransaction($summaryModifier, $filter);

        $summaryModifier = $summaryModifier->get()->toArray();

        $summary = array_merge($summaryProduct, $summaryModifier);
        return [
            'summary_product' => $summary,
            'summary_fee' => $summaryFee,
            'config' => $config
        ];
    }

    public function actionExport(Request $request)
    {
        $post = $request->json()->all();
        $action = $post['action'];
        $id_export_franchise_queue = $post['id_export_franchise_queue'];

        if ($action == 'download') {
            $data = ExportFranchiseQueue::where('id_export_franchise_queue', $id_export_franchise_queue)->first();
            if (!empty($data)) {
                $data['url_export'] = config('url.storage_url_api') . $data['url_export'];
            }
            return response()->json(MyHelper::checkGet($data));
        } elseif ($action == 'deleted') {
            $data = ExportQueue::where('id_export_franchise_queue', $id_export_franchise_queue)->first();
            $file = public_path() . '/' . $data['url_export'];
            if (config('configs.STORAGE') == 'local') {
                $delete = File::delete($file);
            } else {
                $delete = MyHelper::deleteFile($file);
            }

            if ($delete) {
                $update = ExportFranchiseQueue::where('id_export_franchise_queue', $id_export_franchise_queue)->update(['status_export' => 'Deleted']);
                return response()->json(MyHelper::checkUpdate($update));
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['failed to delete file']]);
            }
        }
    }

    public function filterTransaction($query, $filter)
    {
        if (isset($filter['conditions'])) {
            $query->where(function ($query) use ($filter) {
                foreach ($filter['conditions'] as $key => $con) {
                    if (is_object($con)) {
                        $con = (array)$con;
                    }
                    if (isset($con['subject'])) {
                        if ($con['subject'] == 'receipt') {
                            $var = 'transactions.transaction_receipt_number';
                        } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                            $var = 'users.' . $con['subject'];
                        } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                            $var = 'products.' . $con['subject'];
                        } elseif ($con['subject'] == 'product_category') {
                            $var = 'product_categories.product_category_name';
                        } elseif ($con['subject'] == 'order_id') {
                            $var = 'transaction_pickups.order_id';
                        }

                        if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                            $var = 'outlets.' . $con['subject'];
                            if ($filter['rule'] == 'and') {
                                if ($con['operator'] == 'like') {
                                    $query = $query->where($var, 'like', '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->where($var, '=', $con['parameter']);
                                }
                            } else {
                                if ($con['operator'] == 'like') {
                                    $query = $query->orWhere($var, 'like', '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->orWhere($var, '=', $con['parameter']);
                                }
                            }
                        }
                        if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category', 'order_id'])) {
                            if ($filter['rule'] == 'and') {
                                if ($con['operator'] == 'like') {
                                    $query = $query->where($var, 'like', '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->where($var, '=', $con['parameter']);
                                }
                            } else {
                                if ($con['operator'] == 'like') {
                                    $query = $query->orWhere($var, 'like', '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->orWhere($var, '=', $con['parameter']);
                                }
                            }
                        }

                        if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                            $var = 'products.' . $con['subject'];
                            if ($filter['rule'] == 'and') {
                                $query = $query->where($var, $con['operator'], $con['parameter']);
                            } else {
                                $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                            }
                        }

                        if ($con['subject'] == 'reject_reason') {
                            $var = 'transaction_pickups.reject_reason';
                            if ($filter['rule'] == 'and') {
                                if ($con['operator'] == 'like' || $con['operator'] == 'not like') {
                                    $query = $query->where($var, $con['operator'], '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->where($var, $con['operator'], $con['parameter']);
                                }
                            } else {
                                if ($con['operator'] == 'like' || $con['operator'] == 'not like') {
                                    $query = $query->orWhere($var, $con['operator'], '%' . $con['parameter'] . '%');
                                } else {
                                    $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                                }
                            }
                        }

                        if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                            if ($con['subject'] == 'grand_total') {
                                $var = 'transactions.transaction_grandtotal';
                            } else {
                                $var = 'transactions.transaction_tax';
                            }

                            if ($filter['rule'] == 'and') {
                                $query = $query->where($var, $con['operator'], $con['parameter']);
                            } else {
                                $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                            }
                        }

                        if ($con['subject'] == 'transaction_status') {
                            if ($filter['rule'] == 'and') {
                                $where = "where";
                            } else {
                                $where = "orWhere";
                            }

                            $query = $query->$where(function ($query) use ($con) {
                                            $query_pending = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNull('transaction_pickups.receive_at')
                                                        ->whereNull('transaction_pickups.ready_at')
                                                        ->whereNull('transaction_pickups.taken_at')
                                                        ->whereNull('transaction_pickups.taken_by_system_at')
                                                        ->whereNull('transaction_pickups.reject_at');
                                                });
                                            };

                                            $query_taken_by_driver = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.taken_at')
                                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                                });
                                            };

                                            $query_taken_by_customer = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.taken_at')
                                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                                });
                                            };

                                            $query_taken_by_system = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.ready_at')
                                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                                });
                                            };

                                            $query_receive_at = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.receive_at')
                                                        ->whereNull('transaction_pickups.ready_at');
                                                });
                                            };

                                            $query_ready_at = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.ready_at')
                                                        ->whereNull('transaction_pickups.taken_at');
                                                });
                                            };

                                            $query_reject = function ($q, $where = 'where') {
                                                $q->$where(function ($q2) {
                                                    $q2->whereNotNull('transaction_pickups.reject_at');
                                                });
                                            };


                                if ($con['operator'] == 'not') {
                                    if ($con['parameter'] == 'pending') {
                                        $query = $query->where(function ($q) use ($query_receive_at, $query_reject) {
                                            $query_receive_at($q, 'orWhere');
                                            $query_reject($q, 'orWhere');
                                        });
                                    } elseif ($con['parameter'] == 'taken_by_driver') {
                                        $query = $query->where(function ($q) use ($query_ready_at, $query_receive_at, $query_pending) {
                                                    $query_receive_at($q, 'orWhere');
                                                    $query_ready_at($q, 'orWhere');
                                                    $query_pending($q, 'orWhere');
                                        })->whereNull('transaction_pickups.reject_at');
                                    } elseif ($con['parameter'] == 'taken_by_customer') {
                                        $query = $query->where(function ($q) use ($query_ready_at, $query_receive_at, $query_pending) {
                                                    $query_receive_at($q, 'orWhere');
                                                    $query_ready_at($q, 'orWhere');
                                                    $query_pending($q, 'orWhere');
                                        })->whereNull('transaction_pickups.reject_at');
                                    } elseif ($con['parameter'] == 'taken_by_system') {
                                        $query = $query->where(function ($q) use ($query_ready_at, $query_receive_at, $query_pending) {
                                                    $query_receive_at($q, 'orWhere');
                                                    $query_ready_at($q, 'orWhere');
                                                    $query_pending($q, 'orWhere');
                                        })->whereNull('transaction_pickups.reject_at');
                                    } elseif ($con['parameter'] == 'receive_at') {
                                        $query = $query->where(function ($q) {
                                                    $q->whereNull('transaction_pickups.receive_at')
                                                        ->whereNull('transaction_pickups.ready_at')
                                                        ->whereNull('transaction_pickups.taken_at')
                                                        ->whereNull('transaction_pickups.taken_by_system_at');
                                        });
                                    } elseif ($con['parameter'] == 'ready_at') {
                                        $query = $query->where(function ($q) use ($query_receive_at, $query_pending) {
                                                    $query_receive_at($q, 'orWhere');
                                                    $query_pending($q, 'orWhere');
                                        })->whereNull('transaction_pickups.reject_at');
                                    } elseif ($con['parameter'] == 'manual_reject') {
                                        $query = $query->whereNull('transaction_pickups.receive_at')
                                                ->where('transaction_pickups.reject_reason', 'not like', '%auto reject order by system%');
                                    } elseif ($con['parameter'] == 'auto_reject') {
                                        $query = $query->where('transaction_pickups.reject_reason', 'like', 'auto reject order by system');
                                    } else {
                                        $query = $query->whereNull('transaction_pickups.' . $con['parameter']);
                                    }
                                    /*if($con['parameter'] == 'pending'){
                                        $query = $query->whereNotNull('transaction_pickups.receive_at')
                                                ->orWhereNotNull('transaction_pickups.ready_at')
                                                ->orWhereNotNull('transaction_pickups.taken_at')
                                                ->orWhereNotNull('transaction_pickups.taken_by_system_at')
                                                ->orWhereNotNull('transaction_pickups.reject_at');

                                    }
                                    elseif($con['parameter'] == 'taken_by_driver'){
                                        $query = $query->whereNull('transaction_pickups.taken_at')
                                            ->whereIn('transaction_pickups.pickup_by', ['Customer']);
                                    }
                                    elseif ($con['parameter'] == 'taken_by_customer'){
                                        $query = $query->whereNull('transaction_pickups.taken_at')
                                            ->where('transaction_pickups.pickup_by', '!=', 'Customer');
                                    }
                                    elseif ($con['parameter'] == 'taken_by_system'){
                                        $query = $query->whereNull('transaction_pickups.ready_at')
                                            ->whereNull('transaction_pickups.taken_by_system_at');
                                    }
                                    elseif($con['parameter'] == 'receive_at'){
                                        $query = $query->whereNull('transaction_pickups.receive_at')
                                            ->whereNotNull('transaction_pickups.ready_at');
                                    }
                                    elseif($con['parameter'] == 'ready_at'){
                                        $query = $query->whereNull('transaction_pickups.ready_at')
                                            ->whereNotNull('transaction_pickups.taken_at');
                                    }
                                    else{
                                        $query = $query->whereNull('transaction_pickups.'.$con['parameter']);
                                    }*/
                                } else {
                                    if ($con['parameter'] == 'pending') {
                                        $query = $query->whereNull('transaction_pickups.receive_at')
                                                ->whereNull('transaction_pickups.ready_at')
                                                ->whereNull('transaction_pickups.taken_at')
                                                ->whereNull('transaction_pickups.taken_by_system_at')
                                                ->whereNull('transaction_pickups.reject_at');
                                    } elseif ($con['parameter'] == 'taken_by_driver') {
                                        $query = $query->whereNotNull('transaction_pickups.taken_at')
                                                ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                    } elseif ($con['parameter'] == 'taken_by_customer') {
                                        $query = $query->whereNotNull('transaction_pickups.taken_at')
                                                ->where('transaction_pickups.pickup_by', 'Customer');
                                    } elseif ($con['parameter'] == 'taken_by_system') {
                                        $query = $query->whereNotNull('transaction_pickups.ready_at')
                                                ->whereNotNull('transaction_pickups.taken_by_system_at');
                                    } elseif ($con['parameter'] == 'receive_at') {
                                        $query = $query->whereNotNull('transaction_pickups.receive_at')
                                                ->whereNull('transaction_pickups.ready_at');
                                    } elseif ($con['parameter'] == 'ready_at') {
                                        $query = $query->whereNotNull('transaction_pickups.ready_at')
                                                ->whereNull('transaction_pickups.taken_at');
                                    } elseif ($con['parameter'] == 'manual_reject') {
                                        $query = $query->whereNull('transaction_pickups.receive_at')
                                                ->where('transaction_pickups.reject_reason', 'not like', '%auto reject order by system%');
                                    } elseif ($con['parameter'] == 'auto_reject') {
                                        $query = $query->where('transaction_pickups.reject_reason', 'like', 'auto reject order by system');
                                    } else {
                                        $query = $query->whereNotNull('transaction_pickups.' . $con['parameter']);
                                    }
                                }
                            });
                        }

                        if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'id_product_category'])) {
                            switch ($con['subject']) {
                                case 'status':
                                    $var = 'transactions.transaction_payment_status';
                                    break;

                                case 'courier':
                                    $var = 'transactions.transaction_courier';
                                    break;

                                case 'id_product':
                                    $var = 'products.id_product';
                                    $con['operator'] = $con['parameter'];
                                    break;

                                case 'id_outlet':
                                    $var = 'outlets.id_outlet';
                                    break;

                                case 'id_product_category':
                                    // $var = 'brand_product.id_product_category';
                                    $var = 'products.id_product_category';
                                    $con['operator'] = $con['parameter'];
                                    break;

                                default:
                                    continue 2;
                            }

                            if ($filter['rule'] == 'and') {
                                $query = $query->where($var, '=', $con['operator']);
                            } else {
                                $query = $query->orWhere($var, '=', $con['operator']);
                            }
                        }

                        if ($con['subject'] == 'pickup_by') {
                            $var = 'transaction_pickups.pickup_by';
                            if ($con['parameter'] == 'customer') {
                                $op = '=';
                            } else {
                                $op = '!=';
                            }

                            if ($filter['rule'] == 'and') {
                                $query = $query->where($var, $op, 'Customer');
                            } else {
                                $query = $query->orWhere($var, $op, 'Customer');
                            }
                        }
                    }
                }
            });
        }

        return $query;
    }

    public function exportTransaction($filter, $statusReturn = null)
    {
        $post = $filter;

        $delivery = false;
        if (strtolower($post['key']) == 'delivery') {
            $post['key'] = 'pickup order';
            $delivery = true;
        }

        $query = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->select('transaction_pickups.*', 'transactions.*', 'users.*', 'outlets.outlet_code', 'outlets.outlet_name', 'payment_type', 'payment_method', 'transaction_payment_midtrans.gross_amount', 'transaction_payment_ipay88s.amount', 'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay')
            ->leftJoin('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
            ->leftJoin('users', 'transactions.id_user', '=', 'users.id')
            ->orderBy('transactions.transaction_date', 'asc');

        $query = $query->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction');

        $settingMDRAll = [];
        if (isset($post['detail']) && $post['detail'] == 1) {
            $settingMDRAll = MDR::get()->toArray();
            $query->leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
                ->join('transaction_products', 'transaction_products.id_transaction', '=', 'transactions.id_transaction')
                ->leftJoin('transaction_balances', 'transaction_balances.id_transaction', '=', 'transactions.id_transaction')
                ->join('products', 'products.id_product', 'transaction_products.id_product')
                ->join('brands', 'brands.id_brand', 'transaction_products.id_brand')
                ->leftJoin('product_categories', 'products.id_product_category', '=', 'product_categories.id_product_category')
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->leftJoin('cities as c', 'c.id_city', 'users.id_city')
                ->join('provinces', 'cities.id_province', 'provinces.id_province')
                ->leftJoin('transaction_bundling_products', 'transaction_products.id_transaction_bundling_product', '=', 'transaction_bundling_products.id_transaction_bundling_product')
                ->leftJoin('bundling', 'bundling.id_bundling', '=', 'transaction_bundling_products.id_bundling')
                ->with(['transaction_payment_subscription', 'vouchers', 'promo_campaign', 'point_refund', 'point_use', 'subscription_user_voucher.subscription_user.subscription'])
                ->orderBy('transaction_products.id_transaction_bundling_product', 'asc')
                ->addSelect(
                    'transaction_bundling_products.transaction_bundling_product_base_price',
                    'transaction_bundling_products.transaction_bundling_product_qty',
                    'transaction_bundling_products.transaction_bundling_product_total_discount',
                    'transaction_bundling_products.transaction_bundling_product_subtotal',
                    'bundling.bundling_name',
                    'disburse_outlet_transactions.bundling_product_fee_central',
                    'transaction_products.*',
                    'products.product_code',
                    'products.product_name',
                    'product_categories.product_category_name',
                    'brands.name_brand',
                    'cities.city_name',
                    'c.city_name as user_city',
                    'provinces.province_name',
                    'disburse_outlet_transactions.fee_item',
                    'disburse_outlet_transactions.payment_charge',
                    'disburse_outlet_transactions.discount',
                    'disburse_outlet_transactions.subscription',
                    'disburse_outlet_transactions.point_use_expense',
                    'disburse_outlet_transactions.income_outlet',
                    'disburse_outlet_transactions.discount_central',
                    'disburse_outlet_transactions.subscription_central'
                );
        }

        if (
            isset($post['date_start']) && !empty($post['date_start'])
            && isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start = date('Y-m-d', strtotime($post['date_start']));
            $end = date('Y-m-d', strtotime($post['date_end']));
        } else {
            $start = date('Y-m-01 00:00:00');
            $end = date('Y-m-d 23:59:59');
        }

        $query = $query->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end);

        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if ($delivery) {
                $query->where('pickup_by', '<>', 'Customer');
            } else {
                $query->where('pickup_by', 'Customer');
            }
        }

        $query = $this->filterTransaction($query, $post);

        if ($statusReturn == 1) {
            $columnsVariant = '';
            $addAdditionalColumnVariant = '';
            $getVariant = ProductVariant::whereNull('id_parent')->get()->toArray();
            $getAllVariant = ProductVariant::select('id_product_variant', 'id_parent')->get()->toArray();
            foreach ($getVariant as $v) {
                $columnsVariant .= '<td style="background-color: #dcdcdc;" width="10">' . $v['product_variant_name'] . '</td>';
                $addAdditionalColumnVariant .= '<td></td>';
            }
            // $query->whereNull('reject_at');

            $dataTrxDetail = '';
            $cek = '';
            $get = $query->get()->toArray();
            $count = count($get);
            $tmpBundling = '';
            $htmlBundling = '';
            foreach ($get as $key => $val) {
                $payment = '';
                if (!empty($val['payment_type'])) {
                    $payment = $val['payment_type'];
                } elseif (!empty($val['payment_method'])) {
                    $payment = $val['payment_method'];
                } elseif (!empty($val['id_transaction_payment_shopee_pay'])) {
                    $payment = 'Shopeepay';
                }

                $variant = [];
                $productCode = $val['product_code'];
                if (!empty($val['id_product_variant_group'])) {
                    $getProductVariantGroup = ProductVariantGroup::where('id_product_variant_group', $val['id_product_variant_group'])->first();
                    $productCode = $getProductVariantGroup['product_variant_group_code'] ?? '';
                }

                $modifier = TransactionProductModifier::where('id_transaction_product', $val['id_transaction_product'])
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->pluck('text')->toArray();

                if (isset($post['detail']) && $post['detail'] == 1) {
                    $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->where('transaction_product_modifiers.id_transaction_product', $val['id_transaction_product'])
                        ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                        ->select('product_modifiers.text', 'transaction_product_modifiers.transaction_product_modifier_price')->get()->toArray();

                    $addAdditionalColumn = '';
                    $promoName = '';
                    $promoType = '';
                    $promoCode = '';

                    $promoName2 = '';
                    $promoType2 = '';
                    $promoCode2 = '';
                    if (count($val['vouchers']) > 0) {
                        $getDeal = Deal::where('id_deals', $val['vouchers'][0]['id_deals'])->first();
                        if ($getDeal['promo_type'] == 'Discount bill' || $getDeal['promo_type'] == 'Discount delivery') {
                            $promoName2 = $getDeal['deals_title'];
                            $promoType2 = 'Deals';
                            $promoCode2 = $val['vouchers'][0]['voucher_code'];
                        } else {
                            $promoName = $getDeal['deals_title'];
                            $promoType = 'Deals';
                            $promoCode = $val['vouchers'][0]['voucher_code'];
                        }
                    } elseif (!empty($val['promo_campaign'])) {
                        if ($val['promo_campaign']['promo_type'] == 'Discount bill' || $val['promo_campaign']['promo_type'] == 'Discount delivery') {
                            $promoName2 = $val['promo_campaign']['promo_title'];
                            $promoType2 = 'Promo Campaign';
                            $promoCode2 = $val['promo_campaign']['promo_code'];
                        } else {
                            $promoName = $val['promo_campaign']['promo_title'];
                            $promoType = 'Promo Campaign';
                            $promoCode = $val['promo_campaign']['promo_code'];
                        }
                    } elseif (isset($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'])) {
                        $promoName2 = htmlspecialchars($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title']);
                        $promoType2 = 'Subscription';
                    }

                    $promoName = htmlspecialchars($promoName);
                    $status = $val['transaction_payment_status'];
                    if (!is_null($val['reject_at'])) {
                        $status = 'Reject';
                    }

                    $poinUse = '';
                    if (isset($val['point_use']) && !empty($val['point_use'])) {
                        $poinUse = $val['point_use']['balance'];
                    }

                    $pointRefund = '';
                    if (isset($val['point_refund']) && !empty($val['point_refund'])) {
                        $pointRefund = $val['point_refund']['balance'];
                    }

                    $paymentRefund = '';
                    if ($val['reject_type'] == 'payment') {
                        $paymentRefund = $val['amount'] ?? $val['gross_amount'];
                    }

                    $paymentCharge = 0;
                    if ((int)$val['point_use_expense'] > 0) {
                        $paymentCharge = $val['point_use_expense'];
                    }

                    if ((int)$val['payment_charge'] > 0) {
                        $paymentCharge = $val['payment_charge'];
                    }

                    $html = '';
                    $sameData = '';
                    $sameData .= '<td>' . $val['outlet_code'] . '</td>';
                    $sameData .= '<td>' . htmlspecialchars($val['outlet_name']) . '</td>';
                    $sameData .= '<td>' . $val['province_name'] . '</td>';
                    $sameData .= '<td>' . $val['city_name'] . '</td>';
                    $sameData .= '<td>' . $val['transaction_receipt_number'] . '</td>';
                    $sameData .= '<td>' . $status . '</td>';
                    $sameData .= '<td>' . $val['reject_reason'] . '</td>';
                    $sameData .= '<td>' . date('d M Y', strtotime($val['transaction_date'])) . '</td>';
                    $sameData .= '<td>' . date('H:i:s', strtotime($val['transaction_date'])) . '</td>';

                    //for check additional column
                    if (isset($post['show_product_code']) && $post['show_product_code'] == 1) {
                        $addAdditionalColumn = "<td></td>";
                    }

                    if (!empty($val['id_transaction_bundling_product'])) {
                        $totalModPrice = 0;
                        for ($j = 0; $j < $val['transaction_product_bundling_qty']; $j++) {
                            $priceMod = 0;
                            $textMod = '';
                            if (!empty($mod)) {
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            $htmlBundling .= '<td>' . $val['name_brand'] . '</td>';
                            $htmlBundling .= '<td>' . $val['product_category_name'] . '</td>';
                            if (isset($post['show_product_code']) && $post['show_product_code'] == 1) {
                                $htmlBundling .= '<td>' . $productCode . '</td>';
                            }
                            $htmlBundling .= '<td>' . $val['product_name'] . '</td>';
                            $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                                ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            foreach ($getTransactionVariant as $k => $gtV) {
                                $getTransactionVariant[$k]['main_parent'] = app($this->trx)->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            }
                            foreach ($getVariant as $v) {
                                $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                                if ($search !== false) {
                                    $htmlBundling .= '<td>' . $getTransactionVariant[$search]['product_variant_name'] . '</td>';
                                } else {
                                    $htmlBundling .= '<td></td>';
                                }
                            }
                            $totalModPrice = $totalModPrice + $priceMod;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>' . implode(",", $modifier) . '</td>';
                            $htmlBundling .= '<td>' . $textMod . '</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>' . $priceMod . '</td>';
                            $htmlBundling .= '<td>' . htmlspecialchars($val['transaction_product_note']) . '</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>' . $priceMod . '</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>' . ($priceMod) . '</td>';
                            $htmlBundling .= '<td></td><td></td><td></td>';
                            if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';

                            $totalMod = count($mod);
                            if ($totalMod > 1) {
                                for ($i = 1; $i < $totalMod; $i++) {
                                    $totalModPrice = $totalModPrice + $mod[$i]['transaction_product_modifier_price'] ?? 0;
                                    $htmlBundling .= '<tr>';
                                    $htmlBundling .= $sameData;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumn;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumnVariant;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>' . $mod[$i]['text'] ?? '' . '</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>' . $mod[$i]['transaction_product_modifier_price'] ?? (int)'0' . '</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>' . $mod[$i]['transaction_product_modifier_price'] . '</td>';
                                    $htmlBundling .= '<td>0</td>';
                                    $htmlBundling .= '<td>' . $mod[$i]['transaction_product_modifier_price'] . '</td>';
                                    $htmlBundling .= '<td></td><td></td><td></td>';
                                    if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $htmlBundling .= '<td></td><td></td><td></td>';
                                    }
                                    $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $htmlBundling .= '</tr>';
                                }
                            }
                        }

                        if ($key == ($count - 1) || (isset($get[$key + 1]) && $val['id_transaction_bundling_product'] != $get[$key + 1]['id_transaction_bundling_product'])) {
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            $htmlBundling .= '<td>Paket</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumn;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumnVariant;
                            $htmlBundling .= '<td>' . $val['bundling_name'] . '</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>' . (int)($val['transaction_bundling_product_base_price'] + $val['transaction_bundling_product_total_discount']) . '</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>' . (int)($val['transaction_bundling_product_base_price'] + $val['transaction_bundling_product_total_discount']) . '</td>';
                            $htmlBundling .= '<td>' . $val['transaction_bundling_product_total_discount'] . '</td>';
                            $htmlBundling .= '<td>' . (int)($val['transaction_bundling_product_base_price'] + $val['transaction_bundling_product_total_discount'] - $val['transaction_bundling_product_total_discount']) . '</td>';
                            $htmlBundling .= '<td></td><td></td><td></td>';
                            if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';
                            for ($bun = 1; $bun <= $val['transaction_bundling_product_qty']; $bun++) {
                                $html .= $htmlBundling;
                            }
                            $htmlBundling = "";
                        }

                        $tmpBundling = $val['id_transaction_bundling_product'];
                    } else {
                        for ($j = 0; $j < $val['transaction_product_qty']; $j++) {
                            $priceMod = 0;
                            $textMod = '';
                            if (!empty($mod)) {
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td>' . $val['name_brand'] . '</td>';
                            $html .= '<td>' . $val['product_category_name'] . '</td>';
                            if (isset($post['show_product_code']) && $post['show_product_code'] == 1) {
                                $html .= '<td>' . $productCode . '</td>';
                            }
                            $html .= '<td>' . $val['product_name'] . '</td>';
                            $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                                ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            foreach ($getTransactionVariant as $k => $gtV) {
                                $getTransactionVariant[$k]['main_parent'] = app($this->trx)->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            }
                            foreach ($getVariant as $v) {
                                $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                                if ($search !== false) {
                                    $html .= '<td>' . $getTransactionVariant[$search]['product_variant_name'] . '</td>';
                                } else {
                                    $html .= '<td></td>';
                                }
                            }
                            $priceProd = $val['transaction_product_price'] + (float)$val['transaction_variant_subtotal'];
                            $html .= '<td></td>';
                            $html .= '<td>' . implode(",", $modifier) . '</td>';
                            $html .= '<td>' . $textMod . '</td>';
                            $html .= '<td>' . $priceProd . '</td>';
                            $html .= '<td>' . $priceMod . '</td>';
                            $html .= '<td>' . htmlspecialchars($val['transaction_product_note']) . '</td>';
                            if (!empty($val['transaction_product_qty_discount']) && $val['transaction_product_qty_discount'] > $j) {
                                $html .= '<td>' . $promoName . '</td>';
                                $html .= '<td>' . $promoCode . '</td>';
                                $html .= '<td>' . ($priceProd + $priceMod) . '</td>';
                                $html .= '<td>' . $val['transaction_product_base_discount'] . '</td>';
                                $html .= '<td>' . (($priceProd + $priceMod) - $val['transaction_product_base_discount']) . '</td>';
                            } else {
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>' . ($priceProd + $priceMod) . '</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>' . ($priceProd + $priceMod) . '</td>';
                            }
                            $html .= '<td></td><td></td><td></td>';
                            if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';

                            $totalMod = count($mod);
                            if ($totalMod > 1) {
                                for ($i = 1; $i < $totalMod; $i++) {
                                    $html .= '<tr>';
                                    $html .= $sameData;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumn;
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumnVariant;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>' . $mod[$i]['text'] ?? '' . '</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>' . $mod[$i]['transaction_product_modifier_price'] ?? (int)'0' . '</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>' . ($mod[$i]['transaction_product_modifier_price'] ?? 0) . '</td>';
                                    $html .= '<td>0</td>';
                                    $html .= '<td>' . $mod[$i]['transaction_product_modifier_price'] . '</td>';
                                    $html .= '<td></td><td></td><td></td>';
                                    if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $html .= '<td></td><td></td><td></td>';
                                    }
                                    $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $html .= '</tr>';
                                }
                            }
                        }
                    }

                    $sub = 0;
                    if ($key == ($count - 1) || (isset($get[$key + 1]['transaction_receipt_number']) && $val['transaction_receipt_number'] != $get[$key + 1]['transaction_receipt_number'])) {
                        //for product plastic
                        $productPlastics = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                                            ->where('id_transaction', $val['id_transaction'])->where('type', 'Plastic')
                                            ->get()->toArray();

                        foreach ($productPlastics as $plastic) {
                            for ($j = 0; $j < $plastic['transaction_product_qty']; $j++) {
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>' . $plastic['product_name'] ?? '' . '</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>' . $plastic['transaction_product_price'] ?? (int)'0' . '</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>' . $plastic['transaction_product_price'] ?? (int)'0' . '</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>' . $plastic['transaction_product_price'] ?? (int)'0' . '</td>';
                                $html .= '<td></td><td></td><td></td>';
                                if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        }

                        if (!empty($val['transaction_payment_subscription'])) {
                            $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                                ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                                ->where('subscription_user_vouchers.id_subscription_user_voucher', $val['transaction_payment_subscription']['id_subscription_user_voucher'])
                                ->groupBy('subscriptions.id_subscription')->select('subscriptions.*', 'subscription_user_vouchers.voucher_code')->first();

                            if ($getSubcription) {
                                $sub  = abs($val['transaction_payment_subscription']['subscription_nominal']) ?? 0;
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>' . htmlspecialchars($getSubcription['subscription_title']) . '(subscription)</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>' . abs($val['transaction_payment_subscription']['subscription_nominal'] ?? 0) . '</td>';
                                $html .= '<td>' . (-$val['transaction_payment_subscription']['subscription_nominal'] ?? 0) . '</td>';
                                $html .= '<td></td><td></td><td></td>';
                                if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        } elseif (!empty($promoName2)) {
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>' . htmlspecialchars($promoName2) . '(' . $promoType2 . ')' . '</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td>' . abs(abs($val['transaction_discount']) ?? 0) . '</td>';
                            $html .= '<td>' . (-abs($val['transaction_discount']) ?? 0) . '</td>';
                            $html .= '<td></td><td></td><td></td>';
                            if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        if (!empty($val['transaction_shipment_go_send'])) {
                            $discountDelivery = 0;
                            $promoDiscountDelivery = '';
                            if (abs($val['transaction_discount_delivery']) > 0) {
                                $promoDiscountDelivery = ' (' . (empty($promoName) ? $promoName2 : $promoName) . ')';
                                $discountDelivery = abs($val['transaction_discount_delivery']);
                            }

                            if (isset($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']) && !empty($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription'])) {
                                $promoDiscountDelivery = ' (' . $val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']['subscription_title'] . ')';
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>Delivery' . $promoDiscountDelivery . '</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '<td>' . ($val['transaction_shipment_go_send'] ?? 0) . '</td>';
                            $html .= '<td>' . $discountDelivery . '</td>';
                            $html .= '<td>' . ($val['transaction_shipment_go_send'] - $discountDelivery ?? 0) . '</td>';
                            if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $html .= '<tr>';
                        $html .= $sameData;
                        $html .= '<td></td>';
                        $html .= '<td></td>';
                        $html .= $addAdditionalColumn;
                        $html .= '<td>Fee</td>';
                        $html .= $addAdditionalColumnVariant;
                        $html .= '<td></td>';
                        $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                        $html .= '<td>' . ($val['transaction_grandtotal'] - $sub) . '</td>';
                        $html .= '<td>' . (float)$val['fee_item'] . '</td>';
                        $html .= '<td>' . (float)$paymentCharge . '</td>';
                        if (isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                            $html .= '<td>' . (float)$val['discount_central'] . '</td>';
                            $html .= '<td>' . (float)$val['subscription_central'] . '</td>';
                            $html .= '<td>' . (float)$val['bundling_product_fee_central'] . '</td>';
                        }
                        $html .= '<td>' . (float)$val['income_outlet'] . '</td>';
                        $html .= '<td>' . $payment . '</td>';
                        $html .= '<td>' . abs($poinUse) . '</td>';
                        $html .= '<td>' . $val['transaction_cashback_earned'] . '</td>';
                        $html .= '<td>' . $pointRefund . '</td>';
                        $html .= '<td>' . $paymentRefund . '</td>';
                        $html .= '<td>' . (!empty($val['transaction_shipment_go_send']) ? 'Delivery' : $val['trasaction_type']) . '</td>';
                        $html .= '<td>' . ($val['receive_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['receive_at']))) . '</td>';
                        $html .= '<td>' . ($val['ready_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['ready_at']))) . '</td>';
                        $html .= '<td>' . ($val['taken_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['taken_at']))) . '</td>';
                        $html .= '<td>' . ($val['arrived_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['arrived_at']))) . '</td>';
                        $html .= '</tr>';
                    }
                }
                $dataTrxDetail .= $html;
            }
            return [
                'list' => $dataTrxDetail,
                'add_column' => $columnsVariant
            ];
        } else {
            return $query;
        }
    }
}
