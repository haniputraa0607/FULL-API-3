<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\LogBalance;
use App\Http\Models\LogTopup;
use App\Http\Models\UsersMembership;
use App\Http\Models\FraudSetting;
use App\Http\Models\User;


use Illuminate\Support\Facades\Schema;
use DB;
use Hash;


class ApiCronBalanceController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiSettingFraud";
    }

    public function cekLogBalance(Request $request){
        $log = LogBalance::get();
        foreach($log as $dataLog){
            $dataHashBalance = [
                'id_log_balance'                 => $dataLog->id_log_balance,
                'id_user'                        => $dataLog->id_user,
                'balance'                        => $dataLog->balance,
                'balance_before'                 => $dataLog->balance_before,
                'balance_after'                  => $dataLog->balance_after,
                'id_reference'                   => $dataLog->id_reference,
                'source'                         => $dataLog->source,
                'grand_total'                    => $dataLog->grand_total,
                'ccashback_conversion'           => $dataLog->ccashback_conversion,
                'membership_level'               => $dataLog->membership_level,
                'membership_cashback_percentage' => $dataLog->membership_cashback_percentage
            ];
            $encodeCheck = json_encode($dataHashBalance);
            $enc = Hash::make($encodeCheck);
            
            //encrypt manual biar bisa di decrypt
            // $enc = MyHelper::encrypt2019($dataHashBalance);
            
            // update enc column
            $log_balance = LogBalance::where('id_log_balance', $dataLog->id_log_balance)->update(['enc' => $enc]);
            //  $dataHashBalance = [
            //     'id_log_balance'                 => $dataLog->id_log_balance,
            //     'id_user'                        => $dataLog->id_user,
            //     'balance'                        => $dataLog->balance,
            //     'balance_before'                 => $dataLog->balance_before,
            //     'balance_after'                  => $dataLog->balance_after,
            //     'id_reference'                   => $dataLog->id_reference,
            //     'source'                         => $dataLog->source,
            //     'grand_total'                    => $dataLog->grand_total,
            //     'ccashback_conversion'           => $dataLog->ccashback_conversion,
            //     'membership_level'               => $dataLog->membership_level,
            //     'membership_cashback_percentage' => $dataLog->membership_cashback_percentage
            // ];
            
            // if($dataLog->enc){
            //     $decrypt = MyHelper::decrypt2019($dataLog->enc);
            //     $decode = json_decode($decrypt);
                
            //     $fraud = false;
            //     $fraudData = [];
            //     $updateData = [];
            //     if($decode->id_log_balance != $dataLog->id_log_balance){
            //         $fraud = true;
            //         $fraudData[] = ['id_log_balance_table' => $dataLog->id_log_balance, 'id_log_balance_enc' => $decode->id_log_balance];
            //         $updateData[] = ['id_log_balance' => $decode->id_log_balance];
            //     }
            //     if($decode->id_user != $dataLog->id_user){
            //         $fraud = true;
            //         $fraudData[] = ['id_user_table' => $dataLog->id_user, 'id_user_enc' => $decode->id_user];
            //         $updateData[] = ['id_user' => $decode->id_user];
            //     }
            //     if($decode->balance != $dataLog->balance){
            //         $fraud = true;
            //         $fraudData[] = ['balance_table' => $dataLog->balance, 'balance_enc' => $decode->balance];
            //          $updateData[] = ['balance' => $decode->balance];
            //     }
            //     if($decode->balance_before != $dataLog->balance_before){
            //         $fraud = true;
            //         $fraudData[] = ['balance_before_table' => $dataLog->balance_before, 'balance_before_enc' => $decode->balance_before];
            //         $updateData[] = ['balance_before' => $decode->balance_before];
            //     }
            //     if($decode->balance_after != $dataLog->balance_after){
            //         $fraud = true;
            //         $fraudData[] = ['balance_after_table' => $dataLog->balance_after, 'balance_after_enc' => $decode->balance_after];
            //         $updateData[] = ['balance_after' => $decode->balance_after];
            //     }
            //     if($decode->id_reference != $dataLog->id_reference){
            //         $fraud = true;
            //         $fraudData[] = ['id_reference_table' => $dataLog->id_reference, 'id_reference_enc' => $decode->id_reference];
            //         $updateData[] = ['id_reference' => $decode->id_reference];
            //     }
            //     if($decode->source != $dataLog->source){
            //         $fraud = true;
            //         $fraudData[] = ['source_table' => $dataLog->source, 'source_enc' => $decode->source];
            //         $updateData[] = ['source' => $decode->source];
            //     }
            //     if($decode->grand_total != $dataLog->grand_total){
            //         $fraud = true;
            //         $fraudData[] = ['grand_total_table' => $dataLog->grand_total, 'grand_total_enc' => $decode->grand_total];
            //         $updateData[] = ['grand_total' => $decode->grand_total];
            //     }
            //     if($decode->membership_level != $dataLog->membership_level){
            //         $fraud = true;
            //         $fraudData[] = ['membership_level_table' => $dataLog->membership_level, 'membership_level_enc' => $decode->membership_level];
            //         $updateData[] = ['membership_level' => $decode->membership_level];
            //     }
            //     if($decode->membership_cashback_percentage != $dataLog->membership_cashback_percentage){
            //         $fraud = true;
            //         $fraudData[] = ['membership_cashback_percentage_table' => $dataLog->membership_cashback_percentage, 'membership_cashback_percentage_enc' => $decode->membership_cashback_percentage];
            //         $updateData[] = ['membership_cashback_percentage' => $decode->membership_cashback_percentage];
            //     }
                
            //     if($fraud == true){
            //         $fraud = FraudSetting::where('parameter', 'LIKE', '%encryption%')->first();
            //         $user = User::find($dataLog['id_user']);
                    
            //         $fraudData = json_encode($fraudData);
            //         if($fraud && $user){
            //             $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraud['id_fraud_setting'], $user, null, null, $dataLog['id_log_balance'], $fraudData);
            //         } 
                    
            //         //update log balance
            //         $up = LogBalance::where('id_log_balance', $decode->id_log_balance)->update($updateData);
            //     }
            // }
        }
        return 'success';


    }
}
