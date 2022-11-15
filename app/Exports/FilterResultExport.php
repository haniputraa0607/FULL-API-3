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
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariant;
use App\Http\Models\Outlet;
use Modules\Brand\Entities\Brand;

class FilterResultExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle, WithColumnFormatting
{
    use Exportable;

    protected $data;
    protected $title;
    protected $filter;
    protected $padding;
    protected $header;
    protected $columnFormats;
    protected $loadedData = [];

    public function __construct($data, $filter, $title = '', $columnFormats = null)
    {
        if (!$data) {
            $data = [['Result' => 'No data found']];
        }
        $this->data = $data;
        $this->title = $title;
        $this->filter = $filter;
        $this->columnFormats = $columnFormats;

        $this->header[] = ['Filter applied'];
        if (is_array($this->filter['rule']) && $this->filter['rule']) {
            $this->header[] = ['Valid when all conditions are met'];
            foreach ($this->filter['rule'] ?? [] as $rule) {
                if (!isset($rule['parameter']) || is_null($rule['parameter']) || !($rule['hide'] ?? '')) {
                    continue;
                }
                $this->header[] = $this->filterPrettier([
                    $rule['subject'],
                    $rule['operator'] ?? '=',
                    $rule['parameter']
                ]);
            }
            if (($this->filter['operator'] ?? 'and') == 'or') {
                $this->header[] = ['Valid when minimum one condition is met'];
                $this->padding += 1;
            }
            foreach ($this->filter['rule'] ?? [] as $rule) {
                if (!isset($rule['parameter']) || is_null($rule['parameter']) || ($rule['hide'] ?? '')) {
                    continue;
                }
                $this->header[] = $this->filterPrettier([
                    $rule['subject'],
                    $rule['operator'] ?? '=',
                    $rule['parameter']
                ]);
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

        $header[] = array_keys($this->data[0] ?? []);
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
        if (!count($this->data[0] ?? [])) {
            return [];
        }
        $padding_top = $this->padding;
        return [
            AfterSheet::class    => function (AfterSheet $event) use ($padding_top) {
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
                $x_coor = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->data[0] ?? []));
                $event->sheet->getStyle('A' . $padding_top . ':' . $x_coor . ($last + $padding_top))->applyFromArray($styleArray);
                $headRange = 'A' . $padding_top . ':' . $x_coor . $padding_top;
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
                $event->sheet->getStyle('A3:C' . ($padding_top - 2))->applyFromArray([
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

    protected function loadOnce($key, $closures)
    {
        return function () use ($key, $closures) {
            if (!isset($this->loadedData[$key])) {
                $this->loadedData[$key] = $closures();
            }
            return $this->loadedData[$key];
        };
    }

    protected function filterPrettier($rule)
    {
        $filters = [
            'id_outlet' => [
                'label' => 'Outlet',
                'data' => $this->loadOnce('id_outlets', function () {
                    $itemRaw = Outlet::select('id_outlet as id_item', 'outlet_name as item_name')->get();
                    $items = [];
                    $itemRaw->each(function ($item) use (&$items) {
                        $items[$item->id_item] = $item->item_name;
                    });
                    return $items;
                })
            ],
            'id_product' => [
                'label' => 'Product',
                'data' => $this->loadOnce('id_products', function () {
                    $productRaw = Product::select('id_product', 'product_name')->get();
                    $products = [];
                    $productRaw->each(function ($item) use (&$products) {
                        $products[$item->id_product] = $item->product_name;
                    });
                    return $products;
                })
            ],
            'transaction_date' => [
                'label' => 'Date',
            ],
            'id_brand' => [
                'label' => 'Brand',
                'data' => $this->loadOnce('id_brands', function () {
                    $itemRaw = Brand::select('id_brand as id_item', 'name_brand as item_name')->get();
                    $items = [];
                    $itemRaw->each(function ($item) use (&$items) {
                        $items[$item->id_item] = $item->item_name;
                    });
                    return $items;
                })
            ],
            'id_product_category' => [
                'label' => 'Category',
                'data' => $this->loadOnce('id_product_categories', function () {
                    $itemRaw = ProductCategory::select('id_product_category as id_item', 'product_category_name as item_name')->get();
                    $items = [];
                    $itemRaw->each(function ($item) use (&$items) {
                        $items[$item->id_item] = $item->item_name;
                    });
                    return $items;
                })
            ],
            'id_product_variant_group' => [
                'label' => 'Product Variant Group Code',
                'data' => $this->loadOnce('id_product_variant_groups', function () {
                    $itemRaw = ProductVariantGroup::select('id_product_variant_group as id_item', 'product_variant_group_code as item_name')->get();
                    $items = [];
                    $itemRaw->each(function ($item) use (&$items) {
                        $items[$item->id_item] = $item->item_name;
                    });
                    return $items;
                })
            ],
            'id_product_variants' => [
                'label' => 'Product Variant',
                'render' => function ($value) {
                    $variants = $this->loadOnce('id_product_variants', function () {
                        $itemRaw = ProductVariant::select('id_product_variant as id_item', 'product_variant_name as item_name')->get();
                        $items = [];
                        $itemRaw->each(function ($item) use (&$items) {
                            $items[$item->id_item] = $item->item_name;
                        });
                        return $items;
                    })();
                    $value = implode(', ', array_map(function ($val) use ($variants) {
                        return $variants[$val] ?? '';
                    }, $value));
                    return $value;
                }
            ],
        ];

        $filter = $filters[$rule[0]] ?? [];
        if (!$filter) {
            return $rule;
        }

        if (($filter['data'] ?? false)) {
            return [
                $filter['label'],
                $rule[1],
                $filter['data']()[$rule[2]] ?? $rule[2]
            ];
        } elseif (isset($filter['render'])) {
            return [
                $filter['label'],
                $rule[1],
                $filter['render']($rule[2])
            ];
        }

        return [
            $filter['label'],
            $rule[1],
            $rule[2]
        ];
    }
}
