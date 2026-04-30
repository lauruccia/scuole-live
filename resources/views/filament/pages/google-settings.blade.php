<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::section>
            <x-slot name="heading">Stato collegamento</x-slot>

            @php
                $connected = $account->isConnected();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500">Stato</div>
                    <div class="text-lg font-semibold">
                        @if($connected)
                            ✅ Collegato
                        @else
                            ❌ Non collegato
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Email Google</div>
                    <div class="text-lg font-semibold">{{ $account->email ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Calendar ID</div>
                    <div class="text-lg font-semibold">{{ $account->calendar_id ?? 'primary' }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        (Puoi usare “primary” oppure l’ID di un calendario condiviso della scuola.)
                    </div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Scadenza token</div>
                    <div class="text-lg font-semibold">{{ $account->expires_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Nota</x-slot>
            <div class="text-sm text-gray-600">
                Il collegamento va fatto una sola volta dall’account Google della scuola.
                Dopo il collegamento, creazione/annullamento eventi funzioneranno automaticamente.
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
