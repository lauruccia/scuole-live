<x-filament-panels::page>

    {{-- Header con contatori --}}
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Clicca su uno stato per spostare il lead nella pipeline.
        </p>
        <a href="{{ \App\Filament\Resources\LeadResource::getUrl('create') }}"
           class="fi-btn fi-btn-size-sm fi-color-primary fi-btn-color-primary inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-white shadow-sm bg-primary-600 hover:bg-primary-500">
            + Nuovo lead
        </a>
    </div>

    @php
        $columns     = $this->getColumns();
        $leadsByStatus = $this->getLeadsByStatus();
    @endphp

    {{-- Kanban board --}}
    <div class="flex gap-4 overflow-x-auto pb-4" style="align-items: flex-start;">

        @foreach ($columns as $statusKey => $col)

            @php $leads = $leadsByStatus[$statusKey] ?? collect(); @endphp

            <div class="flex-shrink-0 w-64 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                 style="background: {{ $col['bg'] }};">

                {{-- Intestazione colonna --}}
                <div class="px-3 py-2.5 flex items-center justify-between border-b border-gray-200 dark:border-gray-700"
                     style="border-left: 4px solid {{ $col['color'] }};">
                    <span class="font-semibold text-sm" style="color: {{ $col['color'] }};">
                        {{ $col['label'] }}
                    </span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-bold text-white"
                          style="background: {{ $col['color'] }};">
                        {{ $leads->count() }}
                    </span>
                </div>

                {{-- Card lead --}}
                <div class="p-2 flex flex-col gap-2 min-h-24">

                    @forelse ($leads as $lead)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm p-3 text-sm">

                            {{-- Nome + link --}}
                            <a href="{{ \App\Filament\Resources\LeadResource::getUrl('edit', ['record' => $lead]) }}"
                               class="font-semibold text-gray-900 dark:text-gray-100 hover:underline block truncate">
                                {{ $lead->full_name }}
                            </a>

                            {{-- Corso --}}
                            @if ($lead->course_interest)
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $lead->course_interest }}</p>
                            @endif

                            {{-- Telefono / email --}}
                            @if ($lead->phone)
                                <p class="text-xs text-gray-400 mt-1">📞 {{ $lead->phone }}</p>
                            @endif

                            {{-- Follow-up --}}
                            @if ($lead->followup_at)
                                <p class="text-xs mt-1 {{ $lead->hasOverdueFollowup() ? 'text-red-600 font-semibold' : ($lead->hasFollowupToday() ? 'text-amber-600 font-semibold' : 'text-gray-400') }}">
                                    🗓 {{ $lead->followup_at->format('d/m/Y') }}
                                    @if ($lead->hasOverdueFollowup()) ⚠️ @endif
                                </p>
                            @endif

                            {{-- Assegnato --}}
                            @if ($lead->assignedTo)
                                <p class="text-xs text-gray-400 mt-1">👤 {{ $lead->assignedTo->name }}</p>
                            @endif

                            {{-- Azioni cambio stato rapido --}}
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($columns as $targetKey => $targetCol)
                                    @if ($targetKey !== $statusKey)
                                        <button
                                            wire:click="moveToStatus({{ $lead->id }}, '{{ $targetKey }}')"
                                            wire:loading.attr="disabled"
                                            title="Sposta in: {{ $targetCol['label'] }}"
                                            class="rounded px-1.5 py-0.5 text-xs font-medium text-white hover:opacity-80 transition"
                                            style="background: {{ $targetCol['color'] }};">
                                            → {{ $targetCol['label'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-4">Nessun lead</p>
                    @endforelse

                </div>
            </div>

        @endforeach
    </div>

</x-filament-panels::page>
