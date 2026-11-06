<?php

namespace Modules\ExternalFormRequest\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;

class ExternalFormReleasedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ExternalFormRequest $form,
        public ?string $attachmentPath = null,
    ) {}

    public function build()
    {
        $mail = $this->view('externalformrequest::emails.released')->with(['form' => $this->form]);

        if ($this->attachmentPath) {
            $mail->attachFromStorageDisk('public', $this->attachmentPath);
        }

        return $mail;
    }
}
