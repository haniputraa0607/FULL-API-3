<?php

namespace Modules\KitchenDisplay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use App\Http\Models\Transaction;

class ApiKitchenDisplayController extends Controller
{
    public function show(Request $request) 
    {
        $token = $request->bearerToken();
        $outletToken = OutletToken::where('token', $token)->first();
        if(empty($outletToken))
            return response()->json(['error' => 'Unauthenticated: invalid token.'], 401);
        else
            $outletToken = $outletToken->toArray();
        $idOutlet = $outletToken['id_outlet'];
        $result = Transaction::where('id_outlet', $idOutlet)
            ->whereDate('created_at', Carbon::today())
            ->where('transaction_payment_status', 'Completed')
            ->with('transaction_pickup')
            ->whereHas('transaction_pickup', function($query){
                return $query->whereNotNull('receive_at')->whereNull('taken_at');
            })
            ->get()->toArray();
        return response()->json($result, 200);
    }
}
