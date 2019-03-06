<?php

namespace Modules\Report\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\DailyReportTrx;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\GlobalDailyReportTrx;
use App\Http\Models\GlobalMonthlyReportTrx;

use App\Http\Models\DailyReportTrxMenu;
use App\Http\Models\MonthlyReportTrxMenu;
use App\Http\Models\GlobalDailyReportTrxMenu;
use App\Http\Models\GlobalMonthlyReportTrxMenu;

use App\Http\Models\DailyCustomerReportRegistration;
use App\Http\Models\MonthlyCustomerReportRegistration;

use App\Http\Models\DailyMembershipReport;
use App\Http\Models\MonthlyMembershipReport;

use App\Http\Models\DealsUser;
use App\Http\Models\Deal;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Membership;

use App\Lib\MyHelper;
use DB;

class ApiSingleReport extends Controller
{
    // get year list for report filter
    public function getReportYear()
    {
        $data = GlobalMonthlyReportTrx::groupBy('trx_year')->get()->pluck('trx_year');

        return response()->json(MyHelper::checkGet($data));
    }

    // get outlet list for report filter
    public function getOutletList()
    {
        $data = Outlet::select('id_outlet', 'outlet_name', 'outlet_code')->get();

        return response()->json(MyHelper::checkGet($data));
    }

    // get outlet list for report filter
    public function getMembershipList()
    {
        $data = Membership::select('id_membership', 'membership_name')->get();

        return response()->json(MyHelper::checkGet($data));
    }

    // get product list for report filter
    public function getProductList()
    {
        $data = Product::select('id_product', 'product_name')->get();

        return response()->json(MyHelper::checkGet($data));
    }

    // get deals list for report filter
    public function getDealsList()
    {
        $data = Deal::select('id_deals', 'deals_title')->get();

        return response()->json(MyHelper::checkGet($data));
    }

