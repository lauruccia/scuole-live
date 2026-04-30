<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\LessonGeneratorService;
use Illuminate\Console\Command;

class RegenerateAllLessons extends Command
{
    protected $signature = 'lessons:regenerate-all
        {--chunk=200 : Quanti contratti per batch}
        {--from_id= : Riparti da un certo contract_id}
        {--to_id= : Ferma a un certo contract_id}
        {--dry-run : Non scrive nulla, solo conteggi}
        {--force : Forza rigenerazione completa (starts_at anche nel passato)}';

    protected $description = 'Rigenera (o completa) le lezioni per tutti i contratti.';

    public function handle(LessonGeneratorService $service): int
    {
        $chunk = (int) $this->option('chunk');
        $fromId = $this->option('from_id') ? (int) $this->option('from_id') : null;
        $toId = $this->option('to_id') ? (int) $this->option('to_id') : null;

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $q = Contract::query()
            ->whereNotNull('starts_at')
            ->with(['course']) // evita N+1 su lessons_count
            ->orderBy('id');

        if ($fromId) $q->where('id', '>=', $fromId);
        if ($toId) $q->where('id', '<=', $toId);

        $total = (clone $q)->count();
        $this->info("Contratti da processare: {$total}");
        $this->info("Modalità: " . ($dryRun ? "DRY-RUN" : "WRITE") . " | force=" . ($force ? "true" : "false"));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $errors = 0;

        $q->chunkById($chunk, function ($contracts) use ($service, $dryRun, $force, $bar, &$errors) {
            foreach ($contracts as $contract) {
                try {
                    if (! $contract->starts_at) {
                        $bar->advance();
                        continue;
                    }

                    $lessonsCount = (int) ($contract->hours_purchased ?? $contract->course?->hours_purchased ?? 0);
                    if ($lessonsCount <= 0) {
                        $bar->advance();
                        continue;
                    }

                    if (! $dryRun) {
                        $service->generateForContract($contract, $force);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Errore contract_id={$contract->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Fatto. Errori: {$errors}");

        return self::SUCCESS;
    }
}
