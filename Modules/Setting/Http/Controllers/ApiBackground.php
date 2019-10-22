<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\HomeBackground;

use App\Lib\MyHelper;
use File;
use DB;

class ApiBackground extends Controller
{
	
	public $saveImage = "img/background/";
	public $endPoint;
	
	function __construct() {
		date_default_timezone_set('Asia/Jakarta');
		$this->globalcontroller  = "App\Http\Controllers\Controller";
		$this->endPoint  = env('S3_URL_API');
		
	}
	function getBackground($when=null) {
        if($when == null)
			$query = HomeBackground::get()->toArray();
		else
			$query = HomeBackground::where('when', '=',$when)->get()->toArray();
		
        return $query;
    }
	
	/**
     * [Greetings] List
     */
	function listBackground(Request $request) {
		if($request->json('when'))
			$listEmail = $this->getBackground($request->json('when'));
		else 
			$listEmail = $this->getBackground();
		
		if($listEmail){
			foreach($listEmail as $key => $row){
				$listEmail[$key]['picture'] = $this->endPoint.$row['picture'];
			}
		}
        return MyHelper::checkGet($listEmail);
    }

	/**
     * [Greetings] Create
     */
	function createBackground(Request $request) {
		$post = $request->all();
		$data = array();
		$data['when'] = $post['when'];
		$upload = MyHelper::uploadPhoto($post['background'], $this->saveImage, 1080);
		if ($upload['status'] == "success") {
			$data['picture'] = $upload['path'];
		}

		$create = HomeBackground::create($data);
		return MyHelper::checkCreate($create);
    }
	
	/**
     * [Greetings] Delete
     */
	function deleteBackground(Request $request) {
		$post = $request->all();
		$query = HomeBackground::where('id_home_background', '=', $post['id_home_background'])->get()->toArray();
		
		$delete = MyHelper::deletePhoto($query[0]['picture']);
		if ($delete) {
			$value = null;
			$delete = HomeBackground::where('id_home_background', '=', $post['id_home_background'])->delete();
		}
		
		return MyHelper::checkDelete($delete);
    }
}
