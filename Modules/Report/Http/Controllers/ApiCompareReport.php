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

use Modules\Report\Http\Controllers\ApiSingleReport;
use App\Lib\MyHelper;
use DB;

class ApiCompareReport extends Controller
{
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
        if (isset($post['trx_id_outlet_1']) && $post['trx_id_outlet_1']!=0) {
            $params_1['id_outlet'] = $post['trx_id_outlet_1'];
        }
        if (isset($post['trx_id_outlet_2']) && $post['trx_id_outlet_2']!=0) {
            $params_2['id_outlet'] = $post['trx_id_outlet_2'];
        }

        // check if dates equal or not
        // $check_date = 0;

        // get data from database
        $apiSingleReport = new ApiSingleReport();
        switch ($post['time_type']) {
            case 'day':
                /*if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }*/
                $params_1['start_date'] = $post['param1'];
                $params_1['end_date'] = $post['param2'];
                $transactions_1 = $apiSingleReport->trxDay($params_1);

                $params_2['start_date'] = $post['param3'];
                $params_2['end_date'] = $post['param4'];
                $transactions_2 = $apiSingleReport->trxDay($params_2);
                break;
            case 'month':
                /*if ($post['param1'] == $post['param4'] &&
                    $post['param2'] == $post['param5'] &&
                    $post['param3'] == $post['param6']) {
                    $check_date = 1;
                }*/
                $params_1['start_month'] = $post['param1'];
                $params_1['end_month'] = $post['param2'];
                $params_1['year'] = $post['param3'];
                $transactions_1 = $apiSingleReport->trxMonth($params_1);

                $params_2['start_month'] = $post['param4'];
                $params_2['end_month'] = $post['param5'];
                $params_2['year'] = $post['param6'];
                $transactions_2 = $apiSingleReport->trxMonth($params_2);
                break;
            case 'year':
                /*if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }*/
                $params_1['start_year'] = $post['param1'];
                $params_1['end_year'] = $post['param2'];
                $transactions_1 = $apiSingleReport->trxYear($params_1);

                $params_2['start_year'] = $post['param3'];
                $params_2['end_year'] = $post['param4'];
                $transactions_2 = $apiSingleReport->trxYear($params_2);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        $trx_idr_chart = [];
        $trx_qty_chart = [];
        $trx_kopi_point_chart = [];

        // get max length of 2 arrays
        $count_1 = count($transactions_1);
        $count_2 = count($transactions_2);
        if ($count_2 > $count_1) {
            $max_count = $count_2;
        }
        else{
            $max_count = $count_1;
        }

        // get data for chart
        $chart_1 = $this->trxChart($transactions_1, $post['time_type']);
        $chart_2 = $this->trxChart($transactions_2, $post['time_type']);
        $transactions_1 = $chart_1['transactions'];
        $transactions_2 = $chart_2['transactions'];

        // manage if the length of compared array charts not equal
        // if data null, set placeholder "-" or "0"
        for ($i=0; $i < $max_count; $i++) {
            $idr_1 = isset($chart_1['trx_idr_chart'][$i]) ? $chart_1['trx_idr_chart'][$i] : [];
            $idr_2 = isset($chart_2['trx_idr_chart'][$i]) ? $chart_2['trx_idr_chart'][$i] : [];

            $qty_1 = isset($chart_1['trx_qty_chart'][$i]) ? $chart_1['trx_qty_chart'][$i] : [];
            $qty_2 = isset($chart_2['trx_qty_chart'][$i]) ? $chart_2['trx_qty_chart'][$i] : [];

            $kopi_point_1 = isset($chart_1['trx_kopi_point_chart'][$i]) ? $chart_1['trx_kopi_point_chart'][$i] : [];
            $kopi_point_2 = isset($chart_2['trx_kopi_point_chart'][$i]) ? $chart_2['trx_kopi_point_chart'][$i] : [];

            // if dates are equal
            /*if ($check_date) {
                $date   = isset($idr_1['date']) ? $idr_1['date'] : $idr_2['date'];
            }
            else{*/
                $date_1 = isset($idr_1['date']) ? $idr_1['date'] : '-';
                $date_2 = isset($idr_2['date']) ? $idr_2['date'] : '-';
                $date   = $date_1 .' vs '. $date_2;
            // }

            $trx_idr_chart[] = [
                'date'        => $date,
                'total_idr_1' => (isset($idr_1['total_idr']) ? $idr_1['total_idr'] : 0),
                'total_idr_2' => (isset($idr_2['total_idr']) ? $idr_2['total_idr'] : 0)
            ];

            $trx_qty_chart[] = [
                'date'        => $date,
                'total_qty_1' => (isset($qty_1['total_qty']) ? $qty_1['total_qty'] : 0),
                'total_qty_2' => (isset($qty_2['total_qty']) ? $qty_2['total_qty'] : 0),
            ];

            $trx_kopi_point_chart[] = [
                'date'         => $date,
                'kopi_point_1' => (isset($kopi_point_1['kopi_point']) ? $kopi_point_1['kopi_point'] : 0),
                'kopi_point_2' => (isset($kopi_point_2['kopi_point']) ? $kopi_point_2['kopi_point'] : 0),
            ];
        }
        
        $transactions = array_merge($transactions_1, $transactions_2);

        // set number in datatable
        foreach ($transactions as $key => $transaction) {
            $transactions[$key]['number'] = $key + 1;
        }

        $data['transactions']['data'] = $transactions;
        $data['transactions']['trx_idr_chart'] = $trx_idr_chart;
        $data['transactions']['trx_qty_chart'] = $trx_qty_chart;
        $data['transactions']['trx_kopi_point_chart'] = $trx_kopi_point_chart;

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    // manage trx chart data
    private function trxChart($transactions, $time_type)
    {
        $trx_idr_chart = [];
        $trx_qty_chart = [];
        $trx_kopi_point_chart = [];

        foreach ($transactions as $key => $item) {
            switch ($time_type) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['trx_date']));
                    $chart_date = date('d M', strtotime($item['trx_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['trx_month'] ."-". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    break;
                default:
                    break;
            }
            $transactions[$key]['date'] = $item_date;

            $trx_idr_chart[] = [
                'date'       => $chart_date,
                'total_idr'  => (is_null($item['trx_grand']) ? 0 : $item['trx_grand']),
            ];

            $trx_qty_chart[] = [
                'date'       => $chart_date,
                'total_qty'  => (is_null($item['trx_count']) ? 0 : $item['trx_count']),
            ];

            $trx_kopi_point_chart[] = [
                'date'       => $chart_date,
                'kopi_point' => (is_null($item['trx_cashback_earned']) ? 0 : $item['trx_cashback_earned']),
            ];
        }

        $data['transactions'] = $transactions;
        $data['trx_idr_chart'] = $trx_idr_chart;
        $data['trx_qty_chart'] = $trx_qty_chart;
        $data['trx_kopi_point_chart'] = $trx_kopi_point_chart;
        return $data;
    }


    // get product report
    public function getProductReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        $params_1['time_type'] = $post['time_type'];
        $params_2['time_type'] = $post['time_type'];

        if (isset($post['product_id_outlet_1']) && $post['product_id_outlet_1']!=0) {
            $params_1['id_outlet'] = $post['product_id_outlet_1'];
        }
        if (isset($post['id_product']) && $post['id_product']!=0) {
            $params_1['id_product'] = $post['id_product'];
            $params_2['id_product'] = $post['id_product'];
        }
        if (isset($post['product_id_outlet_2']) && $post['product_id_outlet_2']!=0) {
            $params_2['id_outlet'] = $post['product_id_outlet_2'];
        }

        // get data from database
        $apiSingleReport = new ApiSingleReport();

        switch ($post['time_type']) {
            case 'day':
                /*$params_1['start_date'] = $post['param1'];
                $params_1['end_date'] = $post['param2'];
                $products_1 = $apiSingleReport->productDay($params_1);

                $params_2['start_date'] = $post['param3'];
                $params_2['end_date'] = $post['param4'];
                $products_2 = $apiSingleReport->productDay($params_2);*/

                $params_1['param1'] = $post['param1'];
                $params_1['param2'] = $post['param2'];

                $params_2['param1'] = $post['param3'];
                $params_2['param2'] = $post['param4'];
                break;
            case 'month':
                /*$params_1['start_month'] = $post['param1'];
                $params_1['end_month'] = $post['param2'];
                $params_1['year'] = $post['param3'];
                $products_1 = $apiSingleReport->productMonth($params_1);

                $params_2['start_month'] = $post['param4'];
                $params_2['end_month'] = $post['param5'];
                $params_2['year'] = $post['param6'];
                $products_2 = $apiSingleReport->productMonth($params_2);*/
                $params_1['param1'] = $post['param1'];
                $params_1['param2'] = $post['param2'];
                $params_1['param3'] = $post['param3'];

                $params_2['param1'] = $post['param4'];
                $params_2['param2'] = $post['param5'];
                $params_2['param3'] = $post['param6'];
                break;
            case 'year':
                /*$params_1['start_year'] = $post['param1'];
                $params_1['end_year'] = $post['param2'];
                $products_1 = $apiSingleReport->productYear($params_1);

                $params_2['start_year'] = $post['param3'];
                $params_2['end_year'] = $post['param4'];
                $products_2 = $apiSingleReport->productYear($params_2);*/
                $params_1['param1'] = $post['param1'];
                $params_1['param2'] = $post['param2'];

                $params_2['param1'] = $post['param3'];
                $params_2['param2'] = $post['param4'];
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        $custom_req_1 = new Request();
        $custom_req_1->request->add(['time_type'=> 'day']);
        $custom_req_2 = new Request();
        $custom_req_2->request->add($params_2);

        return [
            $params_1, 
            $request->all(),
            $custom_req_1->all()
        ];

        $products_1 = $apiSingleReport->getProductReport($request, 0);
        $products_2 = $apiSingleReport->getProductReport($request, 0);

        $p1 = $products_1['products'];
        $p2 = $products_2['products'];
        // return $p1;

        // get max length of 2 arrays
        $count_1 = count($p1['data']);
        $count_2 = count($p2['data']);
        if ($count_2 > $count_1) {
            $max_count = $count_2;
        }
        else{
            $max_count = $count_1;
        }

        $product_chart = [];
        $product_gender_chart = [];
        $product_age_chart = [];
        $product_device_chart = [];

        // create compared data: data_1, data_2
        // manage if the length of compared array charts not equal
        // if data null, set placeholder "-" or "0"
        for ($i=0; $i < $max_count; $i++) {
            $qty_1 = isset($p1['product_chart'][$i]) ? $p1['product_chart'][$i] : [];
            $qty_2 = isset($p2['product_chart'][$i]) ? $p2['product_chart'][$i] : [];
            $gender_1 = isset($p1['product_gender_chart'][$i]) ? $p1['product_gender_chart'][$i] : [];
            $gender_2 = isset($p2['product_gender_chart'][$i]) ? $p2['product_gender_chart'][$i] : [];
            $age_1 = isset($p1['product_age_chart'][$i]) ? $p1['product_age_chart'][$i] : [];
            $age_2 = isset($p2['product_age_chart'][$i]) ? $p2['product_age_chart'][$i] : [];
            $device_1 = isset($p1['product_device_chart'][$i]) ? $p1['product_device_chart'][$i] : [];
            $device_2 = isset($p2['product_device_chart'][$i]) ? $p2['product_device_chart'][$i] : [];

            $date_1 = isset($qty_1['date']) ? $qty_1['date'] : '-';
            $date_2 = isset($qty_2['date']) ? $qty_2['date'] : '-';
            $date   = $date_1 .' vs '. $date_2;

            $product_chart[] = [
                'date'        => $date,
                'total_qty_1' => (isset($qty_1['total_qty']) ? $qty_1['total_qty'] : 0),
                'total_qty_2' => (isset($qty_2['total_qty']) ? $qty_2['total_qty'] : 0),
            ];
            $product_gender_chart[] = [
                'date'   => $date,
                'male_1' => (isset($gender_1['male']) ? $gender_1['male'] : 0),
                'male_2' => (isset($gender_2['male']) ? $gender_2['male'] : 0),
                'female_1' => (isset($gender_1['female']) ? $gender_1['female'] : 0),
                'female_2' => (isset($gender_2['female']) ? $gender_2['female'] : 0),
            ];
            $product_age_chart[] = [
                'date'   => $date,
                'teens_1' => (isset($age_1['teens']) ? $age_1['teens'] : 0),
                'teens_2' => (isset($age_2['teens']) ? $age_2['teens'] : 0),
                'young_adult_1' => (isset($age_1['young_adult']) ? $age_1['young_adult'] : 0),
                'young_adult_2' => (isset($age_2['young_adult']) ? $age_2['young_adult'] : 0),
                'adult_1' => (isset($age_1['adult']) ? $age_1['adult'] : 0),
                'adult_2' => (isset($age_2['adult']) ? $age_2['adult'] : 0),
                'old_1' => (isset($age_1['old']) ? $age_1['old'] : 0),
                'old_2' => (isset($age_2['old']) ? $age_2['old'] : 0),
            ];
            $product_device_chart[] = [
                'date'   => $date,
                'android_1' => (isset($device_1['android']) ? $device_1['android'] : 0),
                'android_2' => (isset($device_2['android']) ? $device_2['android'] : 0),
                'ios_1' => (isset($device_1['ios']) ? $device_1['ios'] : 0),
                'ios_2' => (isset($device_2['ios']) ? $device_2['ios'] : 0),
            ];
        }
        
        $products = array_merge($p1['data'], $p2['data']);

        // set number in datatable
        foreach ($products as $key => $product) {
            $products[$key]['number'] = $key + 1;
        }

        $data['products']['data'] = $products;
        $data['products']['product_chart'] = $product_chart;
        $data['products']['product_gender_chart'] = $product_gender_chart;
        $data['products']['product_age_chart'] = $product_age_chart;
        $data['products']['product_device_chart'] = $product_device_chart;

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    // manage product chart data
    private function productChart($products, $time_type)
    {
        $product_chart = [];

        foreach ($products as $key => $item) {
            switch ($time_type) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['trx_date']));
                    $chart_date = date('d M', strtotime($item['trx_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['trx_month'] ."-". $item['trx_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['trx_month'], 10)) ." ". $item['trx_year'];
                    break;
                default:
                    break;
            }
            $products[$key]['date'] = $item_date;

            $product_chart[] = [
                'date'       => $chart_date,
                'total_qty'  => (is_null($item['total_qty']) ? 0 : $item['total_qty']),
            ];
        }

        $data['products'] = $products;
        $data['product_chart'] = $product_chart;
        return $data;
    }


