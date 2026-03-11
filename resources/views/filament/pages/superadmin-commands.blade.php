<x-filament::page>
    @php
        $commands = $this->getCommandsCatalog();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach($commands as $cmd)
            @php
                $tone = $cmd['tone'] ?? 'gray';
                $classes = $this->toneClasses($tone);
            @endphp

            <x-filament::section class="border {{ $classes['card'] }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold">
                                {{ $cmd['title'] }}
                            </h3>

                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $classes['badge'] }}">
                                {{ $cmd['subtitle'] }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-gray-600">
                            {{ $cmd['description'] }}
                        </p>
                    </div>

                    <div class="shrink-0">
                        {{-- ✅ Bottone corretto: avvia l’action Filament via Livewire --}}
                        <x-filament::button
                            size="sm"
                            color="{{ $tone === 'danger' ? 'danger' : ($tone === 'warning' ? 'warning' : 'primary') }}"
                            wire:click="mountAction('{{ $cmd['key'] }}')"
                        >
                            Esegui
                        </x-filament::button>
                    </div>
                </div>

                @if(($cmd['tone'] ?? null) === 'danger')
                    <div class="mt-3 text-xs text-danger-700 bg-danger-50 border border-danger-200 rounded-lg p-2">
                        Attenzione: operazione potenzialmente pesante. Valuta un dry-run o un range (from_id/to_id) prima.
                    </div>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Note operative</x-slot>

        <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
            <li>Le operazioni vengono eseguite in modo sincrono: su dataset grandi possono impiegare tempo.</li>
            <li>Usa <strong>Dry-run</strong> quando disponibile per stimare l’impatto senza scrivere sul DB.</li>
            <li>Dopo import massivi è normale lanciare <strong>Bonifica ore fruite</strong> e (se serve) <strong>Backfill Billing Profiles</strong>.</li>
        </ul>
    </x-filament::section>

    {{-- ✅ NECESSARIO: qui Filament stampa i modali delle Actions --}}
    <x-filament-actions::modals />
</x-filament::page>
