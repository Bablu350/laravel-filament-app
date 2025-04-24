<x-filament::page>
    <h1>{{ $this->getTitle() }}</h1>
    <p>Welcome, {{ auth()->user()->name }}!</p>

    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-4">User List</h2>
        {{ $this->table }}
    </div>
</x-filament::page>