    /**
     * Main function
     * Get all reports
     */
    public function getReport(Request $request)
    {
        $post = $request->json()->all();

        if ($post['time_type']!='day' && $post['time_type']!='month' && $post['time_type']!='year') {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Invalid time type']
            ]);
        }

        $data = $this->getTrxReport($request, 0);
        
        $getProducts = $this->getProductReport($request, 0);
        $data['products'] = $getProducts['products'];

        $getRegs = $this->getRegReport($request, 0);
        $data['registrations'] = $getRegs['registrations'];

        $getMemberships = $this->getMembershipReport($request, 0);
        $data['memberships'] = $getMemberships['memberships'];

        $getVouchers = $this->getVoucherReport($request, 0);
        $data['vouchers'] = $getVouchers['vouchers'];

        return response()->json(MyHelper::checkGet($data));
    }

    // get trx report
    public function getTrxReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();
        if (isset($post['trx_id_outlet']) && $post['trx_id_outlet']!=0) {
            $params['id_outlet'] = $post['trx_id_outlet'];
        }

        switch ($post['time_type']) {
            case 'day':
                $params['start_date'] = $post['param1'];
                $params['end_date'] = $post['param2'];
                $transactions = $this->trxDay($params);
                break;
            case 'month':
                $params['start_month'] = $post['param1'];
                $params['end_month'] = $post['param2'];
                $params['year'] = $post['param3'];
                $transactions = $this->trxMonth($params);
                break;
            case 'year':
                $params['start_year'] = $post['param1'];
                $params['end_year'] = $post['param2'];
                $transactions = $this->trxYear($params);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        // trx
        $total_idr    = 0;
        $total_qty    = 0;
        $total_male   = 0;
        $total_female = 0;

        // trx
        $trx_chart = [];
        $trx_gender_chart = [];
        $trx_age_chart = [];
        $trx_device_chart = [];
        $trx_provider_chart = [];
        foreach ($transactions as $key => $item) {
            switch ($post['time_type']) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['trx_date']));
                    $chart_date = date('d M', strtotime($item['trx_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    $chart_date = date('F', mktime(0, 0, 0, $item['trx_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['trx_month'] ."-". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    break;
                default:
                    break;
            }
            $transactions[$key]['number'] = $key + 1;
            $transactions[$key]['date'] = $item_date;

            // trx chart data
            $trx_chart[] = [
                'date'       => $chart_date,
                'total_qty'  => (is_null($item['trx_count']) ? 0 : $item['trx_count']),
                'total_idr'  => (is_null($item['trx_grand']) ? 0 : $item['trx_grand']),
                'kopi_point' => (is_null($item['trx_cashback_earned']) ? 0 : $item['trx_cashback_earned'])
            ];
            $trx_gender_chart[] = $this->genderChart($chart_date, $item);
            $trx_age_chart[] = $this->ageChart($chart_date, $item);
            $trx_device_chart[] = $this->deviceChart($chart_date, $item);
            $trx_provider_chart[] = $this->providerChart($chart_date, $item);
            
            // trx card data
            $total_idr += $item['trx_grand'];
            $total_qty += $item['trx_count'];
            $total_male += $item['cust_male'];
            $total_female += $item['cust_female'];
        }
        // trx
        if ($total_qty > 0) {
            $average_idr = round($total_idr / $total_qty, 2);
        }
        else{
            $average_idr = 0;
        }
        $data['transactions']['data'] = $transactions;
        $data['transactions']['trx_chart'] = $trx_chart;
        $data['transactions']['trx_gender_chart'] = $trx_gender_chart;
        $data['transactions']['trx_age_chart'] = $trx_age_chart;
        $data['transactions']['trx_device_chart'] = $trx_device_chart;
        $data['transactions']['trx_provider_chart'] = $trx_provider_chart;
        $data['transactions']['total_idr'] = number_format($total_idr , 0, '', ',');
        $data['transactions']['average_idr'] = number_format($average_idr , 0, '', ',');
        $data['transactions']['total_male'] = number_format($total_male , 0, '', ',');
        $data['transactions']['total_female'] = number_format($total_female , 0, '', ',');

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }

    // get product report
    public function getProductReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        if (isset($post['product_id_outlet']) && $post['product_id_outlet']!=0) {
            $params['id_outlet'] = $post['product_id_outlet'];
        }
        if (isset($post['id_product']) && $post['id_product']!=0) {
            $params['id_product'] = $post['id_product'];
        }

        switch ($post['time_type']) {
            case 'day':
                $params['start_date'] = $post['param1'];
                $params['end_date'] = $post['param2'];
                $products = $this->productDay($params);
                break;
            case 'month':
                $params['start_month'] = $post['param1'];
                $params['end_month'] = $post['param2'];
                $params['year'] = $post['param3'];
                $products = $this->productMonth($params);
                break;
            case 'year':
                $params['start_year'] = $post['param1'];
                $params['end_year'] = $post['param2'];
                $products = $this->productYear($params);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        $product_total_nominal= 0;
        $product_total_qty    = 0;
        $product_total_male   = 0;
        $product_total_female = 0;

        $product_chart = [];
        $product_gender_chart = [];
        $product_age_chart = [];
        $product_device_chart = [];
        $product_provider_chart = [];
        
        foreach ($products as $key => $item) {
            switch ($post['time_type']) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['trx_date']));
                    $chart_date = date('d M', strtotime($item['trx_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    $chart_date = date('F', mktime(0, 0, 0, $item['trx_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['trx_month'] ."-". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    break;
                default:
                    break;
            }

            $products[$key]['number'] = $key + 1;
            $products[$key]['date'] = $item_date;
            
            // product chart data
            $product_chart[] = [
                'date'       => $chart_date,
                'total_qty'  => (is_null($item['total_qty']) ? 0 : $item['total_qty']),
            ];
            $product_gender_chart[] = $this->genderChart($chart_date, $item);
            $product_age_chart[] = $this->ageChart($chart_date, $item);
            $product_device_chart[] = $this->deviceChart($chart_date, $item);
            $product_provider_chart[] = $this->providerChart($chart_date, $item);

            // product card data
            $product_total_nominal += $item['total_nominal'];
            $product_total_qty += $item['total_qty'];
            $product_total_male += $item['cust_male'];
            $product_total_female += $item['cust_female'];
        }

        $data['products']['data'] = $products;
        $data['products']['product_chart'] = $product_chart;
        $data['products']['product_gender_chart'] = $product_gender_chart;
        $data['products']['product_age_chart'] = $product_age_chart;
        $data['products']['product_device_chart'] = $product_device_chart;
        $data['products']['product_provider_chart'] = $product_provider_chart;
        $data['products']['product_total_nominal'] = number_format($product_total_nominal , 0, '', ',');
        $data['products']['product_total_qty'] = number_format($product_total_qty , 0, '', ',');
        $data['products']['product_total_male'] = number_format($product_total_male , 0, '', ',');
        $data['products']['product_total_female'] = number_format($product_total_female , 0, '', ',');

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    
    // get registration report
    public function getRegReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        switch ($post['time_type']) {
            case 'day':
                $params['start_date'] = $post['param1'];
                $params['end_date'] = $post['param2'];
                $registrations = $this->registrationDay($params);
                break;
            case 'month':
                $params['start_month'] = $post['param1'];
                $params['end_month'] = $post['param2'];
                $params['year'] = $post['param3'];
                $registrations = $this->registrationMonth($params);
                break;
            case 'year':
                $params['start_year'] = $post['param1'];
                $params['end_year'] = $post['param2'];
                $registrations = $this->registrationYear($params);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        $reg_total_male   = 0;
        $reg_total_female = 0;
        $reg_total_android= 0;
        $reg_total_ios    = 0;

        $reg_gender_chart = [];
        $reg_age_chart = [];
        $reg_device_chart = [];
        $reg_provider_chart = [];

        foreach ($registrations as $key => $item) {
            // set date for chart & table data
            switch ($post['time_type']) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['reg_date']));
                    $chart_date = date('d M', strtotime($item['reg_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['reg_month'], 10)) ." ". $item['reg_year'];
                    $chart_date = date('F', mktime(0, 0, 0, $item['reg_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['reg_month'] ."-". $item['reg_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['reg_month'], 10)) ." ". $item['reg_year'];
                    break;
                default:
                    break;
            }

            $registrations[$key]['number'] = $key + 1;
            $registrations[$key]['date'] = $item_date;

            $reg_gender_chart[] = $this->genderChart($chart_date, $item);
            $reg_age_chart[] = $this->ageChart($chart_date, $item);
            $reg_device_chart[] = $this->deviceChart($chart_date, $item);
            $reg_provider_chart[] = $this->providerChart($chart_date, $item);
            
            // reg card data
            $reg_total_male += $item['cust_male'];
            $reg_total_female += $item['cust_female'];
            $reg_total_android += $item['cust_android'];
            $reg_total_ios += $item['cust_ios'];
        }

        $data['registrations']['data'] = $registrations;
        $data['registrations']['reg_gender_chart'] = $reg_gender_chart;
        $data['registrations']['reg_age_chart'] = $reg_age_chart;
        $data['registrations']['reg_device_chart'] = $reg_device_chart;
        $data['registrations']['reg_provider_chart'] = $reg_provider_chart;
        $data['registrations']['reg_total_male'] = number_format($reg_total_male , 0, '', ',');
        $data['registrations']['reg_total_female'] = number_format($reg_total_female , 0, '', ',');
        $data['registrations']['reg_total_android'] = number_format($reg_total_android , 0, '', ',');
        $data['registrations']['reg_total_ios'] = number_format($reg_total_ios , 0, '', ',');

        return $data;
    }

    // get membership report
    public function getMembershipReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        if (isset($post['id_membership']) && $post['id_membership']!=0) {
            $params['id_membership'] = $post['id_membership'];
        }

        switch ($post['time_type']) {
            case 'day':
                $params['start_date'] = $post['param1'];
                $params['end_date'] = $post['param2'];
                $memberships = $this->membershipDay($params);
                break;
            case 'month':
                $params['start_month'] = $post['param1'];
                $params['end_month'] = $post['param2'];
                $params['year'] = $post['param3'];
                $memberships = $this->membershipMonth($params);
                break;
            case 'year':
                $params['start_year'] = $post['param1'];
                $params['end_year'] = $post['param2'];
                $memberships = $this->membershipYear($params);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        // membership
        $mem_total_male   = 0;
        $mem_total_female = 0;
        $mem_total_android= 0;
        $mem_total_ios    = 0;

        $mem_chart = [];
        $mem_gender_chart = [];
        $mem_age_chart = [];
        $mem_device_chart = [];
        $mem_provider_chart = [];
        foreach ($memberships as $key => $item) {
            // set date for chart & table data
            switch ($post['time_type']) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['mem_date']));
                    $chart_date = date('d M', strtotime($item['mem_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['mem_month'], 10)) ." ". $item['mem_year'];
                    $chart_date = date('F', mktime(0, 0, 0, $item['mem_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['mem_month'] ."-". $item['mem_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['mem_month'], 10)) ." ". $item['mem_year'];
                    break;
                default:
                    break;
            }

            $memberships[$key]['number'] = $key + 1;
            $memberships[$key]['date'] = $item_date;
            
            // membership chart data
            $mem_chart[] = [
                'date'      => $chart_date,
                'cust_total'=> (is_null($item['cust_total']) ? 0 : $item['cust_total']),
            ];

            $mem_gender_chart[] = $this->genderChart($chart_date, $item);
            $mem_age_chart[] = $this->ageChart($chart_date, $item);
            $mem_device_chart[] = $this->deviceChart($chart_date, $item);
            $mem_provider_chart[] = $this->providerChart($chart_date, $item);
            
            // membership card data
            $mem_total_male += $item['cust_male'];
            $mem_total_female += $item['cust_female'];
            $mem_total_android += $item['cust_android'];
            $mem_total_ios += $item['cust_ios'];
        }

        $data['memberships']['data'] = $memberships;
        $data['memberships']['mem_chart'] = $mem_chart;
        $data['memberships']['mem_gender_chart'] = $mem_gender_chart;
        $data['memberships']['mem_age_chart'] = $mem_age_chart;
        $data['memberships']['mem_device_chart'] = $mem_device_chart;
        $data['memberships']['mem_provider_chart'] = $mem_provider_chart;
        $data['memberships']['mem_total_male'] = number_format($mem_total_male , 0, '', ',');
        $data['memberships']['mem_total_female'] = number_format($mem_total_female , 0, '', ',');
        $data['memberships']['mem_total_android'] = number_format($mem_total_android , 0, '', ',');
        $data['memberships']['mem_total_ios'] = number_format($mem_total_ios , 0, '', ',');

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }

    // get voucher report
    public function getVoucherReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        if (isset($post['voucher_id_outlet']) && $post['voucher_id_outlet']!=0) {
            $params['id_outlet'] = $post['voucher_id_outlet'];
        }
        if (isset($post['id_deals']) && $post['id_deals']!=0) {
            $params['id_deals'] = $post['id_deals'];
        }

        switch ($post['time_type']) {
            case 'day':
                $params['start_date'] = $post['param1'];
                $params['end_date'] = $post['param2'];
                break;
            case 'month':
                $params['start_month'] = $post['param1'];
                $params['end_month'] = $post['param2'];
                $params['year'] = $post['param3'];
                break;
            case 'year':
                $params['start_year'] = $post['param1'];
                $params['end_year'] = $post['param2'];
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        $vouchers = $this->voucherReport($params);

        $voucher_total_qty = 0;
        $voucher_total_male = 0;
        $voucher_total_female = 0;

        // chart
        $voucher_chart = [];
        $voucher_gender_chart = [];
        $voucher_age_chart = [];
        $voucher_device_chart = [];
        $voucher_provider_chart = [];
        foreach ($vouchers as $key => $item) {
            switch ($post['time_type']) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['used_at']));
                    $chart_date = date('d M', strtotime($item['used_at']));
                    break;
                case 'month':
                    $item_date = date('M Y', strtotime($item['used_at']));
                    $chart_date = date('F', strtotime($item['used_at']));
                    break;
                case 'year':
                    $item_date = date('m-Y', strtotime($item['used_at']));
                    $chart_date = date('M Y', strtotime($item['used_at']));
                    break;
                default:
                    break;
            }
            
            $vouchers[$key]['number'] = $key + 1;
            $vouchers[$key]['date'] = $item_date;

            // voucher chart data
            $voucher_chart[] = [
                'date'  => $chart_date,
                'total' => (is_null($item['voucher_count']) ? 0 : $item['voucher_count']),
            ];
            $voucher_gender_chart[] = $this->genderChart($chart_date, $item);
            $voucher_age_chart[] = $this->ageChart($chart_date, $item);
            $voucher_device_chart[] = $this->deviceChart($chart_date, $item);
            $voucher_provider_chart[] = $this->providerChart($chart_date, $item);
            
            // voucher card data
            $voucher_total_qty += $item['voucher_count'];
            $voucher_total_male += $item['cust_male'];
            $voucher_total_female += $item['cust_female'];
        }

        $data['vouchers']['data'] = $vouchers;
        $data['vouchers']['voucher_chart'] = $voucher_chart;
        $data['vouchers']['voucher_gender_chart'] = $voucher_gender_chart;
        $data['vouchers']['voucher_age_chart'] = $voucher_age_chart;
        $data['vouchers']['voucher_device_chart'] = $voucher_device_chart;
        $data['vouchers']['voucher_provider_chart'] = $voucher_provider_chart;
        $data['vouchers']['voucher_total_qty'] = number_format($voucher_total_qty , 0, '', ',');
        $data['vouchers']['voucher_total_male'] = number_format($voucher_total_male , 0, '', ',');
        $data['vouchers']['voucher_total_female'] = number_format($voucher_total_female , 0, '', ',');
        // $data['vouchers']['voucher_total_android'] = number_format($voucher_total_android , 0, '', ',');
        // $data['vouchers']['voucher_total_ios'] = number_format($voucher_total_ios , 0, '', ',');

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }


    // gender chart data
    private function genderChart($chart_date, $item)
    {
        return [
            'date'      => $chart_date,
            'male'      => (is_null($item['cust_male']) ? 0 : $item['cust_male']),
            'female'    => (is_null($item['cust_female']) ? 0 : $item['cust_female'])
        ];
    }
    // age chart data
    private function ageChart($chart_date, $item)
    {
        return [
            'date'      => $chart_date,
            'teens'     => (is_null($item['cust_teens']) ? 0 : $item['cust_teens']),
            'young_adult' => (is_null($item['cust_young_adult']) ? 0 : $item['cust_young_adult']),
            'adult'     => (is_null($item['cust_adult']) ? 0 : $item['cust_adult']),
            'old'       => (is_null($item['cust_old']) ? 0 : $item['cust_old'])
        ];
    }
    // device chart data
    private function deviceChart($chart_date, $item)
    {
        return [
            'date'      => $chart_date,
            'android'   => (is_null($item['cust_android']) ? 0 : $item['cust_android']),
            'ios'       => (is_null($item['cust_ios']) ? 0 : $item['cust_ios'])
        ];
    }
    // provider chart data
    private function providerChart($chart_date, $item)
    {
        return [
            'date'      => $chart_date,
            'telkomsel' => (is_null($item['cust_telkomsel']) ? 0 : $item['cust_telkomsel']),
            'xl'        => (is_null($item['cust_xl']) ? 0 : $item['cust_xl']),
            'indosat'   => (is_null($item['cust_indosat']) ? 0 : $item['cust_indosat']),
            'tri'       => (is_null($item['cust_tri']) ? 0 : $item['cust_tri']),
            'axis'      => (is_null($item['cust_axis']) ? 0 : $item['cust_axis']),
            'smart'     => (is_null($item['cust_smart']) ? 0 : $item['cust_smart'])
        ];
    }


    // get transaction report by date
    public function trxDay($params)
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $trans = DailyReportTrx::where('id_outlet', $params['id_outlet'])
                ->whereBetween('trx_date', [date('Y-m-d', strtotime($params['start_date'])), date('Y-m-d', strtotime($params['end_date']))]);
        }
        else {
            $trans = GlobalDailyReportTrx::whereBetween('trx_date', [date('Y-m-d', strtotime($params['start_date'])), date('Y-m-d', strtotime($params['end_date']))]);
        }

        $trans = $trans->orderBy('trx_date')->get()->toArray();

        return $trans;
    }
    // get transaction report by month
    public function trxMonth($params) 
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $trans = MonthlyReportTrx::where('id_outlet', $params['id_outlet'])
                ->where('trx_year', $params['year'])
                ->whereBetween('trx_month', [$params['start_month'], $params['end_month']]);
        }
        else {
            $trans = GlobalMonthlyReportTrx::where('trx_year', $params['year'])
                ->whereBetween('trx_month', [$params['start_month'], $params['end_month']]);
        }
        
        $trans = $trans->orderBy('trx_month')->get()->toArray();

        return $trans;
    }
    // get transaction report by year
    public function trxYear($params) 
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $trans = MonthlyReportTrx::where('id_outlet', $params['id_outlet'])
                ->whereBetween('trx_year', [$params['start_year'], $params['end_year']]);
        }
        else {
            $trans = GlobalMonthlyReportTrx::whereBetween('trx_year', [$params['start_year'], $params['end_year']]);
        }
        
        $trans = $trans->orderBy('trx_year')->orderBy('trx_month')->get()->toArray();

        return $trans;
    }


    // get product report by date
    public function productDay($params)
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $data = DailyReportTrxMenu::with('product')
                ->where('id_outlet', $params['id_outlet']);
        }
        else {
            $data = GlobalDailyReportTrxMenu::with('product');
        }

        // set default product
        if (!isset($params['id_product'])) {
            $product = Product::select('id_product')->first();
            $params['id_product'] = $product->id_product;
        }

        $data = $data->where('id_product', $params['id_product'])
            ->whereBetween('trx_date', [date('Y-m-d', strtotime($params['start_date'])), date('Y-m-d', strtotime($params['end_date']))])
            ->orderBy('trx_date')
            ->get()->toArray();

        return $data;
    }
    // get product report by month
    public function productMonth($params) 
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $data = MonthlyReportTrxMenu::with('product')
                ->where('id_outlet', $params['id_outlet']);
        }
        else {
            $data = GlobalMonthlyReportTrxMenu::with('product');
        }

        // set default product
        if (!isset($params['id_product'])) {
            $product = Product::select('id_product')->first();
            $params['id_product'] = $product->id_product;
        }
        
        $data = $data->where('id_product', $params['id_product'])
                ->where('trx_year', $params['year'])
                ->whereBetween('trx_month', [$params['start_month'], $params['end_month']])
                ->orderBy('trx_month')->get()->toArray();

        return $data;
    }
    // get product report by year
    public function productYear($params) 
    {
        // with outlet
        if (isset($params['id_outlet'])) {
            $data = MonthlyReportTrxMenu::with('product')
                ->where('id_outlet', $params['id_outlet']);
        }
        else {
            $data = GlobalMonthlyReportTrxMenu::with('product');
        }

        // set default product
        if (!isset($params['id_product'])) {
            $product = Product::select('id_product')->first();
            $params['id_product'] = $product->id_product;
        }
        
        $data = $data->where('id_product', $params['id_product'])
            ->whereBetween('trx_year', [$params['start_year'], $params['end_year']])
            ->orderBy('trx_year')->orderBy('trx_month')->get()->toArray();

        return $data;
    }


    // get registration report by date
    public function registrationDay($params)
    {
        $data = DailyCustomerReportRegistration::whereBetween('reg_date', [
                date('Y-m-d', strtotime($params['start_date'])), 
                date('Y-m-d', strtotime($params['end_date']))
            ])
            ->orderBy('reg_date')
            ->get()->toArray();

        return $data;
    }
    // get registration report by month
    public function registrationMonth($params) 
    {
        $data = MonthlyCustomerReportRegistration::where('reg_year', $params['year'])
            ->whereBetween('reg_month', [$params['start_month'], $params['end_month']])
            ->orderBy('reg_month')
            ->get()->toArray();

        return $data;
    }
    // get registration report by year
    public function registrationYear($params) 
    {
        $data = MonthlyCustomerReportRegistration::whereBetween('reg_year', [$params['start_year'], $params['end_year']])
            ->orderBy('reg_year')
            ->orderBy('reg_month')
            ->get()->toArray();

        return $data;
    }


    // get membership report by date
    public function membershipDay($params)
    {
        // set default membership
        if (!isset($params['id_membership'])) {
            $membership = Membership::select('id_membership')->first();
            $params['id_membership'] = $membership->id_membership;
        }

        $data = DailyMembershipReport::with('membership')
                ->where('id_membership', $params['id_membership'])
                ->whereBetween('mem_date', [
                    date('Y-m-d', strtotime($params['start_date'])), 
                    date('Y-m-d', strtotime($params['end_date']))
                ])
                ->orderBy('mem_date')->get()->toArray();

        return $data;
    }
    // get membership report by month
    public function membershipMonth($params) 
    {
        // set default membership
        if (!isset($params['id_membership'])) {
            $membership = Membership::select('id_membership')->first();
            $params['id_membership'] = $membership->id_membership;
        }

        $data = MonthlyMembershipReport::with('membership')
                ->where('id_membership', $params['id_membership'])
                ->where('mem_year', $params['year'])
                ->whereBetween('mem_month', [$params['start_month'], $params['end_month']])
                ->orderBy('mem_month')->get()->toArray();

        return $data;
    }
    // get membership report by year
    public function membershipYear($params) 
    {
        // set default membership
        if (!isset($params['id_membership'])) {
            $membership = Membership::select('id_membership')->first();
            $params['id_membership'] = $membership->id_membership;
        }

        $data = MonthlyMembershipReport::with('membership')
                ->where('id_membership', $params['id_membership'])
                ->whereBetween('mem_year', [$params['start_year'], $params['end_year']])
                ->orderBy('mem_year')->orderBy('mem_month')->get()->toArray();

        return $data;
    }

    // get voucher redemption report by day, month, & year
    public function voucherReport($params)
    {
        // $params =  $params->json()->all();
        if (isset($params['start_date']) && isset($params['end_date'])) {
            $start_date = $params['start_date'] . " 00:00:00";
            $end_date = $params['end_date'] . " 23:59:59";
            $date_format = 'Y-m-d';
        }
        else if (isset($params['start_month']) && isset($params['end_month']) && isset($params['year'])) {
            $start_date = date('Y-m-d 00:00:00', mktime(0, 0, 0, $params['start_month'], 1, $params['year']));
            $end_date = date('Y-m-t 23:59:59', mktime(0, 0, 0, $params['end_month'], 1, $params['year']));
            $date_format = 'Y-m';
        }
        else if (isset($params['start_year']) && isset($params['end_year'])) {
            $start_date = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, $params['start_year']));
            $end_date = date('Y-m-t 23:59:59', mktime(0, 0, 0, 12, 31, $params['end_year']));
            $date_format = 'Y-m';
        }

        // join with : deals_vouchers, users, outlets
        $data = DealsUser::whereNotNull('used_at')
            ->join('users', 'deals_users.id_user', '=', 'users.id')
            ->join('deals_vouchers', 'deals_users.id_deals_voucher', '=', 'deals_vouchers.id_deals_voucher')
            ->join('outlets', 'deals_users.id_outlet', '=', 'outlets.id_outlet')
            ->select(
                'deals_users.id_deals_user', 'deals_users.id_deals_voucher', 'deals_users.id_user', 'deals_users.id_outlet', 'deals_users.used_at',
                'users.gender', 'users.birthday', 'users.provider', 'users.android_device', 'users.ios_device',
                'deals_vouchers.id_deals',
                'outlets.outlet_name'
            );

        if (isset($params['id_outlet'])) {
            $data = $data->where('deals_users.id_outlet', $params['id_outlet']);
        }

        if (isset($params['id_deals'])) {
            $data = $data->where('deals_vouchers.id_deals', $params['id_deals']);
        }
        
        /*$data = $data->whereBetween('deals_users.used_at', [$start_date, $end_date])
            ->orderBy('deals_users.used_at')->get()->toArray();*/

        $data = $data->whereBetween('deals_users.used_at', [$start_date, $end_date])
            ->orderBy('deals_users.used_at')->get()
            ->groupBy(function($item) use($date_format) {
                return $item->used_at->format($date_format);
            });

        $user_vouchers = $this->calculateVoucherReport($data);

        return $user_vouchers;
    }

    // calculate age, gender, device, and provider
    private function calculateVoucherReport($data) 
    {
        $user_vouchers = [];

        foreach ($data as $date_key => $date) {
            $temp['used_at'] = "";
            $temp['voucher_count'] = 0;
            $temp['cust_male'] = 0 ;
            $temp['cust_female'] = 0 ;
            $temp['cust_android'] = 0 ;
            $temp['cust_ios'] = 0 ;
            $temp['cust_telkomsel'] = 0 ;
            $temp['cust_xl'] = 0 ;
            $temp['cust_indosat'] = 0 ;
            $temp['cust_tri'] = 0 ;
            $temp['cust_axis'] = 0 ;
            $temp['cust_smart'] = 0 ;
            $temp['cust_teens'] = 0 ;
            $temp['cust_young_adult'] = 0 ;
            $temp['cust_adult'] = 0 ;
            $temp['cust_old'] = 0 ;
            foreach ($date as $key => $item) {
                $birthday = new \DateTime($item['birthday']);
                $today    = new \DateTime('today');
                $age = $birthday->diff($today)->y;

                $temp['voucher_count'] += 1;
                $temp['used_at'] = $date_key;

                $temp['cust_male'] += ($item['gender'] == 'Male' ? 1 : 0);
                $temp['cust_female'] += ($item['gender'] == 'Female' ? 1 : 0);

                $temp['cust_android'] += (is_null($item['android_device']) ? 0 : 1);
                $temp['cust_ios'] += (is_null($item['ios_device']) ? 0 : 1);

                $temp['cust_telkomsel'] += ($item['provider'] == 'Telkomsel' ? 1 : 0);
                $temp['cust_xl'] += ($item['provider'] == 'XL' ? 1 : 0);
                $temp['cust_indosat'] += ($item['provider'] == 'Indosat' ? 1 : 0);
                $temp['cust_tri'] += ($item['provider'] == 'Tri' ? 1 : 0);
                $temp['cust_axis'] += ($item['provider'] == 'Axis' ? 1 : 0);
                $temp['cust_smart'] += ($item['provider'] == 'Smart' ? 1 : 0);
                
                $temp['cust_teens'] += (($age>=11 && $age<=17 ) ? 1 : 0);
                $temp['cust_young_adult'] += (($age>=18 && $age<=24 ) ? 1 : 0);
                $temp['cust_adult'] += (($age>=25 && $age<=34 ) ? 1 : 0);
                $temp['cust_old'] += (($age>=35 && $age<=100 ) ? 1 : 0);

            }
            $user_vouchers[] = $temp;
        }

        return $user_vouchers;
    }
}
