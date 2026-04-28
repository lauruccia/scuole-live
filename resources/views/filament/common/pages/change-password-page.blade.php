<x-filament-panels::page>
    <div class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div>
                <x-filament::button type="submit">
                    Salva nuova password
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>