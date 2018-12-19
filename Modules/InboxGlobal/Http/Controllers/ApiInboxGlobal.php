<?php

namespace Modules\InboxGlobal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Http\Models\InboxGlobal;
use App\Http\Models\InboxGlobalRule;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;

class ApiInboxGlobal extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    public function listInboxGlobal(Request $request){
		$post = $request->json()->all(); 
		
		$query = InboxGlobal::with(['inbox_global_rule_parents', 'inbox_global_rule_parents.rules'])->orderBy('id_inbox_global', 'Desc');
		$count = InboxGlobal::get();
		
		if(isset($post['inbox_global_subject']) && $post['inbox_global_subject'] != ""){
			$query = $query->where('inbox_global_subject','like','%'.$post['inbox_global_subject'].'%');
			$count = $count->where('inbox_global_subject','like','%'.$post['inbox_global_subject'].'%');
		}
		
		$query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
		$count = $count->count();
		
		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Inbox Global']
				];
		}
		return response()->json($result);
	}
	
	public function detailInboxGlobal(Request $request){
		$post = $request->json()->all(); 
		
		$query = InboxGlobal::with(['inbox_global_rule_parents', 'inbox_global_rule_parents.rules'])->where('id_inbox_global','=',$post['id_inbox_global']);
		$count = InboxGlobal::where('id_inbox_global','=',$post['id_inbox_global'])->get();
		
		if(isset($post['inbox_global_subject']) && $post['inbox_global_subject'] != ""){
			$query = $query->where('inbox_global_subject','like','%'.$post['inbox_global_subject'].'%');
			$count = $count->where('inbox_global_subject','like','%'.$post['inbox_global_subject'].'%');
		}
		
		$query = $query->first();
		$count = $count->count();
		
		if(isset($query) && !empty($query)) {
			$result = [
					'status'  => 'success',
					'result'  => $query,
					'count'  => $count
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Inbox Global']
				];
		}
		return response()->json($result);
	}
	
	public function deleteInboxGlobal(Request $request){
		$post = $request->json()->all();

		$checkInboxGlobal = InboxGlobal::where('id_inbox_global','=',$post['id_inbox_global'])->first();
		if($checkInboxGlobal){
			$delete = InboxGlobal::where('id_inbox_global','=',$post['id_inbox_global'])->delete();
			
			if($delete){
				$result = ['status'	=> 'success',
						   'result'	=> ['Inbox Global '.$checkInboxGlobal['inbox_global_subject'].' has been deleted']
						  ];
			} else {
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Delete Failed']
						];
			}
		} else {
			$result = [
						'status'	=> 'fail',
						'messages'	=> ['Inbox Global Not Found']
						];
		}
		return response()->json($result);
	}
	
	public function updateInboxGlobal(Request $request){
		$post = $request->json()->all(); 
		
		$data 						= [];
		$data['id_campaign'] 		= null;
		$data['inbox_global_subject'] 	= $post['inbox_global_subject'];
		$data['inbox_global_clickto'] 		= $post['inbox_global_clickto'];
		if(isset($post['inbox_global_id_reference']))
		$data['inbox_global_id_reference'] 	= $post['inbox_global_id_reference'];
		if(isset($post['inbox_global_content']))
		$data['inbox_global_content'] 		= $post['inbox_global_content'];
		if(isset($post['inbox_global_link']))
		$data['inbox_global_link'] 			= $post['inbox_global_link'];
		
		if(!empty($post['inbox_global_start'])){
			$datetimearr 				= explode(' - ',$post['inbox_global_start']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['inbox_global_start'] 	= $date." ".$datetimearr[1].":00";
		} else $data['inbox_global_start'] = null;
		
		if(!empty($post['inbox_global_end'])){
			$datetimearr 				= explode(' - ',$post['inbox_global_end']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['inbox_global_end'] 	= $date." ".$datetimearr[1].":00";
		} else $data['inbox_global_end'] = null;
		
		$queryInboxGlobal = InboxGlobal::where('id_inbox_global','=',$post['id_inbox_global'])->update($data);
		
		if($queryInboxGlobal){
			$data = [];
			
			if(isset($post['id_inbox_global'])) $data['id_inbox_global'] = $post['id_inbox_global'];
				else $data['id_inbox_global'] = $queryInboxGlobal->id_inbox_global;
				
			$queryInboxGlobalRule = MyHelper::insertCondition('inbox_global', $data['id_inbox_global'], $post['conditions']);
			if(isset($queryInboxGlobalRule['status']) && $queryInboxGlobalRule['status'] == 'success'){
				$resultrule = $queryInboxGlobalRule['data'];
			}else{
				DB::rollBack();
				$result = [
					'status'  => 'fail',
					'messages'  => ['Create Inbox Global Failed']
				];
			}
			
			$result = [
					'status'  => 'success',
					'result'  => 'Set Inbox Global & Rule Success',
					'inboxglobal'  => $queryInboxGlobal,
					'rule'  => $resultrule
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Update Inbox Global Failed']
				];
		}
		
		return response()->json($result);
	}
	public function createInboxGlobal(Request $request){
		$post = $request->json()->all(); 
		
		$data 								= [];
		$data['id_campaign'] 				= null;
		$data['inbox_global_subject'] 		= $post['inbox_global_subject'];
		$data['inbox_global_clickto'] 		= $post['inbox_global_clickto'];
		if(isset($post['inbox_global_id_reference']))
		$data['inbox_global_id_reference'] 	= $post['inbox_global_id_reference'];
		if(isset($post['inbox_global_content']))
		$data['inbox_global_content'] 		= $post['inbox_global_content'];
		if(isset($post['inbox_global_link']))
		$data['inbox_global_link'] 			= $post['inbox_global_link'];
		
		if(!empty($post['inbox_global_start'])){
			$datetimearr 				= explode(' - ',$post['inbox_global_start']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['inbox_global_start'] 	= $date." ".$datetimearr[1].":00";
		} else $data['inbox_global_start'] = null;
		
		if(!empty($post['inbox_global_end'])){
			$datetimearr 				= explode(' - ',$post['inbox_global_end']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['inbox_global_end'] 	= $date." ".$datetimearr[1].":00";
		} else $data['inbox_global_end'] = null;
		
		$queryInboxGlobal = InboxGlobal::create($data);
		
		if($queryInboxGlobal){
			$data = [];
			
			if(isset($post['id_inbox_global'])) $data['id_inbox_global'] = $post['id_inbox_global'];
				else $data['id_inbox_global'] = $queryInboxGlobal->id_inbox_global;
				
			$queryInboxGlobalRule = MyHelper::insertCondition('inbox_global', $data['id_inbox_global'], $post['conditions']);
			if(isset($queryInboxGlobalRule['status']) && $queryInboxGlobalRule['status'] == 'success'){
				$resultrule = $queryInboxGlobalRule['data'];
			}else{
				DB::rollBack();
				$result = [
					'status'  => 'fail',
					'messages'  => ['Create Inbox Global Failed']
				];
			}
			
			$result = [
					'status'  => 'success',
					'result'  => 'Set Inbox Global & Rule Success',
					'inboxglobal'  => $queryInboxGlobal,
					'rule'  => $resultrule
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Create Inbox Global Failed']
				];
		}
		
		return response()->json($result);
	}
}
