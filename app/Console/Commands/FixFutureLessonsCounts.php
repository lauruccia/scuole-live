<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FixFutureLessonsCounts extends Command
{
    protected $signature = 'lessons:fix-future-counts {--dry-run : Mostra quante righe verrebbero aggiornate}';
    protected $description = 'Imposta counts_as_consumed=0 per lezioni future non annullate (solo una volta).';

    public function handle(): int
    {
        $q = Lesson::query()
            ->whereNull('cancelled_at')
            ->where('ends_at', '>', Carbon::now())
            ->where('counts_as_consumed', 1);

        $count = (clone $q)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: aggiornerei {$count} lezioni.");
            return self::SUCCESS;
        }

        $updated = $q->update(['counts_as_consumed' => 0]);

        $this->info("Aggiornate {$updated} lezioni (su {$count} trovate).");
        return self::SUCCESS;
    }
}
