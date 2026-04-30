<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BonificaConsumiCommand extends Command
{
    protected $signature = 'scuole:bonifica
        {--contract= : ID contratto specifico (opzionale)}
        {--from= : Data inizio (YYYY-MM-DD) per filtrare le lezioni}
        {--to= : Data fine (YYYY-MM-DD) per filtrare le lezioni}
        {--chunk=300 : Dimensione chunk}
        {--dry : Simula senza salvare}';

    protected $description = 'Bonifica flag lezioni (counts_as_consumed/is_recoverable) e ricalcola ore consumate contratti';

    public function handle(): int
    {
        $contractId = $this->option('contract');
        $chunk = (int) ($this->option('chunk') ?? 300);
        $dry = (bool) $this->option('dry');

        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to   = $this->option('to')   ? Carbon::parse($this->option('to'))->endOfDay()     : null;

        $this->info('--- Bonifica avviata ---');
        $this->line('Dry run: ' . ($dry ? 'SI' : 'NO'));
        $this->line('Chunk: ' . $chunk);
        if ($contractId) $this->line('Contract ID: ' . $contractId);
        if ($from) $this->line('From: ' . $from->toDateString());
        if ($to) $this->line('To: ' . $to->toDateString());

        // 1) Bonifica flags lezioni
        $updated = 0;
        $scanned = 0;

        $lessonsQuery = Lesson::query()->orderBy('id');

        if ($contractId) {
            $lessonsQuery->where('contract_id', (int) $contractId);
        }

        if ($from) {
            // filtro sulla data lezione (starts_at)
            $lessonsQuery->where('lessons.starts_at', '>=', $from);
        }

        if ($to) {
            $lessonsQuery->where('lessons.starts_at', '<=', $to);
        }

        $this->info('1/2 Bonifica flags lessons...');

        $lessonsQuery->chunkById($chunk, function ($lessons) use (&$updated, &$scanned, $dry) {
            foreach ($lessons as $lesson) {
                $scanned++;

                $oldConsumed = (bool) $lesson->counts_as_consumed;
                $oldRecover  = (bool) $lesson->is_recoverable;

                $lesson->recomputeFlags();

                $changed = ($lesson->counts_as_consumed !== $oldConsumed) || ($lesson->is_recoverable !== $oldRecover);

                if ($changed) {
                    $updated++;
                    if (! $dry) {
                        $lesson->saveQuietly();
                    }
                }
            }

            $this->line("   -> lessons scan: {$scanned} | updated: {$updated}");
        });

        $this->info("Flags lessons completata. Tot scan: {$scanned}, aggiornate: {$updated}");

        // 2) Ricalcolo contratti
        $this->info('2/2 Ricalcolo ore consumate contratti...');

        $contractsQuery = Contract::query()->select('id')->orderBy('id');

        if ($contractId) {
            $contractsQuery->where('id', (int) $contractId);
        }

        $recalced = 0;

        $contractsQuery->chunkById($chunk, function ($contracts) use (&$recalced, $dry) {
            foreach ($contracts as $c) {
                $recalced++;
                if (! $dry) {
                    Contract::recalcConsumedHours((int) $c->id);
                }
            }

            $this->line("   -> contracts recalced: {$recalced}");
        });

        $this->info("--- Bonifica finita ---");

        return self::SUCCESS;
    }
}
