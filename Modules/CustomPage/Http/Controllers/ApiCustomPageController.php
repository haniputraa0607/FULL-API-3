<?php

namespace Modules\CustomPage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\CustomPage\Entities\CustomPage;
use Modules\CustomPage\Entities\CustomPageImage;
use Modules\CustomPage\Entities\CustomPageOutlet;
use Modules\CustomPage\Entities\CustomPageProduct;
use App\Lib\MyHelper;
use DB;

class ApiCustomPageController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $customPage = CustomPage::orderBy('custom_page_order')->get()->toArray();

        foreach ($customPage as $key => $value) {
            if ($value['custom_page_order'] == null || $value['custom_page_order'] == 0) {
                $nullOrZero[] = $customPage[$key];
                unset($customPage[$key]);
            } else {
                $nullOrZero[] = null;
            }
        }

        foreach ($nullOrZero as $key => $value) {
            if ($value == null) {
                unset($nullOrZero[$key]);
            }
        }

        $dataMerge = array_merge($customPage, $nullOrZero);

        return response()->json(['status'  => 'success', 'result' => $dataMerge]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();

        $request->validate([
            'custom_page_title'         => 'required',
            'custom_page_menu'         => 'required',
            'custom_page_description'   => 'required'
        ]);

        if (isset($post['custom_page_title'])) {
            $data['custom_page_title'] = $post['custom_page_title'];
        }
        if (isset($post['custom_page_menu'])) {
            $data['custom_page_menu'] = $post['custom_page_menu'];
        }
        if (isset($post['custom_page_description'])) {
            $data['custom_page_description'] = $post['custom_page_description'];
        }

        if (isset($post['custom_page_icon_image'])) {
            $upload = MyHelper::uploadPhoto($post['custom_page_icon_image'], $path = 'img/custom-page/icon/', 600);
            if ($upload['status'] == "success") {
                $data['custom_page_icon_image'] = $upload['path'];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['fail upload image']
                ];
                return response()->json($result);
            }
        }

        if (isset($post['custom_page_video_text'])) {
            $data['custom_page_video_text'] = $post['custom_page_video_text'];
        } else {
            $data['custom_page_video_text'] = null;
        }

        if (isset($post['custom_page_video'])) {
            $youtube = MyHelper::parseYoutube($post['custom_page_video']);
            if ($youtube['status'] == 'success') {
                $data['custom_page_video'] = $youtube['data'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['url youtube not valid.']
                ]);
            }
        } else {
            $data['custom_page_video'] = null;
        }

        if (isset($post['custom_page_event_date_start'])) {
            $data['custom_page_event_date_start'] = $post['custom_page_event_date_start'];
        } else {
            $data['custom_page_event_date_start'] = null;
        }

        if (isset($post['custom_page_event_date_end'])) {
            $data['custom_page_event_date_end'] = $post['custom_page_event_date_end'];
        } else {
            $data['custom_page_event_date_end'] = null;
        }

        if (isset($post['custom_page_button_form_text'])) {
            $data['custom_page_button_form_text'] = $post['custom_page_button_form_text'];
        } else {
            $data['custom_page_button_form_text'] = null;
        }

        if (isset($post['custom_page_event_time_start'])) {
            $data['custom_page_event_time_start'] = $post['custom_page_event_time_start'];
        } else {
            $data['custom_page_event_time_start'] = null;
        }

        if (isset($post['custom_page_event_time_end'])) {
            $data['custom_page_event_time_end'] = $post['custom_page_event_time_end'];
        } else {
            $data['custom_page_event_time_end'] = null;
        }

        if (isset($post['custom_page_event_location_name'])) {
            $data['custom_page_event_location_name'] = $post['custom_page_event_location_name'];
        } else {
            $data['custom_page_event_location_name'] = null;
        }

        if (isset($post['custom_page_event_location_phone'])) {
            $data['custom_page_event_location_phone'] = $post['custom_page_event_location_phone'];
        } else {
            $data['custom_page_event_location_phone'] = null;
        }

        if (isset($post['custom_page_event_location_address'])) {
            $data['custom_page_event_location_address'] = $post['custom_page_event_location_address'];
        } else {
            $data['custom_page_event_location_address'] = null;
        }

        if (isset($post['custom_page_event_location_map'])) {
            $data['custom_page_event_location_map'] = $post['custom_page_event_location_map'];
        } else {
            $data['custom_page_event_location_map'] = null;
        }

        if (isset($post['custom_page_event_latitude'])) {
            $data['custom_page_event_latitude'] = $post['custom_page_event_latitude'];
        } else {
            $data['custom_page_event_latitude'] = null;
        }

        if (isset($post['custom_page_event_longitude'])) {
            $data['custom_page_event_longitude'] = $post['custom_page_event_longitude'];
        } else {
            $data['custom_page_event_longitude'] = null;
        }

        if (isset($post['custom_page_outlet_text'])) {
            $data['custom_page_outlet_text'] = $post['custom_page_outlet_text'];
        } else {
            $data['custom_page_outlet_text'] = null;
        }

        if (isset($post['id_outlet'])) {
            $outlet = $post['id_outlet'];
        } else {
            $outlet = null;
        }

        if (isset($post['custom_page_product_text'])) {
            $data['custom_page_product_text'] = $post['custom_page_product_text'];
        } else {
            $data['custom_page_product_text'] = null;
        }

        if (isset($post['id_product'])) {
            $product = $post['id_product'];
        } else {
            $product = null;
        }

        if (isset($post['custom_page_button_form'])) {
            $data['custom_page_button_form'] = $post['custom_page_button_form'];
        } else {
            $data['custom_page_button_form'] = null;
        }

        if (isset($post['custom_page_button_form_text'])) {
            $data['custom_page_button_form_text'] = $post['custom_page_button_form_text'];
        } else {
            $data['custom_page_button_form_text'] = null;
        }

        if (isset($post['customform'])) {
            foreach ($post['customform'] as $key => $value) {
                if ($key != 'custom_page_image_header' && $value == null) {
                    unset($post['customform'][$key]);
                } else {
                    if (explode('=', $value['custom_page_image_header'])[0] == 'id_image_header') {
                        $customform[] = (int) explode('=', $value['custom_page_image_header'])[1];
                    } else {
                        $upload = MyHelper::uploadPhoto($value['custom_page_image_header'], $path = 'img/custom-page/image/', 600);
                        if ($upload['status'] == "success") {
                            $customform[] = $upload['path'];
                        } else {
                            $result = [
                                'status'    => 'fail',
                                'messages'    => ['fail upload image']
                            ];
                            return response()->json($result);
                        }
                    }
                }
            }
        } else {
            $customform = null;
        }

        DB::beginTransaction();

        if (isset($post['id_custom_page'])) {
            try {
                $updateCustomPage = CustomPage::where('id_custom_page', $post['id_custom_page'])->update($data);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'messages' => 'Update Custom Page Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }

            if ($customform != null) {
                foreach ($customform as $key => $value) {
                    if (!is_int($value)) {
                        CustomPageImage::create(['id_custom_page' => $post['id_custom_page'], 'custom_page_image' => $value, 'image_order' => $key + 1]);
                    } else {
                        CustomPageImage::where('id_custom_page_image', $value)->update(['image_order' => $key + 1]);
                    }
                }
            }

            if ($outlet != null) {
                foreach ($outlet as $key => $value) {
                    CustomPageOutlet::updateOrCreate(['id_custom_page' => $post['id_custom_page'], 'id_outlet' => $value]);
                }
            }

            if ($product != null) {
                foreach ($product as $key => $value) {
                    CustomPageProduct::updateOrCreate(['id_custom_page' => $post['id_custom_page'], 'id_product' => $value]);
                }
            }

            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_custom_page' => $post['id_custom_page']]]);
        } else {
            try {
                $insertCustomPage = CustomPage::create($data);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'messages' => 'Create Custom Page Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }

            if ($customform != null) {
                foreach ($customform as $key => $value) {
                    CustomPageImage::create(['id_custom_page' => $insertCustomPage['id_custom_page'], 'custom_page_image' => $value, 'image_order' => $key + 1]);
                }
            }

            if ($outlet != null) {
                foreach ($outlet as $key => $value) {
                    CustomPageOutlet::create(['id_custom_page' => $insertCustomPage['id_custom_page'], 'id_outlet' => $value]);
                }
            }

            if ($product != null) {
                foreach ($product as $key => $value) {
                    CustomPageProduct::create(['id_custom_page' => $insertCustomPage['id_custom_page'], 'id_product' => $value]);
                }
            }
        }

        DB::commit();
        return response()->json(['status'  => 'success', 'result' => ['id_custom_page' => $insertCustomPage['id_custom_page']]]);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show(Request $request)
    {
        $post = $request->json()->all();

        $customPage = CustomPage::with(['custom_page_image_header', 'custom_page_outlet.outlet', 'custom_page_product.product'])->where('id_custom_page', $post['id_custom_page'])->first();

        return response()->json(['status'  => 'success', 'result' => $customPage]);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        CustomPage::where('id_custom_page', $post['id_custom_page'])->delete();

        return response()->json(['status'  => 'success']);
    }

    public function listCustomPage()
    {
        $customPage = CustomPage::orderBy('custom_page_order')->get()->toArray();

        foreach ($customPage as $key => $value) {
            if ($value['custom_page_order'] == null || $value['custom_page_order'] == 0) {
                $nullOrZero[] = $customPage[$key];
                unset($customPage[$key]);
            } else {
                $nullOrZero[] = null;
            }
        }

        foreach ($nullOrZero as $key => $value) {
            if ($value == null) {
                unset($nullOrZero[$key]);
            }
        }

        $dataMerge = array_merge($customPage, $nullOrZero);

        $result = [];
        if ($dataMerge) {
            foreach ($dataMerge as $key => $value) {
                $result[$key]['url']            = env("API_URL") . 'api/custom-page/webview/' . $value['id_custom_page'];
                $result[$key]['title']          = $value['custom_page_title'];
                $result[$key]['icon_image']     = env("S3_URL_API") . $value['custom_page_icon_image'];
            }
        }

        return response()->json(['status'  => 'success', 'result' => $result]);
    }

    public function webviewCustomPage(Request $request, $id_custom_page)
    {
        $bearer = $request->header('Authorization');

        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }

        $customPage = CustomPage::with(['custom_page_image_header', 'custom_page_outlet.outlet', 'custom_page_product.product'])->where('id_custom_page', $id_custom_page)->first();
        
        if ($customPage) {
            $data['result'] = $customPage;
            
            $data['result']['custom_page_button_form_text_button'] = json_decode($customPage['custom_page_button_form_text'], true)['button'];
            $data['result']['custom_page_button_form_text_value'] = json_decode($customPage['custom_page_button_form_text'], true)['value'];
            
            return view('custompage::webview.information', $data);
        } else {
            return view('custompage::webview.information', ['result' => null]);
        }
    }
}
