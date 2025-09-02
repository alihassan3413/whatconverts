<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendDataromaExport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $fileName;
    public string $fileContent;
    public string $startDate;
    public string $endDate;
    public string $dateRangeLabel;
    public int $batchNumber;
    public ?string $accountName;
    public ?int $totalLeads;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $fileName,
        string $fileContent,   // <-- binary Excel content instead of path
        string $startDate,
        string $endDate,
        string $dateRangeLabel,
        int $batchNumber,
        ?string $accountName = null,
        ?int $totalLeads = null
    ) {
        $this->fileName = $fileName;
        $this->fileContent = base64_encode($fileContent); // encode for queue safety
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
        return [
            Attachment::fromData(
                fn () => base64_decode($this->fileContent), // decode here
                $this->fileName
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
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
