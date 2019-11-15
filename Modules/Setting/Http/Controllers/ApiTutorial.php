<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Http\Models\Setting;

class ApiTutorial extends Controller
{
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
                    $upload = MyHelper::uploadPhoto($value, $path = 'img/intro/', 1080);
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
        
        if (isset($post['key'])) {
            $data = Setting::where('key', $post['key'])->get()->toArray()[0];
        } else {
            $data = Setting::where('key', 'intro_home')->get()->toArray()[0];
        }
        
        $list = json_decode($data['value'], true);
        foreach (json_decode($data['value_text']) as $key => $value) {
            $list['image'][$key] = env('S3_URL_API') . $value;
        }

        return response()->json(MyHelper::checkGet($list));
    }
}
