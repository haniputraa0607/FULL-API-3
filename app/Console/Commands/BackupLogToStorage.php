<?php

namespace App\Console\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Storage;

class BackupLogToStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:logdb {--truncate} {--table=*} {--chunk=100000} {--maxbackup=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup and Truncate Log Database to s3';

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
        $name = 'alltable';
        $tables = $this->option('table');
        $totalRow = $this->option('chunk');
        $maxbackup = $this->option('maxbackup');

        foreach ($tables as $table) {
            $this->info("Processing $table...");
            if ($table == '*') {
                $table = '';
            }
            if (!$table) {
                continue;
            }
            $currentbackup = 0;

            backupagain:
            if ($currentbackup >= $maxbackup) continue;
            try {
                $foundRecord = \DB::connection('mysql2')->table($table)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30days')))->count();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                continue;
            }
            $this->line('>' . ($foundRecord ?: 'No') . ' records found');
            if ($table && $foundRecord < 1) {
                continue;
            }

            $this->line('>> Backup #' . ($currentbackup + 1));
            $filename = date('YmdHi_'). $currentbackup . '_' . ($table ?: 'alltable') . '.sql';
            $backupFileUC = storage_path('app/' . $filename);

            $dbUser = env('DB2_USERNAME');
            $dbHost = env('DB2_HOST');
            $dbPassword = env('DB2_PASSWORD');
            $dbName = env('DB2_DATABASE');

            $dbPassword = $dbPassword ? '-p'.$dbPassword : '';

            $mysql_dump_command= "mysqldump -v -u{$dbUser} -h{$dbHost} {$dbPassword} {$dbName} {$table} --where=\"1 limit $totalRow\" >  \"$backupFileUC\"";
            $gzip_command= "gzip -9 -f \"$backupFileUC\"";

            $run_mysql= Process::fromShellCommandline($mysql_dump_command);
            $run_mysql->mustRun(); 
            $gzip_process= Process::fromShellCommandline($gzip_command);
            $gzip_process->mustRun();

            Storage::putFileAs('_backup_dblog/', new File($backupFileUC . '.gz') , $filename . '.gz', 'private');
            unlink($backupFileUC . '.gz');

            if ($this->option('truncate') && $table) {
                \DB::connection('mysql2')->table($table)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30days')))->limit($totalRow)->delete();
            }
            $currentbackup++;
            goto backupagain;
        }
    }
}
