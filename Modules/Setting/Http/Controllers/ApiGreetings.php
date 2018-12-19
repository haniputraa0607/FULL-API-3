<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Greeting;
use App\Http\Models\Setting;

use Modules\Setting\Http\Requests\Greeting\greetings_when;
use Modules\Setting\Http\Requests\Greeting\greetings_selected;
use Modules\Setting\Http\Requests\Greeting\greetings_create;
use Modules\Setting\Http\Requests\Greeting\greetings_update;

use App\Lib\MyHelper;
use File;
use DB;

class ApiGreetings extends Controller
{
	function __construct() {
		date_default_timezone_set('Asia/Jakarta');
    }
	
	function listTimeSetting() {
		$data = [];
		$greetings_morning = Setting::where('key','=','greetings_morning')->get()->first();
		$greetings_afternoon = Setting::where('key','=','greetings_afternoon')->get()->first();
		$greetings_evening = Setting::where('key','=','greetings_evening')->get()->first();
		$greetings_late_night = Setting::where('key','=','greetings_late_night')->get()->first();
		
		$data = [];
		$data['greetings_morning'] = $greetings_morning['value'];
		$data['greetings_afternoon'] = $greetings_afternoon['value'];
		$data['greetings_evening'] = $greetings_evening['value'];
		$data['greetings_late_night'] = $greetings_late_night['value'];
		
		return MyHelper::checkGet($data);
	}
	
	function updateTimeSetting(Request $request) {
		$data = $request->json()->all();
		foreach($data as $key => $row){
			$query = Setting::where('key','=',$key)->update(['value' => $row]);
		}
		
		return MyHelper::checkUpdate($query);
	}
	
	
	function getGreetings($when=null) {
        if($when == null)
			$query = Greeting::get()->toArray();
		else
			$query = Greeting::where('when', '=',$when)->get()->toArray();

        return $query;
    }
	
	/**
     * [Greetings] List
     */
	function listGreetings(greetings_when $request) {
		if($request->json('when'))
			$listEmail = $this->getGreetings($request->json('when'));
		else 
			$listEmail = $this->getGreetings();
		
        return MyHelper::checkGet($listEmail);
    }
	
	/**
     * [Greetings] Create
     */
	function createGreetings(greetings_create $request) {
		$data = $request->all();
		$query = Greeting::create($data);
        return MyHelper::checkGet($query);
    }
	
	/**
     * [Greetings] Selected
     */
	function selectGreetings(greetings_selected $request) {
		$request = $request->all();
		$query = Greeting::where('id_greetings','=',$request['id_greetings'])->get()->toArray();
        return MyHelper::checkGet($query);
    }
	
	/**
     * [Greetings] Update
     */
	function updateGreetings(greetings_update $request) {
		$request = $request->all();
		$query = Greeting::where('id_greetings','=',$request['id_greetings'])->update($request);
        return MyHelper::checkGet($query);
    }
	
	/**
     * [Greetings] Delete
     */
	function deleteGreetings(greetings_selected $request) {
		$data = $request->all();
		$query = Greeting::where('id_greetings','=',$request['id_greetings'])->delete();
        return MyHelper::checkGet($query);
    }
}
