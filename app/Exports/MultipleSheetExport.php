<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultipleSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $key => $value) {
            if($key == 'Summary'){
                $sheets[] = new SummaryTrxBladeExport($value);
            }elseif ($key == 'Calculation Fee'){
                $sheets[] = new CalculationFeeBladeExport($value);
            }elseif ($key == 'Detail Transaction'){
                $sheets[] = new TransactionBladeExport($value);
            }else{
                $sheets[] = $value;
            }
        }

        return $sheets;
    }
}
