<?php

namespace App\Http\Controllers;

use App\Mail\SendDataromaExport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
                'to_email' => 'required|email'
            ]);

            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'File not found'], 400);
            }
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('temp', $fileName, 'local');

            Log::info('File stored at: ' . storage_path('app/private/' . $filePath));
            Log::info('File exists: ' . (file_exists(storage_path('app/private/' . $filePath)) ? 'Yes' : 'No'));

            Mail::to($request->to_email)->send(new SendDataromaExport($filePath, $fileName));

            Storage::disk('local')->delete($filePath);

            return response()->json([
                'status' => "Success",
                'message' => "Email Sent Successfully"
            ]);
        } catch (Exception $e) {
            Log::error('Error sending Datorama email: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
