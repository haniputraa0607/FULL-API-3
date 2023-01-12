<?php

namespace App\Http\Controllers;

use App\Http\Models\Districts;
use App\Http\Models\Subdistricts;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionConsultation;
use Illuminate\Http\Request;
use App\Http\Models\Feature;
use App\Http\Models\UserFeature;
use App\Http\Models\User;
use App\Http\Models\City;
use App\Http\Models\Province;
use App\Http\Models\Level;
use App\Http\Models\Configs;
use App\Http\Models\Courier;
use App\Http\Models\Setting;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorUpdateData;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantLogBalance;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function getFeatureControl(Request $request)
    {
        $user = json_decode($request->user(), true);

        if ($user['level'] == 'Super Admin') {
            $checkFeature = Feature::select('id_feature')->where('show_hide', 1)->get()->toArray();
        } else {
            $checkFeature = UserFeature::join('features', 'features.id_feature', '=', 'user_features.id_feature')
                            ->where('features.show_hide', 1)
                            ->where('user_features.id_user', '=', $user['id'])
                            ->select('features.id_feature')->get()->toArray();
        }
        $result = [
            'status'  => 'success',
            'result'  => array_pluck($checkFeature, 'id_feature')
        ];

        return response()->json($result);
    }

    public function getFeature(Request $request)
    {

        $checkFeature = Feature::where('show_hide', 1)->orderBy('order', 'asc')->get()->toArray();
        $result = [
            'status'  => 'success',
            'result'  => $checkFeature
        ];
        return response()->json($result);
    }

    public function getFeatureModule(Request $request)
    {

        $checkFeature = Feature::select('feature_module')->where('show_hide', 1)->orderBy('order', 'asc')->groupBy('feature_module')->get()->toArray();
        $result = [
            'status'  => 'success',
            'result'  => $checkFeature
        ];
        return response()->json($result);
    }

    public function listCity(Request $request)
    {
        $post = $request->json()->all();

        $query = City::select('*');
        if (isset($post['id_province'])) {
            $query->where('id_province', $post['id_province']);
        }

        $query = $query->get()->toArray();
        return MyHelper::checkGet($query);
    }

    public function listProvince(Request $request)
    {
        $query = (new Province())->newQuery();
        if ($id_city = $request->json('id_city')) {
            $query->whereHas('cities', function ($query) use ($id_city) {
                $query->where('id_city', $id_city);
            });
        }
        return MyHelper::checkGet($query->get()->toArray());
    }

    public function listCourier()
    {
        $query = Courier::where('status', 'Active')->get()->toArray();
        return MyHelper::checkGet($query);
    }

    public function listRank()
    {
        $query = Level::get()->toArray();
        return MyHelper::checkGet($query);
    }

    public function getConfig(Request $request)
    {
        $config = Configs::select('id_config')->where('is_active', '1')->get()->toArray();
        $result = [
            'status'  => 'success',
            'result'  => array_pluck($config, 'id_config')
        ];

        return response()->json($result);
    }

    public function uploadImageSummernote(Request $request)
    {
        $post = $request->json()->all();

        if (!file_exists('img/summernote/' . $post['type'])) {
            mkdir('img/summernote/' . $post['type'], 0777, true);
        }

        $upload = MyHelper::uploadPhotoSummerNote($request->json('image'), 'img/summernote/' . $post['type'] . '/', null);

        if ($upload['status'] == "success") {
            $result = [
                'status' => 'success',
                'result' => [
                    'pathinfo' => config('url.storage_url_api') . $upload['path'],
                    'path' => $upload['path']
                ]
            ];
        } else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
    }

    public function deleteImageSummernote(Request $request)
    {
        if (MyHelper::deletePhoto($request->json('image'))) {
            $result = [
                'status' => 'success'
            ];
        } else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
    }

    public function maintenance()
    {
        $get = Setting::where('key', 'maintenance_mode')->first();
        if ($get) {
            $dt = (array)json_decode($get['value_text']);
            $data['status'] = $get['value'];
            $data['message'] = $dt['message'];
            if ($dt['image'] != "") {
                $data['image'] = config('url.storage_url_api') . $dt['image'];
            } else {
                $data['image'] = config('url.storage_url_api') . 'img/maintenance/default.png';
            }
        }
        return view('webview.maintenance_mode', $data);
    }

    public function listDistrict(Request $request)
    {
        $post = $request->json()->all();

        $query = Districts::select('*');
        if (isset($post['id_city'])) {
            $query->where('id_city', $post['id_city']);
        }

        $query = $query->get()->toArray();
        return MyHelper::checkGet($query);
    }

    public function listSubdistrict(Request $request)
    {
        $post = $request->json()->all();

        $query = Subdistricts::select('*');
        if (isset($post['id_district'])) {
            $query->where('id_district', $post['id_district']);
        }

        $query = $query->get()->toArray();
        return MyHelper::checkGet($query);
    }

    public function getSidebarBadge(Request $request)
    {
        $merchantPending = $this->merchant_register_pending();
        $witdrawalPending = $this->withdrawal_pending();

        return [
            'status' => 'success',
            'result' => [
                'doctor_request_update_data_pending' => $this->doctor_request_update_data_pending(),
                'merchant' => $merchantPending + $witdrawalPending,
                'merchant_register_pending' => $merchantPending,
                'withdrawal_pending' => $witdrawalPending,
                'transaction_pending' => $this->transaction_pending(),
                'transaction_consultation_pending' => $this->transaction_consultation_pending()
            ],
        ];
    }

    public function merchant_register_pending()
    {
        $total = Merchant::whereNotIn('merchant_status', ['Active', 'Inactive', 'Rejected'])->count();
        if ($total == 0) {
            $total = null;
        }

        return $total;
    }

    public function withdrawal_pending()
    {
        $total = MerchantLogBalance::where('merchant_balance_source', 'Withdrawal')->whereNull('merchant_balance_status')->count();
        if ($total == 0) {
            $total = null;
        }

        return $total;
    }

    public function transaction_pending()
    {
        $total = Transaction::whereIn('transaction_status', ['Unpaid','Pending'])->count();
        if ($total == 0) {
            $total = null;
        }

        return $total;
    }

    public function transaction_consultation_pending()
    {
        $total = TransactionConsultation::whereNotIn('consultation_status', ['completed'])->count();
        if ($total == 0) {
            $total = null;
        }

        return $total;
    }

    public function doctor_request_update_data_pending()
    {
        $total = DoctorUpdateData::whereNull('approve_at')->whereNull('reject_at')->count();
        if ($total == 0) {
            $total = null;
        }

        return $total;
    }
}
