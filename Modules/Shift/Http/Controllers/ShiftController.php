<?php

namespace Modules\Shift\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ShiftController extends Controller
{
    public function start_shift(Request $request){
        return $request->all();
    }

    public function end_shift(Request $request){

    }

}
