<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuardianAttendanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $weekDays;
    public $attendance;
    public $weekStart;
    public $weekEnd;
    public $weeklyClasswork; // ✅ NEW

    /**
     * Create a new message instance.
     */
    public function __construct(
        $student,
        array $weekDays,
        array $attendance,
        string $weekStart,
        string $weekEnd,
        array $weeklyClasswork // ✅ NEW
    ) {
        $this->student = $student;
        $this->weekDays = $weekDays;
        $this->attendance = $attendance;
        $this->weekStart = $weekStart;
        $this->weekEnd = $weekEnd;
        $this->weeklyClasswork = $weeklyClasswork; // ✅ NEW
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly Student Summary ({$this->weekStart} – {$this->weekEnd})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.guardian-attendance',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
