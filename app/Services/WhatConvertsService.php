<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WhatConvertsService
{
    protected $client;

    public function __construct()
    {
        $this->setupClient();
    }


    protected function setupClient()
    {
        $authToken = env('WHATCONVERTS_API_TOKEN', '6362-ac5646e8b7a691bc');
        $apiSecret = env('WHATCONVERTS_API_SECRET', 'e3fe06878301dd5c1244e8db3225775a');

        if (empty($authToken) || empty($apiSecret)) {
            Log::error('WhatConverts credentials are not properly configured');
            throw new \Exception('WhatConverts credentials are not properly configured');
        }

        $this->client = new Client([
            'base_uri' => 'https://app.whatconverts.com/api/v1/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'auth' => [$authToken, $apiSecret], // Basic Auth with token as username and secret as password
            'verify' => true, // Enable SSL verification
            'timeout' => 60, // Increase timeout to 60 seconds
            'connect_timeout' => 10,
        ]);
    }

    public function fetchLeadsByDateRange(string $startDate, string $endDate): Collection
    {
        set_time_limit(300);

        try {
            $page = 1;
            $allLeads = collect();
            $hasMorePages = true;

            while ($hasMorePages) {
                Log::info('Fetching leads page ' . $page, [
                    'date_range' => "$startDate,$endDate"
                ]);

                $response = $this->client->get('leads', [
                    'query' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'page' => $page,
                        'per_page' => 100 // Fetch 100 leads per page
                    ]
                ]);

                $responseBody = $response->getBody()->getContents();
                $data = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
                }

                // Validate response structure
                if (!isset($data['leads']) || !is_array($data['leads'])) {
                    Log::error('Invalid response structure', ['data' => $data]);
                    throw new \Exception('API response missing leads array');
                }

                // Add leads to collection
                $allLeads = $allLeads->merge($data['leads']);

                // Check for more pages
                if (!isset($data['total_pages']) || $page >= $data['total_pages']) {
                    $hasMorePages = false;
                    Log::info('Finished fetching all leads', [
                        'total_leads_collected' => $allLeads->count()
                    ]);
                } else {
                    $page++;
                }
            }

            return $allLeads;
        } catch (\Exception $e) {
            Log::error('Error fetching leads: ' . $e->getMessage());
            throw $e;
        }
    }

    // Optional helper method to format dates if needed
    protected function formatDateForApi($date): string
    {
        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            return date('Y-m-d', strtotime($date));
        }

        throw new \InvalidArgumentException('Invalid date format');
    }

    /**
     * Store leads in database
     *
     * @param Collection $leads
     * @return int Number of leads stored
     */
    public function storeLeads(Collection $leads): int
    {
        $count = 0;

        foreach ($leads as $leadData) {
            try {
                // Ensure lead_id exists
                if (!isset($leadData['lead_id'])) {
                    Log::warning('Skipping lead without lead_id', ['lead' => $leadData]);
                    continue;
                }

                // Convert date string to correct format
                if (isset($leadData['date_created']) && !empty($leadData['date_created'])) {
                    $leadData['date_created'] = Carbon::parse($leadData['date_created']);
                }

                // Create or update lead based on lead_id
                Lead::updateOrCreate(
                    ['lead_id' => $leadData['lead_id']],
                    [
                        'account_id' => $leadData['account_id'] ?? null,
                        'account' => $leadData['account'] ?? null,
                        'profile_id' => $leadData['profile_id'] ?? null,
                        'profile' => $leadData['profile'] ?? null,
                        'lead_type' => $leadData['lead_type'] ?? null,
                        'lead_status' => $leadData['lead_status'] ?? null,
                        'date_created' => $leadData['date_created'] ?? null,
                        'quotable' => $leadData['quotable'] ?? null,
                        'quote_value' => $leadData['quote_value'] ?? null,
                        'sales_value' => $leadData['sales_value'] ?? null,
                        'lead_source' => $leadData['lead_source'] ?? null,
                        'lead_medium' => $leadData['lead_medium'] ?? null,
                    ]
                );

                $count++;
            } catch (\Exception $e) {
                Log::error('Error storing lead: ' . $e->getMessage(), ['lead_id' => $leadData['lead_id'] ?? 'unknown']);
            }
        }

        return $count;
    }

    /**
     * For debugging: dump the first page of results
     */
    public function testApiConnection()
    {
        try {
            // Log the configured settings (excluding sensitive data)
            Log::info('API Client Configuration', [
                'base_uri' => $this->client->getConfig('base_uri'),
                'headers' => [
                    'Accept' => $this->client->getConfig('headers')['Accept'] ?? null,
                    'Content-Type' => $this->client->getConfig('headers')['Content-Type'] ?? null,
                ],
                'auth' => 'Basic [REDACTED]'
            ]);

            // Make a test request
            $response = $this->client->get('leads', [
                'query' => [
                    'date_range' => now()->subDay()->format('Y-m-d') . ',' . now()->format('Y-m-d'),
                    'page' => 1,
                    'per_page' => 1 // Limit to 1 result for testing
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            Log::info('Test API Response Structure', [
                'status_code' => $response->getStatusCode(),
                'body_structure' => $data ? array_keys($data) : []
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'API connection successful',
                'data' => [
                    'status_code' => $response->getStatusCode(),
                    'response_structure' => $data ? array_keys($data) : [],
                    'sample_data' => $data
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Test Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'API test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
