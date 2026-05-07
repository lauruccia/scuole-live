<x-filament::page>
    <div class="space-y-6">

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Anteprima delle notifiche automatiche che verranno inviate dal sistema oggi
            ({{ $this->oggi }}). Le notifiche già inviate sono contrassegnate.
        </p>

        {{-- Rate in scadenza tra 5 giorni --}}
        <x-filament::section heading="Rate in scadenza tra 5 giorni" icon="heroicon-o-credit-card">
            @if(count($this->rateInScadenza) === 0)
                <p class="text-sm text-gray-400 italic">Nessuna rata in scadenza tra 5 giorni.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                            <tr>
                                <th class="px-3 py-2">Studenti</th>
                                <th class="px-3 py-2">Corso</th>
                                <th class="px-3 py-2">Scadenza</th>
                                <th class="px-3 py-2">Importo</th>
                                <th class="px-3 py-2">Stato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($this->rateInScadenza as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-2">{{ $row['studenti'] }}</td>
                                    <td class="px-3 py-2">{{ $row['corso'] }}</td>
                                    <td class="px-3 py-2">{{ $row['data_scadenza'] }}</td>
                                    <td class="px-3 py-2">{{ $row['importo'] }}</td>
                                    <td class="px-3 py-2">
                                        @if($row['gia_inviata'])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Già inviata
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                                <x-heroicon-s-clock class="w-3.5 h-3.5" />
                                                Da inviare
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Corsi in scadenza tra 20 giorni --}}
        <x-filament::section heading="Contratti in scadenza tra 20 giorni" icon="heroicon-o-academic-cap">
            @if(count($this->corsiInScadenza) === 0)
                <p class="text-sm text-gray-400 italic">Nessun contratto in scadenza tra 20 giorni.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                            <tr>
                                <th class="px-3 py-2">Studenti</th>
                                <th class="px-3 py-2">Corso</th>
                                <th class="px-3 py-2">Fine corso</th>
                                <th class="px-3 py-2">Stato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($this->corsiInScadenza as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-2">{{ $row['studenti'] }}</td>
                                    <td class="px-3 py-2">{{ $row['corso'] }}</td>
                                    <td class="px-3 py-2">{{ $row['data_fine'] }}</td>
                                    <td class="px-3 py-2">
                                        @if($row['gia_inviata'])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Già inviata
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                                <x-heroicon-s-clock class="w-3.5 h-3.5" />
                                                Da inviare
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

    </div>
</x-filament::page>
