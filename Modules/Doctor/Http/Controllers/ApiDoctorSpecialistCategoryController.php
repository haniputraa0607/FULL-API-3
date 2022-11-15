<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Doctor\Entities\DoctorSpecialistCategory;
use Modules\Doctor\Http\Requests\DoctorSpecialistCategoryCreate;
use Validator;
use DB;

class ApiDoctorSpecialistCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $specialist_category = DoctorSpecialistCategory::orderBy('created_at', 'DESC');

        $specialist_category = $specialist_category->get()->toArray();

        return response()->json(['status'  => 'success', 'result' => $specialist_category]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(DoctorSpecialistCategoryCreate $request)
    {
        $post = $request->json()->all();
        unset($post['_token']);

        DB::beginTransaction();
        if (isset($post['id_doctor_specialist_category'])) {
            try {
                DoctorSpecialistCategory::where('id_doctor_specialist_category', $post['id_doctor_specialist_category'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Specialist Category Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_doctor_specialist_category' => $post['id_doctor_specialist_category']]]);
        } else {
            try {
                $save = DoctorSpecialistCategory::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Specialist Category Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            //dd($save);
            return response()->json(['status'  => 'success', 'result' => ['doctor_specialist_category_name' => $post['doctor_specialist_category_name'], 'created_at' => $save->created_at]]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $specialist_category = DoctorSpecialistCategory::where('id_doctor_specialist_category', $id)->first();

        return response()->json(['status'  => 'success', 'result' => $specialist_category]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        try {
            $id_doctor_specialist_category = $request->json('id_doctor_specialist_category');
            $specialist_exists = DoctorSpecialistCategory::join('doctors_specialists', 'doctors_specialists.id_doctor_specialist_category', '=', 'doctors_specialists_categories.id_doctor_specialist_category')
            ->where('doctors_specialists_categories.id_doctor_specialist_category', $id_doctor_specialist_category)->exists();

            if ($specialist_exists) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Cannot delete the category that has been assigned to the specialist']
                ]);
            }
            $doctorSpecialistCategory = DoctorSpecialistCategory::where('id_doctor_specialist_category', $id_doctor_specialist_category)->first();
            $delete = $doctorSpecialistCategory->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Specialist Category has been used.']
            ]);
        }
    }
}
