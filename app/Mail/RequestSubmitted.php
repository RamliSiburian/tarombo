<?php

namespace App\Mail;

use App\Models\NodeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NodeRequest $nodeRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Tarombo] Permintaan Anda Telah Diterima');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.request-submitted');
    }
}
