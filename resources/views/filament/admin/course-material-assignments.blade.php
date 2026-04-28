<div class="space-y-4">
    @if($material->contracts->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
            Questo materiale non è ancora stato assegnato a nessuno studente.
        </p>
    @else
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Studente / Contratto</th>
                    <th class="px-4 py-2">Visibile</th>
                    <th class="px-4 py-2">Assegnato il</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($material->contracts as $contract)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-gray-500">#{{ $contract->id }}</td>
                        <td class="px-4 py-2 font-medium">
                            {{ trim(($contract->billing_last_name ?? '') . ' ' . ($contract->billing_first_name ?? '')) ?: '—' }}
                        </td>
                        <td class="px-4 py-2">
                            @if($contract->pivot->is_visible)
                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Sì
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-gray-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    No
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ $contract->pivot->assigned_at ? \Carbon\Carbon::parse($contract->pivot->assigned_at)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-2">
                            {{-- Toggle visibilità inline --}}
                            <form method="POST" action="{{ route('admin.material.toggle-visibility', ['material' => $material->id, 'contract' => $contract->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="text-xs px-2 py-1 rounded border {{ $contract->pivot->is_visible ? 'border-orange-300 text-orange-600 hover:bg-orange-50' : 'border-green-300 text-green-600 hover:bg-green-50' }}">
                                    {{ $contract->pivot->is_visible ? 'Nascondi' : 'Mostra' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
