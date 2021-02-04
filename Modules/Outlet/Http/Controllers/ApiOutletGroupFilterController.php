<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Outlet\Entities\OutletGroup;
use Modules\Outlet\Entities\OutletGroupFilterCondition;
use Modules\Outlet\Entities\OutletGroupFilterOutlet;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Disburse\Entities\UserFranchise;
use Modules\Disburse\Entities\UserFranchiseOultet;
use Modules\Outlet\Entities\OutletScheduleUpdate;

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\outlet\Upload;
use Modules\Outlet\Http\Requests\outlet\Update;
use Modules\Outlet\Http\Requests\outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\outlet\Create;
use Modules\Outlet\Http\Requests\outlet\Delete;
use Modules\Outlet\Http\Requests\outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\outlet\Nearme;
use Modules\Outlet\Http\Requests\outlet\Filter;
use Modules\Outlet\Http\Requests\outlet\OutletList;
use Modules\Outlet\Http\Requests\outlet\OutletListOrderNow;

use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;

use App\Jobs\SendOutletJob;

class ApiOutletGroupFilterController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    function list(){
        $data = OutletGroup::orderBy('updated_at', 'desc')->get()->toArray();
        return response()->json(MyHelper::checkGet($data));
    }

    function store(Request $request){
        $post = $request->json()->all();

        if(!isset($post['outlets']) && !isset($post['conditions'][0]['subject'])){
            return response()->json(['status' => 'fail', 'messages' => ['Data outlets or conditions can not be empty']]);
        }else{
            $dataOutletGroup = [
                'outlet_group_name' => $post['outlet_group_name'],
                'outlet_group_type' => $post['outlet_group_type']
            ];

            if($post['outlet_group_type'] == 'Conditions'){
                $dataOutletGroup['outlet_group_filter_rule'] = $post['rule'];
            }

            DB::beginTransaction();
            $outletGroup = OutletGroup::create($dataOutletGroup);

            if(!$outletGroup){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed Create outlet group']]);
            }

            if($post['outlet_group_type'] == 'Conditions'){
                $dataFilter = [];
                foreach ($post['conditions'] as $con){
                    $dataFilter[] = [
                        'id_outlet_group' => $outletGroup['id_outlet_group'],
                        'outlet_group_filter_subject' => $con['subject'],
                        'outlet_group_filter_operator' => $con['operator'],
                        'outlet_group_filter_parameter' => $con['parameter'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertFilter = OutletGroupFilterCondition::insert($dataFilter);
                if(!$insertFilter){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter conditions']]);
                }
            }else{
                $dataOutlet = [];
                foreach ($post['outlets'] as $outlet){
                    $dataOutlet[] = [
                        'id_outlet_group' => $outletGroup['id_outlet_group'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertOutlet = OutletGroupFilterOutlet::insert($dataOutlet);
                if(!$insertOutlet){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter outlets']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }
    }

    function detail(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])){
            $detail = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])
                        ->with(['outlet_group_filter_condition', 'outlet_group_filter_outlet'])
                        ->first();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    function update(Request $request){
        $post = $request->json()->all();

        if(!isset($post['outlets']) && !isset($post['conditions'][0]['subject'])){
            return response()->json(['status' => 'fail', 'messages' => ['Data outlets or conditions can not be empty']]);
        }elseif(isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])){
            $dataOutletGroup = [
                'outlet_group_name' => $post['outlet_group_name'],
                'outlet_group_type' => $post['outlet_group_type']
            ];

            if($post['outlet_group_type'] == 'Conditions'){
                $dataOutletGroup['outlet_group_filter_rule'] = $post['rule'];
            }else{
                $dataOutletGroup['outlet_group_filter_rule'] = NULL;
            }

            DB::beginTransaction();
            $outletGroup = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])->update($dataOutletGroup);

            if(!$outletGroup){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed Update outlet group']]);
            }

            OutletGroupFilterOutlet::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterCondition::where('id_outlet_group', $post['id_outlet_group'])->delete();

            if($post['outlet_group_type'] == 'Conditions'){
                $dataFilter = [];
                foreach ($post['conditions'] as $con){
                    $dataFilter[] = [
                        'id_outlet_group' => $post['id_outlet_group'],
                        'outlet_group_filter_subject' => $con['subject'],
                        'outlet_group_filter_operator' => $con['operator'],
                        'outlet_group_filter_parameter' => $con['parameter'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertFilter = OutletGroupFilterCondition::insert($dataFilter);
                if(!$insertFilter){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter conditions']]);
                }
            }else{
                $dataOutlet = [];
                foreach ($post['outlets'] as $outlet){
                    $dataOutlet[] = [
                        'id_outlet_group' => $post['id_outlet_group'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertOutlet = OutletGroupFilterOutlet::insert($dataOutlet);
                if(!$insertOutlet){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter outlets']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    function destroy(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])){
            $delete = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterOutlet::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterCondition::where('id_outlet_group', $post['id_outlet_group'])->delete();

            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function outletGroupFilter($id_outlet_group){
        if(!empty($id_outlet_group)){
            $getOutletGroup = OutletGroup::where('id_outlet_group', $id_outlet_group)->first();
            if(empty($getOutletGroup)){
                return [];
            }

            if($getOutletGroup['outlet_group_type'] == 'Outlets'){
                $arrIdOutlet = OutletGroupFilterOutlet::where('id_outlet_group', $id_outlet_group)->pluck('id_outlet')->toArray();
                $outlets = Outlet::whereIn('id_outlet', $arrIdOutlet)->where('outlet_status', 'Active')
                            ->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();

                return $outlets;
            }else{
                $conditions = OutletGroupFilterCondition::where('id_outlet_group', $id_outlet_group)->get()->toArray();
                $outlets = Outlet::select('id_outlet', 'outlet_code', 'outlet_name')
                    ->join('cities', 'cities.id_city', '=', 'outlets.id_city')
                    ->join('provinces', 'provinces.id_province', '=', 'cities.id_province')
                    ->where('outlet_status', 'Active');

                $rule = 'and';
                if(isset($getOutletGroup['outlet_group_filter_rule'])){
                    $rule = $getOutletGroup['outlet_group_filter_rule'];
                }

                if($rule == 'and'){
                    foreach ($conditions as $row){
                        if(isset($row['outlet_group_filter_subject'])){
                            if($row['outlet_group_filter_subject'] == 'province'){
                                $outlets->where('provinces.id_province', $row['outlet_group_filter_operator']);
                            }

                            if($row['outlet_group_filter_subject'] == 'city'){
                                $outlets->where('cities.id_city', $row['outlet_group_filter_operator']);
                            }

                            if($row['outlet_group_filter_subject'] == 'status_franchise'){
                                $outlets->where('status_franchise', $row['outlet_group_filter_operator']);
                            }

                            if($row['outlet_group_filter_subject'] == 'delivery_order'){
                                $outlets->where('delivery_order', $row['status_delivery']);
                            }

                            if($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name'){
                                if($row['outlet_group_filter_operator'] == '='){
                                    $outlets->where($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                }else{
                                    $outlets->where($row['outlet_group_filter_subject'], 'like', '%'.$row['outlet_group_filter_parameter'].'%');
                                }
                            }

                            if($row['outlet_group_filter_subject'] == 'brand'){
                                $outlets->whereIn('outlets.id_outlet', function($query) use($row) {
                                    $query->select('id_outlet')
                                        ->from('brand_outlet')
                                        ->where('id_brand', $row['outlet_group_filter_operator']);
                                });
                            }
                        }
                    }
                }else{
                    $outlets->where(function ($subquery) use ($conditions){
                        foreach ($conditions as $row){
                            if(isset($row['outlet_group_filter_subject'])){
                                if($row['outlet_group_filter_subject'] == 'province'){
                                    $subquery->orWhere('provinces.id_province', $row['outlet_group_filter_operator']);
                                }

                                if($row['outlet_group_filter_subject'] == 'city'){
                                    $subquery->orWhere('cities.id_city', $row['outlet_group_filter_operator']);
                                }

                                if($row['outlet_group_filter_subject'] == 'status_franchise'){
                                    $subquery->orWhere('status_franchise', $row['outlet_group_filter_operator']);
                                }

                                if($row['outlet_group_filter_subject'] == 'delivery_order'){
                                    $subquery->orWhere('delivery_order', $row['status_delivery']);
                                }

                                if($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name'){
                                    if($row['outlet_group_filter_operator'] == '='){
                                        $subquery->orWhere($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                    }else{
                                        $subquery->orWhere($row['outlet_group_filter_subject'], 'like', '%'.$row['outlet_group_filter_parameter'].'%');
                                    }
                                }

                                if($row['outlet_group_filter_subject'] == 'brand'){
                                    $subquery->orWhereIn('outlets.id_outlet', function($query) use($row) {
                                        $query->select('id_outlet')
                                            ->from('brand_outlet')
                                            ->where('id_brand', $row['outlet_group_filter_operator']);
                                    });
                                }
                            }
                        }
                    });
                }

                return $outlets->get()->toArray();
            }
        }else{
            return [];
        }
    }
}
