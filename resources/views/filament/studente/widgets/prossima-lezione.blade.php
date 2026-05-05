{{--
    Prossima lezione widget — pannello studente.
    Variabili iniettate da ProssimaLezioneWidget::getViewData().
--}}
<x-filament-widgets::widget>
<div class="rounded-xl border {{ isset($lesson) && $lesson ? ($is_today ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-white') : 'border-gray-200 bg-white' }} p-5">

    {{-- Intestazione --}}
    <div class="flex items-center gap-3 mb-4">
        <span class="text-2xl">📅</span>
        <div>
            <h3 class="font-semibold text-gray-900 text-sm leading-tight">Prossima lezione</h3>
            @if(isset($lesson) && $lesson && isset($diff_human))
                <p class="text-xs text-gray-400">{{ $diff_human }}</p>
            @endif
        </div>
        @if(isset($is_today) && $is_today)
            <span class="ml-auto inline-flex items-center rounded-full bg-amber-100 text-amber-700 text-xs font-semibold px-2.5 py-1 leading-none">
                Oggi!
            </span>
        @elseif(isset($is_tomorrow) && $is_tomorrow)
            <span class="ml-auto inline-flex items-center rounded-full bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 leading-none">
                Domani
            </span>
        @endif
    </div>

    @if(! isset($lesson) || ! $lesson)
        {{-- Nessuna lezione programmata --}}
        <div class="text-center py-6 text-gray-400">
            <p class="text-3xl mb-2">🎉</p>
            <p class="text-sm italic">Nessuna lezione programmata nelle prossime settimane.</p>
        </div>
    @else
        {{-- Card lezione --}}
        <div class="rounded-lg border {{ $is_today ? 'border-amber-200 bg-white' : 'border-gray-100 bg-gray-50/60' }} p-4 space-y-3">

            {{-- Giorno + orario --}}
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-base font-bold text-gray-800 leading-tight">{{ $day_label ?? '' }}</p>
                    <p class="text-xl font-semibold {{ $is_today ? 'text-amber-600' : 'text-primary-700' }} leading-tight mt-0.5">
                        {{ $time_range ?? '' }}
                    </p>
                </div>
                @if(! empty($language))
                    <span class="inline-block text-xs bg-primary-100 text-primary-700 rounded-full px-2.5 py-1 font-medium mt-0.5 whitespace-nowrap">
                        {{ $language }}
                    </span>
                @endif
            </div>

            {{-- Docente --}}
            @if(! empty($teacher_name))
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="text-base">🎓</span>
                    <span>{{ $teacher_name }}</span>
                </div>
            @endif

            {{-- Durata --}}
            @if(isset($starts_at) && isset($ends_at))
                @php $minutes = $starts_at->diffInMinutes($ends_at); @endphp
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span>⏱</span>
                    <span>{{ $minutes }} minuti</span>
                </div>
            @endif

            {{-- Google Meet --}}
            @if(! empty($meet_url))
                <a href="{{ $meet_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex items-center justify-center gap-2 w-full rounded-lg px-4 py-2.5
                          {{ $is_today ? 'bg-amber-500 hover:bg-amber-600' : 'bg-primary-600 hover:bg-primary-700' }}
                          text-white text-sm font-semibold transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2V8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                    </svg>
                    Entra in Google Meet
                </a>
            @else
                <div class="flex items-center gap-2 text-xs text-gray-400 italic">
                    <span>🔗</span>
                    <span>Nessun link Meet per questa lezione</span>
                </div>
            @endif
        </div>
    @endif
</div>
</x-filament-widgets::widget>