    // get registration report
    public function getRegReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();

        // get data from database
        $apiSingleReport = new ApiSingleReport();
        switch ($post['time_type']) {
            case 'day':
                $params_1['start_date'] = $post['param1'];
                $params_1['end_date'] = $post['param2'];
                $regs_1 = $apiSingleReport->registrationDay($params_1);

                $params_2['start_date'] = $post['param3'];
                $params_2['end_date'] = $post['param4'];
                $regs_2 = $apiSingleReport->registrationDay($params_2);
                break;
            case 'month':
                if ($post['param1'] == $post['param4'] &&
                    $post['param2'] == $post['param5'] &&
                    $post['param3'] == $post['param6']) {
                    $check_date = 1;
                }
                $params_1['start_month'] = $post['param1'];
                $params_1['end_month'] = $post['param2'];
                $params_1['year'] = $post['param3'];
                $regs_1 = $apiSingleReport->registrationMonth($params_1);

                $params_2['start_month'] = $post['param4'];
                $params_2['end_month'] = $post['param5'];
                $params_2['year'] = $post['param6'];
                $regs_2 = $apiSingleReport->registrationMonth($params_2);
                break;
            case 'year':
                if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }
                $params_1['start_year'] = $post['param1'];
                $params_1['end_year'] = $post['param2'];
                $regs_1 = $apiSingleReport->registrationYear($params_1);

