<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Toko '.$this->tenant->name.' Berhasil Diterima - PetMarket',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant.registered',
        );
    }
}
