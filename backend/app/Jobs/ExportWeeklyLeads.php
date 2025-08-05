<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Mail\SendDataromaExport;

class ExportWeeklyLeads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    protected $accounts = [
        'account1' => [
            'id' => 'account1',
            'name' => 'Main Account',
            'token' => '6362-ac5646e8b7a691bc',
            'secret' => 'e3fe06878301dd5c1244e8db3225775a',
        ],
        'account2' => [
            'id' => 'account2',
            'name' => 'Secondary Account',
            'token' => '8466-035cefafcf94d90f',
            'secret' => '3d17deb69503b6daf73e9bbcc682444d',
        ],
    ];

    protected $leadColumns = [
        'account_id',
        'account',
        'profile_id',
        'profile',
        'lead_id',
        'lead_type',
        'lead_status',
        'date_created',
        'quotable',
        'quote_value',
        'sales_value',
        'lead_source',
        'lead_medium',
        'lead_campaign',
        'spotted_keywords',
        'lead_keyword',
    ];

    protected function createApiClient($account)
    {
        $basicAuth = base64_encode("{$account['token']}:{$account['secret']}");
        return Http::withHeaders([
            'Authorization' => "Basic {$basicAuth}",
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
        ])->baseUrl('https://app.whatconverts.com/api/v1');
    }

    protected function fetchClients()
    {
        try {
            $baseUrl = env('API_BASE_URL');
            if (!$baseUrl) {
                throw new \Exception('API_BASE_URL is not defined in .env');
            }
            $response = Http::withHeaders([
                'Cache-Control' => 'no-cache',
            ])->timeout(30)->get($baseUrl . '/api/clients');

            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] === 'success') {
                $clientMap = [];
                foreach ($response->json()['data'] as $client) {
                    $clientMap[$client['what_converts_id']] = $client['client_id'];
                }
                Log::info('Fetched clients data', ['clientMap' => $clientMap]);
                return $clientMap;
            }
            throw new \Exception('Invalid data format from clients API: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching clients data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function fetchAllLeadsForAccount($account, $startDate, $endDate, $clientMap, $callback)
    {
        $page = 1;
        $leadsPerPage = 250;
        $totalLeads = 0;

        try {
            $api = $this->createApiClient($account);
            do {
                $response = $api->timeout(60)->retry(3, 1000)->get('/leads', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'page_number' => $page,
                    'leads_per_page' => $leadsPerPage,
                    'cache_buster' => time(),
                ]);

                if (!$response->successful() || !isset($response->json()['leads']) || !is_array($response->json()['leads'])) {
                    throw new \Exception("Invalid data format from API for {$account['name']}: " . $response->body());
                }

                $leads = $response->json()['leads'];
                $totalPages = $response->json()['total_pages'] ?? 1;
                Log::info("Fetched page {$page}/{$totalPages} for {$account['name']}", ['leads' => count($leads)]);

                // Process each page via callback
                $processedLeads = $this->processLeads($leads, $clientMap);
                $callback($processedLeads, $page === 1);
                $totalLeads += count($leads);

                $page++;
            } while ($page <= $totalPages && !empty($leads));

            return $totalLeads;
        } catch (\Exception $e) {
            Log::warning("Error fetching leads for {$account['name']}", ['error' => $e->getMessage()]);
            return $totalLeads;
        }
    }

    protected function processLeads($leads, $clientMap)
    {
        return array_map(function ($lead) use ($clientMap) {
            $clientId = isset($clientMap[$lead['account_id']]) ? $clientMap[$lead['account_id']] : $lead['account_id'];
            return [
                'Account ID' => $clientId,
                'Account' => $lead['account'] ?? '-',
                'Profile ID' => $lead['profile_id'] ?? '-',
                'Profile' => $lead['profile'] ?? '-',
                'Lead ID' => $lead['lead_id'] ?? '-',
                'Lead Type' => $lead['lead_type'] ?? '-',
                'Lead Status' => $lead['lead_status'] ?? '-',
                'Date Created' => isset($lead['date_created']) ? date('Y-m-d', strtotime($lead['date_created'])) : '-',
                'Quotable' => $lead['quotable'] ?? '-',
                'Quote Value' => $lead['quote_value'] ?? '-',
                'Sales Value' => $lead['sales_value'] ?? '-',
                'Lead Source' => $lead['lead_source'] ?? '-',
                'Lead Medium' => $lead['lead_medium'] ?? '-',
                'Lead Campaign' => $lead['lead_campaign'] ?? '-',
                'Spotted Keywords' => $lead['spotted_keywords'] ?? '-',
                'Lead Keyword' => $lead['lead_keyword'] ?? '-',
            ];
        }, $leads);
    }

    protected function createExcelFile($account, $startDate, $endDate, $clientMap)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;
        $fileName = str_replace(' ', '_', $account['name']) . "_leads_{$startDate}_to_{$endDate}.xlsx";
        $filePath = 'private/temp/' . $fileName;

        Storage::disk('local')->makeDirectory('private/temp');

        // Callback to write leads to Excel in batches
        $callback = function ($processedLeads, $isFirstPage) use ($sheet, &$row) {
            if ($isFirstPage) {
                $headers = array_keys($processedLeads[0]);
                $sheet->fromArray($headers, null, 'A' . $row);
                $row++;
            }
            $sheet->fromArray($processedLeads, null, 'A' . $row);
            $row += count($processedLeads);
        };

        // Fetch and process leads in batches
        $totalLeads = $this->fetchAllLeadsForAccount($account, $startDate, $endDate, $clientMap, $callback);

        // Auto-size columns
        $headers = $this->leadColumns;
        foreach (range('A', chr(64 + count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $absolutePath = storage_path('app/' . $filePath);
        $writer->save($absolutePath);

        Log::info('Excel file created', ['path' => $absolutePath, 'exists' => file_exists($absolutePath) ? 'Yes' : 'No', 'total_leads' => $totalLeads]);

        return ['fileName' => $fileName, 'filePath' => $filePath, 'totalLeads' => $totalLeads];
    }

    public function handle()
    {
        Log::info('Starting weekly leads export job', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        // Fetch clients data for ID mapping
        $clientMap = $this->fetchClients();

        // Process each account
        foreach ($this->accounts as $account) {
            try {
                Log::info("Fetching leads for account: {$account['name']} (ID: {$account['id']})");

                // Create Excel file with batched leads
                $fileData = $this->createExcelFile(
                    $account,
                    $this->startDate,
                    $this->endDate,
                    $clientMap
                );

                if ($fileData['totalLeads'] === 0) {
                    Log::warning("No leads found for {$account['name']}");
                    continue;
                }

                // Send email with attachment
                Mail::to('5c36415d17f94f169c5638984af7af34@dbx.datorama.com')
                    ->send(new SendDataromaExport(
                        $fileData['fileName'],
                        $fileData['filePath'],
                        $this->startDate,
                        $this->endDate
                    ));

                Log::info("Email sent for {$account['name']}", ['file' => $fileData['fileName'], 'leads' => $fileData['totalLeads']]);

                // Clean up the file
                Storage::disk('local')->delete($fileData['filePath']);
                Log::info('Cleaned up temporary file', ['file' => $fileData['filePath']]);
            } catch (\Exception $e) {
                Log::error("Failed processing for {$account['name']}", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                continue;
            }
        }
    }
}
