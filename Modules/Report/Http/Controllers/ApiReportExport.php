<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentOvo;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Treatment;
use App\Http\Models\Consultation;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\Reservation;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\Product;

use App\Jobs\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\IPay88\Entities\SubscriptionPaymentIpay88;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Report\Entities\ExportQueue;
use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use Modules\Subscription\Entities\SubscriptionPaymentOvo;
use Validator;
use Hash;
use DB;
use Mail;
use File;


class ApiReportExport extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function exportCreate(Request $request){
        $post = $request->json()->all();

        $insertToQueue = [
            'id_user' => $post['id_user'],
            'filter' => json_encode($post),
            'report_type' => $post['report_type'],
            'status_export' => 'Running'
        ];

        $create = ExportQueue::create($insertToQueue);
        if($create){
            ExportJob::dispatch($create)->allOnConnection('database');
        }
        return response()->json(MyHelper::checkCreate($create));
    }

    public function listExport(Request $request){
        $post = $request->json()->all();

        $list = ExportQueue::orderBy('created_at', 'desc');

        if(isset($post['report_type']) && !empty($post['report_type'])){
            $list = $list->where('report_type', $post['report_type']);
        }

        if(isset($post['id_user']) && !empty($post['id_user'])){
            $id_user = $post['id_user'];
            $list = $list->where('id_user', $id_user);
        }

        $list = $list->paginate(30);
        return response()->json(MyHelper::checkGet($list));
    }

    function actionExport(Request $request){
        $post = $request->json()->all();
        $action = $post['action'];
        $id_export_queue = $post['id_export_queue'];

        if($action == 'download'){
            $data = ExportQueue::where('id_export_queue', $id_export_queue)->first();
            if(!empty($data)){
                $data['url_export'] = config('url.storage_url_api').$data['url_export'];
            }
            return response()->json(MyHelper::checkGet($data));
        }elseif($action == 'deleted'){
            $data = ExportQueue::where('id_export_queue', $id_export_queue)->first();
            $file = public_path().'/'.$data['url_export'];
            $delete = MyHelper::deleteFile($file, 'public');

            if($delete){
                $update = ExportQueue::where('id_export_queue', $id_export_queue)->update(['status_export' => 'Deleted']);
                return response()->json(MyHelper::checkUpdate($update));
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['failed to delete file']]);
            }

        }
    }
}
