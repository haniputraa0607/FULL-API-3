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
use Modules\ProductVariant\Entities\ProductVariant;
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
use Modules\Consultation\Entities\TransactionConsultationMessage;
use Modules\Consultation\Entities\TransactionConsultationReschedule;
use Modules\Consultation\Http\Requests\DoneConsultation;
use Modules\Consultation\Entities\LogInfobip;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\UserRatingLog;
use DB;
use DateTime;
use Carbon\Carbon;
use Storage;

class ApiTransactionConsultationController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->payment = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->location = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->doctor = "Modules\Doctor\Http\Controllers\ApiDoctorController";
        $this->promo_trx     = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    /**
     * Get info from given cart data
     * @param  CheckTransaction $request [description]
     * @return View                    [description]
     */
    public function checkTransaction(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //cek date time schedule
        if ($post['consultation_type'] != "now") {
            if (empty($post['date']) && empty($post['time'])) {
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

        if (empty($doctor)) {
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

        $picked_day = strtolower($dateEn->format('l'));
        $picked_time = date('H:i:s', strtotime($post['time']));

        //get doctor consultation
        $doctor_constultation = TransactionConsultation::where('id_doctor', $id_doctor)->where('schedule_date', $picked_date)
                                ->whereNotIn('consultation_status', ['canceled', 'done'])
                                ->where('schedule_start_time', $picked_time)->count();

        $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSetting['value'];

        if ($quota <= $doctor_constultation && $quota != null) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        //selected session
        $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function ($query) use ($post, $picked_time) {
                $query->where('start_time', '<=', $picked_time);
            })->first();

        if (empty($schedule_session)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal Sesi penuh / tidak tersedia']
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
            'date_text' => MyHelper::dateFormatInd($post['date'], true, false, false),
            'day' => $dayId,
            'time' => $post['time']
        ];

        if ($post['consultation_type'] == 'now') {
            $picked_schedule = DoctorSchedule::where('id_doctor', $id_doctor)->leftJoin('time_schedules', function ($query) {
                $query->on('time_schedules.id_doctor_schedule', '=', 'doctor_schedules.id_doctor_schedule');
            })->whereTime('start_time', '<', $result['selected_schedule']['time'])->whereTime('end_time', '>', $result['selected_schedule']['time'])->first();
            $settingMaximumSessionNow = Setting::where('key', 'min_session_consultation_now')->first()['value'] ?? 30;
            $now = strtotime(date('H:i:s'));
            $endSession = date('H:i:s', strtotime($picked_schedule['end_time']));

            $diff = round(abs(strtotime($endSession) - $now) / 60);

            if ($diff < $settingMaximumSessionNow) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Tidak bisa melakukan konsultasi saat ini karena sesi akan berakhir ' . $diff . ' menit lagi']
                ]);
            }
        }

        //check referral code
        if (isset($post['referral_code'])) {
            $outlet = Outlet::where('outlet_referral_code', $post['referral_code'])->first();

            if (empty($outlet)) {
                $outlet = Outlet::where('outlet_code', $post['referral_code'])->first();
            }

            if (empty($outlet)) {
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
        $result['point_use'] = $post['point_use'] ?? false;

        //check payment balance
        $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
        $result['current_points'] = (int) $currentBalance;

        $result['payment_detail'] = [];

        //subtotal
        $result['payment_detail'][] = [
            'name'          => 'Subtotal Sesi Konsultasi Dr. ' . $doctor['doctor_name'] . '',
            "is_discount"   => 0,
            'amount'        => 'Rp ' . number_format($result['subtotal'], 0, ",", ".")
        ];
        $result['id_outlet'] = $doctor['id_outlet'];
        $result = app($this->promo_trx)->applyPromoCheckoutConsultation($result);

        //get available payment
        $fake_request = new Request(['show_all' => 0, 'from_check' => 1]);
        $available_payment = app($this->payment)->availablePayment($fake_request)['result'] ?? null;
        $result['available_payment'] = $available_payment;

        $grandTotalNew = $result['grandtotal'];
        if (isset($post['point_use']) && $post['point_use']) {
            if ($currentBalance >= $grandTotalNew) {
                $usePoint = $grandTotalNew;
                $grandTotalNew = 0;
            } else {
                $usePoint = $currentBalance;
                $grandTotalNew = $grandTotalNew - $currentBalance;
            }

            $currentBalance -= $usePoint;

            if ($usePoint > 0) {
                $result['summary_order'][] = [
                    'name' => 'Point yang digunakan',
                    'value' => '- ' . number_format($usePoint, 0, ",", ".")
                ];
            } else {
                $result['available_checkout'] = false;
                $result['error_messages'] = 'Tidak bisa menggunakan point, Anda tidak memiliki cukup point.';
            }
        }

        $result['grandtotal'] = $grandTotalNew;
        $result['grandtotal_text'] = 'Rp ' . number_format($grandTotalNew, 0, ",", ".");
        $result['current_points'] = $currentBalance;

        return MyHelper::checkGet($result);
    }

    /**
     * Get info from given cart data
     * @param  NewTransaction $request [description]
     * @return View                    [description]
     */

    public function newTransaction(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //cek input date and time
        if ($post['consultation_type'] != 'now') {
            if (empty($post['selected_schedule']['date']) && empty($post['selected_schedule']['time'])) {
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
        ->where('id_doctor', $post['doctor']['id_doctor'])
        ->first();

        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Silahkan pilh dokter terlebih dahulu']
            ]);
        }
        $doctor = $doctor->toArray();

        //cek doctor active
        // if(isset($doctor['is_active']) && $doctor['is_active'] == false){
        //     DB::rollback();
        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Doctor Tutup Sesi Konsuling']
        //     ]);
        // }

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
                                ->whereNotIn('consultation_status', ['canceled', 'done'])
                                ->where('schedule_start_time', $picked_time)->count();
        $getSetting = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSetting['value'];

        if ($quota <= $doctor_constultation && $quota != null) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        if ($post['consultation_type'] == 'now') {
            $checkS = DoctorSchedule::where('id_doctor', $id_doctor)->leftJoin('time_schedules', function ($query) {
                $query->on('time_schedules.id_doctor_schedule', '=', 'doctor_schedules.id_doctor_schedule');
            })->whereTime('start_time', '<', $post['selected_schedule']['time'])->whereTime('end_time', '>', $post['selected_schedule']['time'])->first();
            $settingMaximumSessionNow = Setting::where('key', 'min_session_consultation_now')->first()['value'] ?? 30;
            $now = strtotime(date('H:i:s'));
            $endSession = date('H:i:s', strtotime($checkS['end_time']));

            $diff = round(abs(strtotime($endSession) - $now) / 60);

            if ($diff < $settingMaximumSessionNow) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Tidak bisa melakukan konsultasi saat ini karena sesi akan berakhir ' . $diff . ' menit lagi']
                ]);
            }
        }

        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        //user suspend
        if (isset($user['is_suspended']) && $user['is_suspended'] == '1') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami']
            ]);
        }

        //check validation email
        if (isset($user['email'])) {
            $domain = substr($user['email'], strpos($user['email'], "@") + 1);
            if (
                !filter_var($user['email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false
            ) {
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
        $distance = null;
        if (isset($post['latitude']) &&  isset($post['longitude'])) {
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

        $subtotal = $post['subtotal'];
        $grandtotal = $subtotal;

        //update subtotal and grandtotal
        $post['subtotal'] = $subtotal;
        $post['grandtotal'] = $grandtotal;

        $deliveryTotal = 0;
        $currentDate = date('Y-m-d H:i:s');
        $paymentType = null;
        $transactionStatus = 'Unpaid';
        $paymentStatus = 'Pending';
        if (isset($post['point_use']) && $post['point_use']) { //
            $paymentType = 'Balance';
        }

        DB::beginTransaction();
        UserFeedbackLog::where('id_user', $request->user()->id)->delete();

        //check referral code
        if (isset($post['referral_code'])) {
            $outlet = Outlet::where('outlet_referral_code', $post['referral_code'])->first();

            if (empty($outlet)) {
                $outlet = Outlet::where('outlet_code', $post['referral_code'])->first();
            }

            if (empty($outlet)) {
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
            'transaction_receipt_number' => 'TRX' . time() . rand() . substr($grandtotal, 0, 5),
            'transaction_subtotal' => 0,
            'transaction_shipment' => 0,
            'transaction_grandtotal' => 0,
            'transaction_payment_status' => $paymentStatus,
            'transaction_payment_type' => $paymentType,
            'transaction_group_date' => $currentDate
        ];

        $insertTransactionGroup = TransactionGroup::create($dataTransactionGroup);
        if (!$insertTransactionGroup) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Group Failed']
            ]);
        }

        $grandtotal = $post['grandtotal'];

        $insertTransactionGroup->update([
            'transaction_subtotal' => $subtotal,
            'transaction_shipment' => $deliveryTotal,
            'transaction_grandtotal' => $grandtotal
        ]);


        $transaction = [
            'id_transaction_group'        => $insertTransactionGroup['id_transaction_group'],
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'id_promo_campaign_promo_code' => $post['id_promo_campaign_promo_code'] ?? null,
            'transaction_date'            => $post['transaction_date'],
            'trasaction_type'             => $type,
            'shipment_method'             => $shipment_method ?? null,
            'shipment_courier'            => $shipment_courier ?? null,
            'transaction_notes'           => $post['notes'],
            'transaction_subtotal'        => $subtotal,
            'transaction_gross'           => $post['subtotal_final'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['total_discount'] ?? 0,
            'transaction_discount_delivery' => 0,
            'transaction_discount_item'     => 0,
            'transaction_discount_bill'     => $post['total_discount'] ?? 0,
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $grandtotal,
            'transaction_point_earned'    => $post['point'] ?? 0,
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $paymentType,
            'transaction_payment_status'  => $paymentStatus,
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'distance_customer'           => $distance,
            'void_date'                   => null,
            'transaction_status'          => $transactionStatus
        ];

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (stristr($useragent, 'iOS')) {
            $useragent = 'IOS';
        } elseif (stristr($useragent, 'okhttp')) {
            $useragent = 'Android';
        } else {
            $useragent = null;
        }

        if ($useragent) {
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
        $receiptNumber = 'TRX-' . $outlet['outlet_code'] . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-';
        $lastReceipt = Transaction::where('transaction_receipt_number', 'like', $receiptNumber . '%')->orderBy('transaction_receipt_number', 'desc')->first()['transaction_receipt_number'] ?? '';
        $lastReceipt = explode('-', $lastReceipt)[5] ?? 0;
        $lastReceipt = (int)$lastReceipt;
        $countReciptNumber = $lastReceipt + 1;
        $receipt = $receiptNumber . sprintf("%04d", $countReciptNumber);

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
        if ($post['consultation_type'] != 'now') {
            $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function ($query) {
                $query->on('time_schedules.id_doctor_schedule', '=', 'doctor_schedules.id_doctor_schedule');
            })->where('start_time', '=', $post['selected_schedule']['time'])->first();
        } else {
            $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function ($query) {
                $query->on('time_schedules.id_doctor_schedule', '=', 'doctor_schedules.id_doctor_schedule');
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
            'referral_code'                => $post['referral_code'] ?? null,
            'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])) . ' ' . date('H:i:s'),
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

        if (strtotime($insertTransaction['transaction_date'])) {
            $trx_consultation->created_at = strtotime($insertTransaction['transaction_date']);
        }

        $trxGroup = TransactionGroup::where('id_transaction_group', $insertTransactionGroup['id_transaction_group'])->first();
        if ($paymentType == 'Balance' && isset($post['point_use']) && $post['point_use']) {
            $currentBalance = LogBalance::where('id_user', $user->id)->sum('balance');
            $grandTotalNew = $trxGroup['transaction_grandtotal'];
            if ($currentBalance >= $grandTotalNew) {
                $grandTotalNew = 0;
            } else {
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

            if ($grandTotalNew == 0) {
                $trxGroup->triggerPaymentCompleted();
            }
        } elseif ($insertTransactionGroup['transaction_grandtotal'] == 0 && !empty($post['id_promo_campaign_promo_code'])) {
            $trxGroup->triggerPaymentCompleted();
        }

        if ($post['latitude'] && $post['longitude']) {
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
    public function getTransaction(Request $request)
    {
        $post = $request->json()->all();

        $transaction = Transaction::where('id_transaction', $post['id_transaction'])->where('trasaction_type', 'Consultation')->first();

        if (empty($transaction)) {
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
    public function getSoonConsultationList(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $transaction = Transaction::with('consultation')->where('transaction_payment_status', "Completed")->whereHas('consultation', function ($query) {
            $query->onlySoon();
        })->where('id_user', $id)->get();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak ada transaksi yang akan datang']
            ]);
        }

        // $transaction = $transaction->toArray();


        $now = new DateTime();

        $result = array();
        foreach ($transaction as $key => $value) {
            $doctor = Doctor::where('id_doctor', $value['consultation']['id_doctor'])->first()->toArray();

            //get Consultation
            $transactionConsultation = $value->consultation;

            //get diff datetime
            $now = new DateTime();
            $schedule_date_start_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_start_time'];
            $schedule_date_start_time = new DateTime($schedule_date_start_time);
            $schedule_date_end_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_end_time'];
            $schedule_date_end_time = new DateTime($schedule_date_end_time);
            $diff_date = null;

            //logic schedule diff date
            if ($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
                $diff = $now->diff($schedule_date_start_time);
                if ($diff->d == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
                } else {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
                }
            } elseif ($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } else {
                $diff_date = "missed";
            }

            //badge setting
            switch ($transactionConsultation['consultation_status']) {
                case "soon":
                    $badgeText = $diff_date;
                    $badgeColor = '#E6E6E6';
                    $badgeTextColor = '#000000';
                    break;
                case "ongoing":
                    $badgeText = 'Sedang Berjalan';
                    $badgeColor = '#9c1f60';
                    $badgeTextColor = '#eeeeee';
                    break;
                case "done":
                    $badgeText = 'Menunggu Hasil';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "completed":
                    $badgeText = 'Selesai';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "canceled":
                    $badgeText = 'Dibatalkan';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
                default:
                    $badgeText = 'Terlewati';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
            }


            $result[$key]['id_transaction'] = $value['id_transaction'];
            $result[$key]['id_doctor'] = $value['consultation']['id_doctor'];
            $result[$key]['doctor_name'] = $doctor['doctor_name'];
            $result[$key]['doctor_photo'] = $doctor['doctor_photo'];
            $result[$key]['url_doctor_photo'] = $doctor['url_doctor_photo'];
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date_human_formatted'];
            $result[$key]['diff_date'] = $diff_date;
            $result[$key]['badge_text'] = $badgeText;
            $result[$key]['badge_color'] = $badgeColor;
            $result[$key]['badge_text_color'] = $badgeTextColor;
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
    public function getSoonConsultationDetail(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        if (isset($user->id_doctor)) {
            $id = $user->id_doctor;
        } else {
            $id = $user->id;
        }

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transaction_consultation_chat_url = optional($transaction->consultation)->consultation_chat_url;
        //get Consultation
        $transactionConsultation = $transaction->consultation;
        $transaction = $transaction->toArray();

        //get Doctor
        $detailDoctor = app($this->doctor)->show($transaction['consultation']['id_doctor']);

        if (empty($detailDoctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Dokter tidak di temukan']
            ]);
        }

        //get User
        $detailUser = User::where('id', $transaction['consultation']['id_user'])->first();
        if (empty($detailUser)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Dokter tidak di temukan']
            ]);
        }


        //get day
        $day = $transaction['consultation']['schedule_day_formatted'];

        //get diff datetime
        $now = new DateTime();
        $schedule_date_start_time = $transaction['consultation']['schedule_date'] . ' ' . $transaction['consultation']['schedule_start_time'];

        //if get setting early
        $getSettingEarly = Setting::where('key', 'consultation_starts_early')->first();
        $schedule_date_start_time = date('d-m-Y H:i:s', strtotime($schedule_date_start_time . " -$getSettingEarly->value minutes"));

        $schedule_date_start_time = new DateTime($schedule_date_start_time);

        $schedule_date_end_time = $transaction['consultation']['schedule_date'] . ' ' . $transaction['consultation']['schedule_end_time'];
        $schedule_date_end_time = new DateTime($schedule_date_end_time);
        $diff_date = null;

        //dd($schedule_date_start_time < $now && $schedule_date_end_time > $now);
        //dd($transactionConsultation['consultation_status']);

        //logic schedule diff date
        if ($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
            $diff = $now->diff($schedule_date_start_time);
            if ($diff->d == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
            } elseif ($diff->d == 0 && $diff->h == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
            } elseif ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
            } else {
                $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
            }
        } elseif ($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
            $diff_date = "now";
            $transaction['consultation']['consultation_status'] = 'now';
        } elseif ($transactionConsultation['consultation_status'] == 'done' || $transactionConsultation['consultation_status'] == 'completed') {
            $diff_date = "completed";
        } else {
            $diff_date = "missed";
        }

        $transactionDateId = Carbon::parse($transaction['transaction_date'])->locale('id');
        $transactionDateId->settings(['formatFunction' => 'translatedFormat']);
        $transactionDate = $transactionDateId->format('d F Y');

        //badge setting
        //dd($transactionConsultation['consultation_status']);
        switch ($transactionConsultation['consultation_status']) {
            case "soon":
                $consultationDescription = 'Konsultasi anda akan dimulai dalam ' . $diff_date;
                $canGoDetail = 'false';
                $buttonDetail = "Mulai Konsultasi";
                break;
            case "ongoing":
                $canGoDetail = 'true';
                $buttonDetail = "Lanjutkan Konsultasi";
                break;
            case "done":
                $canGoDetail = 'true';
                $buttonDetail = "Lihat Riwayat";
                break;
            case "completed":
                $canGoDetail = 'true';
                $buttonDetail = "Lihat Riwayat";
                break;
            case "canceled":
                $canGoDetail = 'false';
                $consultationDescription = 'Konsultasi telah berhasil dibatalkan';
                break;
            default:
                $canGoDetail = 'false';
                $consultationDescription = 'Konsultasi anda terlewati silahkan hubungi admin untuk info lebih lanjut';
                break;
        }

        $result = [
            'id_transaction' => $transaction['id_transaction'],
            'transaction_date_time' => $transactionDate . " " . date('H:i', strtotime($transaction['created_at'])),
            'transaction_consultation_status' => $transactionConsultation['consultation_status'],
            'id_transaction_consultation' => $transaction['consultation']['id_transaction_consultation'],
            'doctor' => $detailDoctor->getData()->result,
            'user' => $detailUser,
            'schedule_date' => $transaction['consultation']['schedule_date_human_formatted'],
            'schedule_session_time' => $transaction['consultation']['schedule_start_time_formatted'] . " - " . $transaction['consultation']['schedule_end_time_formatted'],
            'schedule_day' => $day,
            'diff_date' => $diff_date,
            'transaction_consultation_chat_url' => $transaction_consultation_chat_url,
            'can_go_detail' => isset($canGoDetail) ? $canGoDetail : null,
            'button_detail' => isset($buttonDetail) ? $buttonDetail : null,
            'consultation_text_description' => isset($consultationDescription) ? $consultationDescription : null,
            'is_rated' => $transactionConsultation->is_rated,
            'rating_value' => $transactionConsultation->rating_value
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
    public function startConsultation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if (!isset($user->id_doctor)) {
            $id = $user->id;
        } else {
            $id = $user->id_doctor;
        }

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }
        $transaction = $transaction->toArray();

        //get Transaction Consulation
        $transactionConsultation = TransactionConsultation::with('doctor')->with('user')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }
        $transactionConsultation = $transactionConsultation->toArray();

        //get Doctor
        $doctor = Doctor::where('id_doctor', $transaction['consultation']['id_doctor'])->first();

        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor tidak ditemukan']
            ]);
        }

        if (env('BYPASS_VALIDASI') != true) {
            //validasi doctor status
//            if (strtolower($doctor['doctor_status']) != "online") {
//                return response()->json([
//                    'status'    => 'fail',
//                    'messages'  => ['Harap Tunggu Hingga Dokter Siap']
//                ]);
//            }

            //validasi consultation status
            if ($transactionConsultation['consultation_status'] != 'soon' && $transactionConsultation['consultation_status'] != 'ongoing') {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Konsultasi Tidak bisa dimulai kembali']
                ]);
            }

            //validasi starts early
            $currentTime = Carbon::now()->format('Y-m-d H:i:s');
            $getSettingEarly = Setting::where('key', 'consultation_starts_early')->first();
            $getSettingLate = Setting::where('key', 'consultation_starts_late')->first();

            if (!empty($getSettingEarly)) {
                $getStartTime = date('Y-m-d H:i:s', strtotime("{$transaction['consultation']['schedule_date']} {$transaction['consultation']['schedule_start_time']} -{$getSettingEarly->value}minutes"));
            } else {
                $getStartTime = date('Y-m-d H:i:s', strtotime("{$transaction['consultation']['schedule_date']} {$transaction['consultation']['schedule_start_time']}"));
            }

            if ($currentTime < $getStartTime) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Anda belum bisa memulai konsultasi, silahkan cek kembali jadwal konsultasi']
                ]);
            }

            if (!empty($getSettingLate) && $transaction['consultation']['consultation_type'] == "scheduled") {
                $getEndTime = date('Y-m-d H:i:s', strtotime("{$transaction['consultation']['schedule_date']} {$transaction['consultation']['schedule_start_time']} +{$getSettingLate->value}minutes"));
            } else {
                $getEndTime = date('Y-m-d H:i:s', strtotime("{$transaction['consultation']['schedule_date']} {$transaction['consultation']['schedule_end_time']}"));
            }

            if ($currentTime > $getEndTime) {
                $updateStatus = $this->checkConsultationMissed($transaction);

                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Anda tidak bisa memulai konsultasi, karena melebihi batas toleransi keterlambatan']
                ]);
            }
        }

        DB::beginTransaction();
        try {
            //for doctor initiator only
            if (isset($user->id_doctor)) {
                //create agent if empty in doctor
                if (!empty($doctor['id_agent'])) {
                    $agentId = $doctor['id_agent'];
                } else {
                    $outputAgent = $this->createAgent($doctor);
                    if ($outputAgent['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputAgent['response']
                        ];
                    }
                    $agentId = $outputAgent['response']['id'];
                    $doctor->update(['id_agent' => $agentId]);
                }

                //create queue if empty in doctor
                if (!empty($doctor['id_queue'])) {
                    $queueId = $doctor['id_queue'];
                } else {
                    $outputQueue = $this->createQueue($doctor);
                    if ($outputQueue['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputQueue['response']
                        ];
                    }
                    $queueId = $outputQueue['response']['id'];
                    $doctor->update(['id_queue' => $queueId]);
                }

                //create conversation
                if (!empty($transaction['consultation']['id_conversation'])) {
                    $conversationId = $transaction['consultation']['id_conversation'];

                    //get conversation
                    $outputConversation = $this->getConversation($conversationId);
                } else {
                    $outputConversation = $this->createConversation($doctor);
                    if ($outputConversation['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputConversation['response']
                        ];
                    }
                    $conversationId = $outputConversation['response']['id'];
                }
            }

            //update transaction consultation
            if ($transactionConsultation['consultation_status'] != 'completed') {
                $consultation = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])
                ->update([
                    'consultation_status' => "ongoing",
                    'consultation_start_at' => new DateTime()
                ]);
            }

            //update doctor statuses
            // $doctor->update(['doctor_status' => "busy"]);
            $doctor->save();

            $result = [
                'transaction_consultation' => $transaction['consultation']
            ];
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Start Consultation Failed',
                'messages' => ['Start Consultation Failed']
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        //Send Autoresponse to Doctor Device
        if (!empty($transactionConsultation['doctor'])) {
            if (!empty($request->header('user-agent-view'))) {
                $useragent = $request->header('user-agent-view');
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }

            if (stristr($useragent, 'iOS')) {
                $useragent = 'iOS';
            }
            if (stristr($useragent, 'okhttp')) {
                $useragent = 'Android';
            }
            if (stristr($useragent, 'GuzzleHttp')) {
                $useragent = 'Browser';
            }

            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Consultation Has Started',
                    $transactionConsultation['doctor']['doctor_phone'],
                    [
                        'action' => 'consultation_has_started',
                        'messages' => 'Consultation Has Started',
                        'id_conversation' => $transactionConsultation['id_conversation'],
                        'id_transaction' => $transactionConsultation['id_transaction'],
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s')
                    ],
                    $useragent,
                    false,
                    false,
                    'doctor'
                );
            }
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    /**
     * Get info from given cart data
     * @param  GetTransaction $request [description]
     * @return View                    [description]
     */
    public function doneConsultation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if (!isset($user->id_doctor)) {
            $id = $request->user()->id;
        } else {
            $id = $request->user()->id_doctor;
        }

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transaction->consultation;

        $transaction = $transaction->toArray();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        if (env('BYPASS_VALIDASI') != true) {
            if ($transactionConsultation['consultation_status'] != 'ongoing') {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Konsultasi tidak bisa ditandai selesai']
                ]);
            }
        }

        $transactionConsultation->load('user', 'doctor');

        $transactionConsultation = $transactionConsultation->toArray();

        //get Doctor
        $doctor = Doctor::where('id_doctor', $transaction['consultation']['id_doctor'])->first();

        if (empty($doctor)) {
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
                'consultation_end_at' => new DateTime()
            ]);

            //insert balance merchant
            $transaction = Transaction::where('id_transaction', $transaction['consultation']['id_transaction'])->first();
            $idMerchant = Merchant::where('id_outlet', $transaction['id_outlet'])->first()['id_merchant'] ?? null;
            $nominal = $transaction['transaction_grandtotal'] + $transaction['discount_charged_central'];
            $dt = [
                'id_merchant' => $idMerchant,
                'id_transaction' => $transaction['id_transaction'],
                'balance_nominal' => $nominal,
                'source' => 'Transaction Consultation Completed'
            ];

            //update conversation to infobip
            $outputUpdateConversation = $this->updateConversationInfobip($transaction);

            //insert saldo to merchant
            $insertSaldo = app('Modules\Merchant\Http\Controllers\ApiMerchantTransactionController')->insertBalanceMerchant($dt);
            if (! $insertSaldo) {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Done Consultation Failed',
                'messages' => ['Done Consultation Failed']
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        //Send Autoresponse to User Device
        if (!empty($transactionConsultation['user'])) {
            if (!empty($request->header('user-agent-view'))) {
                $useragent = $request->header('user-agent-view');
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }

            if (stristr($useragent, 'iOS')) {
                $useragent = 'iOS';
            }
            if (stristr($useragent, 'okhttp')) {
                $useragent = 'Android';
            }
            if (stristr($useragent, 'GuzzleHttp')) {
                $useragent = 'Browser';
            }

            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Consultation Done',
                    $transactionConsultation['user']['phone'],
                    [
                        'action' => 'consultation_done',
                        'messages' => 'Consultation Done',
                        'id_conversation' => $transactionConsultation['id_conversation'],
                        'id_transaction' => $transactionConsultation['id_transaction'],
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s')
                    ],
                    $useragent,
                    false,
                    false
                );
            }
        }

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
    public function completeConsultation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if (!isset($user->id_doctor)) {
            $id = $request->user()->id;
        } else {
            $id = $request->user()->id_doctor;
        }

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        //get consultation
        $transactionConsultation = $transaction->consultation;

        $transaction = $transaction->toArray();

        if (env('BYPASS_VALIDASI') != true) {
            if ($transaction['consultation']['consultation_status'] != 'done' && $transaction['consultation']['consultation_status'] != 'ongoing') {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Konsultasi Tidak bisa ditandai completed']
                ]);
            }
        }

        //get Doctor
        $doctor = Doctor::where('id_doctor', $transaction['consultation']['id_doctor'])->first();

        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor tidak ditemukan']
            ]);
        }

        //if not from done consulttaion, akses doneConsultation first
        if ($transactionConsultation->consultation_status == 'ongoing') {
            // done consultation
            $params = [
                'id_transaction' => $transaction['id_transaction']
            ];

            //macking request objec
            $fake_request = new DoneConsultation();
            $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
            $fake_request->merge(['user' => $doctor]);
            $fake_request->setUserResolver(function () use ($doctor) {
                return $doctor;
            });

            $done = $this->doneConsultation($fake_request);
        }

        DB::beginTransaction();
        try {
            $result = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])
            ->update([
                'consultation_status' => "completed",
                'completed_at' => new DateTime()
            ]);

            //update doctor status
            $getLiveConsultation = TransactionConsultation::where('id_doctor', $doctor->id_doctor)->where('consultation_status', 'ongoing')->orWhere('consultation_status', 'done')->count();
            if ($getLiveConsultation = 0) {
                $doctor->update(['doctor_status' => "online"]);
                $doctor->save();
            }

            //create to user log Rating
            $payloadLogRating = [
                'id_user' => $transactionConsultation->id_user,
                'id_transaction' => $transactionConsultation->id_transaction,
                'id_transaction_consultation' => $transactionConsultation->id_transaction_consultation,
                'id_doctor' => $transactionConsultation->id_doctor,
                'id_outlet' => $transaction['id_outlet']
            ];

            $userRatingLog = UserRatingLog::create($payloadLogRating);
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Completed Consultation Failed',
                'messages' => ['Completed Consultation Failed']
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        //Send Autoresponse to User Device
        if (!empty($transactionConsultation['user'])) {
            if (!empty($request->header('user-agent-view'))) {
                $useragent = $request->header('user-agent-view');
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }

            if (stristr($useragent, 'iOS')) {
                $useragent = 'iOS';
            }
            if (stristr($useragent, 'okhttp')) {
                $useragent = 'Android';
            }
            if (stristr($useragent, 'GuzzleHttp')) {
                $useragent = 'Browser';
            }

            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Consultation Completed',
                    $transactionConsultation['user']['phone'],
                    [
                        'action' => 'consultation_completed',
                        'messages' => 'Consultation Completed',
                        'id_conversation' => $transactionConsultation['id_conversation'],
                        'id_transaction' => $transactionConsultation['id_transaction'],
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s')
                    ],
                    $useragent,
                    false,
                    false
                );
            }
        }

        $consultation = TransactionConsultation::where('id_transaction', $transaction['consultation']['id_transaction'])->first();
        $result = [
            'id_transaction' => $consultation->id_transaction,
            'id_transaction_consultation' => $consultation->id_transaction_consultation,
            'transaction_consultation_status' => $consultation->consultation_status,
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
    public function getHistoryConsultationList(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $transaction = Transaction::with('consultation')->where('id_user', $id);

        if (!empty($post['filter_date_start']) && !empty($post['filter_date_end'])) {
            $transaction = $transaction->whereHas('consultation', function ($query) use ($post) {
                $query->whereIn('consultation_status', ['done', 'completed', 'missed'])
                    ->whereDate('schedule_date', '>=', date('Y-m-d', strtotime($post['filter_date_start'])))
                    ->whereDate('schedule_date', '<=', date('Y-m-d', strtotime($post['filter_date_end'])));
            });
        } else {
            $transaction = $transaction->whereHas('consultation', function ($query) {
                $query->whereIn('consultation_status', ['done', 'completed', 'missed']);
            });
        }

        $transaction = $transaction->latest()->get();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['History transaksi konsultasi tidak ditemukan']
            ]);
        }

        //$transaction = $transaction->toArray();

        $result = array();
        foreach ($transaction as $key => $value) {
            $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $value['consultation']['id_doctor'])->first();

            $transactionConsultation = $value->consultation;

            //badge setting
            switch ($transactionConsultation['consultation_status']) {
                case "soon":
                    $badgeText = $diff_date;
                    $badgeColor = '#E6E6E6';
                    $badgeTextColor = '#000000';
                    break;
                case "ongoing":
                    $badgeText = 'Sedang Berjalan';
                    $badgeColor = '#9c1f60';
                    $badgeTextColor = '#eeeeee';
                    break;
                case "done":
                    $badgeText = 'Menunggu Hasil';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "completed":
                    $badgeText = 'Selesai';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "canceled":
                    $badgeText = 'Dibatalkan';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
                default:
                    $badgeText = 'Terlewati';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
            }

            $result[$key]['id_transaction'] = $value['id_transaction'] ?? null;
            $result[$key]['doctor_name'] = $doctor['doctor_name'] ?? null;
            $result[$key]['doctor_photo'] = $doctor['url_doctor_photo'] ?? null;
            $result[$key]['outlet'] = $doctor['outlet'] ?? null;
            $result[$key]['specialists'] = $doctor['specialists'] ?? null;
            $result[$key]['schedule_date'] = $value['consultation']['schedule_date_human_short_formatted'] ?? null;
            $result[$key]['consultation_status'] = $value['consultation']['consultation_status'] ?? null;
            $result[$key]['badge_text'] = $badgeText;
            $result[$key]['badge_color'] = $badgeColor;
            $result[$key]['badge_text_color'] = $badgeTextColor;
            $result[$key]['consultation_time'] = date('H:i', strtotime($value['consultation']['schedule_start_time'])) . ' - ' . date('H:i', strtotime($value['consultation']['schedule_end_time']));
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
    public function getHandledConsultation(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id'])) {
            $id = $request->user()->id_doctor;
        } else {
            $id = $post['id_doctor'];
        }

        if (!isset($post['consultation_status'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Status konsultasi tidak bisa kosong']
            ]);
        }

        $transaction = Transaction::with('consultation')->where('transaction_payment_status', 'Completed');
        if ($post['consultation_status'] == "soon" || $post['consultation_status'] == "ongoing") {
            $transaction = $transaction->whereHas('consultation', function ($query) use ($post, $id) {
                $query->where('id_doctor', $id)->where('consultation_status', $post['consultation_status']);
            });
        } else {
            $transaction = $transaction->whereHas('consultation', function ($query) use ($post, $id) {
                $query->where('id_doctor', $id)->whereIn('consultation_status', ['missed','done','completed']);
            });
        }

        $transaction = $transaction->latest()->get();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['History transaksi konsultasi tidak ditemukan']
            ]);
        }

        //$transaction = $transaction->toArray();

        $result = array();
        foreach ($transaction as $key => $value) {
            $user = User::where('id', $value['consultation']['id_user'])->first()->toArray();

            $transactionConsultation = $value->consultation;

            //get diff datetime
            $now = new DateTime();
            $schedule_date_start_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_start_time'];
            $schedule_date_start_time = new DateTime($schedule_date_start_time);
            $schedule_date_end_time = $value['consultation']['schedule_date'] . ' ' . $value['consultation']['schedule_end_time'];
            $schedule_date_end_time = new DateTime($schedule_date_end_time);
            $diff_date = null;

            //logic schedule diff date
            if ($schedule_date_start_time > $now && $schedule_date_end_time > $now) {
                $diff = $now->diff($schedule_date_start_time);
                if ($diff->d == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%h jam, %i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%i mnt");
                } elseif ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    $diff_date = $now->diff($schedule_date_start_time)->format("sebentar lagi");
                } else {
                    $diff_date = $now->diff($schedule_date_start_time)->format("%d hr %h jam");
                }
            } elseif ($schedule_date_start_time < $now && $schedule_date_end_time > $now) {
                $diff_date = "now";
            } elseif ($value['consultation']['consultation_status'] == 'done' || $value['consultation']['consultation_status'] == 'completed') {
                $diff_date = "completed";
            } else {
                $diff_date = "missed";
            }

            //badge setting
            switch ($transactionConsultation['consultation_status']) {
                case "soon":
                    $badgeText = $diff_date;
                    $badgeColor = '#E6E6E6';
                    $badgeTextColor = '#000000';
                    break;
                case "ongoing":
                    $badgeText = 'Sedang Berjalan';
                    $badgeColor = '#9c1f60';
                    $badgeTextColor = '#eeeeee';
                    break;
                case "done":
                    $badgeText = 'Menunggu Hasil';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "completed":
                    $badgeText = 'Selesai';
                    $badgeColor = '#CECECE';
                    $badgeTextColor = '#000000';
                    break;
                case "canceled":
                    $badgeText = 'Dibatalkan';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
                default:
                    $badgeText = 'Terlewati';
                    $badgeColor = '#F6AE2D';
                    $badgeTextColor = '#000000';
                    break;
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
            $result[$key]['badge_text'] = $badgeText;
            $result[$key]['badge_color'] = $badgeColor;
            $result[$key]['badge_text_color'] = $badgeTextColor;
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
        if (isset($user->id_doctor)) {
            $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();
        } else {
            $transactionConsultation = TransactionConsultation::where('id_user', $user->id)->where('id_transaction', $post['id_transaction'])->first();
        }

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        // $diseaseComplaints = !empty($transactionConsultation['disease_complaint']) ? explode(', ', $transactionConsultation['disease_complaint']) : null;
        // $diseaseAnalysis = !empty($transactionConsultation['disease_analysis']) ? explode(', ', $transactionConsultation['disease_analysis']) : null;

        $diseaseComplaints = !empty($transactionConsultation['disease_complaint']) ? json_decode($transactionConsultation['disease_complaint']) : null;
        $diseaseAnalysis = !empty($transactionConsultation['disease_analysis']) ? json_decode($transactionConsultation['disease_analysis']) : null;

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

        if (empty($post['disease_complaint'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Keluhan Pasien Harus Diisi']
            ]);
        }

        if (empty($post['disease_analysis'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Keluhan Pasien Harus Diisi']
            ]);
        }

        if (empty($post['treatment_recomendation'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Anjuran Penanganan Harus Diisi']
            ]);
        }

        $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        if ($transactionConsultation['consultation_status'] == 'completed') {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Anda Tidak Bisa Merubah Data, Transaksi Sudah Ditandai Selesai']
            ]);
        }

        // $diseaseComplaint = implode(", ",$post['disease_complaint']);
        // $diseaseAnalysis = implode(", ",$post['disease_analysis']);

        $diseaseComplaint = json_encode($post['disease_complaint']);
        $diseaseAnalysis = json_encode($post['disease_analysis']);

        DB::beginTransaction();
        try {
            $result = TransactionConsultation::where('id_transaction', $post['id_transaction'])
            ->update([
                'disease_complaint' => $diseaseComplaint,
                'disease_analysis' => $diseaseAnalysis,
                'treatment_recomendation' => $post['treatment_recomendation']
            ]);
        } catch (\Exception $e) {
            \Log::debug($e);
            $result = [
                'status'  => 'fail',
                'message' => 'Update disease and treatement failed',
                'messages' => ['Update disease and treatement failed']
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
        if (isset($user->id_doctor)) {
            $id = $user->id_doctor;
            $transactionConsultation = TransactionConsultation::where('id_doctor', $id)->where('id_transaction', $post['id_transaction'])->first();
        } else {
            $id = $user->id;
            $transactionConsultation = TransactionConsultation::where('id_user', $id)->where('id_transaction', $post['id_transaction'])->first();
        }

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        //get recomendation
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyProduct()->get();

        $items = [];
        if (!empty($recomendations)) {
            foreach ($recomendations as $key => $recomendation) {
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
                    'id_product_variant_group' => $recomendation->id_product_variant_group
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
                $items[$key]['usage_rules_additional_time'] = $recomendation->usage_rules_additional_time ?? null;
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
        if (isset($user->id_doctor)) {
            $id = $user->id_doctor;
            $transactionConsultation = TransactionConsultation::where('id_doctor', $id)->where('id_transaction', $post['id_transaction'])->first();
        } else {
            $id = $user->id;
            $transactionConsultation = TransactionConsultation::where('id_user', $id)->where('id_transaction', $post['id_transaction'])->first();
        }

        $transaction = Transaction::with('outlet')->where('id_transaction', $transactionConsultation['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi konsultasi tidak ditemukan']
            ]);
        }

        if (!empty($transactionConsultation['referral_code'])) {
            $outlet = Outlet::where('outlet_referral_code', $transactionConsultation['referral_code'])->first();
            $outlet_referral_code = $transactionConsultation['referral_code'];
            $outlet = [
                "outlet_name" => $outlet['outlet_name'],
                "outlet_address" => $outlet['outlet_full_address'],
                "outlet_referral_code" => '#' . $outlet_referral_code
            ];
        } else {
            $outlet_referral_code = !empty($transaction->outlet->outlet_referral_code) ? $transaction->outlet->outlet_referral_code : $transaction->outlet->outlet_code;
            $outlet = [
                "outlet_name" => $transaction->outlet->outlet_name,
                "outlet_address" => $transaction->outlet->OutletFullAddress,
                "outlet_referral_code" => '#' . $outlet_referral_code
            ];
        }

        //get recomendation
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();

        $items = [];
        if (!empty($recomendations)) {
            foreach ($recomendations as $key => $recomendation) {
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' => $recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                //decode and implode usage rules time
                $json = json_decode($recomendation->usage_rules_time);
                $usageRules = null;
                if (!empty($json)) {
                    $usageRules = implode(", ", $json);
                }

                $items[$key]['product'] = $detailProduct ?? null;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rules'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rules_time'] = $usageRules ?? null;
                $items[$key]['usage_rules_additional_time'] = $recomendation->usage_rules_additional_time ?? null;
                $items[$key]['treatment_description'] = $recomendation->treatment_description ?? null;
            }
        }

        $result = [
            'id_transaction_consultation' => $transactionConsultation['id_transaction_consultation'],
            'outlet' => $outlet,
            'items' => $items,
            'remaining_recipe_redemption' =>  ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter),
            'total_redemption_limit' => $transactionConsultation->recipe_redemption_limit,
            'recipe_redemption' => $transactionConsultation->recipe_redemption_counter,
            'medical_prescription_url' => url("api/consultation/detail/drug-recomendation/$transactionConsultation[id_transaction_consultation]/medical-prescription.pdf"),
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
        $transactionConsultation = TransactionConsultation::with('doctor')->where('id_transaction_consultation', $post['id_transaction_consultation'])->first();
        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }
        $transactionConsultation = $transactionConsultation->toArray();

        //get Doctor
        $doctor = Doctor::with('specialists')->where('id_doctor', $transactionConsultation['id_doctor'])->first();
        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor Not Found']
            ]);
        }
        $doctor = $doctor->toArray();

        //get User
        $user = User::where('id', $transactionConsultation['id_user'])->first();
        if (empty($user)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }
        $user = $user->toArray();

        $date = Carbon::parse($user['birthday']);
        $now = Carbon::now();
        $user['age'] = $date->diffInYears($now);
        $age = [];

        if (!empty($user['gender'])) {
            $gender = ($user['gender'] == 'Female' ? 'Wanita' : 'Pria');
            $gender = (empty($user['age']) ? 'Jenis Kelamin:' . $gender : 'Usia: ' . $gender);
            $age[] = $gender;
        }

        if (!empty($user['age'])) {
            $age[] = (empty($user['gender']) ? 'Usia: ' . $user['age'] . ' Tahun' : $user['age'] . ' Tahun');
        }

        if (empty($age)) {
            $age[] = 'Umur: -';
        }
        $dataAge = implode(', ', $age);

        //get Transaction
        $transaction = Transaction::where('id_transaction', $transactionConsultation['id_transaction'])->first();
        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Not Found']
            ]);
        }
        $transaction = $transaction->toArray();

        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();
        if (empty($recomendations)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Receipt Not Found']
            ]);
        }


        $items = [];
        if (!empty($recomendations)) {
            foreach ($recomendations as $key => $recomendation) {
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' => $recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                //decode and implode usage rules time
                $json = json_decode($recomendation->usage_rules_time);
                $usageRules = null;
                if (!empty($json)) {
                    $usageRules = implode(", ", $json);
                }

                $items[$key]['product_name'] = $detailProduct['result']['product_name'] ?? null;
                $variantsName = null;
                if (!empty($recomendation['id_product_variant_group'])) {
                    $variants = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')
                        ->where('product_variant_visibility', 'Visible')->where('id_product_variant_group', $recomendation['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                    $variantsName = implode(', ', $variants);
                }
                $items[$key]['variant_name'] = $variantsName;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rule'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rule_time'] = $usageRules ?? null;
                $items[$key]['usage_rule_additional_time'] = $recomendation->usage_rules_additional_time ?? null;
                $items[$key]['treatment_description'] = $recomendation->treatment_description ?? '-';
            }
        }

        //setting template
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . '/download/template_receipt.docx');
        $templateProcessor->setValue('doctor_name', $doctor['doctor_name']);
        $templateProcessor->setValue('doctor_specialist_name', $doctor['specialists'][0]['doctor_specialist_name']);
        $templateProcessor->setValue('doctor_practice_lisence_number', $doctor['registration_certificate_number']);
        $templateProcessor->setValue('transaction_date', MyHelper::dateOnlyFormatInd($transaction['transaction_date']));
        $templateProcessor->setValue('transaction_recipe_code', $transactionConsultation['recipe_code']);
        $templateProcessor->cloneBlock('block_items', 0, true, false, $items);
        $templateProcessor->setValue('customer_name', $user['name']);
        $templateProcessor->setValue('age', $dataAge);

        if (!Storage::exists('receipt/docx')) {
            Storage::makeDirectory('receipt/docx');
        }

        $directory = storage_path('app/public/receipt/docx/receipt_' . $transactionConsultation['recipe_code'] . '.docx');
        $templateProcessor->saveAs($directory);

        if (!Storage::exists('receipt/pdf')) {
            Storage::makeDirectory('receipt/pdf');
        }

        $converter = new CustomOfficeConverter($directory, storage_path('app/public/receipt/pdf'), env('LIBREOFFICE_URL'), true);
        $output = $converter->convertTo('receipt_' . $transactionConsultation['recipe_code'] . '.pdf');

        return response()->download($output);
    }

    public function downloadDrugRecomendationById(Request $request, TransactionConsultation $consultation)
    {
        $post = $request->json()->all();

        $id = $consultation->id_user;

        //get Transaction Consultation Data
        $consultation->load('doctor');
        $transactionConsultation = $consultation;
        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }
        $transactionConsultation = $transactionConsultation->toArray();

        //get Doctor
        $doctor = Doctor::with('specialists')->where('id_doctor', $transactionConsultation['id_doctor'])->first();
        if (empty($doctor)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Doctor Not Found']
            ]);
        }
        $doctor = $doctor->toArray();

        //get User
        $user = User::where('id', $transactionConsultation['id_user'])->first();
        if (empty($user)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }
        $user = $user->toArray();

        $date = Carbon::parse($user['birthday']);
        $now = Carbon::now();
        $user['age'] = $date->diffInYears($now);
        $age = [];

        if (!empty($user['gender'])) {
            $gender = ($user['gender'] == 'Female' ? 'Wanita' : 'Pria');
            $gender = (empty($user['age']) ? 'Jenis Kelamin:' . $gender : 'Usia: ' . $gender);
            $age[] = $gender;
        }

        if (!empty($user['age'])) {
            $age[] = (empty($user['gender']) ? 'Usia: ' . $user['age'] . ' Tahun' : $user['age'] . ' Tahun');
        }

        if (empty($age)) {
            $age[] = 'Umur: -';
        }
        $dataAge = implode(', ', $age);

        //get Transaction
        $transaction = Transaction::where('id_transaction', $transactionConsultation['id_transaction'])->first();
        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Not Found']
            ]);
        }
        $transaction = $transaction->toArray();

        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();
        if (empty($recomendations)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Receipt Not Found']
            ]);
        }


        $items = [];
        if (!empty($recomendations)) {
            foreach ($recomendations as $key => $recomendation) {
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' => $recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                //decode and implode usage rules time
                $json = json_decode($recomendation->usage_rules_time);
                $usageRules = null;
                if (!empty($json)) {
                    $usageRules = implode(", ", $json);
                }

                $items[$key]['product_name'] = $detailProduct['result']['product_name'] ?? null;
                $variantsName = null;
                if (!empty($recomendation['id_product_variant_group'])) {
                    $variants = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')
                        ->where('product_variant_visibility', 'Visible')->where('id_product_variant_group', $recomendation['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                    $variantsName = implode(', ', $variants);
                }
                $items[$key]['variant_name'] = $variantsName;
                $items[$key]['qty'] = $recomendation->qty_product ?? null;
                $items[$key]['usage_rule'] = $recomendation->usage_rules ?? null;
                $items[$key]['usage_rule_time'] = $usageRules ?? null;
                $items[$key]['usage_rule_additional_time'] = $recomendation->usage_rules_additional_time ?? null;
                $items[$key]['treatment_description'] = (empty($recomendation->treatment_description) ? '-' : $recomendation->treatment_description);
            }
        }

        //setting template
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(public_path() . '/download/template_receipt.docx');
        $templateProcessor->setValue('doctor_name', $doctor['doctor_name']);
        $templateProcessor->setValue('doctor_specialist_name', $doctor['specialists'][0]['doctor_specialist_name']);
        $templateProcessor->setValue('doctor_practice_lisence_number', $doctor['registration_certificate_number']);
        $templateProcessor->setValue('transaction_date', MyHelper::dateOnlyFormatInd($transaction['transaction_date']));
        $templateProcessor->setValue('transaction_recipe_code', $transactionConsultation['recipe_code']);
        $templateProcessor->cloneBlock('block_items', 0, true, false, $items);
        $templateProcessor->setValue('customer_name', $user['name']);
        $templateProcessor->setValue('age', $dataAge);

        if (!Storage::disk('public')->exists('receipt/docx')) {
            Storage::disk('public')->makeDirectory('receipt/docx');
        }

        $directory = storage_path('app/public/receipt/docx/receipt_' . $transactionConsultation['recipe_code'] . '.docx');
        $templateProcessor->saveAs($directory);

        if (!Storage::disk('public')->exists('receipt/pdf')) {
            Storage::disk('public')->makeDirectory('receipt/pdf');
        }

        $converter = new CustomOfficeConverter($directory, storage_path('app/public/receipt/pdf'), env('LIBREOFFICE_URL'), true);
        $output = $converter->convertTo('receipt_' . $transactionConsultation['recipe_code'] . '.pdf');

        return response()->download($output, 'receipt_' . $transactionConsultation['recipe_code'] . '.pdf');
    }

    public function updateRecomendation(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        //get Consultation
        $transactionConsultation = TransactionConsultation::where('id_doctor', $user->id_doctor)->where('id_transaction', $post['id_transaction'])->first();
        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction Consultation Not Found']
            ]);
        }

        //get Transaction
        $transaction = Transaction::where('id_transaction', $transactionConsultation['id_transaction'])->first();

        if (env('BYPASS_VALIDASI') != true) {
            if ($transactionConsultation['consultation_status'] == 'completed') {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Konsultasi Sudah Tertandai Completed, Tidak Bisa Mengubah Rekomendasi Lagi']
                ]);
            }
        }

        //merge product
        $post['items'] = $this->mergeProducts($post['items']);

        foreach ($post['items'] as $key => $item) {
            $post['items'][$key]['product_type'] = $post['type'];
            $post['items'][$key]['qty_product_counter'] = $post['items'][$key]['qty_product'];

            if ($post['type'] == 'drug') {
                $post['items'][$key]['usage_rules_time'] = json_encode($post['items'][$key]['usage_rules_time']);
            }
        }

        if ($post['type'] == "drug") {
            //generate recipe code
            $padOutlet = str_pad($transaction['id_outlet'], 3, '0', STR_PAD_LEFT);
            $padTransaction = str_pad($transactionConsultation['id_transaction'], 4, '0', STR_PAD_LEFT);
            $recipeCode = 'KNSL.' . $padOutlet . '-' . $padTransaction;

            $transactionConsultation->update(['recipe_code' => $recipeCode, 'recipe_redemption_limit' => $post['recipe_redemption_limit']]);
        }

        DB::beginTransaction();
        try {
            //drop old recomendation
            $oldRecomendation = TransactionConsultationRecomendation::where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->where('product_type', $post['type'])->delete();
            $items = $transactionConsultation->recomendation()->createMany($post['items']);
        } catch (\Exception $e) {
            \Log::debug($e);
            $result = [
                'status'  => 'fail',
                'message' => 'Gagal update Rekomendasi',
                'messages' => ['Gagal update Rekomendasi']
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();

        $result = $items;

        //product recomendation drug type
        if ($post['type'] == "drug") {
            $result = [
                'recipe_code' => $transactionConsultation['recipe_code'],
                'recipe_redemption_limit' => $transactionConsultation['recipe_redemption_limit'],
                'items' => $items,
            ];
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getConsultationFromAdmin(Request $request)
    {
        $post = $request->json()->all();
        //get Transaction
        $transactions = Transaction::where('trasaction_type', 'Consultation')->with('outlet');

        if ($post['rule']) {
            $countTotal = $transactions->count();
            $this->filterList($transactions, $post['rule'], $post['operator'] ?: 'and');
        }

        if ($request['page']) {
            $result = $transactions->latest()->paginate($post['length'] ?: 10);

            foreach ($result as $key => $transaction) {
                $transactionConsultation = TransactionConsultation::with('doctor')->with('user')->where('id_transaction', $transaction['id_transaction'])->first();

                $result[$key]['consultation'] = $transactionConsultation;
            }
        } else {
            $result = $transactions->latest()->get()->toArray();

            foreach ($result as $key => $transaction) {
                $transactionConsultation = TransactionConsultation::with('doctor')->with('user')->where('id_transaction', $transaction['id_transaction'])->first();

                $result[$key]['consultation'] = $transactionConsultation;
            }
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function filterList($query, $rules, $operator = 'and')
    {
        $newRule = [];
        foreach ($rules as $var) {
            $rule = [$var['operator'] ?? '=',$var['parameter']];
            if ($rule[0] == 'like') {
                $rule[1] = '%' . $rule[1] . '%';
            }
            $newRule[$var['subject']][] = $rule;
        }

        $where = $operator == 'and' ? 'where' : 'orWhere';
        $subjects = ['transaction_receipt_number'];
        foreach ($subjects as $subject) {
            if ($rules2 = $newRule[$subject] ?? false) {
                foreach ($rules2 as $rule) {
                    $query->$where($subject, $rule[0], $rule[1]);
                }
            }
        }

        $subjects2 = ['consultation_status', 'consultation_type'];
        foreach ($subjects2 as $subject) {
            if ($rules2 = $newRule[$subject] ?? false) {
                foreach ($rules2 as $rule) {
                    $query->{$where . 'Has'}('consultation', function ($query2) use ($rule, $where, $subject) {
                        $query2->$where($subject, $rule[0], $rule[1]);
                    });
                }
            }
        }

        if ($rules2 = $newRule['outlet'] ?? false) {
            foreach ($rules2 as $rule) {
                $query->{$where . 'Has'}('outlet', function ($query2) use ($rule) {
                    $query2->where('outlet_name', $rule[0], $rule[1]);
                });
            }
        }
    }

    public function getConsultationDetailFromAdmin($id)
    {
        //get Transaction detail
        $transaction = Transaction::where('id_transaction', $id)->where('trasaction_type', 'Consultation')->first();

        if (empty($transaction)) {
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        //get consultation
        $consultation = $transaction->consultation;

        $consultation->disease_complaint = json_decode($consultation->disease_complaint);
        $consultation->disease_analysis = json_decode($consultation->disease_analysis);

        if (empty($consultation)) {
            return response()->json(['status' => 'fail', 'messages' => ['Consultation not found']]);
        }

        //get doctor
        $doctor = $consultation->doctor;

        if (empty($doctor)) {
            return response()->json(['status' => 'fail', 'messages' => ['Doctor not found']]);
        }

        //get doctor Schedule
        $schedule = app($this->doctor)->getAvailableScheduleDoctor($doctor['id_doctor']);
        $selectedScheduleTime = null;

        //get selected Schedule time
        $scheduleDateFormatted = date('d-m-Y', strtotime($consultation['schedule_date']));
        $idDoctorSchedule = null;
        if (!empty($schedule)) {
            foreach ($schedule as $s) {
                if ($s['date'] == $scheduleDateFormatted) {
                    $idDoctorSchedule = $s['id_doctor_schedule'];
                }
            }
            $selectedScheduleTime = app($this->doctor)->getScheduleTime($idDoctorSchedule);
        }

        //get user
        $user = $transaction->user;

        if (empty($user)) {
            return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
        }

        //get recomendation product
        $recomendationProducts = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "product")->get();

        $itemsRecomendationProduct = [];
        if (!empty($recomendationProducts)) {
            foreach ($recomendationProducts as $key => $product) {
                $params = [
                    'id_product' => $product->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' => $product->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                $itemsRecomendationProduct[$key]['product'] = $detailProduct['result'] ?? null;
                $itemsRecomendationProduct[$key]['qty'] = $product->qty_product ?? null;
                $itemsRecomendationProduct[$key]['usage_rules'] = $product->usage_rules ?? null;
                $itemsRecomendationProduct[$key]['usage_rules_time'] = $product->usage_rules_time ?? null;
                $itemsRecomendationProduct[$key]['usage_rules_additional_time'] = $product->usage_rules_additional_time ?? null;
                $itemsRecomendationProduct[$key]['treatment_description'] = $product->treatment_description ?? null;
            }
        }

        //get recomendation drug
        $recomendationDrugs = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $consultation->id_transaction_consultation)->where('product_type', "drug")->get();

        $itemsRecomendationDrug = [];
        if (!empty($recomendationDrugs)) {
            foreach ($recomendationDrugs as $key => $drug) {
                $params = [
                    'id_product' => $drug->id_product,
                    'id_user' => $id,
                    'id_product_variant_group' => $drug->id_product_variant_group
                ];

                $detailDrug = app($this->product)->detailRecomendation($params);

                $itemsRecomendationDrug[$key]['product'] = $detailDrug['result'] ?? null;
                $itemsRecomendationDrug[$key]['qty'] = $drug->qty_product ?? null;
                $itemsRecomendationDrug[$key]['usage_rules'] = $drug->usage_rules ?? null;
                $itemsRecomendationDrug[$key]['usage_rules_time'] = $drug->usage_rules_time ?? null;
                $itemsRecomendationDrug[$key]['usage_rules_additional_time'] = $drug->usage_rules_additional_time ?? null;
                $itemsRecomendationDrug[$key]['treatment_description'] = $drug->treatment_description ?? null;
            }
        }

        //get messages
        $messages = TransactionConsultationMessage::where('id_transaction_consultation', $transaction->consultation->id_transaction_consultation)->get()->toArray();

        $modifier_user = auth()->user();

        $result = [
            'transaction' => $transaction,
            'consultation' => $consultation,
            'doctor' => $doctor,
            'schedule' => $schedule,
            'selected_schedule_time' => $selectedScheduleTime,
            'customer' => $user,
            'recomendation_product' => $itemsRecomendationProduct,
            'recomendation_drug' => $itemsRecomendationDrug,
            'messages' => $messages,
            'modifier_user' => $modifier_user
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getProductList(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        //get default outlet
        $idOutlet = Outlet::where('id_outlet', $user->id_outlet)->first()['id_outlet'] ?? null;

        $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant'] ?? null;

        if (empty($idOutlet)) {
            return response()->json(['status' => 'fail', 'messages' => ['Doctor Outlet Not Found']]);
        }

        $consultation = TransactionConsultation::where('id_transaction', $post['id_transaction'] ?? null)->first();
        $post['referal_code'] = $consultation['referral_code'] ?? null;

        //if referral code outlet not empty
        if (!empty($post['referal_code'])) {
            $idOutlet = Outlet::where('outlet_referral_code', $post['referal_code'])->first()['id_outlet'] ?? null;

            if (empty($idOutlet)) {
                $idOutlet = Outlet::where('outlet_code', $post['referal_code'])->first()['id_outlet'] ?? null;
            }

            if (empty($idOutlet)) {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet with referral not found']]);
            }

            $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant'] ?? null;
        }

        $list = Product::select(
            'products.id_product',
            'products.product_name',
            'products.product_code',
            'products.product_description',
            'product_variant_status',
            'product_global_price as product_price',
            'product_detail_stock_status as stock_status',
            'product_detail.id_outlet'
        )
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_is_closed', 0)
            ->where('need_recipe_status', 0)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->where('product_detail_stock_status', 'Available')
            ->groupBy('products.id_product');

        if (!empty($idMerchant)) {
            $list = $list->where('id_merchant', $idMerchant);
        }

        if (!empty($post['search_key'])) {
            $list = $list->where('product_name', 'like', '%' . $post['search_key'] . '%');
        }

        if (!empty($post['id_product_category'])) {
            $list = $list->where('id_product_category', $post['id_product_category']);
        }

        if (!empty($post['sort_name'])) {
            $list = $list->orderBy('product_name', $post['sort_name']);
        }

        if (!empty($post['sort_price'])) {
            $list = $list->orderBy('product_price', $post['sort_price']);
        }

        $list->orderBy('product_count_transaction', 'desc');

        if (!empty($post['pagination'])) {
            $list = $list->paginate($post['pagination_total_row'] ?? 10)->toArray();

            foreach ($list['data'] as $key => $product) {
                //get variant
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list['data'][$key]['stock_status'] = 'Sold Out';
                    }
                    $list['data'][$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    //TO DO cek
                    $list['data'][$key]['variants'] = $variantTree ?? null;
                }

                //get merchant name
                $merchant = Merchant::where('id_outlet', $product['id_outlet'])->first();
                $list['data'][$key]['merchant_pic_name'] = $merchant->merchant_pic_name;

                //get ratings product
                $list['data'][$key]['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);

                unset($list['data'][$key]['id_outlet']);
                unset($list['data'][$key]['product_variant_status']);
                $list['data'][$key]['product_price'] = (int)$list['data'][$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list['data'][$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            }
            $list['data'] = array_values($list['data']);
        } else {
            $list = $list->get()->toArray();

            foreach ($list as $key => $product) {
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list[$key]['stock_status'] = 'Sold Out';
                    }
                    $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    //TO DO cek
                    $list[$key]['variants'] = $variantTree ?? null;
                }

                $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                //get merchant name
                $merchant = Merchant::where('id_outlet', $product['id_outlet'])->first();
                $list[$key]['merchant_pic_name'] = $merchant->merchant_pic_name;
                $list[$key]['outlet_name'] = $outlet->outlet_name;

                //get ratings product
                $list[$key]['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);

                unset($list[$key]['product_variant_status']);
                $list[$key]['product_price'] = (int)$list[$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            }

            $list = array_values($list);
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function getDrugList(Request $request)
    {
        $post = $request->json()->all();

        $user = $request->user();

        //get default outlet
        $idOutlet = Outlet::where('id_outlet', $user->id_outlet)->first()['id_outlet'] ?? null;

        $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant'] ?? null;

        if (empty($idOutlet)) {
            return response()->json(['status' => 'fail', 'messages' => ['Doctor Outlet Not Found']]);
        }

        $consultation = TransactionConsultation::where('id_transaction', $post['id_transaction'] ?? null)->first();
        $post['referal_code'] = $consultation['referral_code'] ?? null;

        //if referral code outlet not empty
        if (!empty($post['referal_code'])) {
            $idOutlet = Outlet::where('outlet_referral_code', $post['referal_code'])->first()['id_outlet'] ?? null;

            if (empty($idOutlet)) {
                $idOutlet = Outlet::where('outlet_code', $post['referal_code'])->first()['id_outlet'] ?? null;
            }

            if (empty($idOutlet)) {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }

            $idMerchant = Merchant::where('id_outlet', $idOutlet)->first()['id_merchant'] ?? null;
            if (empty($idMerchant)) {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }
        }

        $list = Product::select(
            'products.id_product',
            'products.product_name',
            'products.product_code',
            'products.product_description',
            'product_variant_status',
            'product_global_price as product_price',
            'product_detail_stock_status as stock_status',
            'product_detail.id_outlet'
        )
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->leftJoin('outlets', 'outlets.id_outlet', 'product_detail.id_outlet')
            ->where('outlet_is_closed', 0)
            ->where('need_recipe_status', 1)
            ->where('product_global_price', '>', 0)
            ->where('product_visibility', 'Visible')
            ->where('product_detail_visibility', 'Visible')
            ->where('product_detail_stock_status', 'Available')
            ->groupBy('products.id_product');

        if (!empty($idMerchant)) {
            $list = $list->where('id_merchant', $idMerchant);
        }

        if (!empty($post['search_key'])) {
            $list = $list->where('product_name', 'like', '%' . $post['search_key'] . '%');
        }

        if (!empty($post['id_product_category'])) {
            $list = $list->where('id_product_category', $post['id_product_category']);
        }

        if (!empty($post['sort_name'])) {
            $list = $list->orderBy('product_name', $post['sort_name']);
        }

        if (!empty($post['sort_price'])) {
            $list = $list->orderBy('product_price', $post['sort_price']);
        }

        $list->orderBy('product_count_transaction', 'desc');

        if (!empty($post['pagination'])) {
            $list = $list->paginate($post['pagination_total_row'] ?? 10)->toArray();

            foreach ($list['data'] as $key => $product) {
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list['data'][$key]['stock_status'] = 'Sold Out';
                    }
                    $list['data'][$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    //TO DO cek
                    $list['data'][$key]['variants'] = $variantTree ?? null;
                }

                //get merchant name
                $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                $merchant = Merchant::where('id_outlet', $product['id_outlet'])->first();
                $list['data'][$key]['merchant_pic_name'] = $merchant->merchant_pic_name;
                $list['data'][$key]['outlet_name'] = $outlet->outlet_name;

                //get ratings product
                $list['data'][$key]['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);

                unset($list['data'][$key]['product_variant_status']);
                $list['data'][$key]['product_price'] = (int)$list['data'][$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list['data'][$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            }
            $list['data'] = array_values($list['data']);
        } else {
            $list = $list->get()->toArray();

            foreach ($list as $key => $product) {
                if ($product['product_variant_status']) {
                    $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                    $variantTree = Product::getVariantTree($product['id_product'], $outlet);
                    if (empty($variantTree['base_price'])) {
                        $list[$key]['stock_status'] = 'Sold Out';
                    }
                    $list[$key]['product_price'] = ($variantTree['base_price'] ?? false) ?: $product['product_price'];
                    //TO DO cek
                    $list[$key]['variants'] = $variantTree ?? null;
                }

                //get merchant name
                $outlet = Outlet::where('id_outlet', $product['id_outlet'])->first();
                $merchant = Merchant::where('id_outlet', $product['id_outlet'])->first();
                $list[$key]['merchant_pic_name'] = $merchant->merchant_pic_name;
                $list[$key]['outlet_name'] = $outlet->outlet_name;

                //get ratings product
                $list[$key]['total_rating'] = round(UserRating::where('id_product', $product['id_product'])->average('rating_value') ?? 0, 1);

                unset($list[$key]['product_variant_status']);
                $list[$key]['product_price'] = (int)$list[$key]['product_price'];
                $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first();
                $list[$key]['image'] = (!empty($image['product_photo']) ? config('url.storage_url_api') . $image['product_photo'] : config('url.storage_url_api') . 'img/default.jpg');
            }
            $list = array_values($list);
        }

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
            return MyHelper::checkGet([], 'Transaction not found');
        }

        if ($trx->transaction_payment_status != 'Pending') {
            return MyHelper::checkGet([], 'Transaction cannot be canceled');
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
                if (
                    (($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? $midtransStatus['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || ($midtransStatus['status_code'] ?? false) == '404' ||
                    (!empty($midtransStatus['payment_type']) && $midtransStatus['payment_type'] == 'gopay' && $midtransStatus['transaction_status'] == 'pending')
                ) {
                    $connectMidtrans = Midtrans::expire($trx['transaction_receipt_number']);

                    if ($connectMidtrans) {
                        $trx->triggerPaymentCancelled();
                        return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
                return [
                    'status' => 'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
            case 'xendit':
                $dtXendit = TransactionPaymentXendit::where('id_transaction_group', $trx['id_transaction_group'])->first();
                if (empty($dtXendit->xendit_id)) {
                    $trx->triggerPaymentCancelled();
                    return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                } else {
                    $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit->xendit_id, $dtXendit->type);

                    $getStatus = $status['status'] ?? $status[0]['status'] ?? 0;
                    $getId = $status['id'] ?? $status[0]['id'] ?? null;
                    if ($status && $getStatus == 'PENDING' && !empty($getId)) {
                        $cancel = app('Modules\Xendit\Http\Controllers\XenditController')->expireInvoice($getId);

                        if ($cancel) {
                            $trx->triggerPaymentCancelled();
                            return ['status' => 'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                        }
                    }
                }
                return [
                    'status' => 'fail',
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
            "name" => "Queue" . $doctor['doctor_name']
        ];

        $url = "/ccaas/1/queues";

        $outputQueue = Infobip::sendRequest('Queue', "POST", $url, $queue);

        return $outputQueue;
    }

    public function getConversation($conversationId)
    {
        //get ConversationId
        // $conversationId = $request->id_conversation;

        $url = "/ccaas/1/conversations/" . $conversationId;

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
            "topic" => "Conversation" . $doctor['id_doctor'],
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

    public function refreshMessage(Request $request)
    {
        $post = $request->json()->all();

        $transactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        //create ConversationId
        $conversationId = $transactionConsultation['id_conversation'];

        $url = "/ccaas/1/conversations/" . $conversationId . "/messages";

        $subject = [
            'action' => "Get Conversations Message $conversationId"
        ];

        $outputMessages = Infobip::getRequest('Conversation', "GET", $url)['response'] ?? null;

        foreach ($outputMessages['messages'] as $outputMessage) {
            //payload messages
            $payload = [
                'id_transaction_consultation' => $transactionConsultation['id_transaction_consultation'],
                'id_message' => $outputMessage['id'],
                'direction' => $outputMessage['singleSendMessage']['direction'],
                'content_type' => $outputMessage['singleSendMessage']['content']['type'],
                'created_at_infobip' => $outputMessage['createdAt']
            ];

            switch ($outputMessage['singleSendMessage']['content']['type']) {
                case "IMAGE":
                    $payload['url'] = $outputMessage['content']['url'];
                    $payload['caption'] = $outputMessage['content']['caption'];

                    break;
                case "DOCUMENT":
                    $payload['url'] = $outputMessage['content']['url'];
                    $payload['caption'] = $outputMessage['content']['caption'];

                    break;
                default:
                    $payload['text'] = $outputMessage['content']['text'];
            }

            $message = TransactionConsultationMessage::updateOrCreate(['id_message' => $outputMessage['id']], $payload);
        }

        return response()->json(MyHelper::checkGet($outputMessages));
    }

    public function getMessage(Request $request)
    {
        $post = $request->json()->all();

        $transactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        $message = TransactionConsultationMessage::select('*', \DB::raw('0 as time'))->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->orderBy('created_at_infobip', 'DESC')->paginate($post['per_page'] ?? 10);

        return response()->json(MyHelper::checkGet($message));
    }

    public function getNewMessage(Request $request)
    {
        $post = $request->validate([
            'direction' => 'required|string|in:forward,backward',
            'id_transaction' => 'required',
            'limit' => 'sometimes|nullable|numeric',
            'last_id' => 'sometimes|nullable|numeric',
        ]);

        $limit = $request->limit ?: 10;

        $transactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }

        $message = TransactionConsultationMessage::select('*', \DB::raw('0 as time'))->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->orderBy('created_at_infobip')->where('id_transaction_consultation_message', $request->direction == 'forward' ? '>' : '<', $request->last_id ?: 0)->take($limit)->get();

        return response()->json(MyHelper::checkGet($message));
    }

    public function createMessage(Request $request)
    {
        // $post = $request->json()->all();
        $post = $request->all();

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi tidak ditemukan']
            ]);
        }

        $transaction = $transaction->toArray();

        //get Transaction Consultation
        $transactionConsultation = TransactionConsultation::with('doctor')->with('user')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }

        //check contentType
        if ($post['content_type'] == 'TEXT') {
            $content = [
                "text" => $post['text']
            ];
        }

        switch ($post['content_type']) {
            case "IMAGE":
                //check extension
                $ext = $post['file']->getClientOriginalExtension();

                //Set Pict Name
                $pictName = mt_rand(0, 1000) . '' . time() . '.' . $ext;

                //Path
                $path = 'img/chat/' . $transaction['consultation']['id_conversation'] . '/';

                $resource = $post['file'];
                $save = Storage::disk(env('STORAGE'))->putFileAs($path, $resource, $pictName, 'public');

                $content = [
                    "url" => env('STORAGE_URL_API') . $path . $pictName,
                    "caption" => $post['caption']
                ];

                break;
            case "DOCUMENT":
                //check extension
                $ext = $post['file']->getClientOriginalExtension();

                //Set Pict Name
                $fileName = mt_rand(0, 1000) . '' . time() . '.' . $ext;

                //Path
                $path = 'file/chat/' . $transaction['consultation']['id_conversation'] . '/';

                $resource = $post['file'];
                $save = Storage::disk(env('STORAGE'))->putFileAs($path, $resource, $fileName, 'public');

                $content = [
                    "url" => env('STORAGE_URL_API') . $path . $fileName,
                    "caption" => $post['caption']
                ];

                break;
            default:
                $content = [
                    "text" => $post['text']
                ];
        }

        //create Message
        $message = [
            "from" => $transaction['consultation']['id_doctor_infobip'],
            "to" => $transaction['consultation']['id_user_infobip'],
            "channel" => "LIVE_CHAT",
            "contentType" => $post['content_type'],
            "content" => $content
        ];

        $url = "/ccaas/1/conversations/" . $transaction['consultation']['id_conversation'] . "/messages";

        $subject = [
            'id_doctor' => $transaction['consultation']['id_doctor'],
            'action' => 'Create Conversations'
        ];

        $outputMessages = Infobip::sendRequest('Conversation', "POST", $url, $message);

        //save message to DB TransactionConsultationMessages
        $response = $outputMessages['response'] ?? false;
        if (!$response || ($response['requestError'] ?? false)) {
            return [
                'status' => 'fail',
                'messages' => is_array($response['requestError'] ?? false) ?
                    array_column($response['requestError'], 'text') :
                    ['Terjadi kesalahan saat mencoba mengirim pesan'],
            ];
        }

        if ($response) {
            $payload = [
                'id_transaction_consultation' => $transaction['consultation']['id_transaction_consultation'],
                'id_message' => $response['id'],
                'direction' => $response['direction'],
                'content_type' => $response['contentType'],
                'created_at_infobip' => $response['createdAt']
            ];

            switch ($response['contentType']) {
                case "IMAGE":
                    $payload['url'] = $response['content']['url'];
                    $payload['caption'] = $response['content']['caption'];

                    break;
                case "DOCUMENT":
                    $payload['url'] = $response['content']['url'];
                    $payload['caption'] = $response['content']['caption'];

                    break;
                default:
                    $payload['text'] = $response['content']['text'];
            }

            $message = TransactionConsultationMessage::updateOrCreate(['id_message' => $payload['id_message']], $payload);
        }

        //Send Autoresponse to Customer Device
        if (!empty($transactionConsultation['user'])) {
            if (!empty($request->header('user-agent-view'))) {
                $useragent = $request->header('user-agent-view');
            } else {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            }

            if (stristr($useragent, 'iOS')) {
                $useragent = 'iOS';
            }
            if (stristr($useragent, 'okhttp')) {
                $useragent = 'Android';
            }
            if (stristr($useragent, 'GuzzleHttp')) {
                $useragent = 'Browser';
            }

            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'User Received Chat',
                    $transactionConsultation['user']['phone'],
                    [
                        'action' => 'user_received_chat',
                        'messages' => 'User Received New Chat',
                        'id_conversation' => $transactionConsultation['id_conversation'],
                        'id_transaction' => $transactionConsultation['id_transaction'],
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s')
                    ],
                    $useragent,
                    false,
                    false
                );
            }
        }

        return response()->json(MyHelper::checkGet($outputMessages));
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function transactionDetail(Request $request)
    {
        $post = $request->json()->all();

        //cek id transaction
        if (!isset($post['id_transaction'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Id transaction tidak boleh kosong']
            ]);
        }

        //get Transaction
        $transaction = Transaction::with('consultation')->with('outlet')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json(MyHelper::checkGet($transaction));
        }

        $transaction_consultation_chat_url = optional($transaction->consultation)->consultation_chat_url;
        $transaction = $transaction->toArray();

        //if cek jadwal missed
        $checkMissed = $this->checkConsultationMissed($transaction);

        //get Consultation
        $consultation = [
            'schedule_date' => $transaction['consultation']['schedule_date_human_short_formatted'],
            'schedule_start_time' => $transaction['consultation']['schedule_start_time_formatted'],
            'schedule_end_time' => $transaction['consultation']['schedule_end_time_formatted'],
            'consultation_status' => $transaction['consultation']['consultation_status']
        ];

        //get Doctor
        $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $transaction['consultation']['id_doctor'])->first();

        if (empty($doctor)) {
            return response()->json(MyHelper::checkGet($doctor));
        }

        $doctor = $doctor->toArray();
        unset($doctor['password']);

        //set detail payment
        $paymentDetail = [
            [
                'text' => 'Subtotal',
                'value' => 'Rp ' . number_format((int)$transaction['transaction_subtotal'], 0, ",", ".")
            ]
        ];

        if (!empty($transaction['transaction_discount'])) {
            $codePromo = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->first()['promo_code'] ?? '';
            $paymentDetail[] = [
                'text' => 'Discount' . (!empty($transaction['transaction_discount_delivery']) ? ' Biaya Kirim' : '') . (!empty($codePromo) ? ' (' . $codePromo . ')' : ''),
                'value' => '-Rp ' . number_format((int)abs($transaction['transaction_discount']), 0, ",", ".")
            ];
        }

        $grandTotal = $transaction['transaction_grandtotal'];
        $trxPaymentBalance = TransactionPaymentBalance::where('id_transaction', $transaction['id_transaction'])->first()['balance_nominal'] ?? 0;

        if (!empty($trxPaymentBalance)) {
            $paymentDetail[] = [
                'text' => 'Point yang digunakan',
                'value' => '-' . number_format($trxPaymentBalance, 0, ",", ".")
            ];
            $grandTotal = $grandTotal - $trxPaymentBalance;
        }

        $trxPaymentMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $transaction['id_transaction_group'])->first();
        $trxPaymentXendit = TransactionPaymentXendit::where('id_transaction_group', $transaction['id_transaction_group'])->first();

        $paymentURL = null;
        $paymentToken = null;
        $paymentType = null;
        if (!empty($trxPaymentMidtrans)) {
            $paymentMethod = $trxPaymentMidtrans['payment_type'] . (!empty($trxPaymentMidtrans['bank']) ? ' (' . $trxPaymentMidtrans['bank'] . ')' : '');
            $paymentMethod = str_replace(" ", "_", $paymentMethod);
            $paymentLogo = config('payment_method.midtrans_' . strtolower($paymentMethod) . '.logo');
            $paymentType = 'Xendit';//'Midtrans';
            if ($transaction['transaction_status'] == 'Unpaid') {
                $paymentURL = $trxPaymentMidtrans['redirect_url'];
                $paymentToken = $trxPaymentMidtrans['token'];
            }
        } elseif (!empty($trxPaymentXendit)) {
            $paymentMethod = $trxPaymentXendit['type'];
            $paymentMethod = str_replace(" ", "_", $paymentMethod);
            $paymentLogo = config('payment_method.xendit_' . strtolower($paymentMethod) . '.logo');
            $paymentType = 'Xendit';
            if ($transaction['transaction_status'] == 'Unpaid') {
                $paymentURL = $trxPaymentXendit['checkout_url'];
            }
        }

        $result = [
            'id_transaction' => $transaction['id_transaction'],
            'receipt_number_group' => TransactionGroup::where('id_transaction_group', $transaction['id_transaction_group'])->first()['transaction_receipt_number'] ?? '',
            'transaction_receipt_number' => $transaction['transaction_receipt_number'],
            'transaction_status' => $transaction['transaction_payment_status'] == 'Completed' ? 'Completed' : $transaction['transaction_status'],
            'transaction_date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_date'])), true),
            'transaction_consultation' => $consultation,
            'show_rate_popup' => $transaction['show_rate_popup'],
            'transaction_grandtotal' => 'Rp ' . number_format($grandTotal, 0, ",", "."),
            'outlet_name' => $transaction['outlet']['outlet_name'],
            'outlet_logo' => (empty($transaction['outlet_image_logo_portrait']) ? config('url.storage_url_api') . 'img/default.jpg' : config('url.storage_url_api') . $transaction['outlet_image_logo_portrait']),
            'user' => User::where('id', $transaction['id_user'])->select('name', 'email', 'phone')->first(),
            'doctor' => $doctor,
            'payment' => $paymentMethod ?? 'Tidak ada pembayaran',
            'payment_logo' => $paymentLogo ?? env('STORAGE_URL_API') . 'default_image/payment_method/default.png',
            'payment_type' => $paymentType,
            'payment_token' => $paymentToken,
            'payment_url' => $paymentURL,
            'payment_detail' => $paymentDetail,
            'point_receive' => (!empty($transaction['transaction_cashback_earned'] && $transaction['transaction_status'] != 'Rejected') ? ($transaction['cashback_insert_status'] ? 'Mendapatkan +' : 'Anda akan mendapatkan +') . number_format((int)$transaction['transaction_cashback_earned'], 0, ",", ".") . ' point dari transaksi ini' : ''),
            'transaction_reject_reason' => $transaction['transaction_reject_reason'],
            'transaction_reject_at' => (!empty($transaction['transaction_reject_at']) ? MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_reject_at'])), true) : null),
            'transaction_consultation_chat_url' => $transaction_consultation_chat_url,
            'consultation_time' => MyHelper::dateFormatInd($consultation['schedule_date'], true, false) . ' ' . date('H:i', strtotime($consultation['schedule_start_time'])) . ' - ' . date('H:i', strtotime($consultation['schedule_end_time']))
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function checkConsultationMissed($transaction)
    {

        //getCurrentTime
        $nowDateTime = Carbon::now();

        $scheduleDateTime = Carbon::parse($transaction['consultation']['schedule_date'] . $transaction['consultation']['schedule_end_time']);

        if (!$scheduleDateTime->gt($nowDateTime)) {
            if ($transaction['consultation']['consultation_status'] == "soon") {
                $updateConsultationStatus = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->update(['consultation_status' => "missed"]);
            }
        }

        return $transaction;
    }

    /**
     * Get info from given cart data
     * @param  detailHistoryTransaction $request [description]
     * @return View                    [description]
     */
    public function getConsultationSettings(Request $request)
    {
        $post = $request->json()->all();

        $getSetting = Setting::where('key', $post['key'])->first()['value_text'] ?? null;

        $result = [];
        //search here
        if (!empty($post['search'])) {
            $settings = json_decode($getSetting);
            foreach ($settings as $setting) {
                if (str_contains(strtolower($setting), strtolower($post['search']))) {
                    $result[] = $setting;
                }
            }
        } else {
            $result = json_decode($getSetting);
        }

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
             "exp" => time() + 3600,
             "ski" => config('infobip.secretkey_id'),
        ];
        $token = MyHelper::jwtTokenGenerator($payload);
        return view('consultation::chat', ['token' => $token]);
    }

    public function getDetailInfobip(Request $request)
    {
        $post = $request->validate([
            'id_transaction' => 'required|numeric',
        ]);
        $user = $request->user();

        //get transaction
        $transactionConsultation = null;
        if (isset($user->id_doctor)) {
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
                'token' => $user->getActiveToken()
            ]
        ];
    }

    public function updateIdUserInfobip(Request $request)
    {
        $post = $request->json()->all();

        //getTransaction
        $transaction = Transaction::where('id_transaction', $post['id_transaction'])->first()->toArray();

        $url = '/people/2/persons?externalId=' . $transaction['transaction_receipt_number'];

        $outputPersonDetail = Infobip::getRequest('Get Detail Person', "GET", $url);

        //return $outputPersonDetail;

        if ($outputPersonDetail['status'] == 'success') {
            $getLiveChatUserId = $outputPersonDetail['response']['contactInformation']['liveChat'][0]['userId'];

            $updateTransactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->update([
                'id_user_infobip' => $getLiveChatUserId
            ]);
        }

        return ['status' => 'Success', 'response' => $outputPersonDetail];
    }

    public function receivedChatFromInfobip(Request $request)
    {
        try {
            $post = $request->json()->all();

            $getWebhookUserId = $post['singleSendMessage']['from']['id'];

            //get Transaction where conversation Id
            $transactionConsultation = TransactionConsultation::with('doctor')->with('user')->where('consultation_status', 'ongoing')->where('id_user_infobip', $getWebhookUserId)->first();

            if (!empty($transactionConsultation)) {
                $updateTransactionConsultation = TransactionConsultation::where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->update([
                    'id_conversation' => $post['conversationId'],
                    'id_doctor_infobip' => $post['singleSendMessage']['to']['id']
                ]);

                $selectedConsultation = $transactionConsultation;
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Transaction Consultation with Id User is not found']
                ]);
            }
            // } else {
            //     //get Transaction where conversation Id is null
            //     $transactionConsultations = TransactionConsultation::where('consultation_status', 'ongoing')->where('id_conversation', null)->get();

            //     if(empty($transactionConsultations)){
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Transaction Consultation with Id Conversation null is not found']
            //         ]);
            //     }
            //     $transactionConsultations = $transactionConsultations->toArray();

            //     //foreach transaction, check to get person endpoint with externalId = transactionConsultation['receipt_number']
            //     foreach($transactionConsultations as $key => $transactionConsultation){
            //         //getTransaction
            //         $transaction = Transaction::where('id_transaction', $transactionConsultation['id_transaction'])->first()->toArray();

            //         $url = '/people/2/persons?externalId='.$transaction['transaction_receipt_number'];

            //         $outputPersonDetail = Infobip::getRequest('Get Detail Person', "GET", $url);

            //         if($outputPersonDetail['status'] == 'success'){
            //             //compare transaction
            //             $getLiveChatUserId = $outputPersonDetail['response']['contactInformation']['liveChat'][0]['userId'];
            //             $getWebhookUserId = $post['singleSendMessage']['from']['id'];

            //             $selectedConsultation = null;
            //             if($getLiveChatUserId == $getWebhookUserId){
            //                 //selected Consultation
            //                 $selectedConsultation = $transactionConsultation;

            //                 //update id_conversation in database
            //                 $updateTransactionConsultation = TransactionConsultation::where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->update([
            //                     'id_conversation' => $post['conversationId'],
            //                     'id_doctor_infobip' => $post['singleSendMessage']['to']['id'],
            //                     'id_user_infobip' => $post['singleSendMessage']['from']['id']
            //                 ]);
            //             }
            //         }
            //     }
            // }

            //save message to DB TransactionConsultationMessages
            $payload = [
                'id_transaction_consultation' => $selectedConsultation['id_transaction_consultation'],
                'id_message' => $post['id'],
                'direction' => $post['direction'],
                'content_type' => $post['contentType'],
                'created_at_infobip' => $post['createdAt']
            ];

            switch ($post['contentType']) {
                case "IMAGE":
                    $payload['url'] = $post['content']['url'];
                    $payload['caption'] = $post['content']['caption'];

                    break;
                case "DOCUMENT":
                    $payload['url'] = $post['content']['url'];
                    $payload['caption'] = $post['content']['caption'];

                    break;
                default:
                    $payload['text'] = $post['content']['text'];
            }

            $message = TransactionConsultationMessage::updateOrCreate(['id_message' => $payload['id_message']], $payload);

            //create doctor data for chat
            if (isset($selectedConsultation['doctor'])) {
                $doctor = Doctor::where('id_doctor', $selectedConsultation['doctor']['id_doctor'])->first();
                //create agent if empty in doctor
                if (!empty($doctor['id_agent'])) {
                    $agentId = $doctor['id_agent'];
                } else {
                    $outputAgent = $this->createAgent($doctor);
                    if ($outputAgent['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputAgent['response']
                        ];
                    }
                    $agentId = $outputAgent['response']['id'];
                    $doctor->update(['id_agent' => $agentId]);
                }

                //create queue if empty in doctor
                if (!empty($doctor['id_queue'])) {
                    $queueId = $doctor['id_queue'];
                } else {
                    $outputQueue = $this->createQueue($doctor);
                    if ($outputQueue['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputQueue['response']
                        ];
                    }
                    $queueId = $outputQueue['response']['id'];
                    $doctor->update(['id_queue' => $queueId]);
                }

                //create conversation
                if (!empty($transaction['consultation']['id_conversation'])) {
                    $conversationId = $transaction['consultation']['id_conversation'];

                    //get conversation
                    $outputConversation = $this->getConversation($conversationId);
                } else {
                    $outputConversation = $this->createConversation($doctor);
                    if ($outputConversation['status'] == "fail") {
                        return [
                            'status' => 'fail',
                            'messages' => $outputConversation['response']
                        ];
                    }
                    $conversationId = $outputConversation['response']['id'];
                }
            }

            //send Autoresponse notification to Doctor Device
            if (!empty($selectedConsultation['doctor'])) {
                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) {
                    $useragent = 'iOS';
                }
                if (stristr($useragent, 'okhttp')) {
                    $useragent = 'Android';
                }
                if (stristr($useragent, 'GuzzleHttp')) {
                    $useragent = 'Browser';
                }

                if ($post['contentType'] == 'TEXT') {
                    $message = $payload['text'];
                }
                if ($post['contentType'] == 'IMAGE') {
                    $message = 'Send an Image';
                }
                if ($post['contentType'] == 'DOCUMENT') {
                    $message = 'Send an Document';
                }

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Doctor Received Chat',
                        $selectedConsultation['doctor']['doctor_phone'],
                        [
                            'action' => 'doctor_received_chat',
                            'messages' => $message,
                            'id_conversation' => $selectedConsultation['id_conversation'],
                            'id_transaction' => $selectedConsultation['id_transaction'],
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s')
                        ],
                        $useragent,
                        false,
                        false,
                        'doctor'
                    );
                }
            }


            //create log data
            $urlApi = env('API_URL') . 'api/consultation/message/received';
            $result = [
                'status' => 'success',
                'result' => [
                    'id_conversation' => $selectedConsultation['id_conversation']
                ]
            ];

            $dataLog = [
                'subject' => 'Receive Message',
                'request' => json_encode($post),
                'request_url' => $urlApi,
                'response' => json_encode($result)
            ];
            LogInfobip::create($dataLog);

            return [
                'status' => 'success',
                'result' => [
                    'id_conversation' => $selectedConsultation['id_conversation']
                ]
            ];
        } catch (Exception $e) {
            $urlApi = env('API_URL') . 'api/consultation/message/received';
            $dataLog = [
                'subject' => 'Receive Message',
                'request' => json_encode($post),
                'request_url' => $urlApi
            ];
            $dataLog['response'] = 'Check your internet connection.';
            LogInfobip::create($dataLog);
            return ['status' => 'fail', 'response' => ['Check your internet connection.']];
        }
    }

    public function updateConsultationFromAdmin(Request $request)
    {
        $post = $request->json()->all();

        //get Transaction
        $transaction = Transaction::where('id_transaction', $post['id_transaction'])->first();

        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction not found']
            ]);
        }

        //get Transaction Consultation
        $transactionConsultation = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Consultation not found']
            ]);
        }

        if (env('BYPASS_VALIDASI') != true) {
            if ($transactionConsultation['consultation_status'] == 'completed') {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Konsultasi Sudah Tertandai Completed, Tidak Bisa Mengubah Rekomendasi Lagi']
                ]);
            }
        }

        $scheduleDate = date('Y-m-d', strtotime($post['schedule_date']));
        $scheduleStartTime = date('H:i:s', strtotime($post['schedule_start_time']));
        $scheduleEndTime = date('H:i:s', strtotime($post['schedule_end_time']));

        if ($transactionConsultation['schedule_date'] != $scheduleDate || $transactionConsultation['schedule_start_time'] != $scheduleStartTime || $transactionConsultation['schedule_end_time'] != $scheduleEndTime) {
            //create log transaction consultation reschedule
            $createReschedule = TransactionConsultationReschedule::create([
                'id_transaction_consultation' => $transactionConsultation['id_transaction_consultation'],
                'id_doctor' => $transactionConsultation['id_doctor'],
                'id_user' => $transactionConsultation['id_user'],
                'old_schedule_date' => $transactionConsultation['schedule_date'],
                'old_schedule_start_time' => $transactionConsultation['schedule_start_time'],
                'old_schedule_end_time' => $transactionConsultation['schedule_end_time'],
                'new_schedule_date' => $scheduleDate,
                'new_schedule_start_time' => $scheduleStartTime,
                'new_schedule_end_time' => $scheduleEndTime,
                'id_user_modifier' => $request->user()->id,
                'user_modifier_type' => 'admin'
            ]);

            $usere = User::where('id', $transactionConsultation['id_user'])->first();
            app($this->autocrm)->SendAutoCRM(
                'Reschedule Consultation',
                $usere->phone,
                [
                    'id_reference'    => $transactionConsultation['id_transaction_consultation']
                ]
            );
        }

        //update Transaction Consultation
        $update = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->update([
            'schedule_date' => $scheduleDate,
            'schedule_start_time' => $scheduleStartTime,
            'schedule_end_time' => $scheduleEndTime,
            'consultation_status' => $post['consultation_status'],
            'reason_status_change' => $post['reason_status_change'],
            'id_user_modifier' => 1
        ]);

        if ($update && strtolower($post['consultation_status']) == 'canceled') {
            $transaction->triggerReject([
                'id_transaction' => $transaction['id_transaction'],
                'reject_reason' => $post['reason_status_change'] ?? 'Konsultasi dibatalkan'
            ]);
        }

        $result = TransactionConsultation::where('id_transaction', $transaction['id_transaction'])->first();

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function getScheduleTimeFromAdmin(Request $request)
    {
        $post = $request->json()->all();

        $selectedScheduleTime = app($this->doctor)->getAvailableScheduleTime($post['id_doctor_schedule'], $post['date']);

        return response()->json(['status'  => 'success', 'result' => $selectedScheduleTime]);
    }

    public function getDateAndRemainingTimeConsultation(Request $request)
    {
        $post = $request->validate([
            'id_transaction' => 'required|numeric',
        ]);

        $transactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->first();

        if (empty($transactionConsultation)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaksi Konsultasi tidak ditemukan']
            ]);
        }

        $transactionConsultation = $transactionConsultation->toArray();

        //get Date Chat
        $message = TransactionConsultationMessage::where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->first();

        $messageDate = MyHelper::indonesian_date_v2($message['created_at_infobip'] ?? time(), 'l, d F Y');
        if ($transactionConsultation['consultation_status'] == 'ongoing') {
            $remaining = strtotime($transactionConsultation['schedule_date'] . ' ' . $transactionConsultation['schedule_end_time']) - time();
            if ($remaining < 0) {
                $remaining = 0;
            }
            $remaining -= 7 * 3600;
            $remainingTime = date('H:i:s', $remaining);
        } else {
            $remainingTime = '00:00:00';
        }

        // $dateId = Carbon::parse($message['created_at_infobip'])->locale('id');

        // $dateId->settings(['formatFunction' => 'translatedFormat']);

        // $dayId = $dateId->format('l');

        // $chatDateId = MyHelper::dateOnlyFormatInd($message['created_at_infobip']);

        // //get Remaining Time
        // $nowTime = Carbon::now();

        // $finishTime = Carbon::parse($transactionConsultation['schedule_end_time']);

        // if ($finishTime->gt($nowTime)) {
        //     $remainingDuration = $finishTime->diffInSeconds($nowTime);

        //     $remainingTime = gmdate('H:i:s', $remainingDuration);
        // } else {
        //     $remainingTime = date('H:i:s', strtotime('00:00:00'));
        // }

        $result = [
            'message_date' => $messageDate, //$dayId.', '.$chatDateId,
            'remaining_time' => $remainingTime,
            'status' => $transactionConsultation
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function submitRescheduleConsultation(Request $request)
    {
        $post = $request->json()->all();

        //cek date time schedule
        if (empty($post['date']) && empty($post['time'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Schedule can not be empty']
            ]);
        }

        //check doctor availability
        $id_doctor = $post['id_doctor'];
        $doctor = Doctor::with('outlet')->with('specialists')->where('id_doctor', $post['id_doctor'])->first();

        if (empty($doctor)) {
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
                                ->whereNotIn('consultation_status', ['canceled', 'done'])
                                ->where('schedule_start_time', $picked_time)->count();

        $getSettingQuota = Setting::where('key', 'max_consultation_quota')->first()->toArray();
        $quota = $getSettingQuota['value'];

        if ($quota <= $doctor_constultation && $quota != null) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        //selected session
        $schedule_session = DoctorSchedule::with('schedule_time')->where('id_doctor', $id_doctor)->where('day', $picked_day)
            ->whereHas('schedule_time', function ($query) use ($post, $picked_time) {
                $query->where('start_time', '=', $picked_time);
            })->first();

        if (empty($schedule_session)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Jadwal penuh / tidak tersedia']
            ]);
        }

        //get picked session
        $picked_schedule = DoctorSchedule::where('id_doctor', $doctor['id_doctor'])->leftJoin('time_schedules', function ($query) {
            $query->on('time_schedules.id_doctor_schedule', '=', 'doctor_schedules.id_doctor_schedule');
        })->where('start_time', '=', $picked_time)->first();

        //get Transaction Consultation
        $transactionConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->first();

        //get Reschedule Settings
        $getSettingReschedule = Setting::where('key', 'max_reschedule_before')->first();
        $settingReschedule = $getSettingReschedule['value'];

        $maxReschedule = Carbon::parse($transactionConsultation['schedule_date'])->subDays((int)$settingReschedule);
        $now = Carbon::now();

        if ($now > $maxReschedule) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Sudah Melewati Batas Dapat Reschedule']
            ]);
        }

        //create log transaction consultation reschedule
        $createReschedule = TransactionConsultationReschedule::create([
            'id_transaction_consultation' => $transactionConsultation['id_transaction_consultation'],
            'id_doctor' => $transactionConsultation['id_doctor'],
            'id_user' => $transactionConsultation['id_user'],
            'old_schedule_date' => $transactionConsultation['schedule_date'],
            'old_schedule_start_time' => $transactionConsultation['schedule_start_time'],
            'old_schedule_end_time' => $transactionConsultation['schedule_end_time'],
            'new_schedule_date' => $picked_date,
            'new_schedule_start_time' => $picked_schedule['start_time'],
            'new_schedule_end_time' => $picked_schedule['end_time'],
            'id_user_modifier' => $request->user()->id,
            'user_modifier_type' => 'customer'
        ]);

        //update to Transaction
        $updateConsultation = TransactionConsultation::where('id_transaction', $post['id_transaction'])->update([
            'schedule_date' => $picked_date,
            'schedule_start_time' => $picked_schedule['start_time'],
            'schedule_end_time' => $picked_schedule['end_time']
        ]);

        return MyHelper::checkGet($createReschedule);
    }

    public function mergeProducts($items)
    {
        // create unique array
        $new_items = [];
        $test = [];
        foreach ($items as $key => $item) {
            $new_item = [
                'id_product' => $item['id_product'],
                'id_product_variant_group' => $item['id_product_variant_group'] ?? null,
                'usage_rules' => $item['usage_rules'] ?? null,
                'usage_rules_time' => $item['usage_rules_time'] ?? null,
                'usage_rules_additional_time' => $item['usage_rules_additional_time'] ?? null,
                'id_outlet' => $item['id_outlet'],
                'treatment_description' => $item['treatment_description'] ?? null
            ];

            $pos = array_search($new_item, $new_items);

            if ($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['qty_product'];
            } else {
                $item_qtys[$pos] += $item['qty_product'];
            }
        }

        // update qty
        foreach ($new_items as $key => &$value) {
            $value['qty_product'] = $item_qtys[$key];
        }

        return $new_items;
    }

    public function cronAutoEndConsultation()
    {
        $log = MyHelper::logCron('Auto End Consultation');
        try {
            $now = Carbon::now();
            $date = date('Y-m-d');
            $time = date('H:i:s');

            $countAutoDone = 0;
            $idsConsultationAutoDone = TransactionConsultation::where('consultation_status', "ongoing")->where('schedule_date', '<=', $date)->where('schedule_end_time', '<=', $time)->pluck('id_transaction');
            if (!empty($idsConsultationAutoDone)) {
                $countAutoDone = $idsConsultationAutoDone->count();
                $idsConsultationAutoDone = $idsConsultationAutoDone->toArray();
                //auto end consultation
                $transactionConsultation = TransactionConsultation::where('consultation_status', "ongoing")->where('schedule_date', '<=', $date)->where('schedule_end_time', '<=', $time)->update([
                    'consultation_status' => 'done'
                ]);

                $transactions = Transaction::whereIn('id_transaction', $idsConsultationAutoDone)->get();
                foreach ($transactions as $key => $transaction) {
                    $outputUpdateConversation = $this->updateConversationInfobip($transaction);

                    //Send Autoresponse to User Device
                    if (!empty($transactionConsultation['user'])) {
                        if (!empty($request->header('user-agent-view'))) {
                            $useragent = $request->header('user-agent-view');
                        } else {
                            $useragent = $_SERVER['HTTP_USER_AGENT'];
                        }

                        if (stristr($useragent, 'iOS')) {
                            $useragent = 'iOS';
                        }
                        if (stristr($useragent, 'okhttp')) {
                            $useragent = 'Android';
                        }
                        if (stristr($useragent, 'GuzzleHttp')) {
                            $useragent = 'Browser';
                        }

                        if (\Module::collections()->has('Autocrm')) {
                            $autocrm = app($this->autocrm)->SendAutoCRM(
                                'Consultation Done',
                                $transactionConsultation['user']['phone'],
                                [
                                    'action' => 'consultation_done',
                                    'messages' => 'Consultation Done',
                                    'id_conversation' => $transactionConsultation['id_conversation'],
                                    'id_transaction' => $transactionConsultation['id_transaction'],
                                    'useragent' => $useragent,
                                    'now' => date('Y-m-d H:i:s'),
                                    'date_sent' => date('d-m-y H:i:s')
                                ],
                                $useragent,
                                false,
                                false
                            );
                        }
                    }
                }
            }

            //update to missed consultation
            //get consultation where status soon
            $countAutoMissed = 0;
            $idsConsultationAutoMissed = [];
            $transactionConsultationSoon = TransactionConsultation::where('consultation_status', 'soon')->get();
            //dd($transactionConsultationSoon);
            foreach ($transactionConsultationSoon as $key => $consultationSoon) {
                //get setting late
                $getSettingLate = Setting::where('key', 'consultation_starts_late')->first();
                $endConsultation = $consultationSoon['schedule_date'] . $consultationSoon['schedule_end_time'];
                if (strtotime($consultationSoon['schedule_start_time']) > strtotime($consultationSoon['schedule_end_time'])) {
                    $date = date('Y-m-d', strtotime($consultationSoon['schedule_date'] . ' +1 day'));
                    $endConsultation = $date . $consultationSoon['schedule_end_time'];
                }

                if (!empty($getSettingLate) && $consultationSoon['consultation_type'] == 'scheduled') {
                    $endConsultation = date('Y-m-d H:i:s', strtotime("{$consultationSoon['schedule_date']} {$consultationSoon['schedule_start_time']} +{$getSettingLate->value}minutes"));
                } elseif ($consultationSoon['consultation_type'] == 'now') {
                    $endConsultation = date('Y-m-d H:i:s', strtotime($consultationSoon['schedule_date'] . ' ' . $consultationSoon['schedule_end_time']));
                }

                $scheduleDateTime = Carbon::parse($endConsultation);
                if (!$scheduleDateTime->gt($now)) {
                    $idsConsultationAutoMissed[] = $consultationSoon['id_transaction'];
                    $updateConsultationStatus = TransactionConsultation::where('id_transaction', $consultationSoon['id_transaction'])->update(['consultation_status' => "missed"]);
                    $countAutoMissed = $countAutoMissed + 1;

                    //Send Autoresponse to User Device
                    if (!empty($transactionConsultation['user'])) {
                        if (!empty($request->header('user-agent-view'))) {
                            $useragent = $request->header('user-agent-view');
                        } else {
                            $useragent = $_SERVER['HTTP_USER_AGENT'];
                        }

                        if (stristr($useragent, 'iOS')) {
                            $useragent = 'iOS';
                        }
                        if (stristr($useragent, 'okhttp')) {
                            $useragent = 'Android';
                        }
                        if (stristr($useragent, 'GuzzleHttp')) {
                            $useragent = 'Browser';
                        }

                        if (\Module::collections()->has('Autocrm')) {
                            $autocrm = app($this->autocrm)->SendAutoCRM(
                                'Consultation Done',
                                $transactionConsultation['user']['phone'],
                                [
                                    'action' => 'consultation_missed',
                                    'messages' => 'Consultation Missed',
                                    'id_conversation' => $transactionConsultation['id_conversation'],
                                    'id_transaction' => $transactionConsultation['id_transaction'],
                                    'useragent' => $useragent,
                                    'now' => date('Y-m-d H:i:s'),
                                    'date_sent' => date('d-m-y H:i:s')
                                ],
                                $useragent,
                                false,
                                false
                            );
                        }
                    }
                }
            }

            // dd($countAutoMissed);

            $log->success([
                'count_auto_done' => $countAutoDone,
                'id_consultation_auto_done' => $idsConsultationAutoDone,
                'count_auto_missed' => $countAutoMissed,
                'id_consultation_auto_missed' => $idsConsultationAutoMissed
            ]);
            return 'success';
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function updateConversationInfobip($transaction)
    {
        $url = "/ccaas/1/conversations/" . $transaction['consultation']['id_conversation'];

        $outputGetConversation = Infobip::getRequest('Get Conversation', "GET", $url);

        $response = $outputGetConversation['response'] ?? false;
        if (!$response || ($response['requestError'] ?? false)) {
            return [
                'status' => 'fail',
                'messages' => is_array($response['requestError'] ?? false) ?
                    array_column($response['requestError'], 'text') :
                    ['Terjadi kesalahan saat mencoba mendapatkan conversation'],
            ];
        }

        $payload = [
            "topic" => $outputGetConversation['response']['topic'],
            "summary" => $outputGetConversation['response']['summary'],
            "status" => "CLOSED",
            "priority" => $outputGetConversation['response']['priority'],
            "queueId" => $outputGetConversation['response']['queueId'],
            "agentId" => $outputGetConversation['response']['agentId'],
        ];

        $outputUpdateConversation = Infobip::sendRequest('Close Conversation', "PUT", $url, $payload);

        $response = $outputUpdateConversation['response'] ?? false;
        if (!$response || ($response['requestError'] ?? false)) {
            return [
                'status' => 'fail',
                'messages' => is_array($response['requestError'] ?? false) ?
                    array_column($response['requestError'], 'text') :
                    ['Terjadi kesalahan saat mencoba mengupdate conversation'],
            ];
        }
    }

    public function exportDetail(Request $request)
    {
        $post = $request->json()->all();

        //get Transaction
        $transaction = Transaction::where('id_transaction', $post['id_transaction'])->first();

        $transactionDate = date('d F Y | H:i', strtotime($transaction['transaction_date']));

        //get Transaction Consultation
        $transactionConsultation = $transaction->consultation;

        $detailTransaction = [
            ['Detail Transaksi', ' '],
            ['Nomor Transaksi' , $transaction['transaction_receipt_number']],
            ['Tanggal Transaksi', $transactionDate],
            ['Doctor', $transactionConsultation->doctor->doctor_name],
            ['Customer' , $transactionConsultation->user->name],
            ['Jenis Pembayaran' , $transaction->trasaction_payment_type],
            ['Status Pembayaran' , $transaction->transaction_payment_status],
            ['Tipe Konsultasi' , $transactionConsultation->consultation_type],
            ['Waktu Mulai' , date('H:i', strtotime($transactionConsultation->schedule_start_time))],
            ['Waktu Selesai' , date('H:i', strtotime($transactionConsultation->schedule_end_time))],
            ['Status Konsultasi' , $transactionConsultation->consultation_status]
        ];


        $detailConsultation = [];
        $diseaseComplaint = json_decode($transactionConsultation['disease_complaint']);
        $diseaseAnalysis = json_decode($transactionConsultation['disease_analysis']);

        $detailConsultation[] = [
            'Hasil Konsultasi', ' '
        ];

        //create tabel disease complaint
        foreach ($diseaseComplaint as $key => $complaint) {
            if ($key == 0) {
                $detailConsultation[] = ['Keluhan', $complaint];
            } else {
                $detailConsultation[] = ['', $complaint];
            }
        }

        //create tabel disease analysis
        foreach ($diseaseAnalysis as $key => $analysis) {
            if ($key == 0) {
                $detailConsultation[] = ['Diagnosa', $analysis];
            } else {
                $detailConsultation[] = ['', $analysis];
            }
        }


        $detailConsultation[] = ['Anjuran Penanganan', $transactionConsultation['treatment_recomendation']];

        //get Transaction Message
        $transactionMessages = TransactionConsultationMessage::where('id_transaction_consultation', $transactionConsultation->id_transaction_consultation)->get();

        //doctor
        $doctor = $transactionConsultation->doctor;

        //user
        $user = $transactionConsultation->user;

        //get Transaction
        $messages = [];
        foreach ($transactionMessages as $key => $message) {
            $messages[0] = [
                'Name',
                'Url',
                'Text / Caption',
                'Created At Infobip'
            ];

            if ($message['direction'] == 'OUTBOUND') {
                $messages[$key + 1]['name'] = $doctor['doctor_name'];
            } else {
                $messages[$key + 1]['name'] = $user['name'];
            }

            if ($message['content_type'] == 'DOCUMENT') {
                $messages[$key + 1]['url'] = $message['url'];
                $messages[$key + 1]['caption'] = $message['caption'];
            } elseif ($message['content_type'] == 'IMAGE') {
                $messages[$key + 1]['url'] = $message['url'];
                $messages[$key + 1]['caption'] = $message['caption'];
            } else {
                $messages[$key + 1]['url'] = '';
                $messages[$key + 1]['text'] = $message['text'];
            }
            $messages[$key + 1]['created_at_infobip'] = date('H:i', strtotime($message['created_at_infobip']));
        }

        //get Transaction Recomendation Product
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyProduct()->get();

        $itemsProduct = [];
        if (!empty($recomendations)) {
            $itemsProduct = [
                ['Nama Product',
                'Harga',
                'Qty',
                'Aturan Pakai',
                'Waktu Pemakaian',
                'Aturan Tambahan',
                'Anjuran Penggunaan']
            ];

            $i = 1;
            foreach ($recomendations as $key => $recomendation) {
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $transaction['id_user'],
                    'id_product_variant_group' => $recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                $itemsProduct[$i][] = $detailProduct['result']['product_name'] ?? null;


                if ($detailProduct['result']['variants'] != null && isset($detailProduct['result']['variants']['childs'][0]['product_variant_group_price'])) {
                    $itemsProduct[$i][] = $detailProduct['result']['variants']['childs'][0]['product_variant_group_price'];
                } else {
                    $itemsProduct[$i][] = $detailProduct['result']['product_price'];
                }

                $itemsProduct[$i][] = $recomendation->qty_product ?? null;
                $itemsProduct[$i][] = $recomendation->usage_rules ?? null;
                $itemsProduct[$i][] = $recomendation->usage_rules_time ?? null;
                $itemsProduct[$i][] = $recomendation->usage_rules_additional_time ?? null;
                $itemsProduct[$i][] = $recomendation->treatment_description ?? null;

                $i++;
            }
        }

        //get Transaction Recomendation Drug
        $recomendations = TransactionConsultationRecomendation::with('product')->where('id_transaction_consultation', $transactionConsultation['id_transaction_consultation'])->onlyDrug()->get();

        $itemsDrugs = [];
        if (!empty($recomendations)) {
            $itemsDrugs = [
                ['Nama Product',
                'Harga',
                'Qty',
                'Aturan Pakai',
                'Waktu Pemakaian',
                'Aturan Tambahan',
                'Anjuran Penggunaan']
            ];

            $i = 1;
            foreach ($recomendations as $key => $recomendation) {
                $params = [
                    'id_product' => $recomendation->id_product,
                    'id_user' => $transaction['id_user'],
                    'id_product_variant_group' => $recomendation->id_product_variant_group
                ];

                $detailProduct = app($this->product)->detailRecomendation($params);

                $itemsDrugs[$i][] = $detailProduct['result']['product_name'] ?? null;


                if ($detailProduct['result']['variants'] != null && isset($detailProduct['result']['variants']['childs'][0]['product_variant_group_price'])) {
                    $itemsDrugs[$i][] = $detailProduct['result']['variants']['childs'][0]['product_variant_group_price'];
                } else {
                    $itemsDrugs[$i][] = $detailProduct['result']['product_price'];
                }

                //decode and implode usage rules time
                $json = json_decode($recomendation->usage_rules_time);
                $usageRules = null;
                if (!empty($json)) {
                    $usageRules = implode(", ", $json);
                }

                $itemsDrugs[$i][] = $recomendation->qty_product ?? null;
                $itemsDrugs[$i][] = $recomendation->usage_rules ?? null;
                $itemsDrugs[$i][] = $usageRules ?? null;
                $itemsDrugs[$i][] = $recomendation->usage_rules_additional_time ?? null;
                $itemsDrugs[$i][] = $recomendation->treatment_description ?? null;

                $i++;
            }

            $outlet_referral_code = !empty($transaction->outlet->outlet_referral_code) ? $transaction->outlet->outlet_referral_code : $transaction->outlet->outlet_code;

            $outlet = [
                ["Outlet", $transaction->outlet->outlet_name],
                ["Alamat Outlet", $transaction->outlet->OutletFullAddress],
                ["Referral Code Outlet", '#' . $outlet_referral_code],
                ["Batas Maksimal Penebusan", ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter) . ' '],
                []
            ];

            // $itemsDrugs[] = ['Outlet', $transaction->outlet->outlet_name];
            // $itemsDrugs[] = ['Alamat Outlet', $transaction->outlet->OutletFullAddress];
            // $itemsDrugs[] = ['Referral Code Outlet', '#'.$outlet_referral_code];
            // $itemsDrugs[] = ['Batas Maksimal Penebusan', ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter)];

            // $itemsDrugs['redemption'] = ['Batas Maksimal Penebusan', ($transactionConsultation->recipe_redemption_limit - $transactionConsultation->recipe_redemption_counter)];

            $prescriptions = [
                'outlet' => $outlet,
                'items' => $itemsDrugs
            ];
        }

        $result = [
            'Detail Transaction' => $detailTransaction,
            'Riwayat Chat' => $messages,
            'Hasil Konsultasi' => $detailConsultation,
            'Rekomendasi Produk' => $itemsProduct,
            'Rekomendasi Obat' => $prescriptions
        ];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }
}
