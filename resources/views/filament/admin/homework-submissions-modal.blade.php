@php
    $submissions = $homework->submissions()->with('student')->get();
    $contract    = $homework->contract;
    // Studenti beneficiari del contratto
    $beneficiaries = $contract?->beneficiaries ?? collect();
@endphp

<div class="p-1 space-y-4">

    {{-- Istruzioni compito --}}
    @if($homework->instructions)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-700 dark:text-gray-300">
            <p class="font-semibold text-gray-900 dark:text-white mb-1">📋 Istruzioni</p>
            <p>{{ $homework->instructions }}</p>
        </div>
    @endif

    {{-- Allegato docente --}}
    @if($homework->attachment_path)
        <div>
            <a href="{{ Storage::disk('public')->url($homework->attachment_path) }}"
               target="_blank"
               class="inline-flex items-center gap-1 text-sm text-primary-600 hover:underline">
                📎 Scarica traccia: {{ $homework->attachment_name ?? 'allegato' }}
            </a>
        </div>
    @endif

    {{-- Lista consegne --}}
    @if($submissions->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            Nessuna consegna ricevuta.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Studente</th>
                        <th class="px-4 py-2 text-left">Stato</th>
                        <th class="px-4 py-2 text-left">Consegnato</th>
                        <th class="px-4 py-2 text-left">File</th>
                        <th class="px-4 py-2 text-left">Voto</th>
                        <th class="px-4 py-2 text-left">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($submissions as $sub)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-3 font-medium">
                                {{ $sub->student?->full_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($sub->status === 'graded')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">✅ Valutato</span>
                                @elseif($sub->status === 'submitted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">📬 Consegnato</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">⏳ In attesa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $sub->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($sub->file_path)
                                    <a href="{{ Storage::disk('public')->url($sub->file_path) }}"
                                       target="_blank"
                                       class="text-primary-600 hover:underline text-xs">
                                        📎 {{ $sub->file_name ?? 'scarica' }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($sub->grade)
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $sub->grade }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($sub->status === 'submitted')
                                    {{-- Form inline per valutare --}}
                                    <form method="POST"
                                          action="{{ route('admin.homework.grade', $sub->id) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="grade"
                                               placeholder="Voto"
                                               class="w-20 text-xs border border-gray-300 rounded px-2 py-1 dark:bg-gray-800 dark:border-gray-600"
                                               required>
                                        <button type="submit"
                                                class="text-xs bg-primary-600 text-white px-2 py-1 rounded hover:bg-primary-700">
                                            Valuta
                                        </button>
                                    </form>
                                @endif
                                @if($sub->teacher_feedback)
                                    <p class="text-xs text-gray-500 mt-1 italic">{{ $sub->teacher_feedback }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
