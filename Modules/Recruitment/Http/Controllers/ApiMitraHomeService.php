<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Jobs\FindingHairStylistHomeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionHomeServiceHairStylistFinding;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiMitraHomeService extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
        $this->getNotif         = "Modules\Transaction\Http\Controllers\ApiNotification";
    }

    public function activeInactiveHomeService(Request $request)
    {
    	$user = $request->user();
    	if($request->status == 1){
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                ->whereDate('date', date('Y-m-d'))
                ->first();
            if(!empty($shift)){
                $current = date('H:i:s');
                $shiftTimeStart = date('H:i:s', strtotime($shift['time_start']));
                $shiftTimeEnd = date('H:i:s', strtotime($shift['time_end']));
                if(strtotime($current) >= strtotime($shiftTimeStart) && strtotime($current) < strtotime($shiftTimeEnd)){
                    return response()->json(['status' => 'fail', 'messages' => ['Tidak bisa mengaktifkan home service karena Anda memiliki jadwal outlet service.']]);
                }
            }
        }
    	$update = UserHairStylist::where('id_user_hair_stylist', $user->id_user_hair_stylist)->update(['home_service_status' => $request->status]);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function setHSLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $hs = $request->user();
        $location = $hs->location()->updateOrCreate([], [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return [
            'status' => 'success'
        ];
    }

    public function listOrder(Request $request){
        $post = $request->all();
        $user = $request->user();
        $currentDate = date('Y-m-d');

        $list = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->whereNotIn('status', ['Cancelled', 'Completed'])
                ->where('id_user_hair_stylist', $user['id_user_hair_stylist']);

        if(!empty($post['status']) && $post['status'] == 'today'){
            $list = $list->where('schedule_date', $currentDate);
        }elseif(!empty($post['status']) && $post['status'] == 'next_day'){
            $list = $list->where('schedule_date', '>', $currentDate);
        }

        $list = $list->orderBy('transaction_home_services.created_at', 'desc')
                ->select('transactions.id_transaction', 'id_transaction_home_service', 'transaction_receipt_number as booking_id', 'status as home_service_status', 'schedule_date', DB::raw('DATE_FORMAT(schedule_time, "%H:%i") as schedule_time'),
                    'destination_name', 'destination_phone', 'destination_address', 'destination_note')->orderBy('schedule_date', 'asc');

        $dateNow = new DateTime("now");
        if(!empty($post['page'])){
            $list = $list->paginate($post['per_page']??10)->toArray();
            foreach ($list['data'] as $key=>$data){
                $timerText = "";
                $dateSchedule = new DateTime($data['schedule_date'] . ' ' .$data['schedule_time']);
                $interval = $dateNow->diff($dateSchedule);
                $day = $interval->d;
                $hour = $interval->h;
                $minute = $interval->i;
                if ($day) {
                    $timerText .= $day.' hari, '. $hour.' jam' ;
                } elseif ($hour) {
                    $timerText .= $hour.' jam' ;
                } else {
                    $timerText .= $minute.' menit' ;
                }

                $timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($data['schedule_date'] . ' ' .$data['schedule_time'])) ? ' lagi' : ' lalu';
                $timerTextColor = (strtotime(date('Y-m-d')) == strtotime($data['schedule_date'])) ? '#FF2424' : '#121212';

                $services = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('id_transaction', $data['id_transaction'])->select('products.id_product', 'product_name', 'transaction_product_qty as qty')->get()->toArray();
                $list['data'][$key]['destination_note'] = (empty($data['destination_note']) ? '-':$data['destination_note']);
                $list['data'][$key]['status'] = (empty($data['status']) ? '':$data['status']);
                $list['data'][$key]['schedule_date_display'] = MyHelper::dateFormatInd($data['schedule_date'], true, false);
                $list['data'][$key]['services'] = $services;
                $list['data'][$key]['timer_text'] = $timerText;
                $list['data'][$key]['timer_text_color'] = $timerTextColor;
            }
        }else{
            $list = $list->get()->toArray();
            foreach ($list as $key=>$data){
                $timerText = "";
                $dateSchedule = new DateTime($data['schedule_date'] . ' ' .$data['schedule_time']);
                $interval = $dateNow->diff($dateSchedule);
                $day = $interval->d;
                $hour = $interval->h;
                $minute = $interval->i;
                if ($day) {
                    $timerText .= $day.' hari, '. $hour.' jam' ;
                } elseif ($hour) {
                    $timerText .= $hour.' jam' ;
                } else {
                    $timerText .= $minute.' menit' ;
                }

                $timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($data['schedule_date'] . ' ' .$data['schedule_time'])) ? ' lagi' : ' lalu';
                $timerTextColor = (strtotime(date('Y-m-d')) == strtotime($data['schedule_date'])) ? '#FF2424' : '#121212';

                $services = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('id_transaction', $data['id_transaction'])->select('products.id_product', 'product_name', 'transaction_product_qty as qty')->get()->toArray();
                $list[$key]['destination_note'] = (empty($data['destination_note']) ? '-':$data['destination_note']);
                $list[$key]['status'] = (empty($data['status']) ? '':$data['status']);
                $list[$key]['schedule_date_display'] = MyHelper::dateFormatInd($data['schedule_date'], true, false);
                $list[$key]['services'] = $services;
                $list[$key]['timer_text'] = $timerText;
                $list[$key]['timer_text_color'] = $timerTextColor;
            }
        }

        return MyHelper::checkGet($list);
    }

    public function action(Request $request){
        $post = $request->json()->all();
        $user = $request->user();

        $detail = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('id_user_hair_stylist', $user['id_user_hair_stylist'])->with('user')->first();

        if(empty($detail)){
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        }

        if($detail['status'] == 'Completed' || $detail['status'] == 'Cancelled'){
            return ['status' => 'fail', 'messages' => ['Can not change status this transaction']];
        }

        $arr = [
            1 => 'Accept',
            2 => 'Reject',
            3 => 'On The Way',
            4 => 'Arrived',
            5 => 'Start Service',
            6 => 'Completed'
        ];
        $status = $arr[$post['status_id']]??null;

        if(!empty($status)){
            switch ($status){
                case 'Accept':
                    if($detail['status'] != 'Finding Hair Stylist'){
                        return ['status' => 'fail', 'messages' => ['Can not reject this transaction']];
                    }

                    $createUpdateStatus = TransactionHomeServiceStatusUpdate::create(['id_transaction' => $detail['id_transaction'],'status' => 'Get Hair Stylist']);
                    if($createUpdateStatus){
                        $update = TransactionHomeService::where('id_transaction', $detail['id_transaction'])->update(['status' => 'Get Hair Stylist']);
                        TransactionHomeServiceHairStylistFinding::where('id_transaction', $detail['id_transaction'])->delete();

                        app($this->autocrm)->SendAutoCRM(
                            'Home Service Update Status',
                            $detail['user']['phone'],
                            [
                                'id_transaction' => $detail['id_transaction'],
                                'status'=> 'Get Hair Stylist',
                                'receipt_number' => $detail['transaction_receipt_number']
                            ]
                        );
                    }
                    break;
                case 'Reject':
                    if($detail['status'] != 'Finding Hair Stylist'){
                        return ['status' => 'fail', 'messages' => ['Can not reject this transaction']];
                    }

                    $update = TransactionHomeServiceHairStylistFinding::where('id_transaction', $detail['id_transaction'])->where('id_user_hair_stylist', $user['id_user_hair_stylist'])->update(['status' => 'Reject']);

                    //cancel all booking
                    app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->cancelBookHS($detail['id_transaction']);
                    app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->bookProductStock($detail['id_transaction']);

                    FindingHairStylistHomeService::dispatch(['id_transaction' => $detail['id_transaction'], 'id_transaction_home_service' => $detail['id_transaction_home_service']])->allOnConnection('findinghairstylistqueue');
                    break;
                case 'On The Way':
                case 'Arrived':
                case 'Start Service':
                case 'Completed':
                    if($detail['status'] == 'Finding Hair Stylist'){
                        return ['status' => 'fail', 'messages' => ['Can not change status this transaction']];
                    }

                    $createUpdateStatus = TransactionHomeServiceStatusUpdate::create(['id_transaction' => $detail['id_transaction'],'status' => $status]);
                    if($createUpdateStatus){
                        $update = TransactionHomeService::where('id_transaction', $detail['id_transaction'])->update(['status' => $status]);
                        if($status == 'Completed'){
                            HairstylistNotAvailable::where('id_transaction', $detail['id_transaction'])->delete();

				            UserRatingLog::updateOrCreate([
				                'id_user' => $detail['id_user'],
				                'id_transaction' => $detail['id_transaction'],
				                'id_user_hair_stylist' => $detail['id_user_hair_stylist']
				            ],[
				                'refuse_count' => 0,
				                'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
				            ]);

				            Transaction::where('id_transaction', $detail['id_transaction'])->update(['show_rate_popup' => '1']);
                            TransactionProduct::where('id_transaction', $detail['id_transaction'])->where('type', 'Service')->update(['transaction_product_completed_at' => date('Y-m-d H:i:s')]);
                            TransactionHomeService::where('id_transaction', $detail['id_transaction'])->update(['completed_at' => date('Y-m-d H:i:s')]);
                        }
                    }

                    if($status == 'Completed'){
                        $newTrx	= Transaction::with('user.memberships', 'outlet')
                            ->where('id_transaction', $detail['id_transaction'])
                            ->first();
                        $savePoint = app($this->getNotif)->savePoint($newTrx);
                        if (!$savePoint) {
                            return [
                                'status'   => 'fail',
                                'messages' => ['Transaction failed'],
                            ];
                        }
                    }

                    app($this->autocrm)->SendAutoCRM(
                        'Home Service Update Status',
                        $detail['user']['phone'],
                        [
                            'id_transaction' => $detail['id_transaction'],
                            'status'=> $status,
                            'receipt_number' => $detail['transaction_receipt_number']
                        ]
                    );
                    break;
            }
        }else{
            return ['status' => 'fail', 'messages' => ['Status not found']];
        }

        return [
            'status' => 'success',
            'result' => [
                'status_id' => $post['status_id'],
                'id_transaction' => $post['id_transaction'],
                'transaction_receipt_number' => $detail['transaction_receipt_number']
            ]
        ];
    }

    public function detailOrder(Request $request){
        $post = $request->json()->all();
        $user = $request->user();

        $detail = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('id_user_hair_stylist', $user['id_user_hair_stylist'])->with('user')->first();

        if(empty($detail)){
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        }

        $timerText = "";
        $dateNow = new DateTime("now");
        $dateSchedule = new DateTime($detail['schedule_date'] . ' ' .$detail['schedule_time']);
        $interval = $dateNow->diff($dateSchedule);
        $day = $interval->d;
        $hour = $interval->h;
        $minute = $interval->i;
        if ($day) {
            $timerText .= $day.' hari, '. $hour.' jam' ;
        } elseif ($hour) {
            $timerText .= $hour.' jam' ;
        } else {
            $timerText .= $minute.' menit' ;
        }

        $timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($detail['schedule_date'] . ' ' .$detail['schedule_time'])) ? ' lagi' : ' lalu';
        $timerTextColor = (strtotime(date('Y-m-d')) == strtotime($detail['schedule_date'])) ? '#FF2424' : '#121212';

        $detail = [
            'id_transaction' => $detail['id_transaction'],
            'booking_id' => $detail['transaction_receipt_number'],
            'destination_address' => $detail['destination_address'],
            'destination_note' => $detail['destination_note'],
            'destination_latitude' => $detail['destination_latitude'],
            'destination_longitude' => $detail['destination_longitude'],
            'schedule_date' => MyHelper::dateFormatInd($detail['schedule_date'], true, false),
            'schedule_time' => date('H:i', strtotime($detail['schedule_time'])),
            'name' => $detail['destination_name'],
            'phone' => $detail['destination_phone'],
            'status' => $detail['status'],
            'timer_text' => $timerText,
            'timer_text_color' => $timerTextColor
        ];

        return MyHelper::checkGet($detail);
    }

    public function detailOrderService(Request $request){
        $post = $request->json()->all();
        $user = $request->user();

        $detail = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('id_user_hair_stylist', $user['id_user_hair_stylist'])->with('user')->first();

        if(empty($detail)){
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        }

        $services = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
            ->where('id_transaction', $detail['id_transaction'])
            ->select(DB::raw('(processing_time_service * transaction_product_qty) as processing_time'), 'products.id_product', 'product_name', 'transaction_product_qty as qty')->get()->toArray();

        $processingTime = array_sum(array_column($services, 'processing_time'));
        $detail = [
            'id_transaction' => $detail['id_transaction'],
            'booking_id' => $detail['transaction_receipt_number'],
            'destination_address' => $detail['destination_address'],
            'destination_note' => $detail['destination_note'],
            'destination_latitude' => $detail['destination_latitude'],
            'destination_longitude' => $detail['destination_longitude'],
            'schedule_date' => MyHelper::dateFormatInd($detail['schedule_date'], true, false),
            'schedule_time' => date('H:i', strtotime($detail['schedule_time'])),
            'name' => $detail['destination_name'],
            'phone' => $detail['destination_phone'],
            'status' => $detail['status'],
            'total_time' => $processingTime*60,
            'services' => $services
        ];

        return MyHelper::checkGet($detail);
    }

    public function listHistoryOrder(Request $request){
        $post = $request->all();
        $user = $request->user();
        $currentDate = date('Y-m-d H:i:s');

        $list = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
            ->leftJoin('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_home_services.id_user_hair_stylist')
            ->leftJoin('users', 'transactions.id_user', 'users.id')
            ->where('status', 'Completed')
            ->where('transaction_home_services.id_user_hair_stylist', $user['id_user_hair_stylist'])
            ->select('transactions.id_transaction', 'id_transaction_home_service', 'transaction_receipt_number', 'user_hair_stylist.fullname as hairstylist_name',
                'schedule_date', 'schedule_time', 'users.name as customer_name');

        if(!empty($post['key_search'])){
            $list = $list->where(function ($q) use ($post){
                $q->where('transaction_receipt_number', 'like', '%'.$post['key_search'].'%')
                    ->orWhere('users.name', 'like', '%'.$post['key_search'].'%');
            });
        }

        if(!empty($post['filter']) && $post['filter'] == 'last 7 days'){
            $dateFilter = date("Y-m-d", strtotime($currentDate." -7 days"));
            $list = $list->whereDate('schedule_date', '>=', $dateFilter)->whereDate('schedule_date', '<=', $currentDate);
        }elseif(!empty($post['filter']) && $post['filter'] == 'last 30 days'){
            $dateFilter = date("Y-m-d", strtotime($currentDate." -30 days"));
            $list = $list->whereDate('schedule_date', '>=', $dateFilter)->whereDate('schedule_date', '<=', $currentDate);
        }elseif(!empty($post['filter']) && $post['filter'] == 'today'){
            $list = $list->whereDate('schedule_date', $currentDate);
        }

        if((!empty($post['sort']) && $post['sort'] == 'desc') || empty($post['sort'])){
            $list = $list->orderBy('schedule_date', 'desc')->orderBy('schedule_time', 'desc');
        }elseif(!empty($post['sort']) && $post['sort'] == 'asc'){
            $list = $list->orderBy('schedule_date', 'asc')->orderBy('schedule_time', 'asc');
        }

        $list = $list->paginate($post['per_page']??10)->toArray();
        foreach ($list['data'] as $key=>$data){
            $statusStart = TransactionHomeServiceStatusUpdate::where('id_transaction', $data['id_transaction'])->where('status', 'Start Service')->first()['created_at']??'';
            $statusCompleted = TransactionHomeServiceStatusUpdate::where('id_transaction', $data['id_transaction'])->where('status', 'Completed')->first()['created_at']??'';
            $services = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->where('id_transaction', $data['id_transaction'])->select('products.id_product', 'product_name', 'transaction_product_qty as qty', 'transaction_product_subtotal as amount')->get()->toArray();

            $trx = Transaction::where('id_transaction', $data['id_transaction'])->first();
            $trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
            $paymentMethod = null;
            foreach ($trxPayment['payment'] as $p) {
                $paymentMethod = $p['name'];
                if (strtolower($p['name']) != 'balance') {
                    break;
                }
            }

            $list['data'][$key]['schedule_date_display'] = MyHelper::dateFormatInd($data['schedule_date'], true, false);
            $list['data'][$key]['schedule_time'] = date('H:i', strtotime($data['schedule_time']));
            $list['data'][$key]['services'] = $services;
            $list['data'][$key]['service_start'] = (empty($statusStart)? '':date('H:i', strtotime($statusStart)));
            $list['data'][$key]['service_end'] = (empty($statusCompleted)? '':date('H:i', strtotime($statusCompleted)));
            $list['data'][$key]['payment_method'] = $paymentMethod;
        }

        return MyHelper::checkGet($list);
    }
}
