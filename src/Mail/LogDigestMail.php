<?php

namespace SolverCircle\LogViewer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LogDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<int, array>  $entries
     * @param  array<int, string>  $levels
     */
    public function __construct(
        public array $entries,
        public int $intervalMinutes,
        public string $environment,
        public array $levels
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $count = count($this->entries);
        $levelList = implode(', ', array_map('strtoupper', $this->levels));

        return new Envelope(
            subject: "[{$this->environment}] Log Alert: {$count} {$levelList} entry(ies) in last {$this->intervalMinutes} mins",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'log-viewer::mail.digest',
        );
    }
}
