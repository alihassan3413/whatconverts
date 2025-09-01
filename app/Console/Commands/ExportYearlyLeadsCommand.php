<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ExportYearlyLeads;
use Carbon\Carbon;

class ExportYearlyLeadsCommand extends Command
{
    protected $signature = 'leads:export 
                            {--days=7 : Number of days to export} 
                            {--batch=1 : Days per batch}';
    protected $description = 'Test export with configurable date range';

    public function handle()
    {
        $timezone = 'America/Toronto';
        $now = Carbon::now($timezone);
        
        // Get options with defaults
        $totalDays = (int)$this->option('days') ?: 7;
        $daysPerBatch = (int)$this->option('batch') ?: 1;

        // Calculate date ranges
        $endDate = $now->copy()->subDay()->endOfDay();
        $startDate = $now->copy()->subDays($totalDays)->startOfDay();

        $this->info("TEST MODE: Exporting leads from {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->info("Processing in batches of {$daysPerBatch} day(s)");

        // Process in configurable batches
        $currentStart = $startDate->copy();
        $batchNumber = 0;
        
        while ($currentStart < $endDate) {
            $batchEnd = $currentStart->copy()->addDays($daysPerBatch - 1)->endOfDay();
            if ($batchEnd > $endDate) {
                $batchEnd = $endDate;
            }

            $dateRangeLabel = $currentStart->format('M d') . ' - ' . $batchEnd->format('M d');
            
            $this->info("Dispatching TEST batch #{$batchNumber}: {$dateRangeLabel}");
            
            ExportYearlyLeads::dispatch(
                $currentStart->toDateString(),
                $batchEnd->toDateString(),
                $dateRangeLabel,
                $batchNumber
            )->onQueue('exports');

            $currentStart->addDays($daysPerBatch);
            $batchNumber++;
        }

        $this->info("All {$batchNumber} test batch jobs dispatched");
    }
}