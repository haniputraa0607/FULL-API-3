<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\TransactionConsultationRecomendation;
use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\Product;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\Merchant\Entities\Merchant;
use App\Http\Models\ProductPhoto;

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
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->payment = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->location = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->doctor = "Modules\Doctor\Http\Controllers\ApiDoctorController";
        $this->promo_trx 	 = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
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
        $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $post['id_doctor'])->first();

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Silahkan pilh dokter terlebih dahulu / Dokter tidak ditemukan']
            ]);
        }
        $doctor = $doctor->toArray();

        //check session availability
        if($post['consultation_type'] != "now") {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));
        } else {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));
        }

        //get doctor consultation
        $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $picked_date)
                                ->where('schedule_start_time', $post['time'])->count();
        
        $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSetting['value'];

        if($quota <= $doctor_constultation && $quota != null){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        //selected session
        $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function($query) use ($post){
                $query->where('start_time', '=', $post['time']);
            })->first();

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
            'outlet_name' => $doctor['outlet']['outlet_name'],
            'doctor_specialist_name' => $doctor['specialists'][0]['doctor_specialist_name'],
            'doctor_session_price' => $doctor['doctor_session_price']
        ];

        $result['selected_schedule'] = [
            'date' => $post['date'],
            'day' => ucwords($schedule_session['day']),
            'time' => $post['time']
        ];

        //check referral code
        if(isset($post['referral_code'])) {
            $outlet = Outlet::where('outlet_referral_code', $post['referral_code'])->first();

            if(empty($outlet)){
                $outlet = Outlet::where('outlet_code', $post['referral_code'])->first();
            }

            if(empty($outlet)){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Referral Code Salah / Outlet Tidak Ditemukan']
                ]);
            }
            //referral code
            $result['referral_code'] = $post['referral_code'];
        }

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
            'amount'        => '-Rp '.number_format($result['subtotal'],0,",",".")
        ];
        $result['id_outlet'] = $doctor['id_outlet'];
        $result = app($this->promo_trx)->applyPromoCheckoutConsultation($result);

        //get available payment
        $available_payment = app($this->payment)->availablePayment(new Request())['result']??null;
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
        $doctor = Doctor::with('outlet')->with('specialists')
        ->where('id_doctor', $post['id_doctor'])->onlyActive()
        ->first();

        if(empty($doctor)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Silahkan pilh dokter terlebih dahulu']
            ]);
        }
        $doctor = $doctor->toArray();

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
        } else {
            $picked_date = $post['date'];
            $picked_day = strtolower(date('l', strtotime($picked_date)));
        }

        //get doctor consultation
        $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $picked_date)
                                ->where('schedule_start_time', $post['time'])->count();
        $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSetting['value'];

        //dd($doctor_constultation);

        if($quota <= $doctor_constultation && $quota != null){
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

        $outlet = Outlet::where('id_outlet', $doctor['id_outlet'])->first();
        $distance = NULL;
        if(isset($post['latitude']) &&  isset($post['longitude'])){
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
        }

        if (!isset($post['notes'])) {
            $post['notes'] = null;
        }

        if (!isset($post['id_outlet'])) {
            $post['id_outlet'] = $doctor['id_outlet'];
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
            $transactionStatus = 'Completed';
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

        $post = app($this->promo_trx)->applyPromoCheckoutConsultation($post);

        $dataTransactionGroup = [
            'id_user' => $user->id,
            'transaction_receipt_number' => 'TRX'.time().rand().substr($grandtotal, 0,5),
            'transaction_subtotal' => 0,
            'transaction_shipment' => 0,
            'transaction_grandtotal' => 0,
            'transaction_payment_status' => $paymentStatus,
            'transaction_payment_type' => $paymentType,
            'transaction_group_date' => $currentDate
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
            'transaction_discount'        => $post['total_discount'],
            'transaction_discount_delivery' => 0,
            'transaction_discount_item' 	=> 0,
            'transaction_discount_bill' 	=> $post['total_discount'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandtotal'],
            'transaction_point_earned'    => $post['point']??0,
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'distance_customer'           => $distance,
            'void_date'                   => null,
            'transaction_status'          => $transactionStatus
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

        if (empty($picked_schedule)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Invalid picked schedule']
            ]);
        }

        $insertTransaction['transaction_receipt_number'] = $receipt;

        $dataConsultation = [
            'id_transaction'               => $insertTransaction['id_transaction'],
            'id_doctor'                    => $doctor['id_doctor'],
            'consultation_type'            => $consultation_type,
            'id_user'                      => $insertTransaction['id_user'],
            'schedule_date'                => $post['date'],
            'schedule_start_time'          => $picked_schedule['start_time'],
            'schedule_end_time'            => $picked_schedule['end_time'],
            'referral_code'                => $post['referral_code']??null,
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

        $trxGroup = TransactionGroup::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->first();
        if ($paymentType == 'Balance' && isset($post['point_use']) && $post['point_use']) {

            $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
            $grandTotalNew = $trxGroup['transaction_grandtotal'];
            if($currentBalance >= $grandTotalNew){
                $grandTotalNew = 0;
            }else{
                $grandTotalNew = $grandTotalNew - $currentBalance;
            }

            $save = app($this->balance)->topUpGroup($user->id, $trxGroup);

            if (!isset($save['status'])) {
                DB::rollBack();
                return response()->json(['status' => 'fail', 'messages' => ['Transaction failed']]);
            }

            if ($save['status'] == 'fail') {
                DB::rollBack();
                return response()->json($save);
            }

            if($grandTotalNew == 0){
                $trxGroup->triggerPaymentCompleted();
            }
        }

        if($post['latitude'] && $post['longitude']){
           $savelocation = app($this->location)->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction'], $outlet['id_outlet']);
        }

        $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
        app($this->promo_trx)->applyPromoNewTrx($trx);
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

            //get diff datetime
            $now = new DateTime();
            $schedule_date_start_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_start_time'];
            $schedule_date_start_time =new DateTime($schedule_date_start_time);
            $schedule_date_end_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_end_time'];
            $schedule_date_end_time =new DateTime($schedule_date_end_time);
            $diff_date = null;

            //logic schedule diff date
            if($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%d days, %h hours and %i minutes");
            } elseif($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
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
    public function doneConsultation(Request $request) {
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
                'consultation_status' => "done",
                'consultation_start_at' => new DateTime
            ]);
    
            $doctor->update(['doctor_status' => "online"]);
            $doctor->save();

            //insert balance merchant
            $transaction = Transaction::where('id_transaction', $transaction['consultation']['id_transaction'])->first();
            $idMerchant = Merchant::where('id_outlet', $transaction['id_outlet'])->first()['id_merchant']??null;
            $nominal = $transaction['transaction_grandtotal'] + $transaction['discount_charged_central'];
            $dt = [
                'id_merchant' => $idMerchant,
                'id_transaction' => $transaction['id_transaction'],
                'balance_nominal' => $nominal,
                'source' => 'Transaction Consultation Completed'
            ];
            $insertSaldo = app('Modules\Merchant\Http\Controllers\ApiMerchantTransactionController')->insertBalanceMerchant($dt);
            if(! $insertSaldo){
                DB::rollBack();
            }
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Done Consultation Failed'
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

    /**
     * Get info from given cart data
     * @param  GetHandledConsultation $request [description]
     * @return View                    [description]
     */
    public function getHandledConsultation(Request $request) {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id_doctor;
        } else {
            $id = $post['id_doctor'];
        }

        $transaction = Transaction::with('consultation');

        if(isset($post['consultation_status'])) {
            $transaction = $transaction->whereHas('consultation', function($query) use ($post, $id){
                $query->where('id_doctor', $id)->where('consultation_status', $post['consultation_status']);
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
            $user = User::where('id', $value['consultation']['id_user'])->first()->toArray();

            //get diff datetime
            $now = new DateTime();
            $schedule_date_start_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_start_time'];
            $schedule_date_start_time =new DateTime($schedule_date_start_time);
            $schedule_date_end_time = $value['consultation']['schedule_date'] .' '. $value['consultation']['schedule_end_time'];
            $schedule_date_end_time =new DateTime($schedule_date_end_time);
            $diff_date = null;

            //logic schedule diff date
            if($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%d days, %h hours and %i minutes");
            } elseif($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
            }

            //set response result
            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['customer_name'] = $user['name'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date'];
            $result[$key]['schedule_start_time'] = $value['consultation']['schedule_start_time'];
            $result[$key]['schedule_diff_date'] = $diff_date;
        }

        return response()->json([
            'status'   => 'success',
            'result'   => $result
        ]);
    }

    public function getDetailSummary(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //get transaction
        $transactionConsultation = null;
        if(isset($user->id_doctor)){
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();    
        } else {
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id)->where('id_transaction', $post['id_transaction'])->first();
        }

        if(empty($transactionConsultation)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        $diseaseComplaints = !empty($transactionConsultation['disease_complaint']) ? explode(', ', $transactionConsultation['disease_complaint']) : null;
        $diseaseAnalysis = !empty($transactionConsultation['disease_complaint']) ? explode(', ', $transactionConsultation['disease_analysis']) : null;

        $result = [];
        $result['disease_complaint'] = $diseaseComplaints;
        $result['disease_analysis'] = $diseaseAnalysis;
        $result['treatment_recomendation'] = $transactionConsultation['treatment_recomendation'];

        return MyHelper::checkGet($result);
    }

    public function updateConsultationDetail(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();

        if(empty($transactionConsultation)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        if(empty($transactionConsultation['consultation_status'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Anda Tidak Bisa Merubah Data, Transaksi Sudah Ditandai Selesai']
            ]);
        }

        $diseaseComplaint = implode(", ",$post['disease_complaint']);
        $diseaseAnalysis = implode(", ",$post['disease_analysis']);

        DB::beginTransaction();
        try {
            $result = TransactionConsultation::where('id_transaction', $post['id_transaction'])
            ->update([
                'disease_complaint' => $diseaseComplaint,
                'disease_analysis' => $diseaseAnalysis,
                'treatment_recomendation' => $post['treatment_recomendation']
            ]);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Update disease and treatement failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getProductRecomendation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        
        //get transaction
        $transactionConsultation = null;
        if(isset($user->id_doctor)){
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();    
        } else {
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id)->where('id_transaction', $post['id_transaction'])->first();
        }

        if(empty($transactionConsultation)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        //get recomendation
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyProduct()->get();

        $items = [];
        if(!empty($recomendations)) {
            foreach($recomendations as $key => $recomendation){
                $items[$key]['product_name'] = $recomendation->product->product_name ?? null;
                $items[$key]['product_price'] = $recomendation->product->price->product_price ?? null;
                $items[$key]['product_photo'] = $recomendation->product->product_photos ?? null;
                $items[$key]['product_rating'] = null;
                $items[$key]['qty'] = $recomendation->qty ?? null;
            }
        }

        $result = $items;

        return MyHelper::checkGet($result);
    }

    public function getDrugRecomendation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //get transaction
        $transactionConsultation = null;
        if(isset($user->id_doctor)){
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();    
        } else {
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id)->where('id_transaction', $post['id_transaction'])->first();
        }

        if(empty($transactionConsultation)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        //get recomendation
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();

        $items = [];
        if(!empty($recomendations)) {
            foreach($recomendations as $key => $recomendation){
                $items[$key]['product_name'] = $recomendation->product->product_name ?? null;
                $items[$key]['product_price'] = $recomendation->product->price->product_price ?? null;
                $items[$key]['product_photo'] = $recomendation->product->product_photos ?? null;
                $items[$key]['product_rating'] = null;
                $items[$key]['qty'] = $recomendation->qty ?? null;
            }
        }

        $result = [
            'remaining_recipe_redemption' =>  ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter), 
            'items' => $items
        ];

        return MyHelper::checkGet($result);
    }

    public function updateRecomendation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();

        if(empty($transactionConsultation)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }

        foreach($post['items'] as $key => $item){
            $post['items'][$key]['product_type'] = $post['type'];
            $post['items'][$key]['qty_product_counter'] = $post['items'][$key]['qty_product'];
        }

        if($post['type'] == "drug"){
            $transactionConsultation->update(['recipe_redemption_limit' => $post['recipe_redemption_limit']]);
        }

        DB::beginTransaction();
        try {
            //drop old recomendation 
            $oldRecomendation = TransactionConsultationRecomendation::where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->where('product_type', $post['type'])->delete();
            $items = $transactionConsultation->recomendation()->createMany($post['items']);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Update disease and treatement failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        $result = $items;

        //product recomendation drug type
        if($post['type'] == "drug"){
            $result = [
                'recipe_redemption_limit' => $transactionConsultation['recipe_redemption_limit'],
                'items' => $items
            ];
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getConsultationFromAdmin(Request $request)
    {
        $post = $request->json()->all();
        //get Transaction
        $transactions = Transaction::where('trasaction_type', 'Consultation')->with('consultation')->with('outlet');

        if ($post['rule']) {
            $countTotal = $transactions->count();
            $this->filterList($transactions, $post['rule'], $post['operator'] ?: 'and');
        }

        if($request['page']) {
            $result = $transactions->paginate($post['length'] ?: 10);
        } else {
            $result = $transactions->get()->toArray();
        }
        
        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $where=$operator=='and'?'where':'orWhere';
        // $subjects=['doctor_name', 'doctor_phone', 'doctor_session_price'];
        // foreach ($subjects as $subject) {
        //     if($rules2=$newRule[$subject]??false){
        //         foreach ($rules2 as $rule) {
        //             $query->$where($subject,$rule[0],$rule[1]);
        //         }
        //     }
        // }

        if($rules2=$newRule['outlet']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('outlet', function($query2) use ($rule) {
                    $query2->where('outlet_name', $rule[0], $rule[1]);
                });
            }
        }
    }

    public function getConsultationDetailFromAdmin($id)
    {
        //get Transaction detail
        $transaction = Transaction::where('id_transaction', $id)->where('trasaction_type', 'Consultation')->first();

        if(empty($transaction)){
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        if(empty($transaction)){
            return response()->json(['status' => 'fail', 'messages' => ['Transaction Not Found']]);
        }

        $consultation = $transaction->consultation;

        if(empty($consultation)){
            return response()->json(['status' => 'fail', 'messages' => ['Consultation not found']]);
        }

        $doctor = $consultation->doctor;

        if(empty($doctor)){
            return response()->json(['status' => 'fail', 'messages' => ['Doctor not found']]);
        }

        $user = $transaction->user;

        if(empty($user)){
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
        }
        
        $recomendationProduct = TransactionConsultationRecomendation::with('product')->with('getOutlet')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "product")->get();

        foreach ($recomendationProduct as $key => $recP) {
            $variant = ProductVariantGroup::where('id_product_variant_group', $recP->id_product_variant_group)->first();

            $recomendationProduct[$key]['variant'] = $variant;
        }

        $recomendationDrug = TransactionConsultationRecomendation::with('product')->with('getOutlet')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "drug")->get();

        foreach ($recomendationDrug as $key => $recD) {
            $variant = ProductVariantGroup::where('id_product_variant_group', $recP->id_product_variant_group)->first();

            $recomendationDrug[$key]['variant'] = $variant;
        }

        $result = [
            'transaction' => $transaction,
            'consultation' => $consultation,
            'doctor' => $doctor,
            'customer' => $user,
            'recomendation_product' => $recomendationProduct,
            'recomendation_drug' => $recomendationDrug
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getProductList(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['referal_code'])){
            $idOutlet = Outlet::where('outlet_referral_code', $post['referal_code'])->first()['id_outlet']??null;

            if(empty($idOutlet)) {
                $idOutlet = Outlet::where('outlet_code', $post['referal_code'])->first()['id_outlet']??null;
            }

            if(empty($idOutlet)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }

            $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant']??null;
        }

        $list = Product::select('products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status', 'product_global_price as product_price',
            'product_detail_stock_status as stock_status', 'product_detail.id_outlet')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_is_closed', 0)
            ->where('need_recipe_status', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->orderBy('product_count_transaction', 'desc')
            ->groupBy('products.id_product');
        
        if(!empty($idMerchant)){
            $list = $list->where('id_merchant', $idMerchant);
        }

        if(!empty($post['search_key'])){
            $list = $list->where('product_name', 'like', '%'.$post['search_key'].'%');
        }

        if(!empty($post['id_product_category'])){
            $list = $list->where('id_product_category', $post['id_product_category']);
        }

        if(!empty($post['pagination'])){
            $list = $list->paginate($post['pagination_total_row']??10)->toArray();

            foreach ($list['data'] as $key=>$product){
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if(empty($variantTree['base_price'])){
                        $list['data'][$key]['stock_status'] = 'Sold Out';
                    }
                    $list['data'][$key]['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
                    //TO DO cek
                    $list['data'][$key]['variants'] = $variantTree ?? null;
                }

                unset($list['data'][$key]['id_outlet']);
                unset($list['data'][$key]['product_variant_status']);
                $list['data'][$key]['product_price'] = (int)$list['data'][$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list['data'][$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api').$image['product_photo'] : config('url.storage_url_api').'img/default.jpg');
            }
            $list['data'] = array_values($list['data']);
        }else{
            $list = $list->get()->toArray();

            foreach ($list as $key=>$product){
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if(empty($variantTree['base_price'])){
                        $list[$key]['stock_status'] = 'Sold Out';
                    }
                    $list[$key]['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
                    //TO DO cek
                    $list[$key]['variants'] = $variantTree ?? null;
                }

                unset($list[$key]['id_outlet']);
                unset($list[$key]['product_variant_status']);
                $list[$key]['product_price'] = (int)$list[$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api').$image['product_photo'] : config('url.storage_url_api').'img/default.jpg');
            }
            $list = array_values($list);
        }

        //dd($list);

        return response()->json(MyHelper::checkGet($list));
    }

    public function getDrugList(Request $request)
    {
        $post = $request->json()->all();

        if(!empty($post['referal_code'])){
            $idOutlet = Outlet::where('outlet_code', $post['referal_code'])->first()['id_outlet']??null;
            if(empty($idMerchant)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }

            $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant']??null;
            if(empty($idMerchant)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }
        }

        $list = Product::select('products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status', 'product_global_price as product_price',
            'product_detail_stock_status as stock_status', 'product_detail.id_outlet')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_is_closed', 0)
            ->where('need_recipe_status', 1)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->orderBy('product_count_transaction', 'desc')
            ->groupBy('products.id_product');
        
        if(!empty($idMerchant)){
            $list = $list->where('id_merchant', $idMerchant);
        }

        if(!empty($post['search_key'])){
            $list = $list->where('product_name', 'like', '%'.$post['search_key'].'%');
        }

        if(!empty($post['id_product_category'])){
            $list = $list->where('id_product_category', $post['id_product_category']);
        }

        if(!empty($post['pagination'])){
            $list = $list->paginate($post['pagination_total_row']??10)->toArray();

            foreach ($list['data'] as $key=>$product){
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if(empty($variantTree['base_price'])){
                        $list['data'][$key]['stock_status'] = 'Sold Out';
                    }
                    $list['data'][$key]['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
                    //TO DO cek
                    $list['data'][$key]['variants'] = $variantTree ?? null;
                }

                unset($list['data'][$key]['id_outlet']);
                unset($list['data'][$key]['product_variant_status']);
                $list['data'][$key]['product_price'] = (int)$list['data'][$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list['data'][$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api').$image['product_photo'] : config('url.storage_url_api').'img/default.jpg');
            }
            $list['data'] = array_values($list['data']);
        }else{
            $list = $list->get()->toArray();

            foreach ($list as $key=>$product){
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if(empty($variantTree['base_price'])){
                        $list[$key]['stock_status'] = 'Sold Out';
                    }
                    $list[$key]['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
                    //TO DO cek
                    $list[$key]['variants'] = $variantTree ?? null;
                }

                unset($list[$key]['id_outlet']);
                unset($list[$key]['product_variant_status']);
                $list[$key]['product_price'] = (int)$list[$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api').$image['product_photo'] : config('url.storage_url_api').'img/default.jpg');
            }
            $list = array_values($list);
        }

        //dd($list);

        return response()->json(MyHelper::checkGet($list));
    }

    public function cancelTransaction(Request $request)
    {
        if ($request->id) {
            $trx = TransactionGroup::where(['id_transaction_group' => $request->id, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        } else {
            $trx = TransactionGroup::where(['transaction_receipt_number' => $request->receipt_number, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        }
        if (!$trx) {
            return MyHelper::checkGet([],'Transaction not found');
        }

        if($trx->transaction_payment_status != 'Pending'){
            return MyHelper::checkGet([],'Transaction cannot be canceled');
        }

        $payment_type = $trx->transaction_payment_type;
        if ($payment_type == 'Balance') {
            $multi_payment = TransactionMultiplePayment::select('type')->where('id_transaction_group', $trx->id_transaction_group)->pluck('type')->toArray();
            foreach ($multi_payment as $pm) {
                if ($pm != 'Balance') {
                    $payment_type = $pm;
                    break;
                }
            }
        }

        switch (strtolower($payment_type)) {
            case 'midtrans':
                $midtransStatus = Midtrans::status($trx['id_transaction_group']);
                if ((($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure', 'pending']) || ($midtransStatus['status_code'] ?? false) == '404' ||
                    (!empty($midtransStatus['payment_type']) && $midtransStatus['payment_type'] == 'gopay' && $midtransStatus['transaction_status'] == 'pending')) {
                    $connectMidtrans = Midtrans::expire($trx['transaction_receipt_number']);

                    if($connectMidtrans){
                        $trx->triggerPaymentCancelled();
                        return ['status'=>'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
                return [
                    'status'=>'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
            case 'xendit':
                $dtXendit = TransactionPaymentXendit::where('id_transaction_group', $trx['id_transaction_group'])->first();
                $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit->xendit_id, $dtXendit->type);

                if ($status && $status['status'] == 'PENDING' && !empty($status['id'])) {
                    $cancel = app('Modules\Xendit\Http\Controllers\XenditController')->expireInvoice($status['id']);

                    if($cancel){
                        $trx->triggerPaymentCancelled();
                        return ['status'=>'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
                return [
                    'status'=>'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
        }
        return ['status' => 'fail', 'messages' => ["Cancel $payment_type transaction is not supported yet"]];
    }
}
