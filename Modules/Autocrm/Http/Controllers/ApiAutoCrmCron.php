<?php

namespace Modules\Autocrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Autocrm;
use App\Http\Models\AutocrmRule;
use App\Http\Models\AutocrmRuleParent;
use App\Http\Models\User;
use App\Http\Models\WhatsappContent;

use Modules\Autocrm\Http\Requests\CreateCron; 
use Modules\Autocrm\Http\Requests\UpdateCron; 

use App\Lib\MyHelper;
use Validator;
use DB;
use App\Lib\SendMail as Mail;

class ApiAutoCrmCron extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		if(\Module::collections()->has('Autocrm')) {
			$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
		}
		$this->user  = "Modules\Users\Http\Controllers\ApiUser";
    }

	public function listAutoCrmCron(Request $request){
		$post = $request->json()->all();
		if(isset($post['id_autocrm'])){
			$query = Autocrm::with('autocrm_rule_parents', 'autocrm_rule_parents.rules', 'whatsapp_content')->where('id_autocrm','=',$post['id_autocrm'])->first(); 
		 }else{
			 $query = Autocrm::where('autocrm_type', 'Cron')->orderBy('updated_at', 'desc')->get()->toArray();
		 }
		return response()->json(MyHelper::checkGet($query));
	}

	public function createAutocrmCron(CreateCron $request){
		$post = $request->json()->all();
		$post = $this->clearInputDisable($post);

		if(isset($post['conditions'])){
			$conditions = $post['conditions'];
			unset($post['conditions']);
		}

		if(isset($post['rule'])){
			$post['autocrm_cron_rule'] = $post['rule'];
			unset($post['rule']);
		}

		if($post['autocrm_trigger'] == 'Daily'){
			$post['autocrm_cron_reference'] = null;
		}

		if(isset($post['autocrm_cron_reference']) && is_array($post['autocrm_cron_reference'])){
			$ref="";
			foreach ($post['autocrm_cron_reference'] as $key => $value) {
				if(strlen($value) == 1 ){
					$value = '0'.$value;
				}
				$ref = $ref.$value;
			}
			$post['autocrm_cron_reference'] = $ref;
		}

		if (isset($post['autocrm_push_image'])) {
			$upload = MyHelper::uploadPhoto($post['autocrm_push_image'], $path = 'img/push/', 600);

			if ($upload['status'] == "success") {
				$post['autocrm_push_image'] = $upload['path'];
			} else{
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Upload Push Notification Image failed.']
					];
				return response()->json($result);
			}
		}
		
		if(isset($post['whatsapp_content'])){
			$contentWa = $post['whatsapp_content'];
			unset($post['whatsapp_content']);
		}else{
			$contentWa = null;
		}

		DB::beginTransaction();	
		$query = Autocrm::create($post);

		if($query && isset($conditions)){
			$queryAutocrmRule = MyHelper::insertCondition('autocrm', $query->id_autocrm, $conditions);

			if(isset($queryAutocrmRule['status']) && $queryAutocrmRule['status'] == 'success'){

				//insert whatsapp content
				if($contentWa && $contentWa > 0){
					$updateWa = $this->updateContentWa($contentWa, $query->id_autocrm);
					if($updateWa == 'fail'){
						DB::rollBack();
						return response()->json([
							'status'    => 'fail',
							'messages'      => [
								'Failed insert content whatsApp'
							]
						]);
					}
				}

				DB::commit();
				return response()->json(MyHelper::checkCreate($queryAutocrmRule));
			}else{
				DB::rollBack();
				return response()->json([
					'status'    => 'fail',
					'messages'      => [
						'Failed create new autocrm'
					]
				]);
			}
		}else {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Failed create new autocrm'
                ]
            ]);
        }
	}

	public function updateAutocrmCron(UpdateCron $request){
		$post = $request->json()->all();
		$post = $this->clearInputDisable($post);
		
		if(isset($post['conditions'])){
			$conditions = $post['conditions'];
			unset($post['conditions']);
		}

		if(isset($post['rule'])){
			$post['autocrm_cron_rule'] = $post['rule'];
			unset($post['rule']);
		}

		if($post['autocrm_trigger'] == 'Daily'){
			$post['autocrm_cron_reference'] = null;
		}

		if(isset($post['autocrm_cron_reference']) && is_array($post['autocrm_cron_reference'])){
			$ref="";
			foreach ($post['autocrm_cron_reference'] as $key => $value) {
				if(strlen($value) == 1 ){
					$value = '0'.$value;
				}
				$ref = $ref.$value;
			}
			$post['autocrm_cron_reference'] = $ref;
		}

		if (isset($post['autocrm_push_image'])) {
			$upload = MyHelper::uploadPhoto($post['autocrm_push_image'], $path = 'img/push/', 600);

			if ($upload['status'] == "success") {
				$post['autocrm_push_image'] = $upload['path'];
			} else{
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Upload Push Notification Image failed.']
					];
				return response()->json($result);
			}
		}

		if(isset($post['whatsapp_content'])){
			$contentWa = $post['whatsapp_content'];
			unset($post['whatsapp_content']);
		}else{
			$contentWa = null;
		}

		DB::beginTransaction();	
		$query = Autocrm::where('id_autocrm', $post['id_autocrm'])->update($post);
		if($query && isset($conditions)){
			$autocrmRuleParent = AutocrmRuleParent::where('id_autocrm', $post['id_autocrm'])->get();
			foreach ($autocrmRuleParent as $key => $value) {
				$deleteRule = $value->rules()->delete();
				if(!$deleteRule){
					DB::rollBack();
					return response()->json([
						'status'    => 'fail',
						'messages'      => [
							'Failed update autocrm'
						]
					]);
				}

				$deleteParent = $value->delete();
				if(!$deleteParent){
					DB::rollBack();
					return response()->json([
						'status'    => 'fail',
						'messages'      => [
							'Failed update autocrm'
						]
					]);
				}
			}

			$queryAutocrmRule = MyHelper::insertCondition('autocrm', $post['id_autocrm'], $conditions);

			if(isset($queryAutocrmRule['status']) && $queryAutocrmRule['status'] == 'success'){

				//insert whatsapp content
				if($contentWa && $contentWa > 0){
					$updateWa = $this->updateContentWa($contentWa, $post['id_autocrm']);
					if($updateWa == 'fail'){
						DB::rollBack();
						return response()->json([
							'status'    => 'fail',
							'messages'      => [
								'Failed insert content whatsApp'
							]
						]);
					}
				}

				DB::commit();
				return response()->json(MyHelper::checkCreate($queryAutocrmRule));
			}else{
				DB::rollBack();
				return response()->json([
					'status'    => 'fail',
					'messages'      => [
						'Failed update autocrm'
					]
				]);
			}

		}else {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Failed update autocrm'
                ]
            ]);
        }
	}

	function clearInputDisable($data){
		if(isset($data['autocrm_email_toogle']) && $data['autocrm_email_toogle'] == 0){
			$data['autocrm_email_subject'] = null;
			$data['autocrm_email_content'] = null;
		}
		if(isset($data['autocrm_sms_toogle']) && $data['autocrm_sms_toogle'] == 0){
			$data['autocrm_sms_content'] = null;
		}
		if(isset($data['autocrm_push_toogle']) && $data['autocrm_push_toogle'] == 0){
			$data['autocrm_push_subject'] = null;
			$data['autocrm_push_content'] = null;
			$data['autocrm_push_image'] = null;
			$data['autocrm_push_clickto'] = null;
			$data['autocrm_push_link'] = null;
			$data['autocrm_push_id_reference'] = null;
		}
		if(isset($data['autocrm_inbox_toogle']) && $data['autocrm_inbox_toogle'] == 0){
			$data['autocrm_inbox_subject'] = null;
			$data['autocrm_inbox_content'] = null;
		}
		if(isset($data['autocrm_forward_toogle']) && $data['autocrm_forward_toogle'] == 0){
			$data['autocrm_forward_subject'] = null;
			$data['autocrm_forward_content'] = null;
			$data['autocrm_forward_email'] = null;
		}
		return $data;
	}

	public function updateContentWa($contentWa, $id_autocrm){

		//delete content
		$idOld = array_filter(array_pluck($contentWa,'id_whatsapp_content'));
		$contentOld = WhatsappContent::where('source', 'autocrm')->where('id_reference', $id_autocrm)->whereNotIn('id_whatsapp_content', $idOld)->get();
		if(count($contentOld) > 0){
			// delete file
			foreach($contentOld as $old){
				if($old['content_type'] == 'image' || $old['content_type'] == 'file'){
					$del = MyHelper::deletePhoto(str_replace(config('url.storage_url_api'), '', $old['content']));
				}
			}

			$delete =  WhatsappContent::where('source', 'campaign')->where('id_reference', $query->id_autocrm)->whereNotIn('id_whatsapp_content', $idOld)->delete();
			if(!$delete){
				return 'fail';
			}
		}

		foreach($contentWa as $content){

			if($content['content']){
				//delete file if update
				if($content['id_whatsapp_content']){
					$whatsappContent = WhatsappContent::find($content['id_whatsapp_content']);
					if($whatsappContent && ($whatsappContent->content_type == 'image' || $whatsappContent->content_type == 'file')){
						MyHelper::deletePhoto($whatsappContent->content);
					}
				}

				if($content['content_type'] == 'image'){
					if (!file_exists('whatsapp/img/autocrm/')) {
						mkdir('whatsapp/img/autocrm/', 0777, true);
					}

					//upload file
					$upload = MyHelper::uploadPhoto($content['content'], $path = 'whatsapp/img/autocrm/');
					if ($upload['status'] == "success") {
						$content['content'] = config('url.storage_url_api').$upload['path'];
					} else{
						return 'fail';
					}
				}
				else if($content['content_type'] == 'file'){
					if (!file_exists('whatsapp/file/autocrm/')) {
						mkdir('whatsapp/file/autocrm/', 0777, true);
					}

					$i = 1;
					$filename = $content['content_file_name'];
					while (file_exists('whatsapp/file/autocrm/'.$content['content_file_name'].'.'.$content['content_file_ext'])) {
						$content['content_file_name'] = $filename.'_'.$i;
						$i++;
					}

					$upload = MyHelper::uploadFile($content['content'], $path = 'whatsapp/file/autocrm/', $content['content_file_ext'], $content['content_file_name']);
					if ($upload['status'] == "success") {
						$content['content'] = config('url.storage_url_api').$upload['path'];
					} else{
						return 'fail';
					}
				}

				$dataContent['source'] 		 = 'autocrm';
				$dataContent['id_reference'] = $id_autocrm;
				$dataContent['content_type'] = $content['content_type'];
				$dataContent['content'] 	 = $content['content'];

				//for update
				if($content['id_whatsapp_content']){
					$whatsappContent = WhatsappContent::where('id_whatsapp_content',$content['id_whatsapp_content'])->update($dataContent);
				}
				//for create
				else{
					$whatsappContent = WhatsappContent::create($dataContent);
				}

				if(!$whatsappContent){
					return 'fail';
				}
			}
			
		}

		return 'success';
	}

	public function deleteAutocrmCron(Request $request){
		$delete = Autocrm::where('id_autocrm', $request->json('id_autocrm'))->delete();
		return response()->json(MyHelper::checkDelete($delete));
	}
	
	public function cronAutocrmCron(Request $request){
		$week = $this->getWeek();
		$cronLists = Autocrm::with(['autocrm_rule_parents', 'autocrm_rule_parents.rules'])->where('autocrm_type', 'Cron')
					->where(function ($query) use ($week){
						$query->where('autocrm_trigger', 'Daily')
							->orWhere(function ($query) {
								$query->where('autocrm_trigger', 'Weekly')
									  ->where('autocrm_cron_reference', date('l'));
							})
							->orWhere(function ($query) {
								$query->where('autocrm_trigger', 'Monthly')
									  ->where('autocrm_cron_reference', date('d'));
							})
							->orWhere(function ($query) use ($week){
								$query->where('autocrm_trigger', 'Monthly')
									  ->where('autocrm_cron_reference', 'LIKE', date('l').'%')
									  ->where(DB::raw('SUBSTRING(autocrm_cron_reference, -1, 1)'), $week);
							})
							->orWhere(function ($query) {
								$query->where('autocrm_trigger', 'Yearly')
									  ->where('autocrm_cron_reference', date('dm'));
							});
					})->get();

		$countUser = 0;
		
		foreach ($cronLists as $key => $cronList) {
			$filter = app($this->user)->UserFilter($cronList['autocrm_rule_parents']);
			if($filter['status'] == 'success'){
				$hasil = $filter['result'];
				foreach ($hasil as $key => $datahasil) { 
					$autocrm = app($this->autocrm)->SendAutoCRM($cronList['autocrm_title'], $datahasil['phone'], $variables = null); 
					$countUser = $countUser + 1;
				} 
			}
		}
		
		$result = [ 
			'status'  => 'success', 
			'messages'  => 'Auto CRM has been sent to '.$countUser.' users'
		]; 
		return $result; 
	}

	public function getWeek(){
		$endDate = date('d');
		$i = 2;
		$week = 1;
		while($i<=$endDate){
			if( date('l',strtotime(date('Y-m-'.$i))) == 'Sunday'){
				$week++;
			}
			$i++;
		}
		return $week;
	}

}
