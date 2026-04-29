<?php

namespace App\Mail;

use App\Models\Lesson;
use App\Models\SchoolSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LessonCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lesson $lesson,
        public string $studentName,
        public string $cancellationType, // 'recoverable' | 'consumed' | 'permanent'
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Comunicazione lezione — ' . SchoolSetting::schoolName())
            ->view('emails.lesson-cancelled');
    }
}
