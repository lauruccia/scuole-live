<x-filament-panels::page>

    {{-- ── Ricerca + filtri ──────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2 items-center mb-4">

        <div class="relative flex-1 min-w-52">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" wire:model.live.debounce.250ms="search"
                placeholder="Cerca studente…"
                class="w-full pl-9 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none"/>
        </div>

        <select wire:model.live="filterStatus"
            class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-1.5 focus:ring-1 focus:ring-primary-500 outline-none">
            <option value="">Tutti gli stati</option>
            <option value="active">Attivo</option>
            <option value="completed">Terminato</option>
            <option value="suspended">Sospeso</option>
        </select>

        @if(count($this->availableLanguages) > 0)
        <select wire:model.live="filterLang"
            class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-1.5 focus:ring-1 focus:ring-primary-500 outline-none">
            <option value="">Tutte le lingue</option>
            @foreach($this->availableLanguages as $lang)
                <option value="{{ $lang }}">{{ $lang }}</option>
            @endforeach
        </select>
        @endif

        <label class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" wire:model.live="filterPending" true-value="1" false-value=""
                class="rounded border-gray-300 text-warning-500 focus:ring-warning-400">
            Da valutare
        </label>

        <span class="text-xs text-gray-400 ml-auto">{{ count($this->filteredStudents) }} studenti</span>
    </div>

    {{-- ── Lista ──────────────────────────────────────────────────────────── --}}
    @if(empty($students))
        <div class="text-center py-16 text-gray-400">
            <x-heroicon-o-academic-cap class="mx-auto h-10 w-10 opacity-30 mb-2"/>
            <p class="text-sm">Nessuno studente ancora — le lezioni svolte popoleranno questa pagina.</p>
        </div>

    @elseif(count($this->filteredStudents) === 0)
        <div class="text-center py-10 text-gray-400">
            <x-heroicon-o-magnifying-glass class="mx-auto h-8 w-8 opacity-30 mb-2"/>
            <p class="text-sm">Nessun risultato. Modifica la ricerca o i filtri.</p>
        </div>

    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 shadow-sm overflow-hidden">

            @foreach($this->filteredStudents as $s)
                @php
                    $statusColor = match($s['status']) {
                        'active'    => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'completed' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                        'suspended' => 'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-300',
                        default     => 'bg-gray-100 text-gray-400',
                    };
                    $statusLabel = match($s['status']) {
                        'active'    => 'Attivo',
                        'completed' => 'Terminato',
                        'suspended' => 'Sospeso',
                        default     => '—',
                    };

                    // URL lezioni filtrate per questo studente
                    $lessonsUrl = route('filament.docente.resources.lessons.index')
                        . '?' . http_build_query(['tableFilters' => ['student_id' => ['value' => $s['id']]]]);

                    // URL esercitazioni filtrate per contratto (tableSearch col nome)
                    $homeworkUrl = route('filament.docente.resources.teacher-homeworks.index')
                        . '?' . http_build_query(['tableSearch' => $s['full_name']]);

                    // URL nuova esercitazione (la pagina ha già student_search)
                    $newHomeworkUrl = route('filament.docente.resources.teacher-homeworks.create');

                    // URL materiali
                    $materialsUrl = route('filament.docente.resources.teacher-materials.index');
                @endphp

                <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                    {{-- Nome + email --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $s['full_name'] }}</p>
                        @if($s['email'])
                            <p class="text-xs text-gray-400 truncate">{{ $s['email'] }}</p>
                        @endif
                    </div>

                    {{-- Lingua --}}
                    @if($s['contract_label'] !== '—')
                        <span class="hidden sm:inline-block flex-shrink-0 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-full whitespace-nowrap">
                            {{ $s['contract_label'] }}
                        </span>
                    @endif

                    {{-- Stats --}}
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <div class="hidden md:flex flex-col items-center leading-none">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $s['lessons_done'] }}</span>
                            <span class="text-xs text-gray-400">svolte</span>
                        </div>
                        <div class="hidden md:flex flex-col items-center leading-none">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $s['lessons_scheduled'] }}</span>
                            <span class="text-xs text-gray-400">progr.</span>
                        </div>
                        @if($s['homework_pending'] > 0)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300 whitespace-nowrap">
                                <x-heroicon-m-inbox-arrow-down class="w-3 h-3"/>
                                {{ $s['homework_pending'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Stato --}}
                    <span class="hidden sm:inline-block flex-shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColor }}">
                        {{ $statusLabel }}
                    </span>

                    {{-- Azioni icone --}}
                    <div class="flex-shrink-0 flex items-center gap-0.5">
                        <a href="{{ $lessonsUrl }}" title="Lezioni di {{ $s['full_name'] }}"
                           class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors">
                            <x-heroicon-m-calendar-days class="w-4 h-4"/>
                        </a>

                        @if($s['contract_id'])
                            <a href="{{ $homeworkUrl }}" title="Esercitazioni di {{ $s['full_name'] }}"
                               class="p-1.5 rounded-lg transition-colors {{ $s['homework_pending'] > 0 ? 'text-warning-500 hover:bg-warning-50 dark:hover:bg-warning-900/20' : 'text-gray-400 hover:text-warning-500 hover:bg-warning-50' }}">
                                <x-heroicon-m-inbox-arrow-down class="w-4 h-4"/>
                            </a>

                            <a href="{{ $newHomeworkUrl }}" title="Nuova esercitazione"
                               class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <x-heroicon-m-plus class="w-4 h-4"/>
                            </a>
                        @endif

                        <a href="{{ $materialsUrl }}" title="Materiali"
                           class="p-1.5 rounded-lg text-gray-400 hover:text-success-600 hover:bg-success-50 dark:hover:bg-success-900/20 transition-colors">
                            <x-heroicon-m-document-arrow-down class="w-4 h-4"/>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
