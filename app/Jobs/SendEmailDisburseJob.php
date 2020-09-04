<?php

namespace App\Jobs;

use App\Http\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Disburse\Entities\Disburse;
use Modules\Disburse\Entities\DisburseOutlet;
use DB;
use Mail;
use Rap2hpoutre\FastExcel\FastExcel;
use File;
use Storage;

class SendEmailDisburseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$disburse;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $getDataDisburse = Disburse::where('reference_no', $this->data['reference_no'])
                            ->join('bank_accounts', 'bank_accounts.id_bank_account', 'disburse.id_bank_account')
                            ->select('disburse.*', 'bank_accounts.send_email_to')
                            ->first();

        if($getDataDisburse && empty($getDataDisburse['send_email_status'])){
            $feeDisburse = (int)$getDataDisburse['disburse_fee'];
            $disburseOutlet = DisburseOutlet::join('disburse_outlet_transactions as dot', 'dot.id_disburse_outlet', 'disburse_outlet.id_disburse_outlet')
                              ->join('transactions as t', 'dot.id_transaction', 't.id_transaction')
                              ->join('outlets as o', 'o.id_outlet', 't.id_outlet')
                              ->where('disburse_outlet.id_disburse', $getDataDisburse['id_disburse'])
                              ->groupBy(DB::raw('DATE(t.transaction_date)'),'t.id_outlet');

            if($getDataDisburse['send_email_to'] == 'Email Outlet'){
                $disburseOutlet = $disburseOutlet->selectRaw('t.transaction_date, o.outlet_code, o.outlet_name, o.outlet_email, Sum(income_outlet) as nominal')
                                  ->get()->toArray();
                $feePerOutlet = $feeDisburse / count($disburseOutlet);
                $data = [];
                foreach ($disburseOutlet as $dt){
                    $check = array_search($dt['outlet_code'], array_column($disburseOutlet, 'outlet_code'));
                    if($check !== false){
                        $data[] = [
                            'outlet_code' => $dt['outlet_code'],
                            'outlet_name' => $dt['outlet_name'],
                            'outlet_email' => $dt['outlet_email'],
                            'datas' => [[
                                'Transaction Date' => date('d M Y', strtotime($dt['transaction_date'])),
                                'Outlet' => $dt['outlet_code'].' - '.$dt['outlet_name'],
                                'Nominal' => number_format($dt['nominal'], 2)
                            ]]
                        ];
                    }else{
                        $arrTmp[$check]['datas'][] = [
                            'Transaction Date' => date('d M Y', strtotime($dt['transaction_date'])),
                            'Outlet' => $dt['outlet_code'].' - '.$dt['outlet_name'],
                            'Nominal' => number_format($dt['nominal'], 2)
                        ];
                    }
                }

                /*send excel to outlet*/
                if(!empty($data)){
                    foreach ($data  as $val){
                        if($val['outlet_email']){
                            $fileName = 'Disburse_['.date('d M Y').']_['.$val['outlet_code'].'].xlsx';
                            $path = storage_path('app/excel_email/'.$fileName);
                            $val['datas'][] = [
                                'Transaction Date' => '',
                                'Outlet' => 'Fee Disburse',
                                'Nominal' => -$feePerOutlet
                            ];
                            if(!Storage::disk(env('local'))->exists('excel_email')){
                                Storage::makeDirectory('excel_email');
                            }
                            $store = (new FastExcel($val['datas']))->export($path);

                            if($store){
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
                                    'html_message' => 'Laporan Disburse tanggal '.date('d M Y').' untuk outlet '.$val['outlet_code'].'-'.$val['outlet_name'].'.',
                                    'setting' => $setting
                                );

                                $to = $val['outlet_email'];
                                $subject = 'Report Disburse ['.date('d M Y').']['.$val['outlet_code'].']';
                                $name =  $val['outlet_name'];
                                $variables['attachment'] = [$path];

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

                                foreach ($variables['attachment'] as $t){
                                    File::delete($t);
                                }
                            }

                            Disburse::where('reference_no', $this->data['reference_no'])->update(['send_email_status' => 1]);
                        }
                    }
                }
            }elseif($getDataDisburse['send_email_to'] == 'Email Bank'){
                $disburseOutlet = $disburseOutlet->selectRaw('DATE_FORMAT(t.transaction_date, "%d %M %Y") as "Transaction Date", CONCAT(o.outlet_code, " - ", o.outlet_name) AS Outlet, FORMAT(SUM(income_outlet), 2) as Nominal')
                    ->get()->toArray();

                if($getDataDisburse['beneficiary_email']){
                    $fileName = 'Disburse_['.date('d M Y').']['.$this->data['reference_no'].'].xlsx';
                    $path = storage_path('app/excel_email/'.$fileName);
                    $listOutlet = array_column($disburseOutlet, 'Outlet');
                    $disburseOutlet[] = [
                        'Transaction Date' => '',
                        'Outlet' => 'Fee Disburse',
                        'Nominal' => -$feeDisburse
                    ];
                    if(!Storage::disk(env('local'))->exists('excel_email')){
                        Storage::makeDirectory('excel_email');
                    }

                    $store = (new FastExcel($disburseOutlet))->export($path);

                    if($store){
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
                            'html_message' => 'Laporan Disburse tanggal '.date('d M Y').'.<br><br> List Outlet : <br>'.implode('<br>',$listOutlet),
                            'setting' => $setting
                        );

                        $to = $getDataDisburse['beneficiary_email'];
                        $subject = 'Report Disburse ['.date('d M Y').']';
                        $name =  $getDataDisburse['beneficiary_name'];
                        $variables['attachment'] = [$path];

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

                        foreach ($variables['attachment'] as $t){
                            File::delete($t);
                        }
                    }

                    Disburse::where('reference_no', $this->data['reference_no'])->update(['send_email_status' => 1]);
                }
            }
        }

        return true;
    }
}
