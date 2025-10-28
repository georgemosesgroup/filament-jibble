<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit">
                Send request
            </x-filament::button>

            <x-filament::button color="gray" type="button" wire:click="mount">
                Reset
            </x-filament::button>
        </div>
    </form>

    @if ($error)
        <x-filament::section class="mt-8" icon="heroicon-o-exclamation-triangle" color="danger">
            <x-slot name="heading">Error</x-slot>
            <p class="text-sm text-gray-700 dark:text-gray-200">
                {{ $error }}
            </p>
        </x-filament::section>
    @endif

    @if ($status)
        <div class="mt-8 space-y-6">
            <x-filament::section icon="heroicon-o-signal">
                <x-slot name="heading">Response</x-slot>

                <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <dt class="font-semibold text-gray-700 dark:text-gray-200">Status</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $status }}</dd>
                    </div>

                    <div class="md:col-span-3">
                        <dt class="font-semibold text-gray-700 dark:text-gray-200">Headers</dt>
                        <dd class="mt-1">
                            <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
{{ json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                            </pre>
                        </dd>
                    </div>
                </dl>
            </x-filament::section>

            @if ($meta)
                <x-filament::section icon="heroicon-o-adjustments-horizontal">
                    <x-slot name="heading">Meta</x-slot>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                </x-filament::section>
            @endif

            @if ($links)
                <x-filament::section icon="heroicon-o-link">
                    <x-slot name="heading">Links</x-slot>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
{{ json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                </x-filament::section>
            @endif

            @if ($result)
                <x-filament::section icon="heroicon-o-document-text">
                    <x-slot name="heading">Payload</x-slot>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