                $params_2['start_year'] = $post['param3'];
                $params_2['end_year'] = $post['param4'];
                $regs_2 = $apiSingleReport->registrationYear($params_2);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        // get max length of 2 arrays
        $count_1 = count($regs_1);
        $count_2 = count($regs_2);
        if ($count_2 > $count_1) {
            $max_count = $count_2;
        }
        else{
            $max_count = $count_1;
        }

        // get data for chart
        $chart_1 = $this->regChart($regs_1, $post['time_type']);
        $chart_2 = $this->regChart($regs_2, $post['time_type']);
        $regs_1 = $chart_1['registrations'];
        $regs_2 = $chart_2['registrations'];

        // manage if the length of compared array charts not equal
        // if data null, set placeholder "-" or "0"
        $reg_gender_chart = [];
        $reg_age_chart = [];
        $reg_device_chart = [];
        $reg_provider_chart = [];

        for ($i=0; $i < $max_count; $i++) {
            $gender_1 = isset($chart_1['reg_gender_chart'][$i]) ? $chart_1['reg_gender_chart'][$i] : [];
            $gender_2 = isset($chart_2['reg_gender_chart'][$i]) ? $chart_2['reg_gender_chart'][$i] : [];
            $age_1 = isset($chart_1['reg_age_chart'][$i]) ? $chart_1['reg_age_chart'][$i] : [];
            $age_2 = isset($chart_2['reg_age_chart'][$i]) ? $chart_2['reg_age_chart'][$i] : [];
            $device_1 = isset($chart_1['reg_device_chart'][$i]) ? $chart_1['reg_device_chart'][$i] : [];
            $device_2 = isset($chart_2['reg_device_chart'][$i]) ? $chart_2['reg_device_chart'][$i] : [];
            $provider_1 = isset($chart_1['reg_provider_chart'][$i]) ? $chart_1['reg_provider_chart'][$i] : [];
            $provider_2 = isset($chart_2['reg_provider_chart'][$i]) ? $chart_2['reg_provider_chart'][$i] : [];

            $date_1 = isset($gender_1['date']) ? $gender_1['date'] : '-';
            $date_2 = isset($gender_2['date']) ? $gender_2['date'] : '-';
            $date   = $date_1 .' vs '. $date_2;

            $reg_gender_chart[] = $this->genderChart($date, $gender_1, $gender_2);
            $reg_age_chart[] = $this->ageChart($date, $age_1, $age_2);
            $reg_device_chart[] = $this->deviceChart($date, $device_1, $device_2);
            $reg_provider_chart[] = $this->providerChart($date, $provider_1, $provider_2);
        }
        
