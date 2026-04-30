<div class="space-y-4">
    <div>
        <div class="text-sm text-gray-500">Lezione</div>
        <div class="font-medium">
            {{ $lesson->starts_at?->format('d/m/Y H:i') ?? '-' }}
        </div>
    </div>

    <div>
        <div class="text-sm text-gray-500">Corso</div>
        <div class="font-medium">
            {{ $lesson->contract?->course?->name ?? '-' }}
        </div>
    </div>

    <div>
        <div class="text-sm text-gray-500">Docente</div>
        <div class="font-medium">
            {{ trim(($lesson->teacher?->first_name ?? '') . ' ' . ($lesson->teacher?->last_name ?? '')) ?: ($lesson->teacher?->name ?? '-') }}
        </div>
    </div>

    <div>
        <div class="text-sm text-gray-500 mb-1">Note e Compiti</div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 whitespace-pre-line">
            {{ $lesson->homework ?: 'Nessun compito assegnato.' }}
        </div>
    </div>
</div>