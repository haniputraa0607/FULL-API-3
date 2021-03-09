<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\DailyReportTrxMenu;
use App\Http\Models\ProductCategory;
use App\Http\Models\Product;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\Brand\Entities\Brand;
use App\Lib\MyHelper;

class ApiReportTransactionController extends Controller
{
    public function product(Request $request)
    {
        $result = DailyReportTrxMenu::select('trx_date', 'product_name', 'total_qty', 'total_nominal', 'total_product_discount', 'id_report_trx_menu', 'id_outlet', \DB::raw('GROUP_CONCAT(product_variants.product_variant_name) as variant_name'))
            ->leftJoin('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'daily_report_trx_menu.id_product_variant_group')
            ->leftJoin('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant');

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

        $result->groupBy('trx_date', 'product_name', 'total_qty', 'total_nominal', 'total_product_discount', 'id_report_trx_menu', 'id_outlet');
        $result->orderBy('id_report_trx_menu', 'DESC');
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
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = ['id_product', 'id_product_variant_group', 'total_qty', 'total_nominal', 'id_brand', 'id_product_category'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $col_name = 'id_product_variants';
            if ($rules = $new_rule[$col_name] ?? false) {
                foreach ($rules as $rul) {
                    $model2->{$where.'In'}('daily_report_trx_menu.id_product_variant_group', function($model3) use ($rul) {
                        $model3->selectRaw("id_product_variant_group from (SELECT `product_variant_groups`.`id_product_variant_group`,
                                CONCAT(',', GROUP_CONCAT(id_product_variant), ',') AS variant_ids
                            FROM `product_variant_groups` 
                            INNER JOIN `product_variant_pivot` 
                                ON `product_variant_pivot`.`id_product_variant_group` = `product_variant_groups`.`id_product_variant_group` 
                            GROUP BY `product_variant_groups`.`id_product_variant_group`) as t1
                        ");
                        foreach ($rul['parameter'] as $id_variant) {
                            $model3->where('variant_ids', 'like',"%,$id_variant,%");
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
            
        }
        return MyHelper::checkGet($result);
    }
}
