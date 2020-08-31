<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


class SummaryCalculationFeeBladeExport implements FromView
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('disburse::disburse.detail_export', [
            'summary_product' => $this->data['summary_product'],
            'report_product' => $this->data['report_product'],
            'config' => $this->data['config']
        ]);
    }
}