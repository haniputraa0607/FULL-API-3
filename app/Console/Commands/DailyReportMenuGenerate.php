<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Outlet;

class DailyReportMenuGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-report-trx-menu:generate {--outlet=all} {--reset=1} {--date-start=} {--date-end=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-generate daily report menu';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $outlet = $this->option('outlet');
        $date_end = $this->option('date-end');
        $date_start = $this->option('date-start');
        $reset = $this->option('reset');
        if (!$date_end) {
            $date_end = date('Y-m-d', time() - 86400);
        }

        if ($outlet == 'all') {
            $this->info('=> Semua outlet akan di re-generate');
            $outlet = Outlet::pluck('id_outlet')->toArray();
        } else {
            $outlet = explode(',', $outlet);
            $this->info('=> Outlet dengan id_outlet [' . implode(', ', $outlet) . '] akan di re-generate');
        }
        if ($date_start) {
            $date_start = date('Y-m-d', strtotime($date_start));
            $date_end = date('Y-m-d', strtotime($date_end));
            $this->info('=> Transaksi dari tanggal ' . date('d-m-Y', strtotime($date_start)) . ' s/d ' . date('d-m-Y', strtotime($date_end)) . ' akan di re-generate');
        } else {
            $date_start = '2000-01-01';
            $date_end = date('Y-m-d', strtotime($date_end));
            $this->info('=> Seluruh transaksi sampai tanggal ' . date('d-m-Y', strtotime($date_end)) . ' akan di re-generate');
        }
        if ($reset) {
            $this->info('=> Data yang ada pada rentang tanggal tersebut akan di dihapus dan ditulis ulang');
        } else {
            $this->info('=> Data yang ada pada rentang tanggal tersebut tidak dihapus, hanya diupdate');
        }
        $question = $this->confirm("Yakin akan men-generate ulang daily_report_trx_menu?");
        if ($question) {
            $this->info('Memproses');
            $result = app('\Modules\Report\Http\Controllers\ApiCronReport')->generate(new \Illuminate\Http\Request([
                'trx_date_start' => $date_start,
                'trx_date_end' => $date_end,
                'clear_old_data' => $reset,
                'id_outlets' => $outlet
            ]), 'dailyReportProduct');
            // ($outlet, [$date_start, $date_end]);
            $this->info('Selesai');
        } else {
            $this->error('Dibatalkan');
        }
    }
}
