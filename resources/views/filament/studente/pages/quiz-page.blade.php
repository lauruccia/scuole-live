<x-filament-panels::page>

    {{-- ── FASE: SELEZIONE LINGUA ─────────────────────────────────────────────── --}}
    @if($phase === 'select')
        <x-filament::section heading="Test di valutazione del livello">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Rispondi alle domande a scelta multipla per scoprire il tuo livello CEFR (A1–C2) nella lingua selezionata.
                Il test è composto da circa 18 domande (3 per livello).
            </p>

            <div class="space-y-4 max-w-sm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Scegli la lingua
                    </label>
                    <select wire:model="selectedLanguage"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">— Seleziona —</option>
                        @foreach(['Arabo','Francese','Inglese','Spagnolo','Tedesco','Italiano per stranieri'] as $lang)
                            <option value="{{ $lang }}">{{ $lang }}</option>
                        @endforeach
                    </select>
                </div>

                <button wire:click="startQuiz"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-primary-700 transition disabled:opacity-50">
                    <x-heroicon-m-play class="h-4 w-4"/>
                    Inizia il test
                </button>
            </div>
        </x-filament::section>

        {{-- Tentativi precedenti --}}
        @if(!empty($pastAttempts))
            <x-filament::section heading="I miei risultati precedenti">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left">Lingua</th>
                                <th class="px-4 py-2 text-left">Livello</th>
                                <th class="px-4 py-2 text-left">Punteggio</th>
                                <th class="px-4 py-2 text-left">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($pastAttempts as $attempt)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-2">{{ $attempt['language'] }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                            @if(in_array($attempt['result_level'], ['A1','A2'])) bg-green-100 text-green-800
                                            @elseif(in_array($attempt['result_level'], ['B1','B2'])) bg-amber-100 text-amber-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ $attempt['result_level'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $attempt['score'] }}/{{ $attempt['total'] }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $attempt['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    {{-- ── FASE: QUIZ ──────────────────────────────────────────────────────────── --}}
    @elseif($phase === 'quiz')
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <span>Test di {{ $selectedLanguage }} — {{ count($answers) }}/{{ count($questions) }} risposte</span>
                    <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 transition-all"
                             style="width: {{ count($questions) > 0 ? round(count($answers)/count($questions)*100) : 0 }}%">
                        </div>
                    </div>
                </div>
            </x-slot>

            <div class="space-y-6">
                @foreach($questions as $i => $q)
                    <div class="rounded-lg border {{ isset($answers[$q['id']]) ? 'border-primary-300 dark:border-primary-600' : 'border-gray-200 dark:border-gray-700' }} p-4">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold flex items-center justify-center text-gray-600">
                                {{ $i + 1 }}
                            </span>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ $q['question_text'] }}
                                <span class="ml-2 text-xs text-gray-400 font-normal">({{ $q['cefr_level'] }})</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 ml-9">
                            @foreach($q['options'] as $idx => $option)
                                <button wire:click="selectAnswer({{ $q['id'] }}, {{ $idx }})"
                                        class="text-left text-sm px-3 py-2 rounded-lg border transition-colors
                                            {{ ($answers[$q['id']] ?? -1) === $idx
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-500 bg-white dark:bg-gray-800' }}">
                                    <span class="font-bold mr-1">{{ ['A','B','C','D'][$idx] }}.</span>
                                    {{ $option }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center justify-between pt-2">
                    <button wire:click="resetQuiz"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        ← Annulla test
                    </button>
                    <button wire:click="submitQuiz"
                            wire:loading.attr="disabled"
                            @if(count($answers) < count($questions)) disabled @endif
                            class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-primary-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="submitQuiz">
                            🎯 Concludi il test ({{ count($answers) }}/{{ count($questions) }})
                        </span>
                        <span wire:loading wire:target="submitQuiz">Calcolo...</span>
                    </button>
                </div>
            </div>
        </x-filament::section>

    {{-- ── FASE: RISULTATO ─────────────────────────────────────────────────────── --}}
    @elseif($phase === 'result')
        <x-filament::section>
            <div class="text-center py-6 space-y-4">
                <div class="text-6xl font-black
                    @if(in_array($result['level'], ['A1','A2'])) text-green-600
                    @elseif(in_array($result['level'], ['B1','B2'])) text-amber-600
                    @else text-red-600 @endif">
                    {{ $result['level'] }}
                </div>
                <div>
                    <p class="text-xl font-semibold text-gray-800 dark:text-white">
                        Il tuo livello di {{ $result['language'] }} è <strong>{{ $result['level'] }}</strong>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Hai risposto correttamente a {{ $result['score'] }} domande su {{ $result['total'] }}
                        ({{ $result['percent'] }}%)
                    </p>
                </div>

                @php
                    $descriptions = [
                        'A1' => 'Livello elementare: conosci parole e frasi di base.',
                        'A2' => 'Livello pre-intermedio: riesci a comunicare su argomenti familiari.',
                        'B1' => 'Livello intermedio: puoi affrontare situazioni quotidiane.',
                        'B2' => 'Livello intermedio-avanzato: buona padronanza della lingua.',
                        'C1' => 'Livello avanzato: uso fluente e spontaneo della lingua.',
                        'C2' => 'Livello esperto: padronanza quasi madrelingua.',
                    ];
                @endphp
                <p class="text-sm text-gray-600 dark:text-gray-300 max-w-md mx-auto">
                    {{ $descriptions[$result['level']] ?? '' }}
                </p>

                <div class="flex justify-center gap-3 pt-2">
                    <button wire:click="resetQuiz"
                            class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-primary-700 transition">
                        🔄 Rifai il test
                    </button>
                </div>

                <p class="text-xs text-gray-400 dark:text-gray-500 pt-2">
                    Il risultato è stato salvato. Puoi condividerlo con la segreteria per ricevere un consiglio sul corso più adatto.
                </p>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
