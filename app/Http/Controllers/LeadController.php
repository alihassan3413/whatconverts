<?php

namespace App\Http\Controllers;

use App\Models\ClientData;
use App\Models\Lead;
use Illuminate\Http\Request;
use App\Services\WhatConvertsService;
use App\Traits\ApiResponse;

class LeadController extends Controller
{
    use ApiResponse;

    protected $whatConvertsService;

    public function __construct(WhatConvertsService $whatConvertsService)
    {
        $this->whatConvertsService = $whatConvertsService;
    }

    /**
     * Get leads with optional date filtering and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Lead::query();

            // Apply date range filter if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('date_created', [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59'
                ]);
            }

            // Apply other filters if needed
            if ($request->has('lead_status')) {
                $query->where('lead_status', $request->input('lead_status'));
            }

            if ($request->has('lead_source')) {
                $query->where('lead_source', $request->input('lead_source'));
            }

            // Paginate results
            $perPage = $request->input('per_page', 50);
            $leads = $query->paginate($perPage);

            // Transform the response to match the WhatConverts API structure
            $response = [
                'page_number' => $leads->currentPage(),
                'leads_per_page' => $leads->perPage(),
                'total_pages' => $leads->lastPage(),
                'total_leads' => $leads->total(),
                'leads' => $leads->items(),
            ];

            return $this->successResponse($response, 'Leads retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve leads: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fetch fresh leads from the API and store them in the database
     */
    public function fetchFromApi(Request $request)
    {
        try {
            $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $leads = $this->whatConvertsService->fetchLeadsByDateRange($startDate, $endDate);
            $count = $this->whatConvertsService->storeLeads($leads);

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched and stored leads',
                'data' => [
                    'stored_count' => $count,
                    'total_fetched' => $leads->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Lead fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch leads: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test API connectivity (debugging endpoint)
     */
    public function testApiConnection()
    {
        try {
            $response = $this->whatConvertsService->testApiConnection();
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'API test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all client data
     */
    public function getAllClients()
    {
        $clients = ClientData::all();
        return $this->successResponse($clients, 'Clients retrieved successfully');
    }
}
