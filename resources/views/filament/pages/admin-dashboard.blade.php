<x-filament::page>
    <h1>{{ $this->getTitle() }}</h1>
    <p>Welcome, {{ auth()->user()->name }}!</p>
</x-filament::page>