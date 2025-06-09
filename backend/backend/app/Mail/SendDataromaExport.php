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

class SendDataromaExport extends Mailable
{
    use Queueable, SerializesModels;

    public $fileName;
    public $filePath;

    /**
     * Create a new message instance.
     */
    public function __construct($fileName, $filePath)
    {
        $this->fileName = $fileName;
        $this->filePath = $filePath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'WhatConverts Leads Export for Dataroma',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dataroma_export',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $absolutePath = storage_path('app/private/temp/' . $this->filePath);
        // Normalize path for Windows
        $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);
        Log::info('Mailable attachment path: ' . $absolutePath);
        Log::info('Mailable file exists: ' . (file_exists($absolutePath) ? 'Yes' : 'No'));

        return [
            Attachment::fromPath($absolutePath)
                ->as($this->fileName)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
