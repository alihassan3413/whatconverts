<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ImportExcelData extends Command
{
    protected $signature = 'import:excel';
    protected $description = 'Import data from an Excel file into the database';

    public function handle()
    {
        // Path to the Excel file
        $filePath = storage_path('app/sheets.xlsx');

        // Load the Excel file
        $data = Excel::toArray([], $filePath);

        // Iterate through the rows
        foreach ($data[0] as $row) {
            // Skip the header row
            if ($row[0] === 'date') {
                continue;
            }

            
            // Save data to the database
            DB::table('client_data')->insert([
                'what_converts_id' => $row[6],
                'client_id' => $row[5],
            ]);
        }

        $this->info('Data imported successfully.');
    }
}
