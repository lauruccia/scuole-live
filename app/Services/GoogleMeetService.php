<?php

namespace App\Services;

use App\Models\Lesson;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleMeetService
{
    public function ensureMeetForLesson(Lesson $lesson): void
    {
        if ($lesson->meet_url || $lesson->google_event_id) {
            return; // già creato
        }

        $calendarId = config('services.google.calendar_id');
        $jsonPath   = config('services.google.service_account_json');

        if (! $calendarId || ! $jsonPath || ! file_exists($jsonPath)) {
            Log::info('GoogleMeetService: configurazione mancante, skip', [
                'lesson_id'           => $lesson->id,
                'calendar_id_present' => (bool) $calendarId,
                'json_path_present'   => $jsonPath ? file_exists($jsonPath) : false,
            ]);
            return; // non configurato: non blocco nulla
        }

        try {
            $client = new GoogleClient();
        $client->setAuthConfig($jsonPath);
        $client->addScope(Calendar::CALENDAR);

        $service = new Calendar($client);

        $event = new Event([
            'summary' => 'Lezione A&A Language',
            'description' => 'Lezione generata automaticamente dal sistema.',
            'start' => new EventDateTime([
                'dateTime' => $lesson->starts_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $lesson->ends_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'conferenceData' => new ConferenceData([
                'createRequest' => new CreateConferenceRequest([
                    'requestId' => 'lesson-' . $lesson->id . '-' . Str::random(8),
                ]),
            ]),
        ]);

        $created = $service->events->insert(
            $calendarId,
            $event,
            ['conferenceDataVersion' => 1]
        );

        // ?.[0] non è supportato in PHP — usiamo variabile intermedia
        $entryPoints = $created->getConferenceData()?->getEntryPoints();
        $meetUrl = (is_array($entryPoints) && isset($entryPoints[0]))
            ? $entryPoints[0]->getUri()
            : null;

        $lesson->google_calendar_id = $calendarId;
        $lesson->google_event_id = $created->getId();
        $lesson->meet_url = $meetUrl ?: null;
        $lesson->saveQuietly();

            Log::info('GoogleMeetService: evento Meet creato', [
                'lesson_id' => $lesson->id,
                'event_id'  => $created->getId(),
                'has_meet'  => (bool) $meetUrl,
            ]);
        } catch (\Throwable $e) {
            // Notifica Sentry + log con context completo per debug
            report($e);
            Log::error('GoogleMeetService: errore creazione evento Meet', [
                'lesson_id'   => $lesson->id,
                'starts_at'   => $lesson->starts_at?->toIso8601String(),
                'calendar_id' => $calendarId,
                'error'       => $e->getMessage(),
                'trace'       => substr($e->getTraceAsString(), 0, 1000),
            ]);
            // Non rilanciamo: la lezione resta valida, il link Meet puo' essere
            // generato manualmente in seguito.
        }
    }
}
