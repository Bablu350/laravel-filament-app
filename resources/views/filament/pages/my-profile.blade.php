<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <div class="mt-6">
            <x-filament::button
                type="submit"
                :disabled="auth()->user()->user_verified">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament::page>