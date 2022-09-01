<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Lib\Infobip;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;
use App\Http\Models\TransactionConsultationRecomendation;
use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\LogBalance;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\Merchant\Entities\Merchant;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use NcJoes\OfficeConverter\OfficeConverter;
use App\Lib\CustomOfficeConverter;

use Modules\UserFeedback\Entities\UserFeedbackLog;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\TimeSchedule;
use Modules\Doctor\Entities\Doctor;
use Modules\Transaction\Entities\TransactionGroup;
use DB;
use DateTime;
use Carbon\Carbon;
use Storage;


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
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
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
        $picked_date = date('Y-m-d', strtotime($post['date']));

        $dateId = Carbon::parse($picked_date)->locale('id');
        $dateId->settings(['formatFunction' => 'translatedFormat']);

        $dayId = $dateId->format('l');

        $dateEn = Carbon::parse($picked_date)->locale('en');
        $dateEn->settings(['formatFunction' => 'translatedFormat']);

        $picked_day = $dateEn->format('l');
        $picked_time = date('H:i:s', strtotime($post['time']));

        //get doctor consultation
        $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $picked_date)
                                ->where('schedule_start_time', $picked_time)->count();
        
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
            ->whereHas('schedule_time', function($query) use ($post, $picked_time){
                $query->where('start_time', '=', $picked_time);
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
            'doctor_session_price' => $doctor['doctor_session_price'],
            'url_doctor_photo' => $doctor['url_doctor_photo'],
        ];

        //selected schedule
        $result['selected_schedule'] = [
            'date' => $post['date'],
            'day' => $dayId,
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
        $grandTotal = $subTotal;

        $result['subtotal'] = $subTotal;
        $result['grandtotal'] = $grandTotal;
        $result['point_use'] = $post['point_use']??false;

        //check payment balance
        $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
        $result['current_points'] = (int) $currentBalance;

        $result['payment_detail'] = [];
                
        //subtotal
        $result['payment_detail'][] = [
            'name'          => 'Subtotal Sesi Konsultasi Dr. '.$doctor['doctor_name'].'',
            "is_discount"   => 0,
            'amount'        => 'Rp '.number_format($result['subtotal'],0,",",".")
        ];
        $result['id_outlet'] = $doctor['id_outlet'];
        $result = app($this->promo_trx)->applyPromoCheckoutConsultation($result);

        //get available payment
        $available_payment = app($this->payment)->availablePayment(new Request())['result']??null;
        $result['available_payment'] = $available_payment;

        $grandTotalNew = $result['grandtotal'];
        if(isset($post['point_use']) && $post['point_use']){
            if($currentBalance >= $grandTotalNew){
                $usePoint = $grandTotalNew;
                $grandTotalNew = 0;
            }else{
                $usePoint = $currentBalance;
                $grandTotalNew = $grandTotalNew - $currentBalance;
            }

            $currentBalance -= $usePoint;

            if($usePoint > 0){
                $result['summary_order'][] = [
                    'name' => 'Point yang digunakan',
                    'value' => '- '.number_format($usePoint,0,",",".")
                ];
            }else{
                $result['available_checkout'] = false;
                $result['error_messages'] = 'Tidak bisa menggunakan point, Anda tidak memiliki cukup point.';
            }
        }

        $result['grandtotal'] = $grandTotalNew;
        $result['grandtotal_text'] = 'Rp '.number_format($grandTotalNew,0,",",".");
        $result['current_points'] = $currentBalance;

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
            if(empty($post['selected_schedule']['date']) && empty($post['selected_schedule']['time'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Schedule can not be empty']
                ]);
            }
        } else {
            $post['selected_schedule']['date'] = date('Y-m-d');
            $post['selected_schedule']['time'] = date("H:i:s");
        }

        //cek doctor exists
        $id_doctor = $post['doctor']['id_doctor'];
        $doctor = Doctor::with('outlet')->with('specialists')
        ->where('id_doctor', $post['doctor']['id_doctor'])->onlyActive()
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
        $picked_date = date('Y-m-d', strtotime($post['selected_schedule']['date']));

        $dateId = Carbon::parse($picked_date)->locale('id');
        $dateId->settings(['formatFunction' => 'translatedFormat']);

        $dayId = $dateId->format('l');

        $dateEn = Carbon::parse($picked_date)->locale('en');
        $dateEn->settings(['formatFunction' => 'translatedFormat']);

        $picked_day = $dateEn->format('l');
        $picked_time = date('H:i:s', strtotime($post['selected_schedule']['time']));

        //get doctor consultation
        $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $picked_date)
                                ->where('schedule_start_time', $picked_time)->count();
        $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSetting['value'];

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
        // $post['discount'] = -$post['discount'];
        // $post['discount_delivery'] = -$post['discount_delivery'];

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
        if(isset($post['point_use']) && $post['point_use']){ //
            $paymentType = 'Balance';
        }

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();

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
            'transaction_discount'        => $post['total_discount']??0,
            'transaction_discount_delivery' => 0,
            'transaction_discount_item' 	=> 0,
            'transaction_discount_bill' 	=> $post['total_discount']??0,
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
            })->where('start_time', '=', $post['selected_schedule']['time'])->first();
        } else {
            $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function($query) {
                $query->on('time_schedules.id_doctor_schedule', '=' , 'doctor_schedules.id_doctor_schedule');
            })->whereTime('start_time', '<', $post['selected_schedule']['time'])->whereTime('end_time', '>', $post['selected_schedule']['time'])->first();
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
            'schedule_date'                => $picked_date,
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

        $transaction = Transaction::with('consultation')->where('transaction_payment_status', "Completed")->whereHas('consultation', function($query){
            $query->onlySoon();
        })->get();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak ada transaksi yang akan datang']
            ]);
        }

        $transaction = $transaction->toArray();

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
                $diff = $now->diff($schedule_date_start_time);
                if($diff->d == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
                } elseif($diff->d == 0 && $diff->h == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
                } elseif($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
                } else {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
                }
            } elseif($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
            }

            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['id_doctor'] = $value['consultation']['id_doctor'];
            $result[$key]['doctor_name'] = $doctor['doctor_name'];
            $result[$key]['doctor_photo'] = $doctor['doctor_photo'];
            $result[$key]['url_doctor_photo'] = $doctor['url_doctor_photo'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date_human_formatted'];
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

        $user = $request->user();

        if (isset($user->id_doctor)) {
            $id = $user->id_doctor;
        } else {
            $id = $user->id;
        }

        //cek id transaction
        if(!isset($post['id_transaction'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transaction_consultation_chat_url = optional($transaction->consultation)->consultation_chat_url;
        $transaction = $transaction->toArray();

        //get Doctor
        $detailDoctor = app($this->doctor)->show($transaction['consultation']['id_doctor']);

        if(empty($detailDoctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Dokter tidak di temukan']
            ]);
        }

        //get User 
        $detailUser = User::where('id', $transaction['consultation']['id_user'])->first();
        if(empty($detailUser)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Dokter tidak di temukan']
            ]);
        }


        //get day
        $day = $transaction['consultation']['schedule_day_formatted'];

        //get diff datetime
        $now = new DateTime();
        $schedule_date_start_time = $transaction['consultation']['schedule_date'] .' '. $transaction['consultation']['schedule_start_time'];
        $schedule_date_start_time =new DateTime($schedule_date_start_time);
        $schedule_date_end_time = $transaction['consultation']['schedule_date'] .' '. $transaction['consultation']['schedule_end_time'];
        $schedule_date_end_time =new DateTime($schedule_date_end_time);
        $diff_date = null;

        //logic schedule diff date
        if($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
            $diff = $now->diff($schedule_date_start_time);
            if($diff->d == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
            } elseif($diff->d == 0 && $diff->h == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
            } elseif($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
            } else {
                $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
            }
        } elseif($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
            $diff_date = "now";
        } else {
            $diff_date = "missed";
        }

        $transactionDateId = Carbon::parse($transaction['transaction_date'])->locale('id');
		$transactionDateId->settings(['formatFunction' => 'translatedFormat']);
		$transactionDate = $transactionDateId->format('d F Y');

        $result = [
            'id_transaction' => $transaction['id_transaction'],
            'transaction_date_time' => $transactionDate." ".date('H:i', strtotime($transaction['created_at'])),
            'transaction_consultation_status' => $transaction['consultation']['consultation_status'],
            'id_transaction_consultation' => $transaction['consultation']['id_transaction_consultation'],
            'doctor' => $detailDoctor->getData()->result,
            'user' => $detailUser,
            'schedule_date' => $transaction['consultation']['schedule_date_human_formatted'],
            'schedule_session_time' => $transaction['consultation']['schedule_start_time_formatted']." - ".$transaction['consultation']['schedule_end_time_formatted'],
            'schedule_day' => $day,
            'diff_date' => $diff_date,
            'transaction_consultation_chat_url' => $transaction_consultation_chat_url,
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

        //validasi doctor status
        // if(strtolower($doctor['doctor_status']) != "online" && strtolower($doctor['doctor_status']) != "busy"){
        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Harap Tunggu Hingga Dokter Siap']
        //     ]);
        // }

        //validasi starts early
        // $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        // $getSettingEarly = Setting::where('key','consultation_starts_early')->first();
        // $getSettingLate = Setting::where('key','consultation_starts_late')->first();

        // if(!empty($getSettingEarly)){
        //     $carbonScheduleStartTime = Carbon::parse($transaction['consultation']['schedule_start_time']);
        //     $carbonSettingEarly = Carbon::parse($getSettingEarly->value);
        //     $getTime = $carbonScheduleStartTime->diff($carbonSettingEarly);
        //     $getStartTime =  Carbon::parse($transaction['consultation']['schedule_date']);
        //     $getStartTime->hour($getTime->h);
        //     $getStartTime->minute($getTime->i);
        //     $getStartTime->second($getTime->s);
        // } else {
        //     $getTime = Carbon::parse($transaction['consultation']['schedule_start_time']);
        //     $getStartTime = Carbon::parse($transaction['consultation']['schedule_date']);
        //     $getStartTime->hour($getTime->h);
        //     $getStartTime->minute($getTime->i);
        //     $getStartTime->second($getTime->s);
        // }

        // if($currentTime < $getStartTime) {
        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Anda belum bisa memulai konsultasi, silahkan cek kembali jadwal konsultasi']
        //     ]);
        // }

        // if(!empty($getSettingLate)){
        //     $carbonScheduleStartTime = Carbon::parse($transaction['consultation']['schedule_start_time']);
        //     $carbonSettingLate = Carbon::parse($getSettingLate->value);
        //     $getTime = $carbonScheduleStartTime->sub($carbonSettingLate);
        //     dd($getTime);
        //     $getStartTime =  Carbon::parse($transaction['consultation']['schedule_date']);
        //     $getStartTime->hour($getTime->h);
        //     $getStartTime->minute($getTime->i);
        //     $getStartTime->second($getTime->s);
        // } else {
        //     $getStartTime =  Carbon::parse($transaction['consultation']['schedule_date']);
        //     $getStartTime->hour($getTime->h);
        //     $getStartTime->minute($getTime->i);
        //     $getStartTime->second($getTime->s);
        // }

        // if($currentTime > $getLateTime) {
        //     $updateStatus = $this->checkConsultationMissed($transaction);

        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Anda tidak bisa memulai konsultasi, karena jadwal konsultasi sudah selesai']
        //     ]);
        // }

        DB::beginTransaction();
        try {
            //create agent if empty in doctor
            if(!empty($doctor['id_agent'])){
                $agentId = $doctor['id_agent'];
            } else {
                $outputAgent = $this->createAgent($doctor);
                if($outputAgent['status'] == "fail"){
                    return [
                        'status'=>'fail',
                        'messages' => $outputAgent['response']
                    ];
                }
                $agentId = $outputAgent['response']['id'];
                $doctor->update(['id_agent' => $agentId]);
            }

            //create queue if empty in doctor
            if(!empty($doctor['id_queue'])){
                $queueId = $doctor['id_queue'];
            } else {
                $outputQueue = $this->createQueue($doctor);
                if($outputQueue['status'] == "fail"){
                    return [
                        'status'=>'fail',
                        'messages' => $outputQueue['response']
                    ];
                }
                $queueId = $outputQueue['response']['id'];
                $doctor->update(['id_queue' => $queueId]);
            }

            //create conversation
            if(!empty($transaction['consultation']['id_conversation'])){
                $conversationId = $transaction['consultation']['id_conversation'];

                //get conversation
                $outputConversation = $this->getConversation($conversationId);
            } else {
                $outputConversation = $this->createConversation($doctor);
                if($outputConversation['status'] == "fail"){
                    return [
                        'status'=>'fail',
                        'messages' => $outputConversation['response']
                    ];
                }
                $conversationId = $outputConversation['response']['id'];
            }

            //update transaction consultation
            $consultation = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])
            ->update([
                'id_conversation' => $conversationId,
                'consultation_status' => "ongoing",
                'consultation_start_at' => new DateTime
            ]);

            //update doctor statuses
            $doctor->update(['doctor_status' => "busy"]);
            $doctor->save();

            $result = [
                'transaction_consultation' => $transaction['consultation'],
                'conversation' => $outputConversation
            ];   
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
        $user = $request->user();

        if (!isset($user->id_doctor)) {
            $id = $request->user()->id;
        } else {
            $id = $request->user()->id_doctor;
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
                'consultation_end_at' => new DateTime
            ]);
    
            // update doctor status
            // $doctor->update(['doctor_status' => "online"]);
            // $doctor->save();

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

            //push notifikasi

            //insert saldo to merchant
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

        $consultation = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])->first();
        $result = [
            'id_transaction' => $consultation->id_transaction,
            'id_transaction_consultation' => $consultation->id_transaction_consultation,
            'transaction_consultation_status' => $consultation->transaction_consultation_status,
            'consultation_start_at' => $consultation->consultation_start_at,
            'consultation_end_at' => $consultation->consultation_end_at
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Get info from given cart data
     * @param  completeConsultation $request [description]
     * @return View                    [description]
     */
    public function completeConsultation(Request $request) {
        $post = $request->json()->all();
        $user = $request->user();

        if (!isset($user->id_doctor)) {
            $id = $request->user()->id;
        } else {
            $id = $request->user()->id_doctor;
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
                'consultation_status' => "completed",
                'consultation_completed_at' => new DateTime
            ]);
    
            //push notifikasi

        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Done Consultation Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        $consultation = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])->first();
        $result = [
            'id_transaction' => $consultation->id_transaction,
            'id_transaction_consultation' => $consultation->id_transaction_consultation,
            'transaction_consultation_status' => $consultation->transaction_consultation_status,
            'consultation_start_at' => $consultation->consultation_start_at,
            'consultation_end_at' => $consultation->consultation_end_at
        ];

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
                $query->where(function($query2) use ($post, $id_doctor){
                    $query2->orWhere('schedule_date', 'like', '%'.$post['filter'].'%');
                    $query2->orWhereIn('id_doctor', $id_doctor);
                });
            });
        } else {
            $transaction = $transaction->whereHas('consultation');
        }

        $transaction = $transaction->latest()->get();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['History transaksi konsultasi tidak ditemukan']
            ]);
        }

        $transaction = $transaction->toArray();

        $result = array();
        foreach($transaction as $key => $value) {
            $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $value['consultation']['id_doctor'])->first();

            $result[$key]['id_transaction'] = $value['id_transaction'] ?? null;
            $result[$key]['doctor_name'] = $doctor['doctor_name'] ?? null;
            $result[$key]['doctor_photo'] = $doctor['url_doctor_photo'] ?? null;
            $result[$key]['outlet'] = $doctor['outlet'] ?? null;
            $result[$key]['specialists'] = $doctor['specialists'] ?? null;
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date_human_short_formatted'] ?? null; 
            $result[$key]['consultation_status'] = $value['consultation']['consultation_status'] ?? null;
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

        if (!isset($post['id'])) {
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
                $diff = $now->diff($schedule_date_start_time);
                if($diff->d == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
                } elseif($diff->d == 0 && $diff->h == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
                } elseif($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
                } else {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
                }
            } elseif($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
            }

            //set response result
            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['id_user'] = $value['consultation']['id_user'];
            $result[$key]['user_name'] = $user['name'];
            $result[$key]['user_photo'] = $user['photo'];
            $result[$key]['url_user_photo'] = $user['url_photo'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date_human_formatted'];
            $result[$key]['schedule_start_time'] = $value['consultation']['schedule_start_time_formatted'];
            $result[$key]['diff_date'] = $diff_date;
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
            $transactionConsultation = TransactionConsultation::where('id_user', $user->id)->where('id_transaction', $post['id_transaction'])->first();
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
            $id = $user->id_doctor;
            $transactionConsultation = TransactionConsultation::where('id_doctor', $id)->where('id_transaction', $post['id_transaction'])->first();    
        } else {
            $id = $user->id;
            $transactionConsultation = TransactionConsultation::where('id_user', $id)->where('id_transaction', $post['id_transaction'])->first();
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
                //get product data
                // $variantGroup = ProductVariantGroup::join('product_variant_group_details', 'product_variant_group_details.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                //                 ->where('id_outlet', $post['id_outlet'])
                //                 ->where('id_product', $product['id_product'])
                //                 ->where('product_variant_group_details.product_variant_group_visibility', 'Visible')
                //                 ->where('product_variant_group_stock_status', 'Available')
                //                 ->orderBy('product_variant_group_price', 'asc')->first();
                // $product['product_price'] = $selectedVariant['product_variant_group_price']??$product['product_price'];
                // $post['id_product_variant_group'] = $selectedVariant['id_product_variant_group']??null;
                // $product['id_product_variant_group'] = $post['id_product_variant_group'];

                // $productDetail = 

                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' =>$recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                // $items[$key]['id_product'] = $recomendation->product->id_product ?? null;
                // $items[$key]['product_name'] = $recomendation->product->product_name ?? null;
                // $items[$key]['product_price'] = $recomendation->product->product_global_price ?? null;
                // $items[$key]['product_description'] = $recomendation->product->product_description ?? null;
                // $items[$key]['product_photo'] = $recomendation->product->product_photos[0]['url_product_photo'] ?? null;
                // $items[$key]['product_rating'] = $recomendation->product->total_rating ?? null;
                // $items[$key]['product_stock_item'] = $recomendation->product->product_detail[0]->product_detail_stock_item ?? null;
                // $items[$key]['product_stock_status'] = $recomendation->product->product_detail[0]->product_detail_stock_status ?? null;
                // $items[$key]['outlet_name'] = $recomendation->product->product_detail[0]->outlet->outlet_name ?? null;
                // $items[$key]['product_variant_group'] = $variantGroup ?? null;
                $items[$key]['product'] = $detailProduct ?? null;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rules'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rules_time'] = $recomendation->usage_rules_time ?? null;
                $items[$key]['usage_rules_additional_time'] = $recomendation->usage_rules_additional ?? null;
                $items[$key]['treatment_description'] = $recomendation->treatment_description ?? null;
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
            $id = $user->id_doctor;
            $transactionConsultation = TransactionConsultation::where('id_doctor', $id)->where('id_transaction', $post['id_transaction'])->first();    
        } else {
            $id = $user->id;
            $transactionConsultation = TransactionConsultation::where('id_user', $id)->where('id_transaction', $post['id_transaction'])->first();
        }

        $transaction = Transaction::with('outlet')->where('id_transaction', $transactionConsultation['id_transaction'])->first();

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
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' =>$recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                $items[$key]['product'] = $detailProduct ?? null;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rules'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rules_time'] = $recomendation->usage_rules_time ?? null;
                $items[$key]['usage_rules_additional_time'] = $recomendation->usage_rules_additional ?? null;
                $items[$key]['treatment_description'] = $recomendation->treatment_description ?? null;
            }
        }

        $result = [
            'id_transaction_consultation' => $transactionConsultation['id_transaction_consultation'],
            'outlet' => $transaction['outlet'],
            'items' => $items,
            'remaining_recipe_redemption' =>  ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter)
        ];

        return MyHelper::checkGet($result);
    }

    public function downloadDrugRecomendation(Request $request)
    {
        //PDF file is stored under project/public/download/info.pdf
        // $file= public_path(). "/download/receipt.pdf";

        // $headers = array(
        //     'Content-Type: application/pdf',
        // );

        // return FacadeResponse::download($file, 'receipt.pdf', $headers);

        $post = $request->json()->all();

        $id = $request->user()->id;

        //get Transaction Consultation Data
        $transactionConsultation = TransactionConsultation::with('doctor')->where('id_transaction_consultation', $post['id_transaction_consultation'])->first()->toArray();
        $doctor = Doctor::with('specialists')->where('id_doctor', $transactionConsultation['id_doctor'])->first()->toArray();
        $user = User::where('id', $transactionConsultation['id_user'])->first()->toArray();
        $date = Carbon::parse($user['birthday']);
        $now = Carbon::now();
        $user['age'] = $date->diffInYears($now);

        $transaction = Transaction::where('id_transaction', $transactionConsultation['id_transaction'])->first()->toArray();
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();

        $items = [];
        if(!empty($recomendations)) {
            foreach($recomendations as $key => $recomendation){
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' =>$recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                $items[$key]['product_name'] = $detailProduct['result']['product_name'] ?? null;
                $items[$key]['variant_name'] = $detailProduct['result']['variants']['product_variant_name'] ?? null;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rule'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rule_time'] = $recomendation->usage_rules_time ?? null;
                $items[$key]['usage_rule_additional_time'] = $recomendation->usage_rules_additional ?? null;
            }
        }

        //$phpWord = new \PhpOffice\PhpWord\PhpWord();

        //$section = $phpWord->addSection();

        //setting template 
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(public_path().'/download/template_receipt.docx');
        $templateProcessor->setValue('doctor_name', $doctor['doctor_name']);
        $templateProcessor->setValue('doctor_specialist_name', $doctor['specialists'][0]['doctor_specialist_name']);
        $templateProcessor->setValue('doctor_practice_lisence_number', $doctor['practice_lisence_number']);
        $templateProcessor->setValue('transaction_date', MyHelper::dateFormatInd($transaction['transaction_date']));
        $templateProcessor->setValue('transaction_receipt_number', $transaction['transaction_receipt_number']);
        $templateProcessor->cloneBlock('block_items', 0, true, false, $items);
        $templateProcessor->setValue('customer_name', $user['name']);
        $templateProcessor->setValue('customer_age', $user['age']);
        $templateProcessor->setValue('customer_gender', $user['gender']);

        if(!Storage::exists('receipt/docx')){
            Storage::makeDirectory('receipt/docx');
        }

        $directory = ('storage/receipt/docx/receipt_'.$transaction['transaction_receipt_number'].'.docx');
        $templateProcessor->saveAs($directory);

        // $description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod

        // tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,

        // quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo

        // consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse

        // cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non

        // proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        // $items = json_encode($items);

        // $description = [
        //     'doctor_name' => $doctor['doctor_name'],
        //     'doctor_specialist_name' => $doctor['specialists'][0]['doctor_specialist_name'],
        //     'transaction_date' => MyHelper::dateFormatInd($transaction['transaction_date']),
        //     'items' => $items
        // ];

        // $description = implode("|", $description);


        //$section->addImage("http://itsolutionstuff.com/frontTheme/images/logo.png");

        //$section->addText($description);

        // $domPdfPath = realpath(base_path('vendor/dompdf/dompdf'));
        // \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        // \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

        // $phpWord = \PhpOffice\PhpWord\IOFactory::load('storage/save_pdf_test2.docx');
        // $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        // $objWriter->save('storage/helloWorld3.pdf');

        // $converter = new OfficeConverter('storage/save_pdf_test2.docx', 'storage/helloWorld4.pdf');

        // $test = 'C:\\tpid-dian\dian\titip\test.txt';
        // return response()->download($test);

        if(!Storage::exists('receipt/pdf')){
            Storage::makeDirectory('receipt/pdf');
        }
    
        $converter = new CustomOfficeConverter($directory, 'storage/receipt/pdf', '"C:\\Program Files\LibreOffice\program\soffice.exe"', true);
        $output = $converter->convertTo('receipt_'.$transaction['transaction_receipt_number'].'.pdf');
        // $converter = new OfficeConverter('storage/save_pdf_test2.docx');
        // $converter->convertTo('storage/save_pdf_test2.pdf');

        return response()->download($output);
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
        
        $recomendationProduct = TransactionConsultationRecomendation::with('product')->with('outlet')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "product")->get();

        foreach ($recomendationProduct as $key => $recP) {
            $variant = ProductVariantGroup::where('id_product_variant_group', $recP->id_product_variant_group)->first();

            $recomendationProduct[$key]['variant'] = $variant;
        }

        $recomendationDrug = TransactionConsultationRecomendation::with('product')->with('outlet')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "drug")->get();

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

    public function createAgent($doctor)
    {
        //create Agent
        $agent = [
            "displayName" => $doctor['doctor_name'],
            "status" => "ACTIVE",
            "role" => "AGENT",
            "enabled" => true 
        ];

        $url = "/ccaas/1/agents";

        $outputAgent = Infobip::sendRequest('Agent', "POST", $url, $agent);

        return $outputAgent;
    }

    public function createQueue($doctor)
    {
        //create Queue
        $queue = [
            "name" => "Queue".$doctor['doctor_name']
        ];

        $url = "/ccaas/1/queues";

        $outputQueue = Infobip::sendRequest('Queue', "POST", $url, $queue);

        return $outputQueue;
    }

    public function getConversation($conversationId)
    {
        //get ConversationId
        // $conversationId = $request->id_conversation;

        $url = "/ccaas/1/conversations/".$conversationId;

        $subject = [
            'action' => "Get Conversations $conversationId"
        ];

        $outputMessage = Infobip::getRequest('Conversation', "GET", $url);

        return response()->json(MyHelper::checkGet($outputMessage));
    }

    public function createConversation($doctor)
    {
        //create Conversation
        $conversation = [
            "topic" => "Conversation".$doctor['id_doctor'],
            "summarry" => null,
            "status" => "OPEN",
            "priority" => "HIGH",
            "queueId" => $doctor['id_queue'],
            "agentId" => $doctor['id_agent'] 
        ];

        $url = "/ccaas/1/conversations";

        $subject = [
            'id_doctor' => $doctor['id_doctor'],
            'action' => 'Create Conversations'
        ];

        $outputConversation = Infobip::sendRequest('Conversation', "POST", $url, $conversation);

        return $outputConversation;
    }

    public function getMessage(Request $request)
    {
        //create ConversationId
        $conversationId = $request->id_conversation;

        $url = "/ccaas/1/conversations/".$conversationId."/messages";

        $subject = [
            'action' => "Get Conversations Message $conversationId"
        ];

        $outputMessage = Infobip::getRequest('Conversation', "GET", $url);

        return response()->json(MyHelper::checkGet($outputMessage));
    }

    public function createMessage(Request $request)
    {
        $post = $request->json()->all();

        //cek id transaction
        if(!isset($post['id_transaction'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if(empty($transaction)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transaction = $transaction->toArray();

        //create Message
        $message = [
            "from" => $post['id_conversation'],
            "to" => $post['id_conversation'],
            "channel" => "LIVE_CHAT",
            "contentType" => "TEXT",
            "content" => $post['message']
        ];

        $url = "/ccaas/1/conversations/".$request->id_conversation."/messages";

        $subject = [
            'id_doctor' => $transaction['consultation']['id_doctor'],
            'action' => 'Create Conversations'
        ];

        $outputMessages = Infobip::sendRequest('Conversation', "POST", $url, $message);

        return response()->json(MyHelper::checkGet($outputMessages));
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function transactionDetail(Request $request) {
        $post = $request->json()->all();

        //cek id transaction
        if(!isset($post['id_transaction'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->with('outlet')->where('id_transaction', $post['id_transaction'])->first();

        if(empty($transaction)){
            return response()->json(MyHelper::checkGet($transaction));
        }

        $transaction_consultation_chat_url = optional($transaction->consultation)->consultation_chat_url;
        $transaction = $transaction->toArray();

        //if cek jadwal missed
        //$checkMissed = $this->checkConsultationMissed($transaction);

        //get Consultation
        $consultation = [
            'schedule_date' => $transaction['consultation']['schedule_date_human_short_formatted'],
            'schedule_start_time' => $transaction['consultation']['schedule_start_time_formatted'],
            'schedule_end_time' => $transaction['consultation']['schedule_end_time_formatted'],
            'consultation_status' => $transaction['consultation']['consultation_status']
        ];

        //get Doctor
        $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $transaction['consultation']['id_doctor'])->first();
        
        if(empty($doctor)){
            return response()->json(MyHelper::checkGet($doctor));
        }

        $doctor = $doctor->toArray();
        unset($doctor['password']);

        //set detail payment
        $paymentDetail = [
            [
                'text' => 'Subtotal',
                'value' => 'Rp '. number_format((int)$transaction['transaction_subtotal'],0,",",".")
            ]
        ];

        if(!empty($transaction['transaction_discount'])){
            $codePromo = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->first()['promo_code']??'';
            $paymentDetail[] = [
                'text' => 'Discount'.(!empty($transaction['transaction_discount_delivery'])? ' Biaya Kirim':'').(!empty($codePromo) ?' ('.$codePromo.')' : ''),
                'value' => '-Rp '. number_format((int)abs($transaction['transaction_discount']),0,",",".")
            ];
        }

        $grandTotal = $transaction['transaction_grandtotal'];
        $trxPaymentBalance = TransactionPaymentBalance::where('id_transaction', $transaction['id_transaction'])->first()['balance_nominal']??0;

        if(!empty($trxPaymentBalance)){
            $paymentDetail[] = [
                'text' => 'Point yang digunakan',
                'value' => '-'.number_format($trxPaymentBalance,0,",",".")
            ];
            $grandTotal = $grandTotal - $trxPaymentBalance;
        }

        $trxPaymentMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $transaction['id_transaction_group'])->first();
        $trxPaymentXendit = TransactionPaymentXendit::where('id_transaction_group', $transaction['id_transaction_group'])->first();

        $paymentURL = null;
        $paymentToken = null;
        $paymentType = null;
        if(!empty($trxPaymentMidtrans)){
            $paymentMethod = $trxPaymentMidtrans['payment_type'].(!empty($trxPaymentMidtrans['bank']) ? ' ('.$trxPaymentMidtrans['bank'].')':'');
            $paymentMethod = str_replace(" ","_",$paymentMethod);
            $paymentLogo = config('payment_method.midtrans_'.strtolower($paymentMethod).'.logo');
            $paymentType = 'Midtrans';
            if($transaction['transaction_status'] == 'Unpaid'){
                $paymentURL = $trxPaymentMidtrans['redirect_url'];
                $paymentToken = $trxPaymentMidtrans['token'];
            }
        }elseif(!empty($trxPaymentXendit)){
            $paymentMethod = $trxPaymentXendit['type'];
            $paymentMethod = str_replace(" ","_",$paymentMethod);
            $paymentLogo = config('payment_method.xendit_'.strtolower($paymentMethod).'.logo');
            $paymentType = 'Xendit';
            if($transaction['transaction_status'] == 'Unpaid'){
                $paymentURL = $trxPaymentXendit['checkout_url'];
            }
        }

        $result = [
            'id_transaction' => $transaction['id_transaction'],
            'receipt_number_group' => TransactionGroup::where('id_transaction_group', $transaction['id_transaction_group'])->first()['transaction_receipt_number']??'',
            'transaction_receipt_number' => $transaction['transaction_receipt_number'],
            'transaction_status' => $transaction['transaction_status']??'',
            'transaction_date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_date'])), true),
            'transaction_consultation' => $consultation,
            'show_rate_popup' => $transaction['show_rate_popup'],
            'transaction_grandtotal' => 'Rp '. number_format($grandTotal,0,",","."),
            'outlet_name' => $transaction['outlet']['outlet_name'],
            'outlet_logo' => (empty($transaction['outlet_image_logo_portrait']) ? config('url.storage_url_api').'img/default.jpg': config('url.storage_url_api').$transaction['outlet_image_logo_portrait']),
            'user' => User::where('id', $transaction['id_user'])->select('name', 'email', 'phone')->first(),
            'doctor' => $doctor,
            'payment' => $paymentMethod??'',
            'payment_logo' => $paymentLogo??'',
            'payment_type' => $paymentType,
            'payment_token' => $paymentToken,
            'payment_url' => $paymentURL,
            'payment_detail' => $paymentDetail,
            'point_receive' => (!empty($transaction['transaction_cashback_earned'] && $transaction['transaction_status'] != 'Rejected') ? 'Mendapatkan +'.number_format((int)$transaction['transaction_cashback_earned'],0,",",".").' Points Dari Transaksi ini' : ''),
            'transaction_reject_reason' => $transaction['transaction_reject_reason'],
            'transaction_reject_at' => (!empty($transaction['transaction_reject_at']) ? MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_reject_at'])), true) : null),
            'transaction_consultation_chat_url' => $transaction_consultation_chat_url,
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function checkConsultationMissed($transaction) {
        
        //getCurrentTime
        $currentTime = date('H:i:s');

        if($transaction['consultation']['schedule_end_time'] < $currentTime) {
            $updateConsultationStatus = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->update(['consultation_status' => "missed"]);
        }
        
        return $transaction;
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function getConsultationSettings(Request $request) {
        $post = $request->json()->all();

        $getSetting = Setting::where('key', $post['key'])->first()['value']??null;

        $result = json_decode($getSetting);

        return response()->json(MyHelper::checkGet($result));
    }

    public function getChatView(Request $request)
    {
        $trx = Transaction::where('id_transaction', $request->id_transaction)->first();
        if (!$trx) {
            return abort(404);
        }

        // if (!password_verify($trx->id_transaction . $trx->id_user, $request->auth_code)) {
        //     return abort(403);
        // }

        $jti = ((string) time()) . rand(10, 99);
        $payload = [
             "jti" => $jti,
             "sid" => "session" . $jti, 
             "sub" => $trx->transaction_receipt_number,
             "stp" => "externalPersonId",
             "iss" => config('infobip.widget_id'),
             "iat" => time(),
             "exp" => time()+3600,
             "ski" => config('infobip.secretkey_id'),
        ];
        $token = MyHelper::jwtTokenGenerator($payload);
        return view('consultation::chat', ['token' => $token]);
    }

    public function getDetailInfobip(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //get transaction
        $transactionConsultation = null;
        if(isset($user->id_doctor)){
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)
                ->where('id_transaction', $post['id_transaction'])
                ->with('doctor', 'user')
                ->first();
        } else {
            $transactionConsultation = TransactionConsultation::where('id_user', $user->id)
                ->where('id_transaction', $post['id_transaction'])
                ->with('doctor', 'user')
                ->first();
        }

        if (!$transactionConsultation) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        return [
            'status' => 'success', 
            'result' => [
                'transaction_consultation_chat_url' => $user->id_doctor ? null : $transactionConsultation->consultation_chat_url,
                'doctor_identity' => $transactionConsultation->doctor->infobip_identity,
                'customer_identity' => $transactionConsultation->user->infobip_identity,
                'token' => $user->getActiveToken(),
            ]
        ];
    }
}
