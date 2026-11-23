<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $ownerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Selamat! Toko '.$this->tenant->name.' Telah Aktif - PetMarket',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant.approved',
        );
    }
}
