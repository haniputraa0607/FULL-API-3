<?php
namespace App\Lib;

use Image;
use File;
use DB;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\LogPoint;
use App\Http\Models\UsersMembership;
use App\Http\Models\Membership as ModelMembership;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\Lib\MyHelper;

class Membership {

    public function __construct() 
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    /* CALCULATE */
    static function calculate($id_user=null, $phone=null) 
    {
        DB::beginTransaction();
        $id_user = Self::getuser($id_user, $phone);

        if ($id_user) {
            // CEK TRANSAKSI
            if ($trx = Self::callTransaction($id_user)) {
                if (Self::callMembership($id_user, $trx)) {
                    DB::commit();
                    return true;
                }
            }
        }

        DB::rollback();
        return true;
    }

    /* ID USER */
    static function getUser($id_user=null, $phone=null) 
    {
        if (!is_null($phone)) {
            $id_user = User::where('phone', $phone)->first();

            if (empty($id_user)) {
                return false;
            }

            $id_user = $id_user->id;
        }

        return $id_user;
    }


    /* TRANSAKSI */
    static function callTransaction($id_user) 
    {
        $dataTrans = [];
        $transaction = Transaction::where('id_user', $id_user)->where('transaction_payment_status', 'Completed')->get()->toArray();

        if ($transaction) {

            $trx = array_column($transaction, 'transaction_grandtotal');

            $dataTrans = [
                'trxAmount' => array_sum($trx),
                'trxCount'  => count($trx)
            ];
        }

        return $dataTrans;
    }

    /* MEMBERSHIT */
    static function callMembership($id_user, $trx)
    {
        $membership = ModelMembership::orderBy('min_total_value', 'ASC')->get()->toArray();

        if ($membership) {
            $dataUser = [];

            foreach ($membership as $key => $value) {
                // assign data id_user
                $dataUser['id_user'] = $id_user;

                if ($trx['trxAmount'] >= $value['min_total_value'] && $trx['trxCount'] >= $value['min_total_count']) {
                    
                    foreach ($value as $k => $v) {
                        $dataUser[$k] = $v;
                    }

                    $dataUser['retain_date'] = date('Y-m-d', strtotime("+ ".$value['retain_days']."days"));
                }
            }

            if (!empty($dataUser)) {
                unset($dataUser['retain_days']);
                unset($dataUser['created_at']);
                unset($dataUser['updated_at']);

                // cek membership
                $cekMembership = UsersMembership::where('id_user', $dataUser['id_user'])->where('id_membership', $dataUser['id_membership'])->first();

                if (!$cekMembership) {
                    // save membership user
                    $save = UsersMembership::create($dataUser);

                    if ($save) {
                        return true;
                    }
                }
                else {
                    return true;
                }
            }
        }

        return false;
    }
}