<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CdrCOntroller extends Controller
{
    public function getByDate(Request $request){

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = DB::table('pkg_cdr')
        ->whereBetween('starttime',[$startDate,$endDate])
        ->get();

        return response()->json($data);
    }
}
