<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\DailyReportTrxMenu;
use Modules\Franchise\Entities\DailyReportTrxModifier;
use Modules\Franchise\Entities\ProductCategory;
use Modules\Franchise\Entities\Product;
use Modules\Franchise\Entities\ProductVariantGroup;
use Modules\Franchise\Entities\ProductVariant;
use Modules\Franchise\Entities\Brand;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\ProductModifier;
use Modules\Franchise\Entities\ExportFranchiseQueue;
use App\Jobs\ExportFranchiseJob;
use App\Exports\FilterResultExport;
use Storage;
use File;

class ApiReportTransactionController extends Controller
{
    public function product(Request $request)
    {
        if (($request->rule['9998']['parameter'] ?? false) == ($request->rule['9999']['parameter'] ?? false) && ($request->rule['9998']['parameter'] ?? false) == date('Y-m-d')) {
            $date = date('Y-m-d');
            $result = \DB::table(\DB::raw("
                (SELECT 
                    DATE(MIN(transaction_date)) AS trx_date,
                    product_name,
                    transaction_products.id_product,
                    transaction_products.id_brand,
                    transactions.id_outlet,
                    transaction_products.id_product_variant_group,
                    products.id_product_category,
                    SUM(transaction_product_qty) AS total_qty,
                    SUM((transaction_product_price + transaction_variant_subtotal) * transaction_product_qty) AS total_nominal,
                    SUM(transaction_product_discount) AS total_product_discount,
                    0 AS id_report_trx_menu
                FROM
                    `transaction_products`
                        INNER JOIN
                    `transactions` ON `transactions`.`id_transaction` = `transaction_products`.`id_transaction`
                        INNER JOIN
                    `transaction_pickups` ON `transactions`.`id_transaction` = `transaction_pickups`.`id_transaction`
                        INNER JOIN
                    `products` ON `products`.`id_product` = `transaction_products`.`id_product`
                WHERE
                    `transaction_payment_status` = 'Completed'
                        AND (`taken_at` IS NOT NULL
                        OR `taken_by_system_at` IS NOT NULL)
                        AND DATE(transaction_date) = '$date'
                GROUP BY DATE(transaction_date) , transaction_products.id_product , transaction_products.id_product_variant_group) as daily_report_trx_menu
            "))
                ->select('trx_date', 'product_name', 'total_qty', 'total_nominal', 'total_product_discount', 'id_report_trx_menu', \DB::raw('GROUP_CONCAT(product_variants.product_variant_name) as variant_name, name_brand, product_category_name'));
        } else {
            $result = DailyReportTrxMenu::select('trx_date', 'product_name', 'total_qty', 'total_nominal', 'total_product_discount', 'id_report_trx_menu', \DB::raw('GROUP_CONCAT(product_variants.product_variant_name) as variant_name, name_brand, product_category_name'));
        }
        $result->leftJoin('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'daily_report_trx_menu.id_product_variant_group')
            ->leftJoin('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
            ->leftJoin('product_categories', 'product_categories.id_product_category', 'daily_report_trx_menu.id_product_category')
            ->leftJoin('brands', 'brands.id_brand', 'daily_report_trx_menu.id_brand');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'trx_date',
                'product_name',
                'variant_name',
                'name_brand',
                'product_category_name',
                'total_qty',
                'total_nominal',
                'total_product_discount',
                'id_report_trx_menu',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->groupBy('trx_date', 'product_name', 'total_qty', 'total_nominal', 'total_product_discount', 'id_report_trx_menu');
        $result->orderBy('id_report_trx_menu', 'DESC');

        if ($request->return_builder) {
            return $result;
        }

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function productSummary(Request $request)
    {
        if (($request->rule['9998']['parameter'] ?? false) == ($request->rule['9999']['parameter'] ?? false) && ($request->rule['9998']['parameter'] ?? false) == date('Y-m-d')) {
            $date = date('Y-m-d');
            $result = \DB::table(\DB::raw("
                (SELECT 
                    DATE(MIN(transaction_date)) AS trx_date,
                    product_name,
                    transaction_products.id_product,
                    transaction_products.id_brand,
                    transactions.id_outlet,
                    transaction_products.id_product_variant_group,
                    products.id_product_category,
                    SUM(transaction_product_qty) AS total_qty,
                    SUM((transaction_product_price + transaction_variant_subtotal) * transaction_product_qty) AS total_nominal,
                    SUM(transaction_product_discount) AS total_product_discount,
                    0 AS id_report_trx_menu
                FROM
                    `transaction_products`
                        INNER JOIN
                    `transactions` ON `transactions`.`id_transaction` = `transaction_products`.`id_transaction`
                        INNER JOIN
                    `transaction_pickups` ON `transactions`.`id_transaction` = `transaction_pickups`.`id_transaction`
                        INNER JOIN
                    `products` ON `products`.`id_product` = `transaction_products`.`id_product`
                WHERE
                    `transaction_payment_status` = 'Completed'
                        AND (`taken_at` IS NOT NULL
                        OR `taken_by_system_at` IS NOT NULL)
                        AND DATE(transaction_date) = '$date'
                GROUP BY DATE(transaction_date) , transaction_products.id_product , transaction_products.id_product_variant_group) as daily_report_trx_menu
            "));
        } else {
            $result = new DailyReportTrxMenu();
        }

        $result = $result->select(
            'product_name',
            \DB::raw('
        		COUNT(DISTINCT product_variants.product_variant_name) as total_variant, 
        		SUM(total_qty) / CASE WHEN COUNT(DISTINCT product_variants.product_variant_name) != 0 
        			THEN COUNT(DISTINCT product_variants.product_variant_name) 
        			ELSE 1 END as total_qty, 
        		SUM(total_nominal) / CASE WHEN COUNT(DISTINCT product_variants.product_variant_name) != 0 
        			THEN COUNT(DISTINCT product_variants.product_variant_name) 
        			ELSE 1 END as total_nominal, 
        		SUM(total_product_discount) / CASE WHEN COUNT(DISTINCT product_variants.product_variant_name) != 0 
        			THEN COUNT(DISTINCT product_variants.product_variant_name) 
        			ELSE 1 END as total_product_discount, 
        		GROUP_CONCAT(DISTINCT(product_variants.product_variant_name)) as variant_name, 
        		name_brand, product_category_name
        	')
        );

        $result->leftJoin('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'daily_report_trx_menu.id_product_variant_group')
            ->leftJoin('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
            ->leftJoin('product_categories', 'product_categories.id_product_category', 'daily_report_trx_menu.id_product_category')
            ->leftJoin('brands', 'brands.id_brand', 'daily_report_trx_menu.id_brand');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'product_name',
                'variant_name',
                'name_brand',
                'product_category_name',
                'total_qty',
                'total_nominal',
                'total_product_discount',
                'id_report_trx_menu',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->groupBy('product_name', 'daily_report_trx_menu.id_product_variant_group');
        $result->orderBy('id_report_trx_menu', 'DESC');

        if ($request->return_builder) {
            return $result;
        }

        if ($request->page) {
            // to support return all rows
            $request_length = $request->length ?: 15;
            if ($request_length == -1) {
                $results = $result->get();
                $result = (new \Illuminate\Pagination\LengthAwarePaginator($results, $results->count(), -1))->toArray();
            } else {
                $result = $result->paginate($request_length)->toArray();
            }

            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function ($model2) use ($model, $where, $new_rule) {
            $inner = ['id_product', 'id_product_variant_group', 'total_qty', 'total_nominal', 'id_brand', 'id_product_category'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('daily_report_trx_menu.' . $col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $col_name = 'id_product_variants';
            if ($rules = $new_rule[$col_name] ?? false) {
                foreach ($rules as $rul) {
                    $model2->{$where . 'In'}('daily_report_trx_menu.id_product_variant_group', function ($model3) use ($rul) {
                        $model3->selectRaw("id_product_variant_group from (SELECT `product_variant_groups`.`id_product_variant_group`,
                                CONCAT(',', GROUP_CONCAT(id_product_variant), ',') AS variant_ids
                            FROM `product_variant_groups` 
                            INNER JOIN `product_variant_pivot` 
                                ON `product_variant_pivot`.`id_product_variant_group` = `product_variant_groups`.`id_product_variant_group` 
                            GROUP BY `product_variant_groups`.`id_product_variant_group`) as t1
                        ");
                        foreach ($rul['parameter'] as $id_variant) {
                            $model3->where('variant_ids', 'like', "%,$id_variant,%");
                        }
                    });
                }
            }
        });

        $col_name = 'id_outlet';
        if ($rules = $new_rule[$col_name] ?? false) {
            foreach ($rules as $rul) {
                $model->where($col_name, $rul['operator'], $rul['parameter']);
            }
        }

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(trx_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    public function modifier(Request $request)
    {
        if (($request->rule['9998']['parameter'] ?? false) == ($request->rule['9999']['parameter'] ?? false) && ($request->rule['9998']['parameter'] ?? false) == date('Y-m-d')) {
            $date = date('Y-m-d');
            $result = \DB::table(\DB::raw("
                (SELECT 
                    DATE(MIN(transaction_date)) AS trx_date,
                    transaction_product_modifiers.text,
                    transaction_product_modifiers.id_product_modifier,
                    transactions.id_outlet,
                    SUM(qty) AS total_qty,
                    SUM(transaction_product_modifier_price * qty) AS total_nominal,
                    0 AS id_report_trx_modifier
                FROM
                    `transaction_product_modifiers`
                        INNER JOIN
                    `transactions` ON `transactions`.`id_transaction` = `transaction_product_modifiers`.`id_transaction`
                        INNER JOIN
                    `transaction_pickups` ON `transactions`.`id_transaction` = `transaction_pickups`.`id_transaction`
                WHERE
                    `transaction_payment_status` = 'Completed'
                        AND (`taken_at` IS NOT NULL
                        OR `taken_by_system_at` IS NOT NULL)
                        AND DATE(transaction_date) = '$date'
                GROUP BY DATE(transaction_date) , transaction_product_modifiers.id_product_modifier) as daily_report_trx_modifier
            "))
                ->select('trx_date', 'product_modifiers.text', 'total_qty', 'total_nominal', 'id_report_trx_modifier')
                ->join('product_modifiers', function ($join) {
                    $join->on('product_modifiers.id_product_modifier', 'daily_report_trx_modifier.id_product_modifier')
                        ->where('modifier_type', '<>', 'Modifier Group');
                });
        } else {
            $result = DailyReportTrxModifier::select('trx_date', 'product_modifiers.text', 'total_qty', 'total_nominal', 'id_report_trx_modifier')
                ->join('product_modifiers', function ($join) {
                    $join->on('product_modifiers.id_product_modifier', 'daily_report_trx_modifier.id_product_modifier')
                        ->where('modifier_type', '<>', 'Modifier Group');
                });
        }

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterModifierList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'trx_date',
                'text',
                'total_qty',
                'total_nominal',
                'id_report_trx_modifier',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->groupBy('trx_date', 'product_modifiers.text', 'total_qty', 'total_nominal', 'id_report_trx_modifier');
        $result->orderBy('id_report_trx_modifier', 'DESC');

        if ($request->return_builder) {
            return $result;
        }

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function modifierSummary(Request $request)
    {
        if (($request->rule['9998']['parameter'] ?? false) == ($request->rule['9999']['parameter'] ?? false) && ($request->rule['9998']['parameter'] ?? false) == date('Y-m-d')) {
            $date = date('Y-m-d');
            $result = \DB::table(\DB::raw("
                (SELECT 
                    DATE(MIN(transaction_date)) AS trx_date,
                    transaction_product_modifiers.text,
                    transaction_product_modifiers.id_product_modifier,
                    transactions.id_outlet,
                    SUM(qty) AS total_qty,
                    SUM(transaction_product_modifier_price * qty) AS total_nominal,
                    0 AS id_report_trx_modifier
                FROM
                    `transaction_product_modifiers`
                        INNER JOIN
                    `transactions` ON `transactions`.`id_transaction` = `transaction_product_modifiers`.`id_transaction`
                        INNER JOIN
                    `transaction_pickups` ON `transactions`.`id_transaction` = `transaction_pickups`.`id_transaction`
                WHERE
                    `transaction_payment_status` = 'Completed'
                        AND (`taken_at` IS NOT NULL
                        OR `taken_by_system_at` IS NOT NULL)
                        AND DATE(transaction_date) = '$date'
                GROUP BY DATE(transaction_date) , transaction_product_modifiers.id_product_modifier) as daily_report_trx_modifier
            "))
                ->select('product_modifiers.text', \DB::raw('SUM(total_qty) as total_qty, SUM(total_nominal) as total_nominal'))
                ->join('product_modifiers', function ($join) {
                    $join->on('product_modifiers.id_product_modifier', 'daily_report_trx_modifier.id_product_modifier')
                        ->where('modifier_type', '<>', 'Modifier Group');
                });
        } else {
            $result = DailyReportTrxModifier::select('product_modifiers.text', \DB::raw('SUM(total_qty) as total_qty, SUM(total_nominal) as total_nominal'))
                ->join('product_modifiers', function ($join) {
                    $join->on('product_modifiers.id_product_modifier', 'daily_report_trx_modifier.id_product_modifier')
                        ->where('modifier_type', '<>', 'Modifier Group');
                });
        }

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterModifierList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'text',
                'total_qty',
                'total_nominal',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->groupBy('product_modifiers.text');
        $result->orderBy('id_report_trx_modifier', 'DESC');

        if ($request->return_builder) {
            return $result;
        }

        if ($request->page) {
            // to support return all rows
            $request_length = $request->length ?: 15;
            if ($request_length == -1) {
                $results = $result->get();
                $result = (new \Illuminate\Pagination\LengthAwarePaginator($results, $results->count(), -1))->toArray();
            } else {
                $result = $result->paginate($request_length)->toArray();
            }

            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterModifierList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function ($model2) use ($model, $where, $new_rule) {
            $inner = ['id_product_modifier', 'total_qty', 'total_nominal'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('daily_report_trx_modifier.' . $col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }
        });

        $col_name = 'id_outlet';
        if ($rules = $new_rule[$col_name] ?? false) {
            foreach ($rules as $rul) {
                $model->where($col_name, $rul['operator'], $rul['parameter']);
            }
        }

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(trx_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    /**
     * Create a new export queue
     * @param  Request $request
     * @return Response
     */
    public function newProductExport(Request $request)
    {
        $post = $request->json()->all();
        unset($post['filter']['_token']);

        $insertToQueue = [
            'id_user_franchise' => $request->user()->id_user_franchise,
            'id_outlet' => $post['id_outlet'],
            'filter' => json_encode($post['filter']),
            'report_type' => ExportFranchiseQueue::REPORT_TYPE_REPORT_TRANSACTION_PRODUCT,
            'status_export' => ExportFranchiseQueue::STATUS_EXPORT_RUNNING
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
    public function listProductExport(Request $request)
    {
        // return $request->all();
        $result = ExportFranchiseQueue::where('report_type', ExportFranchiseQueue::REPORT_TYPE_REPORT_TRANSACTION_PRODUCT)->where('id_user_franchise', $request->user()->id_user_franchise);
        if ($request->id_outlet) {
            $result->where('id_outlet', $request->id_outlet);
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
    public function destroyProductExport(ExportFranchiseQueue $export_queue)
    {
        $filename = $export_queue->url_export;
        $delete = MyHelper::deleteFile($filename);
        if ($delete) {
            $export_queue->status_export = ExportFranchiseQueue::STATUS_EXPORT_DELETED;
            $export_queue->save();
        }
        return MyHelper::checkDelete($delete);
    }

    public function exportProductExcel($queue)
    {
        $filter = (array)json_decode($queue['filter'], true);

        $filter['rule'][] = [
            'subject' => 'id_outlet',
            'operator' => '=',
            'parameter' => $queue->id_outlet,
            'hide' => '1'
        ];

        $list = $this->product(new Request(array_merge($filter, ['return_builder' => true])));
        $data = [];
        foreach ($list->cursor() as $cursor) {
            if (get_class($cursor) == 'stdClass') { // today
                $add = (array) $cursor;
            } else {
                $add = $cursor->toArray();
            }
            $data[] = [
                'Transaction Date' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($add['trx_date']),
                'Product Name' => $add['product_name'],
                'Variant' => $add['variant_name'],
                'Total Sold' => $add['total_qty'],
                'Nominal Sold' => $add['total_nominal'],
                'Total Discount' => $add['total_product_discount'] ?: '0',
            ];
        }

        $rand_string = MyHelper::createrandom(5);

        $excelFile = "Report_Transaction_Product_{$queue->id_export_franchise_queue}_{$rand_string}.xlsx";
        $directory = 'franchise/report/transaction/' . $excelFile;

        $store  = (new FilterResultExport($data, $filter, 'Product Transaction'))->store($directory);

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


    /**
     * Create a new export queue
     * @param  Request $request
     * @return Response
     */
    public function newModifierExport(Request $request)
    {
        $post = $request->json()->all();
        unset($post['filter']['_token']);

        $insertToQueue = [
            'id_user_franchise' => $request->user()->id_user_franchise,
            'id_outlet' => $post['id_outlet'],
            'filter' => json_encode($post['filter']),
            'report_type' => ExportFranchiseQueue::REPORT_TYPE_REPORT_TRANSACTION_MODIFIER,
            'status_export' => ExportFranchiseQueue::STATUS_EXPORT_RUNNING
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
    public function listModifierExport(Request $request)
    {
        // return $request->all();
        $result = ExportFranchiseQueue::where('report_type', ExportFranchiseQueue::REPORT_TYPE_REPORT_TRANSACTION_MODIFIER)->where('id_user_franchise', $request->user()->id_user_franchise);
        if ($request->id_outlet) {
            $result->where('id_outlet', $request->id_outlet);
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
    public function destroyModifierExport(ExportFranchiseQueue $export_queue)
    {
        $filename = $export_queue->url_export;
        $delete = MyHelper::deleteFile($filename);
        if ($delete) {
            $export_queue->status_export = ExportFranchiseQueue::STATUS_EXPORT_DELETED;
            $export_queue->save();
        }
        return MyHelper::checkDelete($delete);
    }

    public function exportModifierExcel($queue)
    {
        $filter = (array)json_decode($queue['filter'], true);

        $filter['rule'][] = [
            'subject' => 'id_outlet',
            'operator' => '=',
            'parameter' => $queue->id_outlet,
            'hide' => '1'
        ];

        $list = $this->modifier(new Request(array_merge($filter, ['return_builder' => true])));
        $data = [];
        foreach ($list->cursor() as $cursor) {
            if (get_class($cursor) == 'stdClass') { // today
                $add = (array) $cursor;
            } else {
                $add = $cursor->toArray();
            }
            $data[] = [
                'Transaction Date' => \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($add['trx_date']),
                'Topping Name' => $add['text'],
                'Total Sold' => $add['total_qty'],
                'Nominal Sold' => $add['total_nominal'],
            ];
        }

        $rand_string = MyHelper::createrandom(5);

        $excelFile = "Report_Transaction_Topping_{$queue->id_export_franchise_queue}_{$rand_string}.xlsx";
        $directory = 'franchise/report/transaction/' . $excelFile;

        $store  = (new FilterResultExport($data, $filter, 'Topping Transaction'))->store($directory);

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

    public function listForSelect($table)
    {
        $result = [];
        switch ($table) {
            case 'products':
                $result = Product::showAllProduct()->select('id_product', 'product_name')->get();
                break;

            case 'brands':
                $result = Brand::select('id_brand', 'name_brand')->get();
                break;

            case 'product_categories':
                $result = ProductCategory::select('id_product_category', 'product_category_name')->get();
                break;

            case 'product_variant_groups':
                $result = ProductVariantGroup::select('id_product_variant_group', 'product_variant_group_code as product_variant_group_name')->get();
                break;

            case 'product_variants':
                $variants = ProductVariant::select('id_product_variant', 'product_variant_name', 'id_parent')->whereNotNull('id_parent')->with('parent')->get();
                $result = [];
                foreach ($variants as $variant) {
                    if (!($result[$variant['parent']['product_variant_name']] ?? false)) {
                        if (!$variant['parent']) {
                            continue;
                        }
                        $result[$variant['parent']['product_variant_name']] = $variant['parent']->toArray();
                    }
                    $child = $variant->toArray();
                    unset($child['parent']);
                    $result[$variant['parent']['product_variant_name']]['children'][] = $child;
                }
                $result = array_values($result);
                break;

            case 'product_variants_raw':
                $result = ProductVariant::select('id_product_variant', 'product_variant_name')->get();
                break;

            case 'modifiers':
                $result = ProductModifier::select('id_product_modifier', 'text')->where('modifier_type', '<>', 'Modifier Group')->get();
                break;
        }
        return MyHelper::checkGet($result);
    }
}
