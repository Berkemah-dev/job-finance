<?php

namespace App\Mail;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StatementOfAccountMail extends Mailable
{
    public function __construct(
        public Customer $customer,
        public array $statement,
        public Carbon $periodFrom,
        public Carbon $periodTo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Statement of Account '.$this->customer->name.' · '.$this->periodFrom->format('d/m/Y').' s.d. '.$this->periodTo->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.soa');
    }
}
