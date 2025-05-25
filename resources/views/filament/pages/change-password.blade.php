<x-filament::page>
    {{ $this->form }}

    <x-filament::button wire:click="submit" class="mt-4">
        Update Password
    </x-filament::button>
</x-filament::page>