        $registrations = array_merge($regs_1, $regs_2);

        // set number in datatable
        foreach ($registrations as $key => $reg) {
            $registrations[$key]['number'] = $key + 1;
        }

        $data['registrations']['data'] = $registrations;
        $data['registrations']['reg_gender_chart'] = $reg_gender_chart;
        $data['registrations']['reg_age_chart'] = $reg_age_chart;
        $data['registrations']['reg_device_chart'] = $reg_device_chart;
        $data['registrations']['reg_provider_chart'] = $reg_provider_chart;

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    // manage registration chart data
    private function regChart($regs, $time_type)
    {
        $reg_gender_chart = [];
        $reg_age_chart = [];
        $reg_device_chart = [];
        $reg_provider_chart = [];

        $apiSingleReport = new ApiSingleReport();
        foreach ($regs as $key => $item) {
            switch ($time_type) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['reg_date']));
                    $chart_date = date('d M', strtotime($item['reg_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['reg_month'], 10)) ." ". $item['reg_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['reg_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['reg_month'] ."-". $item['reg_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['reg_month'], 10)) ." ". $item['reg_year'];
                    break;
                default:
                    break;
            }
            $regs[$key]['date'] = $item_date;

            $reg_gender_chart[] = $apiSingleReport->genderChart($chart_date, $item);
            $reg_age_chart[] = $apiSingleReport->ageChart($chart_date, $item);
            $reg_device_chart[] = $apiSingleReport->deviceChart($chart_date, $item);
            $reg_provider_chart[] = $apiSingleReport->providerChart($chart_date, $item);
        }

