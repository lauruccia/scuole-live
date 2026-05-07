<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Pagina di gestione backup (solo Superadmin).
 *
 * Permette di:
 *  - Visualizzare i backup esistenti con dimensione e data
 *  - Creare un nuovo backup (database) on-demand
 *  - Scaricare un backup
 *  - Eliminare un backup
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

    /** @var array Elenco file di backup caricati */
    public array $backupFiles = [];

    /** @var bool Flag per mostrare spinner durante la creazione */
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

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Ricarica la lista dei file di backup dal disco.
     */
    public function loadFiles(): void
    {
        $disk    = Storage::disk('local-backups');
        $appName = config('backup.backup.name', config('app.name', 'ScuoleLive'));

        $files = collect($disk->files($appName))
            ->filter(fn (string $f) => str_ends_with($f, '.zip'))
            ->map(function (string $path) use ($disk) {
                $basename  = basename($path);
                $size      = $disk->size($path);
                $modified  = $disk->lastModified($path);

                return [
                    'name'      => $basename,
                    'size'      => $this->formatBytes($size),
                    'date'      => date('d/m/Y H:i', $modified),
                    'timestamp' => $modified,
                ];
            })
            ->sortByDesc('timestamp')
            ->values()
            ->toArray();

        $this->backupFiles = $files;
    }

    /**
     * Crea un nuovo backup (solo database, più veloce su hosting condiviso).
     */
    public function runBackup(): void
    {
        $this->running = true;

        try {
            Artisan::call('backup:run', ['--only-db' => true]);

            $this->loadFiles();

            Notification::make()
                ->title('Backup completato')
                ->body('Il backup del database è stato creato con successo.')
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
     * Elimina un file di backup dal disco.
     */
    public function deleteBackup(string $filename): void
    {
        $appName = config('backup.backup.name', config('app.name', 'ScuoleLive'));
        $path    = $appName . '/' . $filename;

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
