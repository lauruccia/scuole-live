<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

class AutoCompleteContracts extends Command
{
    protected $signature = 'contracts:auto-complete {--dry-run : Mostra i contratti che verrebbero completati senza modificarli}';
    protected $description = 'Imposta come "completato" i contratti attivi le cui lezioni sono tutte finite.';

    public function handle(): int
    {
        $done = 0;

        Contract::query()
            ->where(fn ($q) => $q->where('status', 'active')->orWhereNull('status'))
            ->where('auto_complete_disabled', false)
            ->where('hours_purchased', '>', 0)
            ->chunkById(100, function ($contracts) use (&$done) {
                foreach ($contracts as $contract) {
                    if (! $contract->isFinished()) {
                        continue;
                    }

                    $this->line("Contratto #{$contract->id}: completato");

                    if (! $this->option('dry-run')) {
                        $contract->markCompleted('auto');
                    }

                    $done++;
                }
            });

        $this->info(($this->option('dry-run') ? 'Dry run: ' : '') . "{$done} contratti completati.");

        return self::SUCCESS;
    }
}
