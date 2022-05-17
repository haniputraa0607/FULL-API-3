<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;

use Modules\UserFeedback\Entities\UserFeedbackLog;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\TimeSchedule;
use Modules\Doctor\Entities\Doctor;
use Modules\Transaction\Entities\TransactionGroup;
use DB;
use DateTime;


class ApiTransactionConsultationController extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        // $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        // $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        // $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        // $this->notif         = "Modules\Transaction\Http\Controllers\ApiNotification";
        // $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        // $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        // $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        // $this->promo       = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        // $this->plastic       = "Modules\Plastic\Http\Controllers\PlasticController";
        // $this->voucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        // $this->subscription  = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        // $this->bundling      = "Modules\ProductBundling\Http\Controllers\ApiBundlingController";
        $this->payment = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->location = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->doctor = "Modules\Doctor\Http\Controllers\ApiDoctorController";
    }

    /**
     * Get info from given cart data
     * @param  CheckTransaction $request [description]
     * @return View                    [description]
     */
    public function checkTransaction(Request $request) {
        $post = $request->json()->all();
        $user = $request->user();

        //cek date time schedule
        if($post['consultation_type'] != "now") {
            if(empty($post['date']) && empty($post['time'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Schedule can not be empty']
                ]);
            }
        } else {
            $post['date'] = date('Y-m-d');
            $post['time'] = date("H:i:s");
        }

        //check doctor availability
        $id_doctor = $post['id_doctor'];
        $doctor = Doctor::with('clinic')->with('specialists')->where('id_doctor', $post['id_doctor'])->first()->toArray();

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Silahkan pilh dokter terlebih dahulu']
            ]);
        }

        //check session availability
        if($post['consultation_type'] != "now") {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));

            $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function($query) use ($post){
                $query->where('start_time', '=', $post['time'])->onlyAvailabile();
            })->first();
        } else {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));

            $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function($query) use ($post){
                $query->whereTime('start_time', '<', $post['time'])->whereTime('end_time', '>', $post['time'])->onlyAvailabile();
            })->first();
        }

        if(empty($schedule_session)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        $result = array();

        //consultation type
        $result['consultation_type'] = $post['consultation_type'];

        //selected doctor
        $result['doctor'] = [
            'id_doctor' => $doctor['id_doctor'],
            'doctor_name' => $doctor['doctor_name'],
            'doctor_phone' => $doctor['doctor_phone'],
            'doctor_clinic_name' => $doctor['clinic']['doctor_clinic_name'],
            'doctor_specialist_name' => $doctor['specialists'][0]['doctor_specialist_name'],
            'doctor_session_price' => $doctor['doctor_session_price']
        ];

        //selected session
        $schedule_session = $schedule_session->toArray();

        $result['selected_schedule'] = [
            'date' => $post['date'],
            'day' => ucwords($schedule_session['day']),
            'time' => $post['time']
        ];

        //TO DO if any promo
        $subTotal = $doctor['doctor_session_price'];
        $diskon = 0;
        $grandTotal = $subTotal - $diskon;

        $result['subtotal'] = $subTotal;
        $result['diskon'] = $diskon;
        $result['grandtotal'] = $grandTotal;
        $result['used_point'] = 0;

        //check payment balance
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (isset($post['payment_type']) && $post['payment_type'] == 'Balance') {
            if($balance >= ($result['grandtotal']-$result['subscription'])){
                $result['used_point'] = $result['grandtotal'];

	            if ($result['subscription'] >= $result['used_point']) {
	            	$result['used_point'] = 0;
	            }else{
	            	$result['used_point'] = $result['used_point'] - $result['subscription'];
	            }
            }else{
                $result['used_point'] = $balance;
            }

            $result['points'] -= $result['used_point'];
        }

        $result['total_payment'] = $result['grandtotal'] - $result['used_point'];
        $result['payment_detail'] = [];
                
        //subtotal
        $result['payment_detail'][] = [
            'name'          => 'Subtotal Sesi Konsultasi Dr. '.$doctor['doctor_name'].'',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($result['subtotal'],'_CURRENCY')
        ];

        //get available payment
        $available_payment = app($this->payment)->availablePayment(new Request())['result'];

        $result['available_payment'] = $available_payment;

        return MyHelper::checkGet($result);
    }

    /**
     * Get info from given cart data
     * @param  NewTransaction $request [description]
     * @return View                    [description]
     */

    public function newTransaction(Request $request) {
        $post = $request->json()->all();
        $user = $request->user();

        //cek input date and time
        if($post['consultation_type'] != 'now') {
            if(empty($post['date']) && empty($post['time'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Schedule can not be empty']
                ]);
            } 
        } else {
            $post['date'] = date('Y-m-d', strtotime('2022-04-27'));
            $post['time'] = date("H:i:s");
        }

        //cek doctor exists
        $id_doctor = $post['id_doctor'];
        $doctor = Doctor::with('clinic')->with('specialists')
        ->where('id_doctor', $post['id_doctor'])->onlyActive()
        ->first()->toArray();

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Silahkan pilh dokter terlebih dahulu']
            ]);
        }

        //cek doctor active
        if(isset($doctor['is_active']) && $doctor['is_active'] == false){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor Tutup Sesi Konsuling']
            ]);
        }

        //check session availability
        if($post['consultation_type'] != "now") {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));

            $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function($query) use ($post){
                $query->where('start_time', '=', $post['time'])->onlyAvailabile();
            })->first();
        } else {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));

            $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function($query) use ($post){
                $query->whereTime('start_time', '<', $post['time'])->whereTime('end_time', '>', $post['time'])->onlyAvailabile();
            })->first();
        }

        if(empty($schedule_session)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        if (isset($post['transaction_payment_status'])) {
            $post['transaction_payment_status'] = $post['transaction_payment_status'];
        } else {
            $post['transaction_payment_status'] = 'Pending';
        }

        //user suspend
        if(isset($user['is_suspended']) && $user['is_suspended'] == '1'){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami']
            ]);
        }

        //check validation email
        if(isset($user['email'])){
            $domain = substr($user['email'], strpos($user['email'], "@") + 1);
            if(!filter_var($user['email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Alamat email anda tidak valid, silahkan gunakan alamat email yang valid.']
                ]);
            }
        }

        //delete
        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        if (!isset($post['subtotal'])) {
            $post['subtotal'] = 0;
        }

        if (!isset($post['subtotal_final'])) {
            $post['subtotal_final'] = 0;
        }

        if (!isset($post['discount'])) {
            $post['discount'] = 0;
        }

        //delete
        if (!isset($post['discount_delivery'])) {
            $post['discount_delivery'] = 0;
        }

        if (!isset($post['service'])) {
            $post['service'] = 0;
        }

        if (!isset($post['tax'])) {
            $post['tax'] = 0;
        }

        //delete
        $post['discount'] = -$post['discount'];
        $post['discount_delivery'] = -$post['discount_delivery'];

        if (isset($post['payment_type']) && $post['payment_type'] == 'Balance') {
            $post['cashback'] = 0;
            $post['point']    = 0;
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (!isset($post['latitude'])) {
            $post['latitude'] = null;
        }

        if (!isset($post['longitude'])) {
            $post['longitude'] = null;
        }

        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
        $distance = NULL;
        if(isset($post['latitude']) &&  isset($post['longitude'])){
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
        }

        if (!isset($post['notes'])) {
            $post['notes'] = null;
        }

        if (!isset($post['id_outlet'])) {
            $post['id_outlet'] = null;
        }

        if (!isset($post['cashback'])) {
            $post['cashback'] = null;
        }

        if (!isset($post['grandtotal'])) {
            $post['grandtotal'] = null;
        }

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $type = 'Consultation';
        $consultation_type = $post['consultation_type'];

        if (isset($post['headers'])) {
            unset($post['headers']);
        }

        $grandtotal = 0;
        $subtotal = $post['subtotal'];
        $deliveryTotal = 0; 
        $currentDate = date('Y-m-d H:i:s');
        $paymentType = NULL;
        $transactionStatus = 'Unpaid';
        $paymentStatus = 'Pending';
        if(isset($post['point_use']) && $post['point_use']){
            $paymentType = 'Balance';
            $paymentStatus = 'Completed';
            $transactionStatus = 'Pending';
        }

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();

        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->where('outlet_status', 'Active')->first();
        if (empty($outlet)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet tidak ditemukan']
            ]);
        }

        $dataTransactionGroup = [
            'id_user' => $user->id,
            'transaction_receipt_number' => 'TRX'.time().rand().substr($grandtotal, 0,5),
            'transaction_subtotal' => 0,
            'transaction_shipment' => 0,
            'transaction_grandtotal' => 0,
            'transaction_payment_status' => $paymentStatus,
            'transaction_payment_type' => $paymentType,
            'transaction_transaction_date' => $currentDate
        ];

        $insertTransactionGroup = TransactionGroup::create($dataTransactionGroup);
        if(!$insertTransactionGroup){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Group Failed']
            ]);
        }

        $grandtotal = $post['grandtotal'];

        TransactionGroup::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->update([
            'transaction_subtotal' => $subtotal,
            'transaction_shipment' => $deliveryTotal,
            'transaction_grandtotal' => $grandtotal
        ]);

        $transaction = [
            'id_transaction_group'        => $insertTransactionGroup['id_transaction_group'],
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'id_promo_campaign_promo_code'=> $post['id_promo_campaign_promo_code']??null,
            'transaction_date'            => $post['transaction_date'],
            'trasaction_type'             => $type,
            'shipment_method'             => $shipment_method ?? null,
            'shipment_courier'            => $shipment_courier ?? null,
            'transaction_notes'           => $post['notes'],
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'  		  => $post['subtotal_final'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['discount'],
            'transaction_discount_delivery' => $post['discount_delivery'],
            'transaction_discount_item' 	=> 0,
            'transaction_discount_bill' 	=> 0,
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandtotal'],
            'transaction_point_earned'    => $post['point'],
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'distance_customer'           => $distance,
            'void_date'                   => null,
        ];

        if($transaction['transaction_grandtotal'] == 0){
            $transaction['transaction_payment_status'] = 'Completed';
        }

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'IOS';
        elseif(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        if($useragent){
            $transaction['transaction_device_type'] = $useragent;
        }

        $insertTransaction = Transaction::create($transaction);

        if (!$insertTransaction) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        //update receipt
        $receipt = rand().time().'-'.substr($post['id_outlet'], 0, 4).rand(1000,9999);
        $updateReceiptNumber = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
            'transaction_receipt_number' => $receipt
        ]);

        if (!$updateReceiptNumber) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        // MyHelper::updateFlagTransactionOnline($insertTransaction, 'pending', $user);

        //get picked schedule
        $picked_schedule = null;
        if($post['consultation_type'] != 'now') {
            $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function($query) {
                $query->on('time_schedules.id_doctor_schedule', '=' , 'doctor_schedules.id_doctor_schedule');
            })->where('start_time', '=', $post['time'])->first();
        } else {
            $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function($query) {
                $query->on('time_schedules.id_doctor_schedule', '=' , 'doctor_schedules.id_doctor_schedule');
            })->whereTime('start_time', '<', $post['time'])->whereTime('end_time', '>', $post['time'])->first();
        }

        $insertTransaction['transaction_receipt_number'] = $receipt;

        $dataConsultation = [
            'id_transaction'               => $insertTransaction['id_transaction'],
            'id_doctor'                   => $doctor['id_doctor'],
            'consultation_type'            => $consultation_type,
            'id_user'                      => $insertTransaction['id_user'],
            'schedule_date'                => $post['date'],
            'schedule_start_time'          => $picked_schedule['start_time'],
            'schedule_end_time'          => $picked_schedule['end_time'],
            'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
            'updated_at'                   => date('Y-m-d H:i:s')
        ];

        $trx_consultation = TransactionConsultation::create($dataConsultation);
        if (!$trx_consultation) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Consultation Transaction Failed']
            ]);
        }

        if(strtotime($insertTransaction['transaction_date'])){
            $trx_consultation->created_at = strtotime($insertTransaction['transaction_date']);
        }

        //update remaining slot
        $newRemainingSlot = (int)$picked_schedule['remaining_quota_session'] - 1;
        $updateDataTimeSchedule = TimeSchedule::where('id_time_schedule', $picked_schedule['id_time_schedule'])->update(['remaining_quota_session' => $newRemainingSlot]);
        if (!$updateDataTimeSchedule) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Update Data Schedule Failed']
            ]);
        }
        

        // $insertUserTrxProduct = app($this->transaction)->insertUserTrxProduct($userTrxProduct);
        // if ($insertUserTrxProduct == 'fail') {
        //     DB::rollback();
        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Insert Consultation Transaction Failed']
        //     ]);
        // }

        // if ($post['payment_type'] == 'Midtrans') {
        //     if ($post['transaction_payment_status'] == 'Completed') {
        //         //bank
        //         $bank = ['BNI', 'Mandiri', 'BCA'];
        //         $getBank = array_rand($bank);

        //         //payment_method
        //         $method = ['credit_card', 'bank_transfer', 'direct_debit'];
        //         $getMethod = array_rand($method);

        //         $dataInsertMidtrans = [
        //             'id_transaction'     => $insertTransaction['id_transaction'],
        //             'approval_code'      => 000000,
        //             'bank'               => $bank[$getBank],
        //             'eci'                => $this->getrandomnumber(2),
        //             'transaction_time'   => $insertTransaction['transaction_date'],
        //             'gross_amount'       => $insertTransaction['transaction_grandtotal'],
        //             'order_id'           => $insertTransaction['transaction_receipt_number'],
        //             'payment_type'       => $method[$getMethod],
        //             'signature_key'      => $this->getrandomstring(),
        //             'status_code'        => 200,
        //             'vt_transaction_id'  => $this->getrandomstring(8).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(12),
        //             'transaction_status' => 'capture',
        //             'fraud_status'       => 'accept',
        //             'status_message'     => 'Veritrans payment notification'
        //         ];

        //         $insertDataMidtrans = TransactionPaymentMidtran::create($dataInsertMidtrans);
        //         if (!$insertDataMidtrans) {
        //             DB::rollback();
        //             return response()->json([
        //                 'status'    => 'fail',
        //                 'messages'  => ['Insert Data Midtrans Failed']
        //             ]);
        //         }

        //     }
        // }

        if($post['latitude'] && $post['longitude']){
           $savelocation = app($this->location)->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction'], $outlet['id_outlet']);
        }

        DB::commit();

        return response()->json([
            'status'   => 'success',
            'redirect' => true,
            'result'   => $insertTransaction
        ]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function getTransaction(Request $request) {
        $post = $request->json()->all();

        $transaction = Transaction::where('id_transaction', $post['id_transaction'])->where('trasaction_type', 'Consultation')->first();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transaction = $transaction->toArray();

        $transaction_consultation = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->first()->toArray();

        $doctor = Doctor::where('id_doctor', $transaction_consultation['id_doctor'])->with('specialists')->first()->toArray();

        $day = date('l', strtotime($transaction_consultation['schedule_date']));

        $result = [
            "doctor_name" => $doctor['doctor_name'],
            "doctor_specialist" => $doctor['specialists'],
            "day" => $day,
            "date" => $transaction_consultation['schedule_date'],
            "time" => $transaction_consultation['schedule_start_time'],
            "total_payment" => $transaction['transaction_grandtotal'],
            "payment_method" => $transaction['trasaction_payment_type']
        ];

        return response()->json([
            'status'   => 'success',
            'result'   => $result
        ]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function getSoonConsultationList(Request $request) {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $transaction = Transaction::with('consultation')->where('id_user', $id)->whereHas('consultation', function($query){
            $query->onlySoon();
        })->get()->toArray();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak ada transaksi yang akan datang']
            ]);
        }

        $now = new DateTime();

        $result = array();
        foreach($transaction as $key => $value) {
            $doctor = Doctor::where('id_doctor', $value['consultation']['id_doctor'])->first()->toArray();
            $schedule_date_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_start_time'];
            $schedule_date_time =new DateTime($schedule_date_time);
            $diff_date = "missed";
            if($schedule_date_time > $now) {
                $diff_date = $now->diff($schedule_date_time)->format("%d days, %h hours and %i minuts");
            }

            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['id_doctor'] = $value['consultation']['id_doctor'];
            $result[$key]['doctor_name'] = $doctor['doctor_name'];
            $result[$key]['doctor_photo'] = $doctor['doctor_photo'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date'];
            $result[$key]['diff_date'] = $diff_date;
        }

        return response()->json([
            'status'   => 'success',
            'result'   => $result
        ]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function getSoonConsultationDetail(Request $request) {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        //cek id transaction
        if(!isset($post['id_transaction'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first()->toArray();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        //get Doctor
        $detailDoctor = app($this->doctor)->show($transaction['consultation']['id_doctor']);

        if(empty($detailDoctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Dokter tidak di temukan']
            ]);
        }

        //get day
        $day = date('l', strtotime($transaction['consultation']['schedule_date']));

        //get diff date
        $now = new DateTime();
        $schedule_date_time = $transaction['consultation']['schedule_date'] .' '. $transaction['consultation']['schedule_start_time'];
        $schedule_date_time =new DateTime($schedule_date_time);
        $diff_date = "missed";

        if($schedule_date_time > $now) {
            $diff_date = $now->diff($schedule_date_time)->format("%d days, %h hours and %i minuts");
        }

        $result = [
            'doctor' => $detailDoctor->getData()->result,
            'schedule_date' => $transaction['consultation']['schedule_date'],
            'schedule_start_time' => $transaction['consultation']['schedule_start_time'],
            'schedule_day' => $day,
            'diff_date' => $diff_date
        ];

        return response()->json([
            'status'   => 'success',
            'result'   => $result
        ]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function startConsultation(Request $request) {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        //cek id transaction
        if(!isset($post['id_transaction'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first()->toArray();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        //get Doctor
        $doctor = Doctor::where('id_doctor', $transaction['consultation']['id_doctor'])->first();

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor tidak ditemukan']
            ]);
        }

        DB::beginTransaction();
        try {
            $result = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])
            ->update([
                'consultation_status' => "ongoing",
                'consultation_start_at' => new DateTime
            ]);
    
            $doctor->update(['doctor_status' => "busy"]);
            $doctor->save();
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Start Consultation Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function getHistoryConsultationList(Request $request) {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $transaction = Transaction::with('consultation')->where('id_user', $id);

        if(isset($post['filter'])) {
            $id_doctor = Doctor::where('doctor_name', 'like', '%'.$post['filter'].'%')->pluck('id_doctor')->toArray();
            $transaction = $transaction->whereHas('consultation', function($query) use ($post, $id_doctor){
                $query->onlyDone()->where(function($query2) use ($post, $id_doctor){
                    $query2->orWhere('schedule_date', 'like', '%'.$post['filter'].'%');
                    $query2->orWhereIn('id_doctor', $id_doctor);
                });
            });
        } else {
            $transaction = $transaction->whereHas('consultation', function($query){
                $query->onlyDone();
            });
        }

        $transaction = $transaction->get()->toArray();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['History transaksi konsultasi tidak ditemukan']
            ]);
        }

        $result = array();
        foreach($transaction as $key => $value) {
            $doctor = Doctor::where('id_doctor', $value['consultation']['id_doctor'])->first()->toArray();

            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['doctor_name'] = $doctor['doctor_name'];
            $result[$key]['doctor_photo'] = $doctor['doctor_photo'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date'];
        }

        return response()->json([
            'status'   => 'success',
            'result'   => $result
        ]);
    }
}
