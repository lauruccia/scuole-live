<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStudent;
use App\Models\GoogleAccount;
use App\Models\Lesson;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    /**
     * Standard: meet url = lesson.meet_url (se presente) altrimenti contractStudent.meet_url
     */
    private function resolveMeetUrl(Lesson $lesson): ?string
    {
        $lesson->loadMissing('contractStudent');

        $fromLesson = trim((string) ($lesson->meet_url ?? ''));
        if ($fromLesson !== '') {
            return $fromLesson;
        }

        $fromCs = trim((string) ($lesson->contractStudent?->meet_url ?? ''));
        return $fromCs !== '' ? $fromCs : null;
    }

    public function upsertEventForLesson(Lesson $lesson): void
    {
        // se annullata, non deve stare su Google
        if (! empty($lesson->cancelled_at)) {
            $this->cancelEventForLesson($lesson);
            return;
        }

        $lesson->loadMissing(['contractStudent', 'student', 'teacher', 'contract.course']);

        $meetUrl = $this->resolveMeetUrl($lesson);
        if (empty($meetUrl)) {
            // nessun meet -> non creo evento
            return;
        }

        $client = $this->clientOrNull();
        if (! $client) return;

        $calendarId = $this->calendarId();
        $service = new Calendar($client);

        $studentName = $lesson->student?->full_name
            ?? trim(($lesson->student?->first_name ?? '') . ' ' . ($lesson->student?->last_name ?? ''));

        $teacherName = $lesson->teacher?->name ?? '';
        $courseName  = $lesson->contract?->course?->name ?? '';

        $summary = trim("Lezione - {$studentName}");
        $desc = "Corso: {$courseName}\nDocente: {$teacherName}\n\nMeet:\n{$meetUrl}";

        $payload = new Event([
            'summary' => $summary,
            'location' => $meetUrl,
            'description' => $desc,
            'start' => new EventDateTime([
                'dateTime' => $lesson->starts_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $lesson->ends_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
        ]);

        // CREATE
        if (empty($lesson->google_event_id)) {
            $created = $service->events->insert($calendarId, $payload);

            $lesson->google_calendar_id = $calendarId;
            $lesson->google_event_id = $created->getId();

            // “fotografo” localmente il meet usato
            $lesson->meet_url = $meetUrl;

            $lesson->saveQuietly();
            return;
        }

        // UPDATE
        $service->events->patch($calendarId, $lesson->google_event_id, $payload);

        $lesson->google_calendar_id = $calendarId;
        $lesson->meet_url = $meetUrl;
        $lesson->saveQuietly();
    }

    public function ensureEventForLesson(Lesson $lesson): void
    {
        if (! empty($lesson->google_event_id)) {
            return;
        }

        $lesson->loadMissing(['contractStudent', 'contract.course', 'student', 'teacher']);

        $meetUrl = $this->resolveMeetUrl($lesson);
        if (empty($meetUrl)) return;

        $client = $this->clientOrNull();
        if (! $client) return;

        $calendarId = $this->calendarId();
        $service = new Calendar($client);

        $studentName = $lesson->student?->full_name
            ?? trim(($lesson->student?->first_name ?? '') . ' ' . ($lesson->student?->last_name ?? ''));

        $courseName = $lesson->contract?->course?->name ?? '';

        $summary = trim("Lezione - {$studentName}");
        $desc = "Corso: {$courseName}\n\nMeet:\n{$meetUrl}";

        $event = new Event([
            'summary' => $summary,
            'location' => $meetUrl,
            'description' => $desc,
            'start' => new EventDateTime([
                'dateTime' => $lesson->starts_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $lesson->ends_at->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
        ]);

        $created = $service->events->insert($calendarId, $event);

        $lesson->google_calendar_id = $calendarId;
        $lesson->google_event_id = $created->getId();
        $lesson->meet_url = $meetUrl;
        $lesson->saveQuietly();
    }

    public function cancelEventForLesson(Lesson $lesson): void
    {
        if (empty($lesson->google_event_id)) return;

        $client = $this->clientOrNull();
        if (! $client) return;

        $service = new Calendar($client);
        $calendarId = $lesson->google_calendar_id ?: $this->calendarId();

        $service->events->delete($calendarId, $lesson->google_event_id);

        $lesson->google_event_id = null;
        $lesson->saveQuietly();
    }

    /**
     * ✅ Genera un Google Meet creando un evento temporaneo solo per ottenere
     * il link conferenza, poi lo elimina subito. Il link Meet rimane valido.
     */
    public function generateMeetLink(string $title = 'Meet AEA Language'): ?string
    {
        $client = $this->clientOrNull();
        if (! $client) return null;

        $calendarId = $this->calendarId();
        $service = new Calendar($client);

        $start = now()->addMinutes(1);
        $end   = now()->addMinutes(2);

        $event = new Event([
            'summary' => $title,
            'start' => new EventDateTime([
                'dateTime' => $start->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $end->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ]),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ]);

        $created = $service->events->insert($calendarId, $event, [
            'conferenceDataVersion' => 1,
        ]);

        // Recupera il link Meet prima di eliminare l'evento temporaneo
        $link = $created->getHangoutLink();

        // Fallback: prova i conferenceData entry points se hangoutLink è vuoto
        // Nota: ?.[0] non è supportato in PHP - usiamo una variabile intermedia
        if (! $link) {
            $entryPoints = $created->getConferenceData()?->getEntryPoints();
            $link = (is_array($entryPoints) && isset($entryPoints[0]))
                ? $entryPoints[0]->getUri()
                : null;
        }

        // Elimina subito l'evento temporaneo: il calendario rimane pulito.
        // Il link Meet rimane valido indipendentemente dall'evento.
        try {
            $service->events->delete($calendarId, $created->getId());
        } catch (\Throwable $e) {
            // Se la delete fallisce non blocchiamo: il link e' gia' ottenuto.
            // Logghiamo comunque per diagnostica futura (Sentry + log Laravel).
            report($e);
            \Illuminate\Support\Facades\Log::warning('GoogleCalendarService: delete evento temporaneo fallita', [
                'calendar_id' => $calendarId,
                'event_id'    => $created->getId(),
                'error'       => $e->getMessage(),
            ]);
        }

        return $link ?: null;
    }

    /**
     * ✅ Action “Genera Meet” dal contratto:
     * - genera meet per ogni beneficiario (contract_students) se manca (o force)
     * - aggiorna tutte le lezioni future con meet_url
     * - upsert eventi su Google per le lezioni future
     */
    public function generateMeetForContract(Contract $contract, bool $force = false): array
    {
        $contract->loadMissing(['beneficiaries']);

        $updatedStudents = 0;
        $updatedLessons  = 0;
        $upsertedEvents  = 0;

        foreach ($contract->beneficiaries as $cs) {
            /** @var ContractStudent $cs */
            $current = trim((string) ($cs->meet_url ?? ''));

            if ($current !== '' && ! $force) {
                continue;
            }

            $title = 'Meet - ' . trim(($cs->beneficiary_first_name ?? '') . ' ' . ($cs->beneficiary_last_name ?? ''));
            $meet = $this->generateMeetLink($title);

            if (! $meet) {
                continue;
            }

            $cs->meet_url = $meet;
            $cs->saveQuietly();
            $updatedStudents++;

            // aggiorno lezioni future del beneficiario
            $updatedLessons += Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
                ->whereNull('cancelled_at')
                ->where('ends_at', '>', now())
                ->update(['meet_url' => $meet]);

            // upsert eventi (chunk)
            Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
                ->whereNull('cancelled_at')
                ->where('ends_at', '>', now())
                ->orderBy('id')
                ->chunkById(50, function ($lessons) use (&$upsertedEvents) {
                    foreach ($lessons as $l) {
                        $this->upsertEventForLesson($l);
                        $upsertedEvents++;
                    }
                });
        }

        return [
            'updated_students' => $updatedStudents,
            'updated_lessons'  => $updatedLessons,
            'upserted_events'  => $upsertedEvents,
        ];
    }

    private function calendarId(): string
    {
        $acc = GoogleAccount::query()->find(1);
        return $acc?->calendar_id ?: config('services.google.calendar_id', 'primary');
    }

    private function clientOrNull(): ?GoogleClient
{
    /** @var GoogleAccount|null $acc */
    $acc = GoogleAccount::query()->find(1);

    if (! $acc || empty($acc->access_token)) {
        return null;
    }

    $token = json_decode((string) $acc->access_token, true);

    if (! is_array($token)) {
        return null;
    }

    $client = new GoogleClient();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(config('services.google.redirect'));
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->addScope(Calendar::CALENDAR);

    $client->setAccessToken($token);

    if ($client->isAccessTokenExpired()) {
        $refresh = $acc->refresh_token ?: ($token['refresh_token'] ?? null);

        if (empty($refresh)) {
            return null;
        }

        try {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refresh);
        } catch (\Throwable $e) {
            // Errore critico: token refresh fallito. Notifica Sentry + log dettagliato.
            report($e);
            \Illuminate\Support\Facades\Log::error('GoogleCalendarService: refresh token fallito (eccezione)', [
                'account_id' => $acc->id ?? null,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }

        if (! empty($newToken['error'])) {
            \Illuminate\Support\Facades\Log::error('GoogleCalendarService: refresh token rifiutato dal server Google', [
                'account_id' => $acc->id ?? null,
                'error'      => $newToken['error'],
                'desc'       => $newToken['error_description'] ?? null,
            ]);
            return null;
        }

        $newToken['refresh_token'] = $refresh;

        $acc->update([
            'access_token' => json_encode($newToken),
            'refresh_token' => $refresh,
            'expires_at' => now()->addSeconds((int) ($newToken['expires_in'] ?? 3600)),
        ]);

        $client->setAccessToken($newToken);
    }

    return $client;
}
}
