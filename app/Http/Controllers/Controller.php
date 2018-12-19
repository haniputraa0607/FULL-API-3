<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Models\Feature;
use App\Http\Models\UserFeature;
use App\Http\Models\User;
use App\Http\Models\City;
use App\Http\Models\Province;
use App\Http\Models\Level;
use App\Http\Models\Configs;
use App\Http\Models\Courier;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Lib\MyHelper;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	function __construct(){
      date_default_timezone_set('Asia/Jakarta');
    }
	
	function getFeatureControl(Request $request){
		$user = json_decode($request->user(), true);

		if($user['level'] == 'Super Admin'){
			$checkFeature = Feature::select('id_feature')->get()->toArray();
		}else{
			$checkFeature = UserFeature::join('features', 'features.id_feature', '=', 'user_features.id_feature')
							->where('user_features.id_user', '=', $user['id'])
							->select('features.id_feature')->get()->toArray();
		}
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($checkFeature, 'id_feature')
		];

      return response()->json($result);
    }
	
	function getFeature(Request $request){
	
		$checkFeature = Feature::get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function getFeatureModule(Request $request){
	
		$checkFeature = Feature::select('feature_module')->groupBy('feature_module')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function listCity(Request $request){
		$post = $request->json()->all();

		$query = City::select('*');
		if (isset($post['id_province'])) {
			$query->where('id_province', $post['id_province']);
		}

		$query = $query->get()->toArray();
		return MyHelper::checkGet($query); 
	}

	function listProvince(){
		$query = Province::get()->toArray();
		return MyHelper::checkGet($query); 
	}
	
	function listCourier(){
		$query = Courier::where('status','Active')->get()->toArray();
		return MyHelper::checkGet($query); 
	}
	
	function listRank(){
		$query = Level::get()->toArray();
		return MyHelper::checkGet($query); 
	}

	function getConfig(Request $request){
		$config = Configs::select('id_config')->where('is_active', '1')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($config, 'id_config')
		];

      return response()->json($result);
    }
}
