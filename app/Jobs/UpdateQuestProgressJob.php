<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateQuestProgressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $id_transaction;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id_transaction)
    {
        $this->id_transaction = $id_transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app('Modules\Quest\Http\Controllers\ApiQuest')->updateQuestProgress($this->id_transaction);
    }
}
