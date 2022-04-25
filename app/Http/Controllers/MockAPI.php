<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\MyHelper;

class MockAPI extends Controller
{
    public function mock(Request $request)
    {
        if (app()->environment('production')) {
            return abort(404);
        }
        $path = trim($request->getRequestUri(), '/');
        $method = strtoupper($request->getMethod());
        switch ($path) {
            case 'api/mitra/data-update-request':
                if ($method == 'GET') {
                    return MyHelper::checkGet(
                        [
                            'field_list' => [
                                [
                                    'text' => 'Nama',
                                    'value' => 'name',
                                ],
                                [
                                    'text' => 'Email',
                                    'value' => 'email',
                                ],
                                [
                                    'text' => 'Nomor Rekening',
                                    'value' => 'account_number',
                                ],
                            ]
                        ]
                    );
                } else {
                    $request->validate([
                        'field' => 'string|required',
                        'new_value' => 'string|required',
                        'notes' => 'string|sometimes|nullable',
                    ]);
                    return [
                        'status' => 'success',
                        'result' => [
                            'message' => 'Permintaan perubahan data berhasil dikirim'
                        ]
                    ];
                }
                break;
            case 'api/mitra/income-details':
                $request->validate([
                    'month' => 'date_format:Y-m|sometimes|nullable|min:1|max:12',
                ]);
                $month = $request->month ?: date('Y-m');
                return MyHelper::checkGet(
                    [
                        'month' => $month,
                        'bank_name' => 'BNI',
                        'account_number' => '1234433135',
                        'account_name' => 'Rose',
                        'footer' => [
                            'footer_title' => 'Total diterima bulan ini setelah potongan',
                            'footer_content' => 'Rp 2.800.000',
                        ],
                        'incomes' => [
                            [
                                'name' => 'Tengah Bulan',
                                'icon' => 'half',
                                'footer' => [
                                    'title_title' => 'Penerimaan Tengah Bulan',
                                    'title_content' => '2.800.000',
                                    'subtitle_title' => 'Ditransfer',
                                    'subtitle_content' => '15 Des 2022',
                                ],
                                'list' => [
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Mall Putri',
                                        'footer_title' => 'Total',
                                        'footer_content' => '1.400.000',
                                        'contents' => [
                                            [
                                                'title' => 'Kehadiran',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Makan & Transport',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Komisi Haircut',
                                                'content' => '120.000',
                                            ],
                                        ]
                                    ],
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Grand Indonesia',
                                        'footer_title' => 'Total',
                                        'footer_content' => '1.400.000',
                                        'contents' => [
                                            [
                                                'title' => 'Kehadiran',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Makan & Transport',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Komisi Haircut',
                                                'content' => '120.000',
                                            ],
                                        ]
                                    ],
                                ]
                            ],
                            [
                                'name' => 'Akhir Bulan',
                                'icon' => 'full',
                                'footer' => [
                                    'title_title' => 'Penerimaan Akhir Bulan',
                                    'title_content' => '2.800.000',
                                    'subtitle_title' => 'Ditransfer',
                                    'subtitle_content' => '31 Des 2022',
                                ],
                                'list' => [
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Mall Putri',
                                        'footer_title' => 'Total',
                                        'footer_content' => '1.400.000',
                                        'contents' => [
                                            [
                                                'title' => 'Kehadiran',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Makan & Transport',
                                                'content' => '640.000',
                                            ],
                                            [
                                                'title' => 'Komisi Haircut',
                                                'content' => '120.000',
                                            ],
                                        ]
                                    ],
                                    [
                                        'header_title' => null,
                                        'header_content' => null,
                                        'footer_title' => null,
                                        'footer_content' => null,
                                        'contents' => [
                                            [
                                                'title' => 'Total Penerimaan',
                                                'content' => '1.400.000',
                                            ],
                                            [
                                                'title' => 'Total Potongan',
                                                'content' => '100.000',
                                            ],
                                        ]
                                    ],
                                ]
                            ],
                        ],
                        'attendances' => [
                            [
                                'name' => 'Tengah Bulan',
                                'icon' => 'half',
                                'footer' => null,
                                'list' => [
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Mall Putri',
                                        'footer_title' => null,
                                        'footer_content' => null,
                                        'contents' => [
                                            [
                                                'title' => 'Hari Masuk',
                                                'content' => '12',
                                            ],
                                            [
                                                'title' => 'Tambahan Jam',
                                                'content' => '3',
                                            ],
                                            [
                                                'title' => 'Customer Haircut',
                                                'content' => '5',
                                            ],
                                        ]
                                    ],
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Grand Indonesia',
                                        'footer_title' => null,
                                        'footer_content' => null,
                                        'contents' => [
                                            [
                                                'title' => 'Hari Masuk',
                                                'content' => '12',
                                            ],
                                            [
                                                'title' => 'Tambahan Jam',
                                                'content' => '3',
                                            ],
                                            [
                                                'title' => 'Customer Haircut',
                                                'content' => '5',
                                            ],
                                        ]
                                    ],
                                ]
                            ],
                            [
                                'name' => 'Akhir Bulan',
                                'icon' => 'full',
                                'footer' => null,
                                'list' => [
                                    [
                                        'header_title' => 'Outlet',
                                        'header_content' => 'Ixobox Mall Putri',
                                        'footer_title' => null,
                                        'footer_content' => null,
                                        'contents' => [
                                            [
                                                'title' => 'Hari Masuk',
                                                'content' => '12',
                                            ],
                                            [
                                                'title' => 'Tambahan Jam',
                                                'content' => '3',
                                            ],
                                            [
                                                'title' => 'Customer Haircut',
                                                'content' => '5',
                                            ],
                                        ]
                                    ],
                                ]
                            ],
                        ],
                        'salary_cuts' => [
                            [
                                'name' => 'Akhir Bulan',
                                'icon' => 'full',
                                'footer' => [
                                    'title_title' => 'Total Potongan',
                                    'title_content' => '100.000',
                                    'subtitle_title' => null,
                                    'subtitle_content' => null,
                                ],
                                'list' => [
                                    [
                                        'header_title' => null,
                                        'header_content' => null,
                                        'footer_title' => null,
                                        'footer_content' => null,
                                        'contents' => [
                                            [
                                                'title' => 'Keterlambatan',
                                                'content' => '-',
                                            ],
                                            [
                                                'title' => 'Deposit',
                                                'content' => '100.000',
                                            ],
                                            [
                                                'title' => 'Pinjaman Koperasi',
                                                'content' => '-',
                                            ],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                );
                break;
        }
        return [];
    }
}
