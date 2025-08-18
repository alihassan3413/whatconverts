<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ExportWeeklyLeads;
use Carbon\Carbon;

class ExportWeeklyLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:export-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports leads for the previous week and sends them via email, scheduled for 9:00 AM Canada time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set the timezone to Canada (e.g., Toronto)
        $timezone = 'America/Toronto';

        // Calculate the date range: start of last week to end of yesterday
        $startDate = Carbon::now($timezone)->subWeek()->startOfDay()->toDateString();
        $endDate = Carbon::now($timezone)->subDay()->endOfDay()->toDateString();

        $this->info("Starting weekly leads export for period: {$startDate} to {$endDate}");

        // Dispatch the ExportWeeklyLeads job
        ExportWeeklyLeads::dispatch($startDate, $endDate)->onQueue('default');

        $this->info('Weekly leads export job has been dispatched successfully.');
    }
}
