<?php

namespace Modules\Disburse\Http\Controllers;

use App\Exports\MultipleSheetExport;
use App\Exports\SummaryTrxBladeExport;
use App\Http\Models\Configs;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use App\Jobs\SendRecapManualy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;

use DB;
use Modules\Disburse\Entities\DisburseOutletTransaction;
use Modules\Disburse\Entities\UserFranchise;
use function Clue\StreamFilter\fun;

use App\Http\Models\Setting;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\DisburseOutlet;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Illuminate\Support\Facades\Storage;
use File;
use Mail;
use Maatwebsite\Excel\Excel;

class ApiDisburseController extends Controller
{
    function __construct() {
        $this->trx="Modules\Transaction\Http\Controllers\ApiTransaction";
    }

    public function dashboard(Request $request){
        $post = $request->json()->all();
        $nominal = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')->where('disburse.disburse_status', 'Success');
        $nominal_fail = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')->whereIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts']);
        $income_central = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')->where('disburse.disburse_status', 'Success');
        $total_disburse = DisburseOutletTransaction::join('transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                            ->where(function ($q){
                                $q->whereNotNull('transaction_pickups.taken_at')
                                    ->orWhereNotNull('transaction_pickups.taken_by_system_at');
                            })
                            ->where(function ($q){
                                $q->orWhereNull('id_disburse_outlet')
                                    ->orWhereIn('id_disburse_outlet', function($query){
                                    $query->select('id_disburse_outlet')
                                        ->from('disburse')
                                        ->join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                                        ->where('disburse.disburse_status', 'Queued');
                                });
                            });

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $nominal->where('disburse_outlet.id_outlet', $post['id_outlet']);
            $nominal_fail->where('disburse_outlet.id_outlet', $post['id_outlet']);
            $income_central->where('disburse_outlet.id_outlet', $post['id_outlet']);
            $total_disburse->where('transactions.id_outlet', $post['id_outlet']);
        }

        if(isset($post['fitler_date']) && $post['fitler_date'] == 'today'){

            $nominal->whereDate('disburse.created_at', date('Y-m-d'));
            $nominal_fail->whereDate('disburse.created_at', date('Y-m-d'));
            $income_central->where('disburse.created_at', date('Y-m-d'));
            $total_disburse->where('transactions.transaction_date', date('Y-m-d'));

        }elseif(isset($post['fitler_date']) && $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $nominal->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $nominal_fail->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $income_central->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $total_disburse->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date);
            }
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $nominal->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
            $nominal_fail->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        $nominal = $nominal->selectRaw('SUM(disburse_outlet.disburse_nominal-(disburse.disburse_fee / disburse.total_outlet)) as "nom_success", SUM(disburse_outlet.total_fee_item) as "nom_item", SUM(disburse_outlet.total_omset) as "nom_grandtotal", SUM(disburse_outlet.total_expense_central) as "nom_expense_central", SUM(disburse_outlet.total_delivery_price) as "nom_delivery"')->first();
        $nominal_fail = $nominal_fail->selectRaw('SUM(disburse_outlet.disburse_nominal-(disburse.disburse_fee / disburse.total_outlet)) as "disburse_nominal"')->first();
        $income_central = $income_central->sum('total_income_central');
        $total_disburse = $total_disburse->sum('disburse_outlet_transactions.income_outlet');

        $result = [
            'status' => 'success',
            'result' => [
                'nominal' => $nominal,
                'nominal_fail' => $nominal_fail,
                'income_central' => $income_central,
                'total_disburse' => $total_disburse
            ]
        ];
        return response()->json($result);
    }

    public function getOutlets(Request $request){
        $post = $request->json()->all();

        $outlet = Outlet::leftJoin('bank_account_outlets', 'bank_account_outlets.id_outlet', 'outlets.id_outlet')
            ->leftJoin('bank_accounts', 'bank_accounts.id_bank_account', 'bank_account_outlets.id_bank_account')
            ->leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name');

        if(isset($post['for'])){
            $outlet->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name');
        }else{
            $outlet->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name', 'outlets.status_franchise', 'outlets.outlet_special_status', 'outlets.outlet_special_fee',
                'bank_accounts.id_bank_name', 'bank_accounts.beneficiary_name', 'bank_accounts.beneficiary_alias', 'bank_accounts.beneficiary_account', 'bank_accounts.beneficiary_email',
                'bank_name.bank_name', 'bank_name.bank_code');
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $outlet->join('user_franchise_outlet', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_bank'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.bank_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.bank_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_name'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_name', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_alias'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_alias', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_account'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_account', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_email'){
                            if($row['operator'] == '='){
                                $outlet->where('bank_accounts.beneficiary_email', $row['parameter']);
                            }else{
                                $outlet->where('bank_accounts.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $outlet->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_bank'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.bank_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.bank_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_alias'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_alias', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_account'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_account', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('bank_accounts.beneficiary_email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('bank_accounts.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        if(isset($post['page'])){
            $outlet = $outlet->paginate(25);
        }else{
            $outlet = $outlet->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    function listDisburse(Request $request, $status){
        $post = $request->json()->all();

        $data = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'disburse_outlet.id_outlet')
            ->leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name');

        if(isset($post['id_disburse']) && !is_null($post['id_disburse'])){
            $data->where('disburse.id_disburse', $post['id_disburse']);
        }

        if($status != 'all'){
            $data->where('disburse.disburse_status', ucfirst($status));
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('disburse.created_at', '>=', $start_date)
                ->whereDate('disburse.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'error_status'){
                            $data->where('disburse.error_code', $row['operator'])
                                ->where('disburse.disburse_status', 'Fail');
                        }

                        if($row['subject'] == 'bank_name'){
                            $data->where('bank_name.id_bank_name', $row['operator']);
                        }

                        if($row['subject'] == 'status'){
                            $data->where('disburse.disburse_status', $row['operator']);
                        }

                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $data->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $data->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $data->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $data->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'account_number'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_account_number', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'recipient_name'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_name', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'error_status'){
                                $subquery->orWhere(function ($q) use($row){
                                    $q->where('disburse.error_code', $row['operator'])
                                        ->where('disburse.disburse_status', 'Fail');
                                });
                            }

                            if($row['subject'] == 'bank_name'){
                                $subquery->orWhere('bank_name.id_bank_name', $row['operator']);
                            }

                            if($row['subject'] == 'status'){
                                $subquery->orWhere('disburse.disburse_status', $row['operator']);
                            }

                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'account_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('disburse.beneficiary_account_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'recipient_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWheree('disburse.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        if(isset($post['export']) && $post['export'] == 1){
            $data = $data->selectRaw('disburse_status as "Disburse Status", bank_name.bank_name as "Bank Name", CONCAT(" ",disburse.beneficiary_account_number) as "Account Number", disburse.beneficiary_name as "Recipient Name", DATE_FORMAT(disburse.created_at, "%d %M %Y %H:%i") as "Date", CONCAT(outlets.outlet_code, " - ", outlets.outlet_name) as "Outlet", disburse_outlet.disburse_nominal as "Nominal Disburse",
                total_omset as "Total Gross Sales", total_discount as "Total Discount", total_delivery_price as "Total Delivery", 
                total_fee_item as "Total Fee Item", total_payment_charge as "Total Fee Payment", total_promo_charged as "Total Fee Promo",
                total_point_use_expense as "Total Fee Point Use", total_subscription as "Total Fee Subscription"')
                ->get()->toArray();

        }else{
            $data = $data->select('disburse.error_code', 'disburse.error_message', 'disburse_outlet.id_disburse_outlet', 'outlets.outlet_name', 'outlets.outlet_code', 'disburse.id_disburse', 'disburse_outlet.disburse_nominal', 'disburse.disburse_status', 'disburse.beneficiary_account_number',
                'disburse.beneficiary_name', 'disburse.created_at', 'disburse.updated_at', 'bank_name.bank_code', 'bank_name.bank_name', 'disburse.count_retry', 'disburse.error_message')->orderBy('disburse.created_at','desc')
                ->paginate(25);
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function listDisburseFailAction(Request $request){
        $post = $request->json()->all();

        $data = Disburse::leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name')
            ->with(['disburse_outlet'])
            ->whereIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts'])
            ->select('disburse.error_code', 'disburse.id_disburse', 'disburse.disburse_nominal', 'disburse.disburse_status', 'disburse.beneficiary_account_number',
                'disburse.beneficiary_name', 'disburse.created_at', 'disburse.updated_at', 'bank_name.bank_code', 'bank_name.bank_name', 'disburse.count_retry', 'disburse.error_message')->orderBy('disburse.created_at','desc');

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->whereIn('disburse.id_disburse', function ($query) use ($post){
                $query->select('disburse_outlet.id_disburse')
                    ->from('disburse_outlet')
                    ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                    ->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                    ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
            });
        }

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('disburse.created_at', '>=', $start_date)
                ->whereDate('disburse.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'error_status'){
                            $data->where('disburse.error_code', $row['operator']);
                        }

                        if($row['subject'] == 'bank_name'){
                            $data->where('bank_name.id_bank_name', $row['operator']);
                        }

                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $data->whereIn('disburse.id_disburse', function ($query) use ($row){
                                    $query->select('disburse_outlet.id_disburse')
                                        ->from('disburse_outlet')
                                        ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_code',$row['parameter']);
                                });
                            }else{
                                $data->whereIn('disburse.id_disburse', function ($query) use ($row){
                                    $query->select('disburse_outlet.id_disburse')
                                        ->from('disburse_outlet')
                                        ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $data->whereIn('disburse.id_disburse', function ($query) use ($row){
                                    $query->select('disburse_outlet.id_disburse')
                                        ->from('disburse_outlet')
                                        ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_name',$row['parameter']);
                                });
                            }else{
                                $data->whereIn('disburse.id_disburse', function ($query) use ($row){
                                    $query->select('disburse_outlet.id_disburse')
                                        ->from('disburse_outlet')
                                        ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                        ->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }

                        if($row['subject'] == 'account_number'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_account_number', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'recipient_name'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_name', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'error_status'){
                                $subquery->orWhere('disburse.error_code', $row['operator']);
                            }

                            if($row['subject'] == 'bank_name'){
                                $subquery->orWhere('bank_name.id_bank_name', $row['operator']);
                            }

                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('disburse.id_disburse', function ($query) use ($row){
                                        $query->select('disburse_outlet.id_disburse')
                                            ->from('disburse_outlet')
                                            ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_code',$row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('disburse.id_disburse', function ($query) use ($row){
                                        $query->select('disburse_outlet.id_disburse')
                                            ->from('disburse_outlet')
                                            ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('disburse.id_disburse', function ($query) use ($row){
                                        $query->select('disburse_outlet.id_disburse')
                                            ->from('disburse_outlet')
                                            ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_name',$row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('disburse.id_disburse', function ($query) use ($row){
                                        $query->select('disburse_outlet.id_disburse')
                                            ->from('disburse_outlet')
                                            ->join('outlets', 'disburse_outlet.id_outlet', 'outlets.id_outlet')
                                            ->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }

                            if($row['subject'] == 'account_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('disburse.beneficiary_account_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'recipient_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWheree('disburse.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    function listTrx(Request $request){
        $post = $request->json()->all();

        $data = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
            ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
            ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->where('transactions.transaction_payment_status', 'Completed')
            ->where('transactions.trasaction_type', '!=', 'Offline')
            ->select('disburse.disburse_status', 'transactions.*', 'outlets.outlet_name', 'outlets.outlet_code');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('transactions.created_at', '>=', $start_date)
                ->whereDate('transactions.created_at', '<=', $end_date);
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            if($row['operator'] == 'Unprocessed'){
                                $data->whereNull('disburse.disburse_status');
                            }else{
                                $data->where('disburse.disburse_status', $row['operator']);
                            }

                        }

                        if($row['subject'] == 'recipient_number'){
                            if($row['operator'] == '='){
                                $data->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $data->where('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                if($row['operator'] == 'Unprocessed'){
                                    $subquery->orWhereNull('disburse_status');
                                }else{
                                    $subquery->orWhere('disburse_status', $row['operator']);
                                }

                            }

                            if($row['subject'] == 'recipient_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    function detailDisburse(Request $request ,$id){
        $post = $request->json()->all();

        $disburse = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'disburse_outlet.id_outlet')
            ->leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name')
            ->where('disburse_outlet.id_disburse_outlet', $id)
            ->select('disburse_outlet.id_disburse_outlet', 'outlets.outlet_name', 'outlets.outlet_code', 'disburse.id_disburse', 'disburse_outlet.disburse_nominal', 'disburse.disburse_status', 'disburse.beneficiary_account_number',
                'disburse.beneficiary_name', 'disburse.created_at', 'disburse.updated_at', 'bank_name.bank_code', 'bank_name.bank_name')->first();
        $data = Transaction::join('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
            ->where('disburse_outlet_transactions.id_disburse_outlet', $id)
            ->select('disburse_outlet_transactions.*', 'transactions.*', 'transaction_payment_balances.balance_nominal');

        $config = [];
        if(isset($post['export']) && $post['export'] == 1){
            $config = Configs::where('config_name', 'show or hide info calculation disburse')->first();
            $data = $data->get()->toArray();
        }else{
            $data = $data->paginate(25);
        }

        $result = [
            'status' => 'success',
            'result' => [
                'data_disburse' => $disburse,
                'list_trx' => $data,
                'config' => $config
            ]
        ];
        return response()->json($result);
    }

    function listDisburseDataTable(Request $request, $status){
        $post = $request->json()->all();

        $start = $post['start'];
        $length = $post['length'];

        $data = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->join('outlets', 'outlets.id_outlet', 'disburse_outlet.id_outlet')
            ->leftJoin('bank_name', 'bank_name.bank_code', 'disburse.beneficiary_bank_name')
            ->select('disburse_outlet.id_disburse_outlet as 0', DB::raw("CONCAT (outlets.outlet_code, ' - ',outlets.outlet_name) as '1'"), DB::raw("DATE_FORMAT(disburse.created_at, '%d %b %Y %H:%i') as '2'"), DB::raw('FORMAT(disburse.disburse_nominal,2) as "3"'), 'disburse.disburse_status',
                'bank_name.bank_name as 4', 'disburse.beneficiary_account_number as 5', 'disburse.beneficiary_name as 6', 'disburse.updated_at', 'bank_name.bank_code')->orderBy('disburse.created_at','desc');

        if($status != 'all'){
            $data->where('disburse.disburse_status', $status);
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['fitler_date']) &&  $post['fitler_date']== 'today'){
            $data->whereDate('disburse.created_at', date('Y-m-d'));
        }elseif(isset($post['fitler_date']) &&  $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $data->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
            }
        }

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $data->where('disburse_outlet.id_outlet', $post['id_outlet']);
        }

        $total = $data->count();
        $data = $data->skip($start)->take($length)->get()->toArray();
        $result = [
            'status' => 'success',
            'result' => $data,
            'total' => $total
        ];

        return response()->json($result);
    }

    function listCalculationDataTable(Request $request){
        $post = $request->json()->all();

        $start = $post['start'];
        $length = $post['length'];

        $data = DisburseOutletTransaction::join('transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
            ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
            ->select('disburse.disburse_status as 0', DB::raw("CONCAT (outlets.outlet_code, ' - ',outlets.outlet_name) as '1'"), DB::raw("DATE_FORMAT(disburse.created_at, '%d %b %Y %H:%i') as '2'"),
                DB::raw("DATE_FORMAT(transactions.transaction_date, '%d %b %Y %H:%i') as '3'"), 'transactions.transaction_receipt_number as 4',
                DB::raw('FORMAT(transactions.transaction_grandtotal,2) as "5"'), DB::raw('FORMAT(transactions.transaction_discount,2) as "6"'), DB::raw('transactions.transaction_shipment_go_send as "7"'),
                DB::raw('FORMAT(transactions.transaction_subtotal,2) as "8"'), DB::raw('FORMAT(disburse_outlet_transactions.fee_item,2) as "9"'), DB::raw('FORMAT(disburse_outlet_transactions.payment_charge,2) as "10"'),
                DB::raw('FORMAT(disburse_outlet_transactions.discount,2) as "11"'), DB::raw('FORMAT(disburse_outlet_transactions.subscription,2) as "12"'), DB::raw('FORMAT(disburse_outlet_transactions.point_use_expense,2) as "13"'),
                DB::raw('FORMAT(disburse_outlet_transactions.income_outlet,2) as "14"'), DB::raw('FORMAT(disburse_outlet_transactions.income_central,2) as "15"'), DB::raw('FORMAT(disburse_outlet_transactions.expense_central,2) as "16"'))
            ->orderBy('transactions.transaction_date','desc');

        if(isset($post['fitler_date']) &&  $post['fitler_date']== 'today'){
            $data->where(function ($q){
                $q->whereDate('disburse.created_at', date('Y-m-d'))
                    ->orWhereDate('transactions.transaction_date', date('Y-m-d'));
            });
        }elseif(isset($post['fitler_date']) &&  $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $data->where(function ($qu) use($start_date,$end_date){
                    $qu->where(function ($q) use($start_date,$end_date){
                        $q->whereDate('disburse.created_at', '>=', $start_date)
                            ->whereDate('disburse.created_at', '<=', $end_date);
                    });

                    $qu->orWhere(function ($q) use($start_date,$end_date){
                        $q->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date);
                    });
                });
            }
        }

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $data->where('transactions.id_outlet', $post['id_outlet']);
        }

        $total = $data->count();
        $data = $data->skip($start)->take($length)->get()->toArray();
        $result = [
            'status' => 'success',
            'result' => $data,
            'total' => $total
        ];

        return response()->json($result);
    }

    function syncListBank(){
        $getListBank = MyHelper::connectIris('Banks', 'GET','api/v1/beneficiary_banks',[]);

        if(isset($getListBank['status']) && $getListBank['status'] == 'success'){
            $getCurrentListBank = BankName::get()->toArray();
            $currentBank = array_column($getCurrentListBank, 'bank_code');

            $arrTmp = [];
            foreach ($getListBank['response']['beneficiary_banks'] as $dt){
                $checkExist = array_search($dt['code'], $currentBank);
                if($checkExist === false){
                    $arrTmp[] = [
                        'bank_code' => $dt['code'],
                        'bank_name' => $dt['name']
                    ];
                }
            }

            BankName::insert($arrTmp);
        }

        return 'success';
    }


    function userFranchise(Request $request){
        $post = $request->json()->all();

        if(isset($post['user_type'])){
            $data = UserFranchise::where('user_franchise_type', $post['user_type'])->get()->toArray();
        }elseif(isset($post['phone'])){
            $data = UserFranchise::where('phone', $post['phone'])->first();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function userFranchiseResetPassword(Request $request){
        $post = $request->json()->all();
        $get = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->first();

        if(!password_verify($post['current_pin'], $get['password'])){
            return response()->json(['status' => 'fail', 'message' => 'Current pin does not match']);
        }else{
            $update = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->update([
                'password' => bcrypt($post['pin']), 'password_default_plain_text' => NULL
            ]);

            if($update){
                return response()->json(['status' => 'success']);
            }else{
                return response()->json(['status' => 'fail', 'message' => 'Failed update pin']);
            }
        }
    }

    function updateStatusDisburse(Request $request){
        $post = $request->json()->all();
        $checkFirst = Disburse::where('id_disburse', $post['id'])->first();
        if($checkFirst['disburse_status'] == 'Failed Create Payouts'){
            $update = Disburse::where('id_disburse', $post['id'])->update(['disburse_status' => 'Retry From Failed Payouts']);
        }elseif(strpos($checkFirst['error_message'],"Partner does not have sufficient balance for the payout") !== false){
            $update = Disburse::where('id_disburse', $post['id'])->update(['disburse_status' => 'Queued']);
        }else{
            $update = Disburse::where('id_disburse', $post['id'])->update(['disburse_status' => $post['disburse_status']]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    function sendRecapTransactionOultet(Request $request){
        $post = $request->json()->all();
        SendRecapManualy::dispatch(['date' => $post['date'], 'type' => 'recap_transaction_to_outlet'])->onConnection('disbursequeue');
        return 'Success';
    }

    public function cronSendEmailDisburse($date = null){
        $log = MyHelper::logCron('Send Email Recap To Outlet');
        try {
            $currentDate = date('Y-m-d');
            $yesterday = date('Y-m-d',strtotime($currentDate . "-1 days"));
            if(!empty($date)){
                $yesterday =  date('Y-m-d', strtotime($date));
            }

            $getOultets = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')->whereNull('reject_at')
                ->whereDate('transaction_date', $yesterday)
                ->groupBy('transactions.id_outlet')->pluck('id_outlet');
            $getEmail = Outlet::whereIn('id_outlet', $getOultets)
                ->whereNotNull('outlet_email')
                ->groupBy('outlet_email')
                ->pluck('outlet_email');

            if(!empty($getEmail)){
                foreach ($getEmail as $e){
                    $tmpPath = [];
                    $tmpOutlet = [];
                    $outlets = Outlet::where('outlet_email', $e)->select('id_outlet', 'outlet_code', 'outlet_name', 'outlet_email')->get()->toArray();
                    foreach ($outlets as $outlet){
                        if(empty($outlet['outlet_email'])){
                            continue 2;
                        }
                        $filter['date_start'] = $yesterday;
                        $filter['date_end'] = $yesterday;
                        $filter['detail'] = 1;
                        $filter['key'] = 'all';
                        $filter['rule'] = 'and';
                        $filter['conditions'] = [
                            [
                                'subject' => 'id_outlet',
                                'operator' => $outlet['id_outlet'],
                                'parameter' => null
                            ],
                            [
                                'subject' => 'status',
                                'operator' => 'Completed',
                                'parameter' => null
                            ]
                        ];

                        $summary = $this->summaryCalculationFee($yesterday, $outlet['id_outlet']);
                        $generateTrx = app($this->trx)->exportTransaction($filter, 1);
                        $dataDisburse = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                            ->join('disburse_outlet_transactions as dot', 'dot.id_transaction', 'transactions.id_transaction')
                            ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
                            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                            ->where('transaction_payment_status', 'Completed')
                            ->whereNull('reject_at')
                            ->where('transactions.id_outlet', $outlet['id_outlet'])
                            ->whereDate('transactions.transaction_date', $yesterday)
                            ->with(['transaction_payment_subscription'=> function($q){
                                $q->join('subscription_user_vouchers', 'subscription_user_vouchers.id_subscription_user_voucher', 'transaction_payment_subscriptions.id_subscription_user_voucher')
                                    ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                                    ->leftJoin('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription');
                            }, 'vouchers.deal', 'promo_campaign'])
                            ->select('payment_type', 'payment_method', 'dot.*', 'outlets.outlet_name', 'outlets.outlet_code', 'transactions.transaction_receipt_number',
                                'transactions.transaction_date', 'transactions.transaction_shipment_go_send',
                                'transactions.transaction_grandtotal',
                                'transactions.transaction_discount', 'transactions.transaction_subtotal')
                            ->get()->toArray();

                        if(!empty($generateTrx)){
                            $excelFile = 'Transaction_['.$yesterday.']_['.$outlet['outlet_code'].'].xlsx';
                            $store  = (new MultipleSheetExport([
                                "Summary" => $summary,
                                "Calculation Fee" => $dataDisburse,
                                "Detail Transaction" => $generateTrx
                            ]))->store('excel_email/'.$excelFile);

                            if($store){
                                $tmpPath[] = storage_path('app/excel_email/'.$excelFile);
                                $tmpOutlet[] = $outlet['outlet_code'].' - '.$outlet['outlet_name'];
                            }
                        }
                    }

                    if(!empty($tmpPath)){
                        $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                        $setting = array();
                        foreach ($getSetting as $key => $value) {
                            if($value['key'] == 'email_setting_url'){
                                $setting[$value['key']]  = (array)json_decode($value['value_text']);
                            }else{
                                $setting[$value['key']] = $value['value'];
                            }
                        }

                        $data = array(
                            'customer' => '',
                            'html_message' => 'Report Transaksi tanggal '.date('d M Y', strtotime($yesterday)).'.<br><br> List Outlet : <br>'.implode('<br>',$tmpOutlet),
                            'setting' => $setting
                        );

                        $to = $outlets[0]['outlet_email'];
                        $subject = 'Report Transaksi ['.date('d M Y', strtotime($yesterday)).']';
                        $name =  $outlets[0]['outlet_name'];
                        $variables['attachment'] = $tmpPath;

                        try{
                            Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting,$variables)
                            {
                                $message->to($to, $name)->subject($subject);
                                if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                                    $message->from($setting['email_sender'], $setting['email_from']);
                                }else if(!empty($setting['email_sender'])){
                                    $message->from($setting['email_sender']);
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

                                // attachment
                                if(isset($variables['attachment']) && !empty($variables['attachment'])){
                                    foreach($variables['attachment'] as $attach){
                                        $message->attach($attach);
                                    }
                                }
                            });
                        }catch(\Exception $e){
                        }

                        foreach ($tmpPath as $t){
                            File::delete($t);
                        }
                    }
                }
            }

            $log->success();
            return 'succes';
        }catch (\Exception $e) {
            $log->fail($e->getMessage());
        };
    }

    function summaryCalculationFee($date, $id_outlet = null){
        $summaryFee = [];
        $summaryFee = DisburseOutletTransaction::join('transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_subscriptions as tps', 'tps.id_transaction', 'transactions.id_transaction')
            ->whereDate('transactions.transaction_date', $date)
            ->where('transactions.transaction_payment_status', 'Completed')
            ->whereNull('transaction_pickups.reject_at')
            ->selectRaw('COUNT(transactions.id_transaction) total_trx, SUM(transactions.transaction_grandtotal) as total_gross_sales,
                        SUM(tps.subscription_nominal) as total_subscription, 
                        SUM(transactions.transaction_subtotal) as total_sub_total, 
                        SUM(transactions.transaction_shipment_go_send) as total_delivery, SUM(transactions.transaction_discount) as total_discount, 
                        SUM(fee_item) total_fee_item, SUM(payment_charge) total_fee_pg, SUM(income_outlet) total_income_outlet,
                        SUM(discount_central) total_income_promo, SUM(subscription_central) total_income_subscription');

        if($id_outlet){
            $summaryFee = $summaryFee->where('id_outlet', $id_outlet);
        }

        $summaryFee = $summaryFee->first()->toArray();

        $config = Configs::where('config_name', 'show or hide info calculation disburse')->first();

        $summaryProduct = TransactionProduct::join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->join('products as p', 'p.id_product', 'transaction_products.id_product')
            ->where('transaction_payment_status', 'Completed')
            ->whereNull('reject_at')
            ->whereDate('transaction_date', $date)
            ->where('transactions.id_outlet', $id_outlet)
            ->groupBy('transaction_products.id_product')
            ->selectRaw("p.product_name as name, 'Product' as type, SUM(transaction_products.transaction_product_qty) as total_qty");
        $summaryModifier = TransactionProductModifier::join('transactions', 'transactions.id_transaction', 'transaction_product_modifiers.id_transaction')
            ->join('transaction_products as tp', 'tp.id_transaction_product', 'transaction_product_modifiers.id_transaction_product')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->join('product_modifiers as pm', 'pm.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
            ->where('transaction_payment_status', 'Completed')
            ->whereNull('reject_at')
            ->whereDate('transaction_date', $date)
            ->where('transactions.id_outlet', $id_outlet)
            ->groupBy('transaction_product_modifiers.id_product_modifier')
            ->selectRaw("pm.text as name, 'Modifier' as type, SUM(transaction_product_modifiers.qty * tp.transaction_product_qty) as total_qty");

        $summary = $summaryProduct->unionAll($summaryModifier)->get()->toArray();
        return [
            'summary_product' => $summary,
            'summary_fee' => $summaryFee,
            'config' => $config
        ];
    }

    public function dashboardV2(Request $request)
    {
    	$pending 	= $this->getDisburseDashboardData($request, 'pending');
    	$processed 	= $this->getDisburseDashboardData($request, 'processed');

    	$result	= [
    		'pending'	=> $pending,
    		'processed'	=> $processed
    	];

    	return MyHelper::checkGet($result);
    }

    public function getDisburseDashboardData($request, $status){
        $post = $request->json()->all();

        if ($status == 'pending') {
        	$operator = '!=';
        }
        else{
        	$operator = '=';
        }

        $nominal = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse');
        $income_central = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse');

        if ($status == 'processed') {
        	$nominal = $nominal->whereIn('disburse.disburse_status', ['Success']);
	        $income_central = $income_central->whereIn('disburse.disburse_status', ['Success']);

        	$nominal_fail = Disburse::join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')->whereIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts']);
        }

        if ($status == 'pending') {
        	$nominal = $nominal->whereNotIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts', 'Success']);
	        $income_central = $income_central->whereNotIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts', 'Success']);

	        $total_disburse = DisburseOutletTransaction::join('transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
	                            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
	                            ->where(function ($q){
	                                $q->whereNotNull('transaction_pickups.taken_at')
	                                    ->orWhereNotNull('transaction_pickups.taken_by_system_at');
	                            })
	                            ->where(function ($q){
	                                $q->orWhereNull('id_disburse_outlet')
	                                    ->orWhereIn('id_disburse_outlet', function($query){
	                                    $query->select('id_disburse_outlet')
	                                        ->from('disburse')
	                                        ->join('disburse_outlet', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
	                                        ->where('disburse.disburse_status', 'Queued');
	                                });
	                            });
        }

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $nominal->where('disburse_outlet.id_outlet', $post['id_outlet']);
            $income_central->where('disburse_outlet.id_outlet', $post['id_outlet']);
            if ($status == 'processed') {
	            $nominal_fail->where('disburse_outlet.id_outlet', $post['id_outlet']);
	        }
	        if ($status == 'pending') {
	            $total_disburse->where('transactions.id_outlet', $post['id_outlet']);
	        }
        }

        if(isset($post['fitler_date']) && $post['fitler_date'] == 'today'){

            $nominal->whereDate('disburse.created_at', date('Y-m-d'));
            $income_central->where('disburse.created_at', date('Y-m-d'));
            if ($status == 'processed') {
            	$nominal_fail->whereDate('disburse.created_at', date('Y-m-d'));
            }
            if ($status == 'pending') {
            	$total_disburse->where('transactions.transaction_date', date('Y-m-d'));
            }

        }elseif(isset($post['fitler_date']) && $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $nominal->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $income_central->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                if ($status == 'processed') {
	                $nominal_fail->whereDate('disburse.created_at', '>=', $start_date)
	                    ->whereDate('disburse.created_at', '<=', $end_date);
	            }
	            if ($status == 'pending') {
	                $total_disburse->whereDate('transactions.transaction_date', '>=', $start_date)
	                    ->whereDate('transactions.transaction_date', '<=', $end_date);
	            }
            }
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $nominal->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                ->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);

            if ($status == 'processed') {
            	$nominal_fail->join('user_franchise_outlet', 'user_franchise_outlet.id_outlet', 'disburse_outlet.id_outlet')
                	->where('user_franchise_outlet.id_user_franchise', $post['id_user_franchise']);
            }
        }

        $nominal = $nominal->selectRaw(
        	'SUM(disburse_outlet.disburse_nominal-(disburse.disburse_fee / disburse.total_outlet)) as "nom_success", 
        	SUM(disburse_outlet.total_fee_item) as "nom_item", 
        	SUM(disburse_outlet.total_omset) as "nom_grandtotal", 
        	SUM(disburse_outlet.total_expense_central) as "nom_expense_central", 
        	SUM(disburse_outlet.total_delivery_price) as "nom_delivery"'
        )->first();

        $income_central = $income_central->sum('total_income_central');
        if ($status == 'processed') {
        	$nominal_fail = $nominal_fail->selectRaw('SUM(disburse_outlet.disburse_nominal-(disburse.disburse_fee / disburse.total_outlet)) as "disburse_nominal"')->first();
        }
        if ($status == 'pending') {
        	$total_disburse = $total_disburse->sum('disburse_outlet_transactions.income_outlet');
        }

        $result = [
            'nominal' => $nominal,
            'nominal_fail' => $nominal_fail??0,
            'income_central' => $income_central,
            'total_disburse' => $total_disburse??0
        ];
        return $result;
        // return response()->json($result);
    }

    public function sendRecap(Request $request){
        $post = $request->json()->all();
        SendRecapManualy::dispatch(['date' => $post['date'], 'type' => 'recap_disburse'])->onConnection('disbursequeue');

        return 'Success';
    }

    public function shortcutRecap($date = null){
        $log = MyHelper::logCron('Send Recap Disburse');
        try {
            $currentDate = date('Y-m-d');
            $yesterday = date('Y-m-d',strtotime($currentDate . "-1 days"));
            if(!empty($date)){
                $yesterday =  date('Y-m-d', strtotime($date));
            }

            $filter['date_start'] = $yesterday;
            $filter['date_end'] = $yesterday;
            $filter['detail'] = 1;
            $filter['key'] = 'all';
            $filter['rule'] = 'and';
            $filter['conditions'] = [
                [
                    'subject' => 'status',
                    'operator' => 'Completed',
                    'parameter' => null
                ]
            ];

            $generateTrx = app($this->trx)->exportTransaction($filter, 1);
            $dataDisburse = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->join('disburse_outlet_transactions as dot', 'dot.id_transaction', 'transactions.id_transaction')
                ->leftJoin('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->whereNull('reject_at')
                ->whereDate('transactions.transaction_date', $yesterday)
                ->with(['transaction_payment_subscription'=> function($q){
                    $q->join('subscription_user_vouchers', 'subscription_user_vouchers.id_subscription_user_voucher', 'transaction_payment_subscriptions.id_subscription_user_voucher')
                        ->join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                        ->leftJoin('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription');
                }, 'vouchers.deal', 'promo_campaign'])
                ->select('payment_type', 'payment_method', 'dot.*', 'outlets.outlet_name', 'outlets.outlet_code', 'transactions.transaction_receipt_number',
                    'transactions.transaction_date', 'transactions.transaction_shipment_go_send',
                    'transactions.transaction_grandtotal',
                    'transactions.transaction_discount', 'transactions.transaction_subtotal')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();

            $getEmailTo = Setting::where('key', 'email_to_send_recap_transaction')->first();

            if(!empty($dataDisburse) && !empty($generateTrx) && !empty($getEmailTo['value'])){
                $excelFile = 'Transaction_['.$yesterday.'].xlsx';
                $summary = $this->summaryCalculationFee($yesterday);
                $store  = (new MultipleSheetExport([
                    "Summary" => $summary,
                    "Calculation Fee" => $dataDisburse,
                    "Detail Transaction" => $generateTrx
                ]))->store('excel_email/'.$excelFile);

                if($store){
                    $tmpPath[] = storage_path('app/excel_email/'.$excelFile);
                }

                if(!empty($tmpPath)){
                    $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();

                    $setting = array();
                    foreach ($getSetting as $key => $value) {
                        if($value['key'] == 'email_setting_url'){
                            $setting[$value['key']]  = (array)json_decode($value['value_text']);
                        }else{
                            $setting[$value['key']] = $value['value'];
                        }
                    }

                    $data = array(
                        'customer' => '',
                        'html_message' => 'Report Transaksi tanggal '.date('d M Y', strtotime($yesterday)),
                        'setting' => $setting
                    );

                    $to = $getEmailTo['value'];
                    $subject = 'Report Transaksi ['.date('d M Y', strtotime($yesterday)).']';
                    $name =  '';
                    $variables['attachment'] = $tmpPath;

                    try{
                        Mail::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting,$variables)
                        {
                            $message->to($to, $name)->subject($subject);
                            if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                                $message->from($setting['email_sender'], $setting['email_from']);
                            }else if(!empty($setting['email_sender'])){
                                $message->from($setting['email_sender']);
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

                            // attachment
                            if(isset($variables['attachment']) && !empty($variables['attachment'])){
                                foreach($variables['attachment'] as $attach){
                                    $message->attach($attach);
                                }
                            }
                        });
                    }catch(\Exception $e){
                    }

                    foreach ($tmpPath as $t){
                        File::delete($t);
                    }
                }

            }

            $log->success();
            return 'succes';
        }catch (\Exception $e) {
            $log->fail($e->getMessage());
        };
    }
}
