<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\LogActivityReadOnly;
use App\Http\Models\QuinosUser;

use Modules\POS\Http\Requests\Quinos\CreateQuinosUser;
use Modules\POS\Http\Requests\Quinos\UpdateQuinosUser;

class ApiQuinos extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
	}

	public function createQuinosUser(CreateQuinosUser $request){
		$post = $request->json()->all();

		$create = QuinosUser::create([
					'username' => $post['username'],
					'password'		=> bcrypt($post['password']),
				]);

		return response()->json(MyHelper::checkGet($create));
	}

	public function updateQuinosUser(UpdateQuinosUser $request){
		$post = $request->json()->all();

		$update = QuinosUser::where('username', $post['username'])->update([
					'password'		=> bcrypt($post['new_password']),
				]);

		return response()->json(MyHelper::checkUpdate($update));
	}
	
	public function log(Request $request)
    {
		$post = $request->json()->all();
		if(isset($post['take'])){
			$take = $post['take'];
		}else{
			$take = 10;
		}
		if(isset($post['skip'])){
			$skip = $post['skip'];
		}else{
			$skip = 0;
		}

		if(isset($post['rule'])){
			$rule = $post['rule'];
		}else{
			$rule = 'and';
		}


		$query = LogActivityReadOnly::where('module','=','POS')
							->orderBy('id_log_activity','desc')
							->select('id_log_activity', 'response_status', 'ip', 'created_at', 'subject', 'useragent', 'module');

		if(isset($post['date_start'])){
			$query = $query->where('created_at', '>=', date('Y-m-d H:i:00', strtotime($post['date_start'])));
		}
		if(isset($post['date_end'])){
			$query = $query->where('created_at', '<=', date('Y-m-d H:i:00', strtotime($post['date_end'])));
		}
		if(isset($post['conditions'])){
			foreach($post['conditions'] as $condition){
				if(isset($condition['subject'])){
					if($condition['operator'] != '=' && $condition['operator'] != 'like'){
						$condition['parameter'] = $condition['operator'];
					}
	
					if($condition['operator'] == 'like'){
						if($rule == 'and'){
							$query = $query->where($condition['subject'],'LIKE','%'.$condition['parameter'].'%');
						} else {
							$query = $query->orWhere($condition['subject'],'LIKE', '%'.$condition['parameter']).'%';
						}
					}else{
						if($rule == 'and'){
							$query = $query->where($condition['subject'],'=',$condition['parameter']);
						} else {
							$query = $query->orWhere($condition['subject'],'=',$condition['parameter']);
						}
					}
				}
			}
		}

		if(isset($post['pagination'])){
			$query = $query->paginate($post['take']);
		}else{
			$query = $query->skip($skip)->take($take)
							->get()
							->toArray();
		}
		
		if($query){
			$result = ['status'	=> 'success',
					   'result'	=> $query
					  ]; 
		} else {
			$result = [
						'status'	=> 'fail',
						'messages'	=> ['Log Activity Not Found']
						];
		}
		return response()->json($result);
	}

	public function detailLog($id, Request $request){
		$log = LogActivityReadOnly::where('id_log_activity', $id)->first();
		if($log){
		    if($log['response']){
    			$res = MyHelper::decrypt2019($log['response']);
    			if(!$log['response']){
    				$log['response'] = $res;
    			} 
    			// $log['response'] = str_replace('}','\r\n}',str_replace(',',',\r\n&emsp;',str_replace('{','{\r\n&emsp;',strip_tags($log['response']))));
		    }
		    
    	    if($log['request']){
    			$req = MyHelper::decrypt2019($log['request']);
    			if(!$log['request']){
    				$log['request'] = $req;
    			} 
    			// $log['request'] = str_replace('}','\r\n}',str_replace(',',',\r\n&emsp;',str_replace('{','{\r\n&emsp;',strip_tags($log['request']))));
    	    }
		}

		return response()->json(MyHelper::checkGet($log));
	}
	

}
