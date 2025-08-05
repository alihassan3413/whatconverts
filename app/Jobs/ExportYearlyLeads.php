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

class ExportYearlyLeads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $dateRangeLabel;
    protected $batchNumber;
    protected $maxLeadsPerFile = 5000;
    protected $leadsPerPage = 250;
    protected $delayBetweenRequests = 1; // seconds

    public function __construct($startDate, $endDate, $dateRangeLabel, $batchNumber)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->dateRangeLabel = $dateRangeLabel;
        $this->batchNumber = $batchNumber;
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

    protected function fetchAllLeads($account, $clientMap)
    {
        $allLeads = [];
        $page = 1;
        $api = $this->createApiClient($account);
        $totalLeads = 0;

        do {
            try {
                $response = $api->timeout(120)
                    ->retry(3, 1000)
                    ->get('/leads', [
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate,
                        'page_number' => $page,
                        'leads_per_page' => $this->leadsPerPage,
                        'cache_buster' => time(),
                    ]);

                if (!$response->successful()) {
                    throw new \Exception("API request failed with status: {$response->status()}");
                }

                $data = $response->json();
                $leads = $data['leads'] ?? [];
                $totalPages = $data['total_pages'] ?? 1;

                $processedLeads = $this->processLeads($leads, $clientMap);
                $allLeads = array_merge($allLeads, $processedLeads);
                $totalLeads += count($processedLeads);

                Log::info("Fetched page {$page}/{$totalPages} for {$account['name']}", [
                    'batch' => $this->batchNumber,
                    'date_range' => $this->dateRangeLabel,
                    'total_leads' => $totalLeads
                ]);

                $page++;
                
                // Delay to avoid rate limiting
                if ($page <= $totalPages) {
                    sleep($this->delayBetweenRequests);
                }

            } catch (\Exception $e) {
                Log::error("Failed fetching leads for {$account['name']}", [
                    'error' => $e->getMessage(),
                    'batch' => $this->batchNumber,
                    'page' => $page
                ]);
                break;
            }

        } while ($page <= $totalPages && !empty($leads));

        return $allLeads;
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

    protected function createExcelFiles($account, $leads)
    {
        $fileData = [];
        $leadChunks = array_chunk($leads, $this->maxLeadsPerFile);
        $fileCount = 1;

        foreach ($leadChunks as $chunk) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add headers
            $headers = array_keys($chunk[0] ?? []);
            if (!empty($headers)) {
                $sheet->fromArray($headers, null, 'A1');
                
                // Add data
                $sheet->fromArray($chunk, null, 'A2');
                
                // Auto-size columns
                foreach (range('A', chr(64 + count($headers))) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $fileName = sprintf(
                    '%s_leads_batch%d_part%d_%s.xlsx',
                    str_replace(' ', '_', $account['name']),
                    $this->batchNumber,
                    $fileCount,
                    $this->dateRangeLabel
                );
                
                // Create directory if it doesn't exist
                $directory = 'private/temp';
                // Correct path building - only prepend storage_path once
                $fullPath = storage_path("app/{$directory}/{$fileName}"); 

                // Ensure directory exists
                if (!file_exists(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0755, true);
                }

                // Use DIRECTORY_SEPARATOR for cross-platform compatibility
                $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

                $writer = new Xlsx($spreadsheet);
                $writer->save($fullPath);

                $fileData[] = [
                    'fileName' => $fileName,
                    'filePath' => $filePath,
                    'leadCount' => count($chunk)
                ];

                $fileCount++;
            }
        }

        return $fileData;
    }

    public function handle()
    {
        Log::info('Processing leads batch', [
            'batch_number' => $this->batchNumber,
            'date_range' => $this->dateRangeLabel,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        $clientMap = $this->fetchClients();

        foreach ($this->accounts as $account) {
            try {
                $leads = $this->fetchAllLeads($account, $clientMap);

                if (empty($leads)) {
                    Log::warning("No leads found for {$account['name']}", [
                        'batch' => $this->batchNumber,
                        'date_range' => $this->dateRangeLabel
                    ]);
                    continue;
                }

                $files = $this->createExcelFiles($account, $leads);

                foreach ($files as $file) {
                    Mail::to('5c36415d17f94f169c5638984af7af34@dbx.datorama.com')
                        ->send(new SendDataromaExport(
                            $file['fileName'],
                            $file['filePath'],
                            $this->startDate,
                            $this->endDate,
                            $this->dateRangeLabel,
                            $this->batchNumber,
                            $account['name'],
                            $file['leadCount']
                        ));

                    Log::info("Email sent for {$account['name']}", [
                        'file' => $file['fileName'],
                        'leads' => $file['leadCount'],
                        'batch' => $this->batchNumber
                    ]);

                    Storage::delete($file['filePath']);
                }

            } catch (\Exception $e) {
                Log::error("Failed processing batch {$this->batchNumber} for {$account['name']}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
    }
}