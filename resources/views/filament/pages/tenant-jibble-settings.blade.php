<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Configure how this tenant connects to Jibble. These settings control which organization,
            project, and group will be used when syncing data for this tenant.
        </p>

        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button wire:click="save">
                Save Settings
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
