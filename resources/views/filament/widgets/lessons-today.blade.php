@php
    $data       = $this->getLessonsToday();
    $lessons    = $data['lessons'];
    $total      = $data['total'];
    $completed  = $data['completed'];
    $upcoming   = $data['upcoming'];
    $inProgress = $data['inProgress'];
@endphp

<x-filament-widgets::widget>
<div class="rounded-xl border border-gray-200 bg-white p-4">

    {{-- Intestazione --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="text-xl">📆</span>
            <div>
                <h3 class="font-semibold text-gray-900 text-sm">Lezioni di oggi</h3>
                <p class="text-xs text-gray-400">{{ \Illuminate\Support\Carbon::today()->isoFormat('dddd D MMMM Y') }}</p>
            </div>
        </div>
        {{-- Badge contatori --}}
        <div class="flex items-center gap-2">
            @if($completed > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-success-100 text-success-700 text-xs font-semibold px-2.5 py-1">
                    ✓ {{ $completed }} svolte
                </span>
            @endif
            @if($inProgress > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-100 text-warning-700 text-xs font-semibold px-2.5 py-1">
                    ▶ {{ $inProgress }} in corso
                </span>
            @endif
            @if($upcoming > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 text-primary-700 text-xs font-semibold px-2.5 py-1">
                    🕐 {{ $upcoming }} prossime
                </span>
            @endif
            <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1">
                {{ $total }} tot.
            </span>
        </div>
    </div>

    @if($lessons->isEmpty())
        <div class="text-center py-8 text-gray-400">
            <p class="text-2xl mb-2">🎉</p>
            <p class="text-sm italic">Nessuna lezione programmata per oggi</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
            @foreach($lessons as $lesson)
                @php
                    $start     = \Illuminate\Support\Carbon::parse($lesson->starts_at);
                    $end       = $lesson->ends_at ? \Illuminate\Support\Carbon::parse($lesson->ends_at) : null;
                    $now       = now();
                    $isDone    = (bool) $lesson->counts_as_consumed;
                    $isRunning = !$isDone && $start->lte($now) && ($end ? $end->gte($now) : true);
                    $isPending = !$isDone && !$isRunning;

                    $studentName = null;
                    if ($lesson->student) {
                        $studentName = trim(($lesson->student->first_name ?? '') . ' ' . ($lesson->student->last_name ?? ''));
                    }
                    $teacherName = null;
                    if ($lesson->teacher) {
                        $teacherName = trim(($lesson->teacher->first_name ?? '') . ' ' . ($lesson->teacher->last_name ?? ''));
                        if (!$teacherName) $teacherName = $lesson->teacher->name ?? null;
                    }
                    $courseName = $lesson->contract?->course?->name ?? null;

                    $borderColor = $isDone ? 'border-success-200 bg-success-50/30' : ($isRunning ? 'border-warning-200 bg-warning-50/30' : 'border-gray-100 bg-gray-50/50');
                    $dotColor    = $isDone ? 'bg-success-400' : ($isRunning ? 'bg-warning-400 animate-pulse' : 'bg-gray-300');
                @endphp

                <div class="rounded-lg border {{ $borderColor }} p-3 flex flex-col gap-1.5">
                    {{-- Orario + stato --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('filament.admin.resources.lessons.edit', ['record' => $lesson->id]) }}"
                           class="font-bold text-sm text-gray-800 hover:text-primary-600 hover:underline">
                            {{ $start->format('H:i') }}{{ $end ? ' – ' . $end->format('H:i') : '' }}
                        </a>
                        <span class="flex items-center gap-1.5 text-[10px]">
                            <span class="w-2 h-2 rounded-full {{ $dotColor }} inline-block"></span>
                            @if($isDone)
                                <span class="text-success-600 font-medium">Svolta</span>
                            @elseif($isRunning)
                                <span class="text-warning-600 font-medium">In corso</span>
                            @else
                                <span class="text-gray-400">Programmata</span>
                            @endif
                        </span>
                    </div>

                    {{-- Studente --}}
                    @if($studentName)
                        <div class="flex items-center gap-1.5 text-xs text-gray-700">
                            <span class="text-gray-400">👤</span>
                            <a href="{{ route('filament.admin.resources.lessons.edit', ['record' => $lesson->id]) }}"
                               class="font-medium hover:text-primary-600 hover:underline">{{ $studentName }}</a>
                        </div>
                    @endif

                    {{-- Docente --}}
                    @if($teacherName)
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <span class="text-gray-400">🎓</span>
                            <span>{{ $teacherName }}</span>
                        </div>
                    @endif

                    {{-- Corso --}}
                    @if($courseName)
                        <div class="mt-0.5">
                            <span class="inline-block text-[10px] bg-primary-100 text-primary-700 rounded px-1.5 py-0.5 font-medium">
                                {{ $courseName }}
                            </span>
                        </div>
                    @endif

                    {{-- Google Meet --}}
                    @if($lesson->meet_url)
                        <a href="{{ $lesson->meet_url }}" target="_blank"
                           class="flex items-center gap-1 text-[10px] text-blue-600 hover:underline mt-0.5">
                            🎥 Entra in Meet
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
</x-filament-widgets::widget>
