<?php

namespace App\Http\Controllers;

use App\Models\ClientData;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleSheetController extends Controller
{
    public function fetchData()
    {
        try {
            // Initialize the Google Client
            $client = new Client();
            $client->setApplicationName('Google Sheets API Laravel');
            $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
            $client->setAuthConfig(storage_path('app/google_sheets.json'));

            // Log the client email for debugging
            $serviceAccountEmail = json_decode(file_get_contents(storage_path('app/google_sheets.json')), true)['client_email'];
            Log::info('Service Account Email: ' . $serviceAccountEmail);

            // Initialize Sheets service
            $service = new Sheets($client);

            // Spreadsheet ID
            $spreadsheetId = '1Q8sZPeyhMbxE3H-K_7sL_VDPUoiCfpforsqXVRVHBF4'; // Use the new spreadsheet ID
            Log::info('Spreadsheet ID: ' . $spreadsheetId);


            $range = 'Sheet1!F2:G';
            Log::info('Range: ' . $range);

            // Fetch data
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                Log::warning('No data found in the specified range.', [
                    'spreadsheetId' => $spreadsheetId,
                    'range' => $range,
                ]);
                return response()->json([
                    'error' => 'No data found in the specified range.',
                ], 404);
            }

            return $this->storeData($values);
        } catch (\Exception $e) {
            Log::error('Failed to fetch data from Google Sheet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to fetch data from Google Sheet',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeData($values)
    {
        // if same id does appears again then we update that row
        // if not then we insert new row

        // Iterate through the rows
        foreach ($values as $row) {
            $client = ClientData::where('client_id', $row[0])->first();
            if ($client) {
                $client->update([
                    'what_converts_id' => $row[1],
                ]);
                continue;
            }
            ClientData::create([
                'client_id' => $row[0],
                'what_converts_id' => $row[1],
            ]);
        }

        return response()->json([
            'message' => 'Data imported successfully.',
        ]);
    }

    public function handleWebhook(Request $request)
    {
        try {
            // Verify the request is from Google
            $resourceId = $request->header('X-Goog-Resource-ID');
            $resourceState = $request->header('X-Goog-Resource-State');

            Log::info('Webhook received:', [
                'resource_id' => $resourceId,
                'resource_state' => $resourceState,
            ]);

            // Fetch and update data from the Google Sheet
            $this->fetchData();

            return response()->json([
                'message' => 'Webhook handled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to handle webhook',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function createWatchRequest()
    {
        try {
            // Initialize the Google Client
            $client = new Client();
            $client->setApplicationName('Google Sheets API Laravel');
            $client->setScopes([Sheets::SPREADSHEETS, Sheets::DRIVE]);
            $client->setAuthConfig(storage_path('app/google_sheets.json'));

            // Initialize Drive service
            $driveService = new \Google\Service\Drive($client);

            // Spreadsheet ID
            $spreadsheetId = '1Q8sZPeyhMbxE3H-K_7sL_VDPUoiCfpforsqXVRVHBF4'; // Replace with your spreadsheet ID

            // Webhook URL (your ngrok or localtunnel URL)
            $webhookUrl = 'http://127.0.0.1:8000/api/google-sheets-webhook';

            // Create a watch request
            $channel = new \Google\Service\Drive\Channel();
            $channel->setId(uniqid()); // Unique ID for the channel
            $channel->setType('web_hook');
            $channel->setAddress($webhookUrl);

            // Set the resource to watch (the Google Sheet)
            $channel->setResourceId($spreadsheetId);

            // Set the notification parameters
            $channel->setParams([
                'ttl' => 3600, // Time-to-live in seconds (1 hour)
            ]);

            // Send the watch request
            $response = $driveService->files->watch($spreadsheetId, $channel);

            Log::info('Watch request created:', $response->toSimpleObject());

            return response()->json([
                'message' => 'Watch request created successfully.',
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create watch request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to create watch request',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
