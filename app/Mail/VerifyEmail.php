<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent right after registration. Carries a signed, time-limited link to
 * /api/email/verify/{id}/{hash} that flips `email_verified_at` server-side.
 *
 * `ShouldQueue` puts the actual SMTP call on the Redis queue worker so the
 * /register response returns immediately even if the mail provider is slow.
 */
class VerifyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verifyUrl;

    public function __construct(User $user)
    {
        $this->user = $user;

        // Signed temporary URL — Laravel verifies the signature on the way
        // back in, so the link can't be tampered with or replayed past its
        // 60-minute window. Hash matches the one Laravel's email-verification
        // notification uses, so the standard `verify` endpoint also accepts
        // it if we ever swap to the framework default.
        $this->verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erősítsd meg az e-mail címed - Buttercup Perfumery',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify',
            with: [
                'name'      => $this->user->name,
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
