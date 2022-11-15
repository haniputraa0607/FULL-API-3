<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorClinic;
use Modules\Doctor\Http\Requests\DoctorClinicCreate;
use Validator;
use DB;

class ApiDoctorClinicController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $clinic = DoctorClinic::orderBy('created_at', 'DESC');

        $clinic = $clinic->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $clinic]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(DoctorClinicCreate $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        DB::beginTransaction();
        if (isset($post['id_doctor_clinic'])) {
            try {
                DoctorClinic::where('id_doctor_clinic', $post['id_doctor_clinic'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Clinic Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_doctor_clinic' => $post['id_doctor_clinic']]]);
        } else {
            try {
                $save = DoctorClinic::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Clinic Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['doctor_clinic_name' => $post['doctor_clinic_name'], 'created_at' => $save->created_at]]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $clinic = DoctorClinic::where('id_doctor_clinic', $id)->first();

        return response()->json(['status'  => 'success', 'result' => $clinic]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            $id_doctor_clinic = $request->json('id_doctor_clinic');
            $clinic_exists = DoctorClinic::join('doctors', 'doctors.id_doctor_clinic', '=', 'doctors_clinics.id_doctor_clinic')
            ->where('doctors_clinics.id_doctor_clinic', $id_doctor_clinic)->exists();

            if ($clinic_exists) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Cannot delete the clinic that has been assigned to the doctor']
                ]);
            }
            $doctorClinic = DoctorClinic::where('id_doctor_clinic', $id_doctor_clinic)->first();
            $delete = $doctorClinic->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Clinic has been used.']
            ]);
        }
    }
}
