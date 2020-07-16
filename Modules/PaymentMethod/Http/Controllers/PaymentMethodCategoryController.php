<?php

namespace Modules\PaymentMethod\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\PaymentMethodCategory;
use App\Lib\MyHelper;

class PaymentMethodCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $data = PaymentMethodCategory::all();
        return response()->json(MyHelper::checkGet($data));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post  = $request->json()->all();
        $save = PaymentMethodCategory::create($post);

        return MyHelper::checkCreate($save);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('paymentmethod::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $data = PaymentMethodCategory::find($id);

        return response()->json(MyHelper::checkGet($data));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $post  = $request->json()->all();
        $save = PaymentMethodCategory::find($id)->update($post);

        return MyHelper::checkUpdate($save);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $category = PaymentMethodCategory::find($id);

        if($category){
            return MyHelper::checkDelete($category->delete());
        }

        return response()->json(['status' => 'fail']);
    }
}
