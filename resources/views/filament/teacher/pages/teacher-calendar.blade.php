<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600">
            Qui vedi le lezioni assegnate a te (range ultimi 30 giorni e prossimi 120).
        </div>

        <div class="rounded-xl border bg-white p-4">
            <ul class="divide-y">
                @forelse ($events as $e)
                    <li class="py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ $e['title'] }}</div>
                            <div class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($e['start'])->format('d/m/Y H:i') }}
                                →
                                {{ \Carbon\Carbon::parse($e['end'])->format('H:i') }}
                            </div>
                        </div>

                        <a href="{{ $e['url'] }}"
                           class="text-sm font-medium text-primary-600 hover:underline">
                            Apri
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-gray-500">Nessuna lezione nel periodo selezionato.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-filament-panels::page>
