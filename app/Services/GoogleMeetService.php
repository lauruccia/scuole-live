<?php

namespace App\Services;

use App\Models\Lesson;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceData;
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
            return; // non configurato: non blocco nulla
        }

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

        $meetUrl = $created->getConferenceData()?->getEntryPoints()?.[0]?->getUri();

        $lesson->google_calendar_id = $calendarId;
        $lesson->google_event_id = $created->getId();
        $lesson->meet_url = $meetUrl ?: null;
        $lesson->saveQuietly();
    }
}
