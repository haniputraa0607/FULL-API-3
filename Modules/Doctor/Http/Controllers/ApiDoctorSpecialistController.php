<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorSpecialist;
use Modules\Doctor\Http\Requests\DoctorSpecialistCreate;
use Validator;
use DB;

class ApiDoctorSpecialistController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $specialist = DoctorSpecialist::with('category')->orderBy('created_at', 'DESC');

        $specialist = $specialist->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $specialist]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        DB::beginTransaction();
        if (isset($post['id_doctor_specialist'])) {
            try {
                DoctorSpecialist::where('id_doctor_specialist', $post['id_doctor_specialist'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Specialist Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_doctor_specialist' => $post['id_doctor_specialist']]]);
        } else {
            try {
                $save = DoctorSpecialist::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Specialist Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            //dd($save);
            return response()->json(['status'  => 'success', 'result' => ['doctor_specialist_name' => $post['doctor_specialist_name'], 'created_at' => $save->created_at]]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $specialist = DoctorSpecialist::where('id_doctor_specialist', $id)->first();

        return response()->json(['status'  => 'success', 'result' => $specialist]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            $id_doctor_specialist = $request->json('id_doctor_specialist');
            $specialist_pivot_exists = DoctorSpecialist::join('doctors_specialists_pivots', 'doctors_specialists.id_doctor_specialist', '=', 'doctors_specialists_pivots.id_doctor_specialist')
            ->where('doctors_specialists.id_doctor_specialist', $id_doctor_specialist)->exists();

            if ($specialist_pivot_exists) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Cannot delete the specialist that has been assigned to the specialist']
                ]);
            }
            $doctorSpecialist = DoctorSpecialist::where('id_doctor_specialist', $id_doctor_specialist)->first();
            $delete = $doctorSpecialist->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Specialist has been used.']
            ]);
        }
    }
}
