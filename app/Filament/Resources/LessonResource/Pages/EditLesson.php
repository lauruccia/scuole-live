<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Contract;
use App\Models\Lesson;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected ?int $contractId = null;

    protected function beforeFill(): void
{
    $this->contractId = $this->record->contract_id;

    // ✅ se la lezione non ha meet_url, usa quello del contract_student (fallback)
    if (blank($this->record->meet_url) && $this->record->contractStudent?->meet_url) {
        $this->record->meet_url = $this->record->contractStudent->meet_url;
    }
}

    /**
     * Ore consumate da una singola lezione:
     * - se start/end presenti: ceil(diff_min / 60)
     * - minimo 1
     */
    private function lessonHours($startsAt, $endsAt, int $fallbackMinutes = 60): int
    {
        if ($startsAt && $endsAt) {
            $mins = Carbon::parse($startsAt)->diffInMinutes(Carbon::parse($endsAt), false);
            if ($mins > 0) {
                return max(1, (int) ceil($mins / 60));
            }
        }

        return max(1, (int) ceil(max(1, $fallbackMinutes) / 60));
    }

    /**
     * Ricalcolo robusto:
     * hours_consumed = somma ore di TUTTE le lezioni con counts_as_consumed=1
     */
    private function recalcContractConsumedHours(int $contractId): void
    {
        DB::transaction(function () use ($contractId) {
            $contract = Contract::query()->lockForUpdate()->find($contractId);
            if (! $contract) return;

            $lessons = Lesson::query()
                ->where('contract_id', $contractId)
                ->get(['starts_at', 'ends_at', 'duration_minutes', 'counts_as_consumed']);

            $sum = 0;

            foreach ($lessons as $l) {
                if (! (bool) $l->counts_as_consumed) continue;

                $sum += $this->lessonHours(
                    $l->starts_at,
                    $l->ends_at,
                    (int) ($l->duration_minutes ?? 60),
                );
            }

            $contract->hours_consumed = max(0, (float) $sum);
            $contract->save();
        });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1) normalizza start/end e duration_minutes (fine modificabile)
        if (! empty($data['starts_at'])) {
            $start = Carbon::parse($data['starts_at']);

            if (! empty($data['ends_at'])) {
                $end = Carbon::parse($data['ends_at']);
                $mins = $start->diffInMinutes($end, false);

                if ($mins <= 0) {
                    $mins = 60;
                    $data['ends_at'] = $start->copy()->addMinutes(60);
                }

                $data['duration_minutes'] = $mins;
            } else {
                $data['ends_at'] = $start->copy()->addMinutes(60);
                $data['duration_minutes'] = 60;
            }
        }

        // 2) status_virtual => annullamento
        $statusVirtual = $data['status_virtual'] ?? null;

        if (in_array($statusVirtual, ['annullata', 'annullata_recover'], true)) {
            $data['cancelled_at'] = $data['cancelled_at'] ?? now();
            $data['is_recoverable'] = ($statusVirtual === 'annullata_recover');
        } else {
            $data['cancelled_at'] = null;
            $data['cancellation_reason'] = null;
            $data['is_recoverable'] = false;
        }

        // 3) counts_as_consumed (REGOLA UNICA)
        if (! empty($data['cancelled_at'])) {
            // annullata recuperabile => NON consuma
            $data['counts_as_consumed'] = ! (bool) ($data['is_recoverable'] ?? false);
        } else {
            // non annullata: consuma solo se è finita (passata)
            $end = ! empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;
            $data['counts_as_consumed'] = ($end && $end->isPast()) ? 1 : 0;
        }

        return $data;
    }

/*    protected function afterSave(): void
{
    if ($this->contractId) {
        \App\Models\Contract::recalcConsumedHours($this->contractId);
    }
}*/
}
