<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ExportWeeklyLeadsCommand;

// Define the schedule for the weekly leads export command
Schedule::command('leads:export-weekly')
    ->weekly()
    ->mondays()
    ->at('09:00')
    ->timezone('America/Toronto')
    ->onOneServer();
