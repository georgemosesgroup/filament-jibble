<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Configure your personal Jibble connection. Save either a personal access token or OAuth client credentials,
            then use the "Fetch from Jibble" action to load your organizations before selecting one.
        </p>

        {{ $this->form }}

        <div class="flex items-center justify-between">
            <x-filament::button color="gray" wire:click="syncNow" wire:loading.attr="disabled">
                Run Sync Now
            </x-filament::button>

            <x-filament::button wire:click="save" wire:loading.attr="disabled">
                Save & Test Connection
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