        $data['registrations'] = $regs;
        $data['reg_gender_chart'] = $reg_gender_chart;
        $data['reg_age_chart'] = $reg_age_chart;
        $data['reg_device_chart'] = $reg_device_chart;
        $data['reg_provider_chart'] = $reg_provider_chart;
        return $data;
    }


    // get membership report
    public function getMembershipReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();
        if (isset($post['id_membership_1']) && $post['id_membership_1']!=0) {
            $params_1['id_membership'] = $post['id_membership_1'];
        }
        if (isset($post['id_membership_2']) && $post['id_membership_2']!=0) {
            $params_2['id_membership'] = $post['id_membership_2'];
        }

        // check if dates equal or not
        // $check_date = 0;

        // get data from database
        $apiSingleReport = new ApiSingleReport();
        switch ($post['time_type']) {
            case 'day':
                /*if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }*/
                $params_1['start_date'] = $post['param1'];
                $params_1['end_date'] = $post['param2'];
                $memberships_1 = $apiSingleReport->membershipDay($params_1);

                $params_2['start_date'] = $post['param3'];
                $params_2['end_date'] = $post['param4'];
                $memberships_2 = $apiSingleReport->membershipDay($params_2);
                break;
            case 'month':
                /*if ($post['param1'] == $post['param4'] &&
                    $post['param2'] == $post['param5'] &&
                    $post['param3'] == $post['param6']) {
                    $check_date = 1;
                }*/
                $params_1['start_month'] = $post['param1'];
                $params_1['end_month'] = $post['param2'];
                $params_1['year'] = $post['param3'];
                $memberships_1 = $apiSingleReport->membershipMonth($params_1);

                $params_2['start_month'] = $post['param4'];
                $params_2['end_month'] = $post['param5'];
                $params_2['year'] = $post['param6'];
                $memberships_2 = $apiSingleReport->membershipMonth($params_2);
                break;
            case 'year':
                /*if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }*/
                $params_1['start_year'] = $post['param1'];
                $params_1['end_year'] = $post['param2'];
                $memberships_1 = $apiSingleReport->membershipYear($params_1);

                $params_2['start_year'] = $post['param3'];
                $params_2['end_year'] = $post['param4'];
                $memberships_2 = $apiSingleReport->membershipYear($params_2);
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        // get max length of 2 arrays
        $count_1 = count($memberships_1);
        $count_2 = count($memberships_2);
        if ($count_2 > $count_1) {
            $max_count = $count_2;
        }
        else{
            $max_count = $count_1;
        }

        // get data for chart
        $chart_1 = $this->memChart($memberships_1, $post['time_type']);
        $chart_2 = $this->memChart($memberships_2, $post['time_type']);
        $memberships_1 = $chart_1['memberships'];
        $memberships_2 = $chart_2['memberships'];

        $mem_chart = [];
        // manage if the length of compared array charts not equal
        // if data null, set placeholder "-" or "0"
        for ($i=0; $i < $max_count; $i++) {
            $qty_1 = isset($chart_1['mem_chart'][$i]) ? $chart_1['mem_chart'][$i] : [];
            $qty_2 = isset($chart_2['mem_chart'][$i]) ? $chart_2['mem_chart'][$i] : [];

            // if dates are equal
            /*if ($check_date) {
                $date   = isset($qty_1['date']) ? $qty_1['date'] : $qty_2['date'];
            }
            else {*/
                $date_1 = isset($qty_1['date']) ? $qty_1['date'] : '-';
                $date_2 = isset($qty_2['date']) ? $qty_2['date'] : '-';
                $date   = $date_1 .' vs '. $date_2;
            // }

            $mem_chart[] = [
                'date'         => $date,
                'cust_total_1' => (isset($qty_1['cust_total']) ? $qty_1['cust_total'] : 0),
                'cust_total_2' => (isset($qty_2['cust_total']) ? $qty_2['cust_total'] : 0)
            ];
        }
        
        $memberships = array_merge($memberships_1, $memberships_2);

        // set number in datatable
        foreach ($memberships as $key => $membership) {
            $memberships[$key]['number'] = $key + 1;
        }

        $data['memberships']['data'] = $memberships;
        $data['memberships']['mem_chart'] = $mem_chart;

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    // manage membership chart data
    private function memChart($memberships, $time_type)
    {
        $mem_chart = [];
        foreach ($memberships as $key => $item) {
            switch ($time_type) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['mem_date']));
                    $chart_date = date('d M', strtotime($item['mem_date']));
                    break;
                case 'month':
                    $item_date = date('M', mktime(0, 0, 0, $item['mem_month'], 10)) ." ". $item['mem_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['mem_month'], 10));
                    break;
                case 'year':
                    $item_date = $item['mem_month'] ."-". $item['mem_year'];
                    $chart_date = date('M', mktime(0, 0, 0, $item['mem_month'], 10)) ." ". $item['mem_year'];
                    break;
                default:
                    break;
            }
            $memberships[$key]['date'] = $item_date;

            $mem_chart[] = [
                'date'      => $chart_date,
                'cust_total'=> (is_null($item['cust_total']) ? 0 : $item['cust_total'])
            ];
        }

        $data['memberships'] = $memberships;
        $data['mem_chart'] = $mem_chart;
        return $data;
    }


    // get voucher report
    public function getVoucherReport(Request $request, $flag=1)
    {
        $post = $request->json()->all();
        if (isset($post['voucher_id_outlet_1']) && $post['voucher_id_outlet_1']!=0) {
            $params_1['id_outlet'] = $post['voucher_id_outlet_1'];
        }
        if (isset($post['id_deals_1']) && $post['id_deals_1']!=0) {
            $params_1['id_deals'] = $post['id_deals_1'];
        }
        if (isset($post['voucher_id_outlet_2']) && $post['voucher_id_outlet_2']!=0) {
            $params_2['id_outlet'] = $post['voucher_id_outlet_2'];
        }
        if (isset($post['id_deals_2']) && $post['id_deals_2']!=0) {
            $params_2['id_deals'] = $post['id_deals_2'];
        }

        switch ($post['time_type']) {
            case 'day':
                $params_1['start_date'] = $post['param1'];
                $params_1['end_date'] = $post['param2'];

                $params_2['start_date'] = $post['param3'];
                $params_2['end_date'] = $post['param4'];
                break;
            case 'month':
                if ($post['param1'] == $post['param4'] &&
                    $post['param2'] == $post['param5'] &&
                    $post['param3'] == $post['param6']) {
                    $check_date = 1;
                }
                $params_1['start_month'] = $post['param1'];
                $params_1['end_month'] = $post['param2'];
                $params_1['year'] = $post['param3'];

                $params_2['start_month'] = $post['param4'];
                $params_2['end_month'] = $post['param5'];
                $params_2['year'] = $post['param6'];
                break;
            case 'year':
                if ($post['param1'] == $post['param3'] && $post['param2'] == $post['param4']) {
                    $check_date = 1;
                }
                $params_1['start_year'] = $post['param1'];
                $params_1['end_year'] = $post['param2'];

                $params_2['start_year'] = $post['param3'];
                $params_2['end_year'] = $post['param4'];
                break;
            default:
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid time type']
                ]);
                break;
        }

        // get data from database
        $apiSingleReport = new ApiSingleReport();
        $vouchers_1 = $apiSingleReport->voucherReport($params_1);
        $vouchers_2 = $apiSingleReport->voucherReport($params_2);

        // get max length of 2 arrays
        $count_1 = count($vouchers_1);
        $count_2 = count($vouchers_2);
        if ($count_2 > $count_1) {
            $max_count = $count_2;
        }
        else{
            $max_count = $count_1;
        }

        // get data for chart
        $chart_1 = $this->voucherChart($vouchers_1, $post['time_type']);
        $chart_2 = $this->voucherChart($vouchers_2, $post['time_type']);
        $vouchers_1 = $chart_1['vouchers'];
        $vouchers_2 = $chart_2['vouchers'];

        $voucher_chart = [];
        $voucher_gender_chart = [];
        $voucher_age_chart = [];
        $voucher_device_chart = [];
        $voucher_provider_chart = [];

        // manage if the length of compared array charts not equal
        // if data null, set placeholder "-" or "0"
        for ($i=0; $i < $max_count; $i++) {
            $voucher_1 = isset($chart_1['voucher_chart'][$i]) ? $chart_1['voucher_chart'][$i] : [];
            $voucher_2 = isset($chart_2['voucher_chart'][$i]) ? $chart_2['voucher_chart'][$i] : [];
            $gender_1 = isset($chart_1['voucher_gender_chart'][$i]) ? $chart_1['voucher_gender_chart'][$i] : [];
            $gender_2 = isset($chart_2['voucher_gender_chart'][$i]) ? $chart_2['voucher_gender_chart'][$i] : [];
            $age_1 = isset($chart_1['voucher_age_chart'][$i]) ? $chart_1['voucher_age_chart'][$i] : [];
            $age_2 = isset($chart_2['voucher_age_chart'][$i]) ? $chart_2['voucher_age_chart'][$i] : [];
            $device_1 = isset($chart_1['voucher_device_chart'][$i]) ? $chart_1['voucher_device_chart'][$i] : [];
            $device_2 = isset($chart_2['voucher_device_chart'][$i]) ? $chart_2['voucher_device_chart'][$i] : [];
            $provider_1 = isset($chart_1['voucher_provider_chart'][$i]) ? $chart_1['voucher_provider_chart'][$i] : [];
            $provider_2 = isset($chart_2['voucher_provider_chart'][$i]) ? $chart_2['voucher_provider_chart'][$i] : [];

            $date_1 = isset($gender_1['date']) ? $gender_1['date'] : '-';
            $date_2 = isset($gender_2['date']) ? $gender_2['date'] : '-';
            $date   = $date_1 .' vs '. $date_2;

            $voucher_chart[] = [
                'date'  => $date,
                'total_1' => (isset($voucher_1['total']) ? $voucher_1['total'] : 0),
                'total_2' => (isset($voucher_2['total']) ? $voucher_2['total'] : 0),
            ];
            $voucher_gender_chart[] = $this->genderChart($date, $gender_1, $gender_2);
            $voucher_age_chart[] = $this->ageChart($date, $age_1, $age_2);
            $voucher_device_chart[] = $this->deviceChart($date, $device_1, $device_2);
            $voucher_provider_chart[] = $this->providerChart($date, $provider_1, $provider_2);
        }
        
        $vouchers = array_merge($vouchers_1, $vouchers_2);

        // set number in datatable
        foreach ($vouchers as $key => $reg) {
            $vouchers[$key]['number'] = $key + 1;
        }

        $data['vouchers']['data'] = $vouchers;
        $data['vouchers']['voucher_chart'] = $voucher_chart;
        $data['vouchers']['voucher_gender_chart'] = $voucher_gender_chart;
        $data['vouchers']['voucher_age_chart'] = $voucher_age_chart;
        $data['vouchers']['voucher_device_chart'] = $voucher_device_chart;
        $data['vouchers']['voucher_provider_chart'] = $voucher_provider_chart;

        if ($flag==1) {
            // if called as api
            return response()->json(MyHelper::checkGet($data));
        }
        else{
            // if called in controller
            return $data;
        }
    }
    // manage voucher chart data
    private function voucherChart($vouchers, $time_type)
    {
        $voucher_chart = [];
        $voucher_gender_chart = [];
        $voucher_age_chart = [];
        $voucher_device_chart = [];
        $voucher_provider_chart = [];

        $apiSingleReport = new ApiSingleReport();
        foreach ($vouchers as $key => $item) {
            switch ($time_type) {
                case 'day':
                    $item_date = date('d-m-Y', strtotime($item['used_at']));
                    $chart_date = date('d M', strtotime($item['used_at']));
                    break;
                case 'month':
                    $item_date = date('M Y', strtotime($item['used_at']));
                    $chart_date = date('M', strtotime($item['used_at']));
                    break;
                case 'year':
                    $item_date = date('m-Y', strtotime($item['used_at']));
                    $chart_date = date('M Y', strtotime($item['used_at']));
                    break;
                default:
                    break;
            }

            $vouchers[$key]['date'] = $item_date;

            $voucher_chart[] = [
                'date'  => $chart_date,
                'total' => (is_null($item['voucher_count']) ? 0 : $item['voucher_count']),
            ];
            $voucher_gender_chart[] = $apiSingleReport->genderChart($chart_date, $item);
            $voucher_age_chart[] = $apiSingleReport->ageChart($chart_date, $item);
            $voucher_device_chart[] = $apiSingleReport->deviceChart($chart_date, $item);
            $voucher_provider_chart[] = $apiSingleReport->providerChart($chart_date, $item);
        }

        $data['vouchers'] = $vouchers;
        $data['voucher_chart'] = $voucher_chart;
        $data['voucher_gender_chart'] = $voucher_gender_chart;
        $data['voucher_age_chart'] = $voucher_age_chart;
        $data['voucher_device_chart'] = $voucher_device_chart;
        $data['voucher_provider_chart'] = $voucher_provider_chart;
        return $data;
    }


    // gender chart compare data
    private function genderChart($date, $item_1, $item_2)
    {
        return [
            'date'     => $date,
            'male_1'   => (isset($item_1['male']) ? $item_1['male'] : 0),
            'female_1' => (isset($item_1['female']) ? $item_1['female'] : 0),
            'male_2'   => (isset($item_2['male']) ? $item_2['male'] : 0),
            'female_2' => (isset($item_2['female']) ? $item_2['female'] : 0)
        ];
    }
    // age chart compare data
    private function ageChart($date, $item_1, $item_2)
    {
        return [
            'date'        => $date,
            'teens_1'     => (isset($item_1['teens']) ? $item_1['teens'] : 0),
            'young_adult_1' => (isset($item_1['young_adult']) ? $item_1['young_adult']:0),
            'adult_1'     => (isset($item_1['adult']) ? $item_1['adult'] : 0),
            'old_1'       => (isset($item_1['old']) ? $item_1['old'] : 0),

            'teens_2'     => (isset($item_2['teens']) ? $item_2['teens'] : 0),
            'young_adult_2' => (isset($item_2['young_adult']) ? $item_2['young_adult']:0),
            'adult_2'     => (isset($item_2['adult']) ? $item_2['adult'] : 0),
            'old_2'       => (isset($item_2['old']) ? $item_2['old'] : 0)
        ];
    }
    // device chart compare data
    private function deviceChart($date, $item_1, $item_2)
    {
        return [
            'date' => $date,
            'android_1' => (isset($item_1['android']) ? $item_1['android'] : 0),
            'ios_1' => (isset($item_1['ios']) ? $item_1['ios'] : 0),
            'android_2' => (isset($item_2['android']) ? $item_2['android'] : 0),
            'ios_2' => (isset($item_2['ios']) ? $item_2['ios'] : 0)
        ];
    }
    // provider chart compare data
    private function providerChart($date, $item_1, $item_2)
    {
        return [
            'date'      => $date,

            'telkomsel_1' => (isset($item_1['telkomsel']) ? $item_1['telkomsel'] : 0),
            'xl_1'        => (isset($item_1['xl']) ? $item_1['xl'] : 0),
            'indosat_1'   => (isset($item_1['indosat']) ? $item_1['indosat'] : 0),
            'tri_1'       => (isset($item_1['tri']) ? $item_1['tri'] : 0),
            'axis_1'      => (isset($item_1['axis']) ? $item_1['axis'] : 0),
            'smart_1'     => (isset($item_1['smart']) ? $item_1['smart'] : 0),

            'telkomsel_2' => (isset($item_2['telkomsel']) ? $item_2['telkomsel'] : 0),
            'xl_2'        => (isset($item_2['xl']) ? $item_2['xl'] : 0),
            'indosat_2'   => (isset($item_2['indosat']) ? $item_2['indosat'] : 0),
            'tri_2'       => (isset($item_2['tri']) ? $item_2['tri'] : 0),
            'axis_2'      => (isset($item_2['axis']) ? $item_2['axis'] : 0),
            'smart_2'     => (isset($item_2['smart']) ? $item_2['smart'] : 0)
        ];
    }


}