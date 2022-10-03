<?php

namespace App\Console\Commands;

use App\Lib\MyHelper;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Storage;
use Symfony\Component\Process\Process;

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
        $log = MyHelper::logCron('Backup Log Database');
        try {
            $name      = 'alltable';
            $tables    = $this->option('table');
            $totalRow  = $this->option('chunk');
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
                if ($currentbackup >= $maxbackup) {
                    continue;
                }

                $foundRecord = \DB::connection('mysql2')->table($table)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30days')))->count();
                $this->line('>' . ($foundRecord ?: 'No') . ' records found');
                if ($table && $foundRecord < 1) {
                    continue;
                }

                $this->line('>> Backup #' . ($currentbackup + 1));
                $filename     = date('YmdHi_') . $currentbackup . '_' . ($table ?: 'alltable') . '.sql';
                $backupFileUC = storage_path('app/' . $filename);

                $dbUser     = config('database.connections.mysql2.username');
                $dbHost     = config('database.connections.mysql2.host');
                $dbPassword = config('database.connections.mysql2.password');
                $dbName     = config('database.connections.mysql2.database');
                $awsCliProfile = env('S3_AWS_PROFILE');
                $bucketBackup     = env('S3_BUCKET_BACKUP');

                $dbPassword = $dbPassword ? '-p' . $dbPassword : '';

                $mysql_dump_command = "mysqldump -v -u{$dbUser} -h {$dbHost} {$dbPassword} {$dbName} {$table} --where=\"1 limit $totalRow\" >  \"$backupFileUC\"";
                $gzip_command       = "gzip -9 -f \"$backupFileUC\"";

                $run_mysql = Process::fromShellCommandline($mysql_dump_command);
                $run_mysql->mustRun();

                if ($this->option('truncate') && $table) {
                    \DB::connection('mysql2')->table($table)->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30days')))->limit($totalRow)->delete();
                }

                $gzip_process = Process::fromShellCommandline($gzip_command);
                $gzip_process->mustRun();

                if (config('filesystems.default') == 's3') {
                    $sync_command = "aws s3 cp \"$backupFileUC.gz\" s3://$bucketBackup/_backup_dblog/ --acl=private --profile=\"$awsCliProfile\"";

                    $run_sync = Process::fromShellCommandline($sync_command);
                    $run_sync->mustRun();
                    unlink($backupFileUC . '.gz');
                }

                $currentbackup++;
                goto backupagain;
            }
            $log->success($tables);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }
}
