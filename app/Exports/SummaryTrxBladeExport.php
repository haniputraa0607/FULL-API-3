<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;


class SummaryTrxBladeExport implements FromView, WithTitle
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('disburse::detail_export', [
            'summary_product' => $this->data['summary_product'],
            'summary_fee' => $this->data['summary_fee'],
            'config' => $this->data['config']
        ]);
    }

    public function title(): string
    {
        return 'Summary';
    }
}