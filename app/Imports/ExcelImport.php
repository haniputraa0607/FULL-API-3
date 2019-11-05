<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExcelImport implements ToArray,WithHeadingRow
{
    /**
    * @param Array $collection
    */
    public function array(Array $collection)
    {
        return [];
    }
}
