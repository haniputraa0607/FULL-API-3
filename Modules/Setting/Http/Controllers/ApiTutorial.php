<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Http\Models\Setting;
use App\Http\Models\User;

class ApiTutorial extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->user     = "Modules\Users\Http\Controllers\ApiUser";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function introList(Request $request)
    {
        $data = $request->json()->all();

        if (isset($data['key']))
            $intro = Setting::where('key', $data['key'])->first();

        if (!$intro) {
            $intro = Setting::create([
                'key' => $data['key'], 'value' => json_encode([
                    'active'        => 0,
                    'skippable'     => 0,
                    'text_next'     => 'Selanjutnya',
                    'text_skip'     => 'Lewati',
                    'text_last'     => 'Mulai'
                ])
            ]);
        }

        return response()->json(MyHelper::checkGet($intro));
    }

    public function introSave(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['value_text'])) {
            foreach ($post['value_text'] as $value) {
                if (explode('=', $value)[0] == 'value') {
                    $value_text[] = explode('=', $value)[1];
                } else {
                    $upload = MyHelper::uploadPhotoStrict($value, $path = 'img/intro/', 1080, 1920);
                    if ($upload['status'] == "success") {
                        $value_text[] = $upload['path'];
                    } else {
                        $result = [
                            'status'    => 'fail',
                            'messages'    => ['fail upload image']
                        ];
                        return response()->json($result);
                    }
                }
            }
            $post['value_text'] = json_encode($value_text);
        } else {
            $value_text = null;
            $post['value_text'] = json_encode($value_text);
        }

        $insert = Setting::updateOrCreate(['key' => $post['key']], $post);

        return response()->json(MyHelper::checkCreate($insert));
    }

    public function introListFrontend(Request $request)
    {
        $post = $request->json()->all();

        if(!isset($post['key'])){
            $post['key'] = 'intro_home';
        }
        $data = Setting::where('key', $post['key'])->first();
        
        if (!$data) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => 'Tutorial belum di setup'
            ]);
        }

        $list = json_decode($data->value, true);
        
        if ($data->value_text != 'null') {
            foreach (json_decode($data->value_text, true) as $key => $value) {
                $list['image'][$key] = config('url.storage_url_api') . $value;
            }
        } else {
            $list['image'] = [];
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function introHomeFrontend(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if ($user['status_new_user'] == 1) {

            $data = Setting::where('key', $post['key'])->first();
    
            if ($data) {
                User::where('id', $user['id'])->update(['status_new_user' => 0]);
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => 'Tutorial belum di setup'
                ]);
            }
            
            $list = json_decode($data->value, true);
        
            if ($data->value_text != 'null') {
                foreach (json_decode($data->value_text, true) as $key => $value) {
                    $list['image'][$key] = config('url.storage_url_api') . $value;
                }
            } else {
                $list['image'] = [];
            }
        } else {
            $list['active'] = 0;
        }
        
        return response()->json(MyHelper::checkGet($list));
    }
}
