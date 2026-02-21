<x-filament-panels::page.simple>
    @if ($subheading = $this->getSubheading())
    <x-slot name="subheading">
        {{ $subheading }}
    </x-slot>
    @endif

    <form wire:submit="accept">
        {{ $this->form }}

        <x-filament::actions
            :actions="$this->getFormActions()"
            alignment="center" />
    </form>
</x-filament-panels::page.simple>