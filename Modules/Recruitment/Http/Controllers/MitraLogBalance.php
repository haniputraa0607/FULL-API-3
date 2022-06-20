<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

use Illuminate\Support\Facades\Schema;
use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Http\Requests\DetailCustomerQueueRequest;
use Modules\Recruitment\Http\Requests\StartOutletServiceRequest;

use Modules\Outlet\Entities\OutletBox;

use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use DB;
use DateTime;

class MitraLogBalance extends Controller
{
    public function insertLogBalance($data)
    {
        // check balance data from hashed text
        $checkHashBefore = $this->checkHash($data['id_user_hair_stylist']);
        if (!$checkHashBefore) {
            return false;
        }

        DB::beginTransaction();
        $balanceBefore = HairstylistLogBalance::where('id_user_hair_stylist', $data['id_user_hair_stylist'])->sum('balance');

        $logBalance = [
            'id_user_hair_stylist'           => $data['id_user_hair_stylist'],
            'balance'                        => $data['balance'],
            'balance_before'                 => $balanceBefore,
            'balance_after'                  => $balanceBefore+$data['balance'],
            'id_reference'                   => $data['id_reference']??null,
            'source'                         => $data['source']
        ];

        $create = HairstylistLogBalance::updateOrCreate(['id_user_hair_stylist' => $logBalance['id_user_hair_stylist'], 'id_reference' => $logBalance['id_reference'], 'source' => $logBalance['source']], $logBalance);

        $dataLogBalance = HairstylistLogBalance::find($create->id_hairstylist_log_balance);
        $dataHashBalance = [
            'id_hairstylist_log_balance'     => $dataLogBalance->id_hairstylist_log_balance,
            'id_user_hair_stylist'           => $dataLogBalance['id_user_hair_stylist'],
            'balance'                        => $dataLogBalance['balance'],
            'balance_before'                 => $dataLogBalance['balance_before'],
            'balance_after'                  => $dataLogBalance['balance_before']+$data['balance'],
            'id_reference'                   => $dataLogBalance['id_reference'],
            'source'                         => $dataLogBalance['source']
        ];

        $enc = MyHelper::encrypt2019(json_encode(($dataHashBalance)));
        $dataLogBalance->update(['enc' => $enc]);

        $newBalance = HairstylistLogBalance::where('id_user_hair_stylist', $data['id_user_hair_stylist'])->sum('balance');
        $updateUser = UserHairStylist::where('id_user_hair_stylist', $data['id_user_hair_stylist'])->update(['total_balance' => $newBalance]);

        if (!($dataLogBalance && $updateUser)) {
            DB::rollback();
            return false;
        }

        DB::commit();
        return $dataLogBalance;
    }

    public function checkHash($id_user_hair_stylist)
    {
        $check = HairstylistLogBalance::where('id_user_hair_stylist', $id_user_hair_stylist)->orderBy('created_at', 'DESC')->first();
        if (!$check) {
            return true;
        }

        if (count($check->toArray()) < 1) {
            return true;
        }

        if (!isset($check['enc'])) {
            return true;
        }

        $dataHash = [
            'id_hairstylist_log_balance'     => $check['id_hairstylist_log_balance'],
            'id_user_hair_stylist'           => $check['id_user_hair_stylist'],
            'balance'                        => $check['balance'],
            'balance_before'                 => $check['balance_before'],
            'balance_after'                  => $check['balance_after'],
            'id_reference'                   => $check['id_reference'],
            'source'                         => $check['source']
        ];

        $encodeCheck = json_encode($dataHash);

        if (MyHelper::decrypt2019($check['enc']) == $encodeCheck) {
            return true;
        }
        return false;
    }
}
