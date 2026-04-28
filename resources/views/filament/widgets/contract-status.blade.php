@php
    $stats = $this->getStats();
    $lowHours      = $stats['low_hours'];
    $exhausted     = $stats['exhausted'];
    $excessLessons = $stats['excess_lessons'];
    $expiring      = $stats['expiring'];
@endphp

<div class="flex flex-col gap-5">

    {{-- ═══════════════════════════════════════════════════════════
         Riga riepilogo (3 badge affiancati, slim)
    ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        {{-- Badge 1 --}}
        <a href="#sezione-ore-quasi-esaurite"
           class="flex items-center gap-3 rounded-xl border border-warning-200 bg-warning-50 px-5 py-3 hover:bg-warning-100 transition-colors">
            <span class="text-2xl leading-none">⚠️</span>
            <div class="min-w-0">
                <p class="text-xs text-warning-600 font-medium truncate">Ore quasi esaurite <span class="font-normal">(≥ 80% fruite)</span></p>
                <p class="text-2xl font-bold text-warning-900 leading-tight">{{ $lowHours->count() }}</p>
            </div>
        </a>

        {{-- Badge 2 --}}
        <a href="#sezione-ore-esaurite"
           class="flex items-center gap-3 rounded-xl border border-danger-200 bg-danger-50 px-5 py-3 hover:bg-danger-100 transition-colors">
            <span class="text-2xl leading-none">🔴</span>
            <div class="min-w-0">
                <p class="text-xs text-danger-600 font-medium truncate">Ore esaurite <span class="font-normal">(da chiudere)</span></p>
                <p class="text-2xl font-bold text-danger-900 leading-tight">{{ $exhausted->count() }}</p>
            </div>
        </a>

        {{-- Badge 3 --}}
        <a href="#sezione-lezioni-eccesso"
           class="flex items-center gap-3 rounded-xl border border-purple-200 bg-purple-50 px-5 py-3 hover:bg-purple-100 transition-colors">
            <span class="text-2xl leading-none">📅</span>
            <div class="min-w-0">
                <p class="text-xs text-purple-600 font-medium truncate">Lezioni future in eccesso</p>
                <p class="text-2xl font-bold text-purple-900 leading-tight">{{ $excessLessons->count() }}</p>
            </div>
        </a>

        {{-- Badge 4 --}}
        <a href="#sezione-in-scadenza"
           class="flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-5 py-3 hover:bg-sky-100 transition-colors">
            <span class="text-2xl leading-none">📆</span>
            <div class="min-w-0">
                <p class="text-xs text-sky-600 font-medium truncate">In scadenza <span class="font-normal">(30 gg)</span></p>
                <p class="text-2xl font-bold text-sky-900 leading-tight">{{ $expiring->count() }}</p>
            </div>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         Sezione dettagli: le 3 liste in larghezza piena, una sotto l'altra
    ══════════════════════════════════════════════════════════════ --}}

    {{-- Lista 1: Ore quasi esaurite --}}
    @if($lowHours->isNotEmpty())
    <div id="sezione-ore-quasi-esaurite" class="rounded-xl border border-warning-200 bg-warning-50/30 overflow-hidden scroll-mt-6">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-warning-200 bg-warning-50/60">
            <span>⚠️</span>
            <h3 class="font-semibold text-warning-900 text-sm">Ore quasi esaurite</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4">
            @foreach($lowHours as $c)
                @php
                    $purchased  = (float) $c->hours_purchased;
                    $consumed   = (float) $c->hours_consumed;
                    $remaining  = round($purchased - $consumed, 2);
                    $pct        = $purchased > 0 ? min(100, round($consumed / $purchased * 100)) : 0;
                @endphp
                <div class="bg-white rounded-lg p-3 border border-warning-100 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <a href="{{ route('filament.admin.resources.contracts.edit', ['record' => $c->id]) }}"
                           class="font-semibold text-warning-900 hover:underline text-sm leading-tight">
                            #{{ $c->id }} {{ $this->getContractName($c) }}
                        </a>
                        @if($c->academic_year)
                            <span class="shrink-0 text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">{{ $c->academic_year }}</span>
                        @endif
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                        <div class="h-2 rounded-full bg-warning-400" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>{{ $consumed }}h / {{ $purchased }}h</span>
                        <span class="font-semibold text-warning-700">{{ $remaining }}h residue ({{ 100 - $pct }}%)</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Lista 2: Ore esaurite --}}
    @if($exhausted->isNotEmpty())
    <div id="sezione-ore-esaurite" class="rounded-xl border border-danger-200 bg-danger-50/30 overflow-hidden scroll-mt-6">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-danger-200 bg-danger-50/60">
            <span>🔴</span>
            <h3 class="font-semibold text-danger-900 text-sm">Ore esaurite — da chiudere</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4">
            @foreach($exhausted as $c)
                @php
                    $purchased = (float) $c->hours_purchased;
                    $consumed  = (float) $c->hours_consumed;
                    $overrun   = round($consumed - $purchased, 2);
                @endphp
                <div class="bg-white rounded-lg p-3 border border-danger-100 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <a href="{{ route('filament.admin.resources.contracts.edit', ['record' => $c->id]) }}"
                           class="font-semibold text-danger-900 hover:underline text-sm leading-tight">
                            #{{ $c->id }} {{ $this->getContractName($c) }}
                        </a>
                        @if($c->academic_year)
                            <span class="shrink-0 text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">{{ $c->academic_year }}</span>
                        @endif
                    </div>
                    <div class="w-full bg-danger-200 rounded-full h-2 mb-2">
                        <div class="h-2 rounded-full bg-danger-500 w-full"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>{{ $consumed }}h / {{ $purchased }}h</span>
                        @if($overrun > 0)
                            <span class="font-semibold text-danger-700">+{{ $overrun }}h in eccesso</span>
                        @else
                            <span class="font-semibold text-danger-700">100% esaurite</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Lista 3: Lezioni future in eccesso --}}
    @if($excessLessons->isNotEmpty())
    <div id="sezione-lezioni-eccesso" class="rounded-xl border border-purple-200 bg-purple-50/30 overflow-hidden scroll-mt-6">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-purple-200 bg-purple-50/60">
            <span>📅</span>
            <h3 class="font-semibold text-purple-900 text-sm">Lezioni future in eccesso</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4">
            @foreach($excessLessons as $c)
                @php
                    $purchased   = (float) $c->hours_purchased;
                    $consumed    = (float) $c->hours_consumed;
                    $remaining   = round(max(0, $purchased - $consumed), 2);
                    $futureHours = round((float) $c->future_hours, 2);
                    $excess      = round($futureHours - $remaining, 2);
                    $pct         = $purchased > 0 ? min(100, round($consumed / $purchased * 100)) : 0;
                @endphp
                <div class="bg-white rounded-lg p-3 border border-purple-100 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <a href="{{ route('filament.admin.resources.contracts.edit', ['record' => $c->id]) }}"
                           class="font-semibold text-purple-900 hover:underline text-sm leading-tight">
                            #{{ $c->id }} {{ $this->getContractName($c) }}
                        </a>
                        @if($c->academic_year)
                            <span class="shrink-0 text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">{{ $c->academic_year }}</span>
                        @endif
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2 relative overflow-hidden">
                        <div class="h-2 rounded-full bg-purple-400" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>📌 {{ $futureHours }}h pianificate, {{ $remaining }}h residue</span>
                        <span class="font-semibold text-danger-600">+{{ $excess }}h</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Lista 4: Contratti in scadenza --}}
    @if($expiring->isNotEmpty())
    <div id="sezione-in-scadenza" class="rounded-xl border border-sky-200 bg-sky-50/30 overflow-hidden scroll-mt-6">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-sky-200 bg-sky-50/60">
            <span>📆</span>
            <h3 class="font-semibold text-sky-900 text-sm">Contratti in scadenza nei prossimi 30 giorni</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 p-4">
            @foreach($expiring as $c)
                @php
                    $daysLeft = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($c->ends_at), false);
                    $urgencyBorder = $daysLeft <= 7  ? 'border-danger-200'  : ($daysLeft <= 14 ? 'border-warning-200' : 'border-sky-100');
                    $urgencyBadgeBg = $daysLeft <= 7  ? 'bg-danger-100 text-danger-700'  : ($daysLeft <= 14 ? 'bg-warning-100 text-warning-700' : 'bg-sky-100 text-sky-700');
                    $urgencyText   = $daysLeft === 0  ? 'Scade oggi'
                                   : ($daysLeft === 1 ? 'Scade domani'
                                   : "Scade in {$daysLeft} giorni");
                @endphp
                <div class="bg-white rounded-lg p-3 border {{ $urgencyBorder }} shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <a href="{{ route('filament.admin.resources.contracts.edit', ['record' => $c->id]) }}"
                           class="font-semibold text-sky-900 hover:underline text-sm leading-tight">
                            #{{ $c->id }} {{ $this->getContractName($c) }}
                        </a>
                        @if($c->academic_year)
                            <span class="shrink-0 text-xs text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">{{ $c->academic_year }}</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-xs mt-1">
                        <span class="text-gray-500">Scadenza: {{ \Carbon\Carbon::parse($c->ends_at)->format('d/m/Y') }}</span>
                        <span class="font-semibold rounded px-1.5 py-0.5 {{ $urgencyBadgeBg }}">{{ $urgencyText }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Messaggio se tutto ok --}}
    @if($lowHours->isEmpty() && $exhausted->isEmpty() && $excessLessons->isEmpty() && $expiring->isEmpty())
        <p class="text-sm text-gray-400 italic text-center py-6">✅ Nessuna situazione critica rilevata</p>
    @endif

</div>
