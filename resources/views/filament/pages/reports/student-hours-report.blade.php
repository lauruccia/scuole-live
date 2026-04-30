<x-filament::page>
    <div class="space-y-4">
        {{ $this->form }}

        <x-filament::section heading="Risultati">
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament::page>
