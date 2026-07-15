<?php

namespace Modules\System\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\System\App\Models\Admin;
use Modules\System\App\Support\MailConfigRegistrar;
use Starship\Core\Contracts\Mail\MailSenderInterface;

class AdminPasswordResetService
{
    public const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        protected MailSenderInterface $mailSender,
    ) {}

    public function ensureMailReady(): void
    {
        MailConfigRegistrar::apply();

        if (! MailConfigRegistrar::isConfigured()) {
            throw new \RuntimeException(__('system::messages.mail_not_configured'));
        }
    }

    public function sendResetLink(string $email): void
    {
        $this->ensureMailReady();

        $email = strtolower(trim($email));
        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($admin === null) {
            throw new \RuntimeException(__('system::messages.password_reset_email_not_found'));
        }

        if ((int) $admin->status !== 1) {
            throw new \RuntimeException(__('system::messages.password_reset_admin_disabled'));
        }

        $plainToken = Str::random(64);

        DB::table('admin_password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ],
        );

        $resetUrl = $this->buildResetUrl($email, $plainToken);
        $siteName = trim((string) config('mail.from.name', config('app.name')));
        $subject = __('system::messages.password_reset_mail_subject', ['site' => $siteName]);
        $html = view('system::emails.admin-password-reset', [
            'resetUrl' => $resetUrl,
            'admin' => $admin,
            'siteName' => $siteName,
        ])->render();

        try {
            $this->mailSender->send($email, $subject, $html, ['html' => true]);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('system::messages.password_reset_mail_failed'),
                previous: $exception,
            );
        }
    }

    public function verifyResetToken(string $email, string $token): void
    {
        $this->assertValidResetToken($email, $token);

        $email = strtolower(trim($email));
        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 1)
            ->first();

        if ($admin === null) {
            throw new \RuntimeException(__('system::messages.password_reset_token_invalid'));
        }
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $this->verifyResetToken($email, $token);

        $email = strtolower(trim($email));
        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 1)
            ->first();

        if ($admin === null) {
            throw new \RuntimeException(__('system::messages.password_reset_token_invalid'));
        }

        $admin->password = $password;
        $admin->save();

        DB::table('admin_password_reset_tokens')->where('email', $email)->delete();
    }

    private function assertValidResetToken(string $email, string $token): void
    {
        $email = strtolower(trim($email));
        $token = trim($token);

        $record = DB::table('admin_password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($record === null || ! Hash::check($token, (string) $record->token)) {
            throw new \RuntimeException(__('system::messages.password_reset_token_invalid'));
        }

        $createdAt = $record->created_at ? strtotime((string) $record->created_at) : false;
        if ($createdAt === false || $createdAt + self::TOKEN_TTL_MINUTES * 60 < time()) {
            DB::table('admin_password_reset_tokens')->where('email', $email)->delete();
            throw new \RuntimeException(__('system::messages.password_reset_token_expired'));
        }
    }

    private function buildResetUrl(string $email, string $token): string
    {
        $base = rtrim((string) config('app.admin_frontend_url', config('app.url')), '/');

        return $base.'/auth/reset-password?'.http_build_query([
            'email' => $email,
            'token' => $token,
        ]);
    }
}
