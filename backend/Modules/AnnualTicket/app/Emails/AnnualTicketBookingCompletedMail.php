<?php

namespace Modules\AnnualTicket\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\AnnualTicket\Models\AnnualTicket;

class AnnualTicketBookingCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AnnualTicket $ticket,
        public array $documentPaths = [],
    ) {}

    public function build(): self
    {
        $mail = $this
            ->subject("Annual Ticket {$this->ticket->document_number} - E-Ticket Issued")
            ->view('annualticket::emails.booking_completed');

        // Dokumen issued travel document dilampirkan ke email karyawan (pakai nama asli file)
        foreach ($this->documentPaths as $document) {
            $path = is_array($document) ? ($document['path'] ?? null) : $document;
            $name = is_array($document) ? ($document['name'] ?? null) : null;

            if (! $path) {
                continue;
            }

            if ($name) {
                $mail->attachFromStorageDisk('public', $path, $name);
            } else {
                $mail->attachFromStorageDisk('public', $path);
            }
        }

        return $mail;
    }
}
