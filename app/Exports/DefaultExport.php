<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DefaultExport implements FromArray, WithHeadings, ShouldAutoSize
{
	public function _construct($data)
	{
		$this->data = $data;
	}
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
        return $data;
    }
    public function headings(): array
    {
        return array_keys($this->data[0]??[]);
    	// return array_map(function($x){return ucwords(str_replace('_', ' ', $x));}, array_keys($this->outlets[0]??[]));
    }
}
