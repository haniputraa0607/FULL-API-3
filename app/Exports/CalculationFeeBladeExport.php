<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class CalculationFeeBladeExport implements FromView, WithTitle
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('disburse::detail_calculation_fee', [
            'data' => (isset($this->data['show_another_income']) ? $this->data['data'] : $this->data),
            'show_another_income' => $this->data['show_another_income'] ?? 0
        ]);
    }

    public function title(): string
    {
        return 'Calculation Fee';
    }
}
