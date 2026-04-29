<x-filament-panels::page>

    {{-- Pulsante aggiorna --}}
    <div class="flex justify-end mb-2">
        <button wire:click="refreshHomeworks"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-primary-600 transition">
            <span wire:loading.remove wire:target="refreshHomeworks">🔄 Aggiorna</span>
            <span wire:loading wire:target="refreshHomeworks">Aggiornamento...</span>
        </button>
    </div>

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
                    $sub        = $hw['submission'];
                    $isGraded   = $sub && $sub['status'] === 'graded';
                    $isSubmitted = $sub && $sub['status'] === 'submitted';
                    $isPastDue  = $hw['is_past_due'];
                    $canEdit    = $isSubmitted && ! $isGraded; // può modificare/annullare
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
                                <span class="{{ $isPastDue && !$isSubmitted && !$isGraded ? 'text-red-600 font-semibold' : '' }}">
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

                        {{-- === VOTO (se valutato) === --}}
                        @if($isGraded)
                            <div class="rounded-lg border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-4 space-y-2">
                                <p class="text-sm font-bold text-green-800 dark:text-green-300 text-lg">
                                    🏆 Voto: {{ $sub['grade'] }}
                                </p>
                                @if($sub['teacher_feedback'])
                                    <p class="text-sm text-green-700 dark:text-green-400 italic">
                                        💬 "{{ $sub['teacher_feedback'] }}"
                                    </p>
                                @endif
                                @if($sub['file_name'])
                                    <p class="text-xs text-gray-500 mt-1">
                                        📎 File consegnato: <span class="font-medium">{{ $sub['file_name'] }}</span>
                                    </p>
                                @endif
                                @if($sub['submitted_at'])
                                    <p class="text-xs text-gray-400">Consegnato il {{ $sub['submitted_at'] }}</p>
                                @endif
                            </div>

                        {{-- === CONSEGNATO MA NON ANCORA VALUTATO === --}}
                        @elseif($isSubmitted)
                            <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4 space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                                        📬 Consegnato il {{ $sub['submitted_at'] }} — in attesa di valutazione
                                    </p>
                                    @if($sub['file_name'])
                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                            📎 File: <span class="font-medium">{{ $sub['file_name'] }}</span>
                                        </p>
                                    @endif
                                    @if($sub['student_note'])
                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 italic">
                                            Nota: {{ $sub['student_note'] }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Azioni modifica / annullamento (solo se non valutato) --}}
                                <div class="border-t border-blue-200 dark:border-blue-700 pt-3 space-y-3">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        ✏️ Puoi sostituire il file o annullare la consegna finché non viene valutata.
                                    </p>

                                    {{-- Sostituzione file --}}
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Sostituisci file</label>
                                        <input type="file"
                                               wire:model="uploadedFiles.{{ $hw['id'] }}"
                                               class="block w-full text-sm text-gray-600 dark:text-gray-400
                                                      file:mr-3 file:py-1 file:px-3
                                                      file:rounded-lg file:border-0
                                                      file:text-xs file:font-medium
                                                      file:bg-primary-50 file:text-primary-700
                                                      hover:file:bg-primary-100">
                                        @if(isset($uploadedFiles[$hw['id']]) && $uploadedFiles[$hw['id']])
                                            <p class="text-xs text-green-600 mt-1">✅ Nuovo file pronto</p>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Nota aggiornata</label>
                                        <textarea wire:model="studentNotes.{{ $hw['id'] }}"
                                                  rows="2"
                                                  placeholder="{{ $sub['student_note'] ?? 'Aggiungi o modifica nota...' }}"
                                                  class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:outline-none resize-none"></textarea>
                                    </div>

                                    <div class="flex gap-2">
                                        <button wire:click="updateSubmission({{ $hw['id'] }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1 bg-primary-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-primary-700 transition disabled:opacity-50">
                                            <span wire:loading.remove wire:target="updateSubmission({{ $hw['id'] }})">💾 Aggiorna consegna</span>
                                            <span wire:loading wire:target="updateSubmission({{ $hw['id'] }})">Salvataggio...</span>
                                        </button>
                                        <button wire:click="cancelSubmission({{ $hw['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:confirm="Sei sicuro di voler annullare la consegna?"
                                                class="inline-flex items-center gap-1 bg-red-50 text-red-600 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-200 hover:bg-red-100 transition disabled:opacity-50">
                                            <span wire:loading.remove wire:target="cancelSubmission({{ $hw['id'] }})">🗑 Annulla consegna</span>
                                            <span wire:loading wire:target="cancelSubmission({{ $hw['id'] }})">Annullamento...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        {{-- === DA CONSEGNARE === --}}
                        @elseif(!$isPastDue)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3 bg-gray-50 dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">📤 Consegna il compito</p>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">File (opzionale)</label>
                                    <input type="file"
                                           wire:model="uploadedFiles.{{ $hw['id'] }}"
                                           class="block w-full text-sm text-gray-600 dark:text-gray-400
                                                  file:mr-3 file:py-1.5 file:px-3
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-medium
                                                  file:bg-primary-50 file:text-primary-700
                                                  hover:file:bg-primary-100">
                                    @if(isset($uploadedFiles[$hw['id']]) && $uploadedFiles[$hw['id']])
                                        <p class="text-xs text-green-600 mt-1">✅ File selezionato</p>
                                    @endif
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
