<x-filament-panels::page>

    {{-- ── Azioni header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            I backup sono salvati in <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs">storage/app/backups</code>.
            Il backup automatico viene eseguito ogni notte alle 02:00.
        </p>

        <button
            wire:click="runBackup"
            wire:loading.attr="disabled"
            wire:target="runBackup"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove wire:target="runBackup">
                <x-heroicon-o-archive-box-arrow-down class="h-4 w-4" />
            </span>
            <span wire:loading wire:target="runBackup">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </span>
            <span wire:loading.remove wire:target="runBackup">Crea backup ora</span>
            <span wire:loading wire:target="runBackup">Backup in corso…</span>
        </button>
    </div>

    {{-- ── Tabella backup ─────────────────────────────────────────────────── --}}
    <x-filament::section heading="File di backup disponibili">

        @if(empty($backupFiles))
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <div class="mx-auto mb-3 opacity-40" style="width:3rem;height:3rem;">
                    <x-heroicon-o-archive-box class="w-full h-full" />
                </div>
                <p class="font-medium">Nessun backup trovato</p>
                <p class="text-sm mt-1">Clicca "Crea backup ora" per generare il primo backup.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4 font-semibold">File</th>
                            <th class="py-3 pr-4 font-semibold">Dimensione</th>
                            <th class="py-3 pr-4 font-semibold">Data</th>
                            <th class="py-3 font-semibold text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($backupFiles as $file)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-archive-box class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300">
                                            {{ $file['name'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                    {{ $file['size'] }}
                                </td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                    {{ $file['date'] }}
                                </td>
                                <td class="py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        {{-- Download --}}
                                        <a href="{{ route('backup.download', ['filename' => $file['name'], 'folder' => dirname($file['path'])]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-md bg-info-50 dark:bg-info-900/30 px-3 py-1.5 text-xs font-medium text-info-700 dark:text-info-400 hover:bg-info-100 dark:hover:bg-info-900/50 transition-colors">
                                            <x-heroicon-m-arrow-down-tray class="h-3.5 w-3.5" />
                                            Scarica
                                        </a>

                                        {{-- Elimina --}}
                                        <button
                                            wire:click="deleteBackup('{{ addslashes($file['path']) }}')"
                                            wire:confirm="Sei sicuro di voler eliminare questo backup? L'operazione è irreversibile."
                                            class="inline-flex items-center gap-1.5 rounded-md bg-danger-50 dark:bg-danger-900/30 px-3 py-1.5 text-xs font-medium text-danger-700 dark:text-danger-400 hover:bg-danger-100 dark:hover:bg-danger-900/50 transition-colors"
                                        >
                                            <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                            Elimina
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                {{ count($backupFiles) }} {{ count($backupFiles) === 1 ? 'file trovato' : 'file trovati' }}
                · Mantenimento automatico: 7 giornalieri, 4 settimanali, 3 mensili
            </p>
        @endif

    </x-filament::section>

</x-filament-panels::page>
