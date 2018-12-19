<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use DB;
use App\Lib\MyHelper;

use App\Http\Models\TransactionSetting;

class ApiSettingCashbackController extends Controller
{
    public function list(Request $request)
    {
        $getlist = TransactionSetting::get()->toArray();
        return response()->json(MyHelper::checkGet($getlist));
    }

    public function update(Request $request)
    {
    	DB::beginTransaction();

    	$post = $request->json()->all();

    	if (empty($post['list'])) {
    		$delete = TransactionSetting::truncate();
    		if (!$delete) {
    			DB::rollBack();
    			return response()->json(['status' => 'fail', 'messages' => ['Update setting cashback failed']]);
    		}

    		DB::commit();
        	return response()->json(['status' => 'success']);
    	}

    	$id_cashback = array_column($post['list'], 'id');
    	$delete = TransactionSetting::get()->toArray();
        if (!empty($delete)) {
            foreach ($delete as $key => $value) {
                if (!in_array($value['id_transaction_setting'], $id_cashback)) {
                    $delete = TransactionSetting::where('id_transaction_setting', $value['id_transaction_setting'])->delete();
                    if (!$delete) {
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'messages' => ['Update setting cashback failed']]);
                    }
                }
            }
        }

        foreach ($post['list'] as $key => $value) {
        	$data = [
        		'cashback_percent' => $value['cashback_percent'],
        		'cashback_maximum' => $value['cashback_maximum']
        	];

        	$update = TransactionSetting::updateOrCreate(['id_transaction_setting' => $value['id']], $data);
            if (!$update) {
                DB::rollBack();
                return response()->json(['status' => 'fail', 'messages' => ['Update setting cashback failed']]);
            }
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }
}
