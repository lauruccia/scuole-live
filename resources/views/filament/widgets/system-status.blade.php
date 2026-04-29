@php
    $data = $this->getStatusData();

    $colorMap = [
        'ok'      => ['bg' => 'bg-success-50',  'border' => 'border-success-300',  'dot' => 'bg-success-500',  'text' => 'text-success-700'],
        'warning' => ['bg' => 'bg-warning-50',  'border' => 'border-warning-300',  'dot' => 'bg-warning-500',  'text' => 'text-warning-700'],
        'error'   => ['bg' => 'bg-danger-50',   'border' => 'border-danger-300',   'dot' => 'bg-danger-500',   'text' => 'text-danger-700'],
    ];

    $icons = [
        'google' => 'heroicon-o-cloud',
        'email'  => 'heroicon-o-envelope',
        'queue'  => 'heroicon-o-queue-list',
    ];

    $titles = [
        'google' => 'Google OAuth',
        'email'  => 'Email',
        'queue'  => 'Code (Queue)',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-server-stack"
        heading="Stato sistema"
        description="Aggiornamento automatico ogni 60 secondi"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach (['google', 'email', 'queue'] as $key)
                @php
                    $item   = $data[$key];
                    $status = $item['status'];
                    $colors = $colorMap[$status];
                @endphp

                <div class="rounded-xl border p-4 {{ $colors['bg'] }} {{ $colors['border'] }}">
                    {{-- Intestazione --}}
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full {{ $colors['dot'] }}"></span>
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $titles[$key] }}
                        </span>
                    </div>

                    {{-- Label principale --}}
                    <p class="text-sm font-bold {{ $colors['text'] }} leading-tight mb-1">
                        {{ $item['label'] }}
                    </p>

                    {{-- Dettaglio --}}
                    @if (!empty($item['detail']))
                        <p class="text-xs text-gray-500 leading-snug">
                            {{ $item['detail'] }}
                        </p>
                    @endif

                    {{-- Action button (solo per Google quando c'è un problema) --}}
                    @if (!empty($item['action_url']))
                        <a href="{{ $item['action_url'] }}"
                           class="inline-block mt-3 text-xs font-semibold px-3 py-1 rounded-lg
                                  bg-white border {{ $colors['border'] }} {{ $colors['text'] }}
                                  hover:opacity-80 transition">
                            {{ $item['action_label'] }} →
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Banner alert globale se c'è almeno un errore critico --}}
        @if (collect($data)->contains('status', 'error'))
            <div class="mt-4 rounded-lg bg-danger-50 border border-danger-200 px-4 py-3 flex items-start gap-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-600 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-danger-700">Attenzione: ci sono problemi critici</p>
                    <p class="text-xs text-danger-600 mt-0.5">
                        Controlla le sezioni evidenziate in rosso e intervieni prima di mettere il sistema in produzione.
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
