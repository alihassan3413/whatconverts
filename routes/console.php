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

Schedule::command('leads:export --days=365 --batch=15')
    ->weekly()
    ->mondays()
    ->at('14:30')
    ->timezone('America/Toronto')
    ->onOneServer();