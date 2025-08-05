<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDataromaExport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $fileName;
    public $filePath;
    public $startDate;
    public $endDate;
    public $dateRangeLabel;
    public $batchNumber;
    public $accountName;
    public $totalLeads;

    /**
     * Create a new message instance.
     */
    public function __construct(
        $fileName,
        $filePath,
        $startDate,
        $endDate,
        $dateRangeLabel,
        $batchNumber,
        $accountName = null,
        $totalLeads = null
    ) {
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->dateRangeLabel = $dateRangeLabel;
        $this->batchNumber = $batchNumber;
        $this->accountName = $accountName;
        $this->totalLeads = $totalLeads;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = sprintf(
            '%sLeads Export: %s (Batch %d)',
            $this->accountName ? $this->accountName . ' - ' : '',
            $this->dateRangeLabel,
            $this->batchNumber
        );

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dataroma_export',
            with: [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'dateRangeLabel' => $this->dateRangeLabel,
                'batchNumber' => $this->batchNumber,
                'accountName' => $this->accountName,
                'totalLeads' => $this->totalLeads,
                'fileName' => $this->fileName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $absolutePath = storage_path('app/' . $this->filePath);
        $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);

        // Verify file exists before attaching
        if (!file_exists($absolutePath)) {
            Log::error('Attachment file not found: ' . $absolutePath);
            return [];
        }

        Log::info('Attaching file to email', [
            'path' => $absolutePath,
            'size' => filesize($absolutePath) . ' bytes',
            'batch' => $this->batchNumber,
            'account' => $this->accountName
        ]);

        return [
            Attachment::fromPath($absolutePath)
                ->as($this->fileName)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil()
    {
        return now()->addMinutes(10);
    }
}