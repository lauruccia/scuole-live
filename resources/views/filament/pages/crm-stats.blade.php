<x-filament-panels::page>

@php
    $data           = $this->getData();
    $perStato       = $data['perStato'];
    $perFonte       = $data['perFonte'];
    $perMese        = $data['perMese'];
    $tempoMedio     = $data['tempoMedio'];
    $totale         = $data['totale'];
    $iscritti       = $data['iscritti'];
    $persi          = $data['persi'];
    $tasso          = $data['tasso'];
    $tassoPerdita   = $data['tassoPerdita'];
    $topAssegnatari = $data['topAssegnatari'];

    $statoColors = [
        'new'           => ['fg' => '#6b7280', 'label' => 'Nuovo'],
        'contacted'     => ['fg' => '#3b82f6', 'label' => 'Contattato'],
        'proposal_sent' => ['fg' => '#f59e0b', 'label' => 'Proposta inviata'],
        'enrolled'      => ['fg' => '#22c55e', 'label' => 'Iscritto'],
        'lost'          => ['fg' => '#ef4444', 'label' => 'Perso'],
    ];
@endphp

{{-- KPI: colonna compatta --}}
<div class="inline-flex flex-col gap-1.5 mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3">

    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Statistiche CRM</h3>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Lead totali</span>
        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $totale }}</span>
    </div>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Tasso conversione</span>
        <span class="text-sm font-bold text-green-600">{{ $tasso }}%</span>
    </div>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Tasso perdita</span>
        <span class="text-sm font-bold text-red-500">{{ $tassoPerdita }}%</span>
    </div>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Tempo medio conversione</span>
        <span class="text-sm font-bold text-blue-600">{{ $tempoMedio ? round($tempoMedio) . 'gg' : 'N/D' }}</span>
    </div>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Iscritti</span>
        <span class="text-sm font-bold text-amber-600">{{ $iscritti }}</span>
    </div>

    <div class="flex items-center justify-between gap-6">
        <span class="text-xs text-gray-500">Persi</span>
        <span class="text-sm font-bold text-gray-500">{{ $persi }}</span>
    </div>

</div>

{{-- Grafici --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

    {{-- Lead per stato --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-4">Lead per stato</h3>
        @foreach ($statoColors as $key => $col)
            @php $n = $perStato[$key] ?? 0; $pct = $totale > 0 ? round($n / $totale * 100) : 0; @endphp
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1">
                    <span style="color: {{ $col['fg'] }};" class="font-medium">{{ $col['label'] }}</span>
                    <span class="text-gray-400">{{ $n }} &nbsp;({{ $pct }}%)</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all" style="width: {{ $pct }}%; background: {{ $col['fg'] }};"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Lead per fonte --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-4">Lead per fonte</h3>
        @forelse ($perFonte as $fonte)
            @php
                $pct = $totale > 0 ? round($fonte->totale / $totale * 100) : 0;
                $label = \App\Models\Lead::SOURCES[$fonte->source] ?? $fonte->source;
            @endphp
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                    <span class="text-gray-400">{{ $fonte->totale }} &nbsp;({{ $pct }}%)</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $pct }}%;"></div>
                </div>
            </div>
        @empty
            <p class="text-xs text-gray-400 mt-2">Nessun dato ancora disponibile.</p>
        @endforelse
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- Lead per mese --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-4">Nuovi lead — ultimi 6 mesi</h3>
        @php $maxMese = $perMese->max('totale') ?: 1; @endphp
        @forelse ($perMese as $m)
            @php $pct = round($m->totale / $maxMese * 100); @endphp
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600 dark:text-gray-300">{{ $m->mese }}</span>
                    <span class="text-gray-400">{{ $m->totale }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-amber-500" style="width: {{ $pct }}%;"></div>
                </div>
            </div>
        @empty
            <p class="text-xs text-gray-400 mt-2">Nessun dato ancora disponibile.</p>
        @endforelse
    </div>

    {{-- Top performer --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-4">Top conversioni per operatore</h3>
        @forelse ($topAssegnatari as $i => $row)
            <div class="flex items-center gap-3 mb-3">
                <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ $i + 1 }}
                </div>
                <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $row->assignedTo?->name ?? 'N/D' }}</div>
                <div class="text-xs font-semibold text-green-600">{{ $row->conversioni }} iscritti</div>
            </div>
        @empty
            <p class="text-xs text-gray-400 mt-2">Nessun dato ancora disponibile.</p>
        @endforelse
    </div>

</div>

</x-filament-panels::page>
