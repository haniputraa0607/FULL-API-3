<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\ExcelImport;

class FirstSheetOnlyImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new ExcelImport()
        ];
    }
}
