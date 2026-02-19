<?php

namespace App\Observers;

use App\Models\Lesson;
use App\Services\GoogleCalendarService;

class LessonMeetObserver
{
    public function created(Lesson $lesson): void
    {
        // crea evento (se possibile)
        app(GoogleCalendarService::class)->upsertEventForLesson($lesson);
    }

    public function updated(Lesson $lesson): void
    {
        $service = app(GoogleCalendarService::class);

        // 1) Gestione annullo/ripristino
        if ($lesson->wasChanged('cancelled_at')) {
            $wasCancelled = ! empty($lesson->getOriginal('cancelled_at'));
            $isCancelled  = ! empty($lesson->cancelled_at);

            // annullata adesso -> cancella SOLO quell’evento
            if (! $wasCancelled && $isCancelled) {
                $service->cancelEventForLesson($lesson);
                return;
            }

            // ripristinata (da annullata a non annullata) -> ricrea/aggiorna
            if ($wasCancelled && ! $isCancelled) {
                $service->upsertEventForLesson($lesson);
                return;
            }
        }

        // 2) Modifiche che richiedono sync (spostata / cambi orario / cambiano riferimenti)
        $shouldSync = $lesson->wasChanged([
            'starts_at',
            'ends_at',
            'teacher_id',
            'student_id',
            'contract_student_id',
        ]);

        if ($shouldSync) {
            // se è annullata non deve stare su Google
            if (! empty($lesson->cancelled_at)) {
                $service->cancelEventForLesson($lesson);
                return;
            }

            $service->upsertEventForLesson($lesson);
        }
    }

    public function deleted(Lesson $lesson): void
    {
        // se elimini la lezione dal DB, idealmente elimini anche l’evento
        app(GoogleCalendarService::class)->cancelEventForLesson($lesson);
    }
}
