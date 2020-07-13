<?php

namespace Modules\TransactionNoteFormat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use App\Http\Models\TransactionNoteFormat;
use App\Http\Models\Outlet;

class ApiTransactionNoteFormatController extends Controller
{
    public function getPlain($format_type)
    {
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', $format_type)->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        return response()->json(['content' => $transactionNoteFormat['content']], 200);
    }

    public function get($format_type, $id_outlet) {
        $outlet = Outlet::where('id_outlet', $id_outlet)->first();
        if(empty($outlet))
            return response()->json(['error' => 'Outlet ID not found'], 200);
        $outlet = $outlet->toArray();
        $columnList = Schema::getColumnListing('outlets');
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', $format_type)->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        $content = $transactionNoteFormat['content'];
        foreach($columnList as $column)
            if(array_key_exists($column, $outlet))
                $content = str_replace('%'.$column.'%', $outlet[$column], $content);
        return response()->json(['content' => $content], 200);
    }

    public function set(Request $request, $format_type) 
    {
        if(!$request->has('content'))
            return response()->json(['error' => 'Bad request: please provide content.'], 400);
        $content = $request->get('content');
        TransactionNoteFormat::updateOrCreate(['format_type' => $format_type], ['content' => $content]);
        return response()->json(['message' => 'Setting note format successful.'], 200);
    }
}
