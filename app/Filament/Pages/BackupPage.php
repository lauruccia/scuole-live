<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Pagina di gestione backup (solo Superadmin).
 *
 * Implementa un backup PHP-nativo del database, senza dipendere da
 * spatie/laravel-backup o da mysqldump (non sempre disponibile su cPanel).
 *
 * Il dump viene salvato come <timestamp>.sql, compresso in un .zip e
 * archiviato sul disco 'local-backups' (storage/app/backups/).
 */
class BackupPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Backup';
    protected static ?string $title           = 'Backup database e file';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $slug            = 'backup';
    protected static string  $view            = 'filament.pages.backup-page';
    protected static ?int    $navigationSort  = 90;

    /** @var array Elenco file di backup */
    public array $backupFiles = [];

    /** @var bool Flag spinner durante la creazione */
    public bool $running = false;

    // ── Accesso ───────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) return false;

        return $u->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadFiles();
    }

    // ── File listing ──────────────────────────────────────────────────────────

    /**
     * Carica la lista dei .zip presenti nel disco di backup.
     * Cerca nella sottocartella col nome dell'app E nella root (per robustezza).
     */
    public function loadFiles(): void
    {
        $disk    = Storage::disk('local-backups');
        $appName = config('backup.backup.name', config('app.name', 'ScuoleLive'));

        // Cerca in <appName>/ e nella root
        $candidates = collect();

        foreach ([$appName, ''] as $folder) {
            try {
                $path  = $folder === '' ? '' : $folder;
                $items = $disk->files($path);
                $candidates = $candidates->merge(
                    collect($items)->filter(fn ($f) => str_ends_with($f, '.zip'))
                );
            } catch (\Throwable) {
                // cartella non esiste ancora: ok
            }
        }

        $this->backupFiles = $candidates
            ->unique()
            ->map(function (string $path) use ($disk) {
                return [
                    'name'      => basename($path),
                    'path'      => $path,
                    'size'      => $this->formatBytes((int) $disk->size($path)),
                    'date'      => date('d/m/Y H:i', (int) $disk->lastModified($path)),
                    'timestamp' => (int) $disk->lastModified($path),
                ];
            })
            ->sortByDesc('timestamp')
            ->values()
            ->toArray();
    }

    // ── Creazione backup ──────────────────────────────────────────────────────

    /**
     * Crea un backup PHP-nativo del database (nessuna dipendenza esterna).
     *
     * 1. Legge la lista delle tabelle via PDO/SHOW TABLES
     * 2. Dumpa CREATE TABLE + INSERT INTO per ogni tabella
     * 3. Salva il .sql in una cartella temporanea
     * 4. Lo comprime in un .zip e lo sposta su 'local-backups'
     */
    public function runBackup(): void
    {
        $this->running = true;

        try {
            $timestamp = now()->format('Y-m-d-H-i-s');
            $sqlFile   = storage_path("app/backup-temp/{$timestamp}.sql");
            $zipFile   = storage_path("app/backup-temp/{$timestamp}.zip");

            // Assicura che la cartella temporanea esista
            @mkdir(storage_path('app/backup-temp'), 0755, true);

            // ── 1. Genera il dump SQL ──────────────────────────────────────
            $this->generateSqlDump($sqlFile);

            // ── 2. Comprime in zip ────────────────────────────────────────
            if (! class_exists(ZipArchive::class)) {
                throw new \RuntimeException('Estensione PHP ZipArchive non disponibile sul server.');
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Impossibile creare il file zip.');
            }
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();

            // ── 3. Sposta sul disco di backup ─────────────────────────────
            $disk    = Storage::disk('local-backups');
            $appName = config('backup.backup.name', config('app.name', 'ScuoleLive'));
            $dest    = $appName . '/' . $timestamp . '.zip';

            $disk->put($dest, file_get_contents($zipFile));

            // Pulizia temporanei
            @unlink($sqlFile);
            @unlink($zipFile);

            $this->loadFiles();

            Notification::make()
                ->title('Backup completato')
                ->body("File: {$timestamp}.zip — Database esportato con successo.")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Errore durante il backup')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->running = false;
        }
    }

    /**
     * Genera un dump SQL completo di tutte le tabelle del database.
     */
    private function generateSqlDump(string $outputFile): void
    {
        $pdo      = DB::getPdo();
        $dbName   = DB::getDatabaseName();
        $output   = [];

        $output[] = "-- ScuoleLive Database Backup";
        $output[] = "-- Generato: " . now()->format('Y-m-d H:i:s');
        $output[] = "-- Database: {$dbName}";
        $output[] = "-- --------------------------------------------------------";
        $output[] = "";
        $output[] = "SET FOREIGN_KEY_CHECKS=0;";
        $output[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
        $output[] = "SET time_zone='+00:00';";
        $output[] = "";

        // Lista tabelle
        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_NUM);

        foreach ($tables as $tableRow) {
            $table = $tableRow[0];

            // CREATE TABLE
            $createSql = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $output[] = "-- --------------------------------------------------------";
            $output[] = "-- Tabella: `{$table}`";
            $output[] = "-- --------------------------------------------------------";
            $output[] = "";
            $output[] = "DROP TABLE IF EXISTS `{$table}`;";
            $output[] = $createSql[1] . ";";
            $output[] = "";

            // Dati: INSERT a blocchi di 500 righe
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (! empty($rows)) {
                $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $chunks  = array_chunk($rows, 500);

                foreach ($chunks as $chunk) {
                    $values = array_map(function (array $row) use ($pdo) {
                        return '(' . implode(', ', array_map(
                            fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                            $row
                        )) . ')';
                    }, $chunk);

                    $output[] = "INSERT INTO `{$table}` ({$columns}) VALUES";
                    $output[] = implode(",\n", $values) . ";";
                    $output[] = "";
                }
            }
        }

        $output[] = "";
        $output[] = "SET FOREIGN_KEY_CHECKS=1;";

        file_put_contents($outputFile, implode("\n", $output));
    }

    // ── Elimina ───────────────────────────────────────────────────────────────

    public function deleteBackup(string $path): void
    {
        // $path è il percorso relativo al disco (es. "A&A/2026-05-07-10-00-00.zip")
        Storage::disk('local-backups')->delete($path);
        $this->loadFiles();

        Notification::make()
            ->title('Backup eliminato')
            ->success()
            ->send();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)    return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
