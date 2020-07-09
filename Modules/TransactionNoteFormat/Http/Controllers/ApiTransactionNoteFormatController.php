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
    public function getHeaderPlain() 
    {
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', 'header')->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        return response()->json(['content' => $transactionNoteFormat['content']], 200);
    }

    public function getHeader($outletId) {
        $outlet = Outlet::where('id_outlet', $outletId)->first();
        if(empty($outlet))
            return response()->json(['error' => 'Outlet ID not found'], 200);
        $outlet = $outlet->toArray();
        $columnList = Schema::getColumnListing('outlets');
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', 'header')->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        $content = $transactionNoteFormat['content'];
        foreach($columnList as $column)
            if(array_key_exists($column, $outlet))
                $content = str_replace('%'.$column.'%', $outlet[$column], $content);
        return response()->json(['content' => $content], 200);
    }

    public function setHeader(Request $request) 
    {
        if(!$request->has('content'))
            return response()->json(['error' => 'Bad request: please provide content.'], 400);
        $content = $request->get('content');
        TransactionNoteFormat::updateOrCreate(['format_type' => 'header'], ['content' => $content]);
        return response()->json(['message' => 'Setting note format successful.'], 200);
    }

    public function getFooterPlain() 
    {
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', 'footer')->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        return response()->json(['content' => $transactionNoteFormat['content']], 200);
    }

    public function getFooter($outletId) {
        $outlet = Outlet::where('id_outlet', $outletId)->first();
        if(empty($outlet))
            return response()->json(['error' => 'Outlet ID not found'], 200);
        $outlet = $outlet->toArray();
        $columnList = Schema::getColumnListing('outlets');
        $transactionNoteFormat = TransactionNoteFormat::where('format_type', 'footer')->first();
        if(empty($transactionNoteFormat))
            return response()->json(['content' => ''], 200);
        $transactionNoteFormat = $transactionNoteFormat->toArray();
        $content = $transactionNoteFormat['content'];
        foreach($columnList as $column)
            if(array_key_exists($column, $outlet))
                $content = str_replace('%'.$column.'%', $outlet[$column], $content);
        return response()->json(['content' => $content], 200);
    }

    public function setFooter(Request $request) 
    {
        if(!$request->has('content'))
            return response()->json(['error' => 'Bad request: please provide content.'], 400);
        $content = $request->get('content');
        TransactionNoteFormat::updateOrCreate(['format_type' => 'footer'], ['content' => $content]);
        return response()->json(['message' => 'Setting note format successful.'], 200);
    }
}
