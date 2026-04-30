<x-filament::page>
    <div class="space-y-6">

        {{-- FILTRI --}}
        <x-filament::section>
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button
                    wire:click="runChecks"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-magnifying-glass"
                    size="lg"
                    color="primary"
                >
                    <span wire:loading.remove wire:target="runChecks">Avvia controllo</span>
                    <span wire:loading wire:target="runChecks">⏳ Analisi in corso…</span>
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- RIEPILOGO --}}
        @if ($this->ran)
            @php $total = $this->getTotalIssues(); @endphp

            <div class="rounded-xl border px-6 py-4 flex items-center gap-4
                {{ $total === 0
                    ? 'bg-success-50 border-success-300 dark:bg-success-950 dark:border-success-700'
                    : ($total >= 5 ? 'bg-danger-50 border-danger-300 dark:bg-danger-950 dark:border-danger-700'
                                   : 'bg-warning-50 border-warning-300 dark:bg-warning-950 dark:border-warning-700') }}">
                <div class="text-5xl leading-none">{{ $total === 0 ? '✅' : '⚠️' }}</div>
                <div>
                    @if ($total === 0)
                        <p class="text-xl font-bold text-success-700 dark:text-success-300">Tutto in ordine</p>
                        <p class="text-sm text-success-600 dark:text-success-400 mt-0.5">Nessuna anomalia trovata nel periodo selezionato.</p>
                    @else
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">
                            {{ $total }} {{ $total === 1 ? 'anomalia trovata' : 'anomalie trovate' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            Espandi le sezioni qui sotto per i dettagli e le istruzioni di risoluzione.
                        </p>
                    @endif
                </div>
            </div>

            {{-- SEZIONI --}}
            @foreach ($this->results as $key => $section)
                @php
                    $issueCount   = $this->getSectionIssueCount($section);
                    $sectionLabel = $this->getSectionLabel($key);
                    $hasDanger    = collect($section)->contains(fn ($i) => $i['severity'] === 'danger' && $i['count'] > 0);
                    $headingColor = $issueCount === 0 ? 'text-success-700 dark:text-success-400'
                                  : ($hasDanger ? 'text-danger-700 dark:text-danger-400'
                                                : 'text-warning-700 dark:text-warning-400');
                @endphp

                <x-filament::section
                    collapsible
                    :collapsed="$issueCount === 0"
                >
                    <x-slot name="heading">
                        <span class="{{ $headingColor }} font-semibold">
                            {{ $sectionLabel }}
                            @if ($issueCount > 0)
                                <span class="ml-2 text-xs font-bold rounded-full px-2 py-0.5
                                    {{ $hasDanger ? 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300'
                                                  : 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300' }}">
                                    {{ $issueCount }} {{ $issueCount === 1 ? 'problema' : 'problemi' }}
                                </span>
                            @else
                                <span class="ml-2 text-xs font-bold rounded-full px-2 py-0.5 bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">
                                    OK
                                </span>
                            @endif
                        </span>
                    </x-slot>

                    <div class="space-y-2">
                        @foreach ($section as $item)
                            @php
                                $isOk = $item['severity'] === 'success' || $item['count'] === 0;
                                $borderColor = $isOk ? 'border-success-300 dark:border-success-700'
                                    : ($item['severity'] === 'danger' ? 'border-danger-400 dark:border-danger-600'
                                                                      : 'border-warning-400 dark:border-warning-600');
                                $bgColor = $isOk ? 'bg-success-50 dark:bg-success-950/40'
                                    : ($item['severity'] === 'danger' ? 'bg-danger-50 dark:bg-danger-950/40'
                                                                      : 'bg-warning-50 dark:bg-warning-950/40');
                                $icon = $isOk ? '🟢' : ($item['severity'] === 'danger' ? '🔴' : '🟡');
                                $badgeClass = $isOk
                                    ? 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200'
                                    : ($item['severity'] === 'danger'
                                        ? 'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200'
                                        : 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200');
                            @endphp

                            <div class="rounded-lg border {{ $borderColor }} {{ $bgColor }} overflow-hidden">
                                {{-- Header riga --}}
                                <div class="flex items-center gap-3 px-4 py-3">
                                    {{-- Icona + badge count --}}
                                    <div class="flex flex-col items-center shrink-0 w-12 text-center">
                                        <span class="text-lg leading-none">{{ $icon }}</span>
                                        <span class="mt-1 text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[1.5rem] text-center {{ $badgeClass }}">
                                            {{ $item['count'] }}
                                        </span>
                                    </div>

                                    {{-- Testo principale --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm leading-snug">
                                            {{ $item['label'] }}
                                        </p>
                                        @if (!$isOk && !empty($item['description']))
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-relaxed">
                                                {{ $item['description'] }}
                                            </p>
                                        @elseif ($isOk)
                                            <p class="text-xs text-success-600 dark:text-success-400 mt-0.5">Nessun problema rilevato.</p>
                                        @endif
                                    </div>

                                    {{-- Link "Visualizza" --}}
                                    @if (!$isOk && !empty($item['url']))
                                        <div class="shrink-0">
                                            <a href="{{ $item['url'] }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-md
                                                      bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600
                                                      text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700
                                                      transition-colors whitespace-nowrap">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                Visualizza
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- "Come risolvere" — solo se c'è un problema --}}
                                @if (!$isOk && !empty($item['howToFix']))
                                    <div class="border-t {{ $borderColor }} bg-white/60 dark:bg-gray-900/40 px-4 py-2.5 flex items-start gap-2">
                                        <span class="text-blue-500 shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                                            </svg>
                                        </span>
                                        <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                                            <span class="font-semibold">Come risolvere:</span>
                                            {{ $item['howToFix'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach

        @endif

    </div>
</x-filament::page>
