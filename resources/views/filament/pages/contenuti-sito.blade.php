<x-filament-panels::page>

    {{-- Selettore pagina --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <label for="pagina-select" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium text-gray-950 dark:text-white">
            Pagina del sito da modificare
        </label>
        <select
            id="pagina-select"
            wire:model.live="pagina"
            class="fi-select-input mt-2 block w-full max-w-md rounded-lg border-none bg-white py-1.5 pe-8 ps-3 text-base text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 sm:text-sm"
        >
            @foreach ($this->pagine as $slug => $label)
                <option value="{{ $slug }}">{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Svuota un campo e salva per ripristinare il testo originale della pagina.
            Le modifiche compaiono sul sito entro pochi minuti.
        </p>
    </div>

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

</x-filament-panels::page>
