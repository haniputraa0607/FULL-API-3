<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

class ApiOutletWebview extends Controller
{
    public function detailWebview(Request $request, $id)
    {
    	$bearer = $request->header('Authorization');
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }

        // if ($request->isMethod('get')) {
        //     return view('error', ['msg' => 'Url method is POST']);
        // }
        
        $list = MyHelper::postCURLWithBearer('api/outlet/list?log_save=0', ['id_outlet' => $id], $bearer);
        // return $list;
        return view('outlet::webview.list', ['data' => $list['result']]);
    }

    public function listOutletGofood(Request $request)
    {
    	$bearer = $request->header('Authorization');
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }

        // if ($request->isMethod('get')) {
        //     return view('error', ['msg' => 'Url method is POST']);
        // }

    	$post = $request->all();
    	// $post['latitude'] = '-7.803761';
     //    $post['longitude'] = '110.383058';
        $post['sort'] = 'Nearest';
        $post['type'] = 'transaction';
        $post['search'] = '';
    	$post['gofood'] = 1;
        // return $post;
        // $list = MyHelper::post('outlet/filter', $post);
        $list = MyHelper::postCURLWithBearer('api/outlet/filter/gofood', $post, $bearer);
        // return $list;
        if (isset($list['status']) && $list['status'] == 'success') {
            return view('outlet::webview.outlet_gofood_v2', ['outlet' => $list['result']]);
        } elseif (isset($list['status']) && $list['status'] == 'fail') {
            return view('error', ['msg' => 'Data failed']);
        } else {
            return view('error', ['msg' => 'Something went wrong, try again']);
        }
    }
}
