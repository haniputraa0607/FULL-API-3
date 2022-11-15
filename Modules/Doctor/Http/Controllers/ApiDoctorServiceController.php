<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorService;
use Modules\Doctor\Http\Requests\DoctorServiceCreate;
use Validator;
use DB;

class ApiDoctorServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $service = DoctorService::orderBy('created_at', 'DESC');

        $service = $service->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $service]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(DoctorServiceCreate $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        DB::beginTransaction();
        if (isset($post['id_doctor_service'])) {
            try {
                DoctorService::where('id_doctor_service', $post['id_doctor_service'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Service Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_doctor_service' => $post['id_doctor_service']]]);
        } else {
            try {
                $save = DoctorService::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Service Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            //dd($save);
            return response()->json(['status'  => 'success', 'result' => ['doctor_service_name' => $post['doctor_service_name'], 'created_at' => $save->created_at]]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $service = DoctorService::where('id_doctor_service', $id)->first();

        return response()->json(['status'  => 'success', 'result' => $service]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            $id_doctor_service = $request->json('id_doctor_service');
            $doctorService = DoctorService::where('id_doctor_service', $id_doctor_service)->first();
            $delete = $doctorService->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Service has been used.']
            ]);
        }
    }
}
