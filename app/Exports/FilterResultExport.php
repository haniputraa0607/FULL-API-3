<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use App\Lib\MyHelper;

class FilterResultExport implements FromArray,  ShouldAutoSize, WithEvents, WithTitle, WithColumnFormatting
{
    use Exportable;
    protected $data;
	protected $title;
    protected $filter;
    protected $padding;
    protected $header;
    protected $columnFormats;

	public function __construct($data, $filter, $title = '', $columnFormats = null)
	{
		$this->data = $data;
        $this->title = $title;
        $this->filter = $filter;
        $this->columnFormats = $columnFormats;

        $this->header[] = ['Filter applied'];
        if (is_array($this->filter['rule']) && $this->filter['rule']) {
            $this->header[] = ['Valid when all conditions are met'];
            foreach ($this->filter['rule']??[] as $rule) {
                if (!isset($rule['parameter']) || is_null($rule['parameter']) || !($rule['hide']??'')) {
                    continue;
                }
                $this->header[] = [
                    $rule['subject'],
                    $rule['operator'] ?? '=',
                    $rule['parameter']
                ];
            }
            if (($this->filter['operator']??'and') == 'or') {
                $this->header[] = ['Valid when minimum one condition is met'];
                $this->padding += 1;
            }
            foreach ($this->filter['rule']??[] as $rule) {
                if (!isset($rule['parameter']) || is_null($rule['parameter']) || ($rule['hide']??'')) {
                    continue;
                }
                $this->header[] = [
                    $rule['subject'],
                    $rule['operator'] ?? '=',
                    $rule['parameter']
                ];
            }
        } else {
            $this->header[] = ['No Filter applied'];
        }
        $this->header[] = [''];

        $this->padding = 3 + count($this->header);
	}

    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
        $header = [
            [$this->title],
            [''],
        ];

        $header = array_merge($header, $this->header);

        $header[] = array_keys($this->data[0]??[]);
        return array_merge($header, $this->data);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        if (!count($this->data[0]??[])) {
            return [];
        }
        $padding_top = $this->padding;
        return [
            AfterSheet::class    => function(AfterSheet $event) use ($padding_top) {
                $last = count($this->data);
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]
                    ],
                ];
                $styleHead = [
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'rotation' => 90,
                        'startColor' => [
                            'argb' => 'FFA0A0A0',
                        ],
                        'endColor' => [
                            'argb' => 'FFFFFFFF',
                        ],
                    ],
                ];
                $x_coor = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->data[0]??[]));
                $event->sheet->getStyle('A'. $padding_top .':'.$x_coor.($last+$padding_top))->applyFromArray($styleArray);
                $headRange = 'A'.$padding_top.':'.$x_coor.$padding_top;
                $event->sheet->getStyle($headRange)->applyFromArray($styleHead);
                $event->sheet->mergeCells('A1:I1');
                $event->sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $event->sheet->mergeCells('A3:C3');
                $event->sheet->getStyle('A3:C3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'rotation' => 90,
                        'startColor' => [
                            'argb' => 'FFA0A0A0',
                        ],
                        'endColor' => [
                            'argb' => 'FFFFFFFF',
                        ],
                    ],
                ]);            
                $event->sheet->getStyle('A3:C'.($padding_top - 2))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]
                    ],
                ]);            
            },
        ];
    }

    public function columnFormats(): array
    {
        if ($this->columnFormats) {
            return $this->columnFormats;
        }
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => '#,##0',
            'E' => '"Rp "#,##0',
            'F' => '"Rp "#,##0',
        ];
    }
}
