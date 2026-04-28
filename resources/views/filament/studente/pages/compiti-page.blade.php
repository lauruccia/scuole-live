<x-filament-panels::page>

    @if(empty($homeworks))
        <x-filament::section>
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-clipboard-document-check class="mx-auto h-12 w-12 opacity-40 mb-3"/>
                <p class="font-medium">Nessun compito assegnato</p>
                <p class="text-sm mt-1">I compiti assegnati dal tuo docente appariranno qui.</p>
            </div>
        </x-filament::section>
    @else
        <div class="space-y-4">
            @foreach($homeworks as $hw)
                @php
                    $sub = $hw['submission'];
                    $isGraded   = $sub && $sub['status'] === 'graded';
                    $isSubmitted = $sub && in_array($sub['status'], ['submitted', 'graded']);
                    $isPastDue  = $hw['is_past_due'];
                @endphp

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span>{{ $hw['title'] }}</span>
                            @if($hw['language'])
                                <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full">
                                    {{ $hw['language'] }}
                                </span>
                            @endif
                            @if($isGraded)
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 px-2 py-0.5 rounded-full">
                                    ✅ Valutato
                                </span>
                            @elseif($isSubmitted)
                                <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full">
                                    📬 Consegnato
                                </span>
                            @elseif($isPastDue)
                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 px-2 py-0.5 rounded-full">
                                    ⚠️ Scaduto
                                </span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300 px-2 py-0.5 rounded-full">
                                    ⏳ Da consegnare
                                </span>
                            @endif
                        </div>
                    </x-slot>

                    <div class="space-y-3">
                        {{-- Meta info --}}
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>👩‍🏫 {{ $hw['teacher_name'] }}</span>
                            @if($hw['due_at'])
                                <span class="{{ $isPastDue && !$isSubmitted ? 'text-red-600 font-semibold' : '' }}">
                                    ⏰ Scadenza: {{ $hw['due_at'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Istruzioni --}}
                        @if($hw['instructions'])
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $hw['instructions'] }}
                            </div>
                        @endif

                        {{-- Allegato docente --}}
                        @if($hw['attachment_path'])
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($hw['attachment_path']) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:underline">
                                📎 Scarica traccia: {{ $hw['attachment_name'] ?? 'allegato' }}
                            </a>
                        @endif

                        {{-- Voto e feedback --}}
                        @if($isGraded)
                            <div class="rounded-lg border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-3 space-y-1">
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                                    🏆 Voto: {{ $sub['grade'] }}
                                </p>
                                @if($sub['teacher_feedback'])
                                    <p class="text-sm text-green-700 dark:text-green-400 italic">
                                        "{{ $sub['teacher_feedback'] }}"
                                    </p>
                                @endif
                            </div>
                        @elseif($isSubmitted)
                            <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-3">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    ✅ Consegnato il {{ $sub['submitted_at'] }}
                                    @if($sub['file_name'])
                                        — <span class="font-medium">{{ $sub['file_name'] }}</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Form consegna (solo se non ancora consegnato e non scaduto) --}}
                        @if(!$isSubmitted && !($isPastDue))
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3 bg-gray-50 dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">📤 Consegna il compito</p>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">File (opzionale)</label>
                                    <input type="file"
                                           wire:model="uploadFiles.{{ $hw['id'] }}"
                                           class="block w-full text-sm text-gray-600 dark:text-gray-400
                                                  file:mr-3 file:py-1.5 file:px-3
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-medium
                                                  file:bg-primary-50 file:text-primary-700
                                                  hover:file:bg-primary-100">
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nota (opzionale)</label>
                                    <textarea wire:model="studentNotes.{{ $hw['id'] }}"
                                              rows="2"
                                              placeholder="Aggiungi un messaggio per il docente..."
                                              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:outline-none resize-none"></textarea>
                                </div>

                                <button wire:click="submitHomework({{ $hw['id'] }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 bg-primary-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary-700 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="submitHomework({{ $hw['id'] }})">📤 Consegna</span>
                                    <span wire:loading wire:target="submitHomework({{ $hw['id'] }})">Invio...</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
