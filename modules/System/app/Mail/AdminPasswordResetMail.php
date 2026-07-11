<?php

namespace Modules\System\App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\System\App\Models\Admin;

class AdminPasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $resetUrl,
        public Admin $admin,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = trim((string) config('mail.from.name', config('app.name')));

        return new Envelope(
            subject: __('system::messages.password_reset_mail_subject', ['site' => $siteName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'system::emails.admin-password-reset',
            with: [
                'resetUrl' => $this->resetUrl,
                'admin' => $this->admin,
                'siteName' => trim((string) config('mail.from.name', config('app.name'))),
            ],
        );
    }
}
