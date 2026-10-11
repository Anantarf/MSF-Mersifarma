<?php

namespace App\Console\Commands;

use App\Services\CallPlanService;
use Illuminate\Console\Command;

class ExpireStaleCallPlansCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msf:expire-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire scheduled call plans past their plan date without an actual visit.';

    /**
     * Execute the console command.
     */
    public function handle(CallPlanService $callPlanService): int
    {
        $count = $callPlanService->expireStalePlans();

        $this->info("Expired {$count} stale call plan(s).");

        return self::SUCCESS;
    }
}
