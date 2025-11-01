@php
    $tenant = filament()->getTenant();
@endphp

@push('styles')
    <style>
        .fi-widget-timesheet-heatmap .fi-timesheet-slot {
            display: block;
            height: 1.25rem;
            width: 1.25rem;
            border-radius: 0.375rem;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--legend {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 0.875rem;
            width: 0.875rem;
            border-radius: 0.25rem;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--missing {
            background-color: #e5e7eb;
        }

        .dark .fi-widget-timesheet-heatmap .fi-timesheet-slot--missing {
            background-color: #1f2937;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--off {
            background-color: #d1d5db;
        }

        .dark .fi-widget-timesheet-heatmap .fi-timesheet-slot--off {
            background-color: #374151;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--target {
            background-color: #22c55e;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--extended {
            background-color: #facc15;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--overtime {
            background-color: #ef4444;
        }

        .fi-widget-timesheet-heatmap .fi-timesheet-slot--excessive {
            background-color: #991b1b;
        }
    </style>
@endpush

<x-filament::widget class="fi-widget-timesheet-heatmap">
    <x-filament::card>
        <div class="mb-4">
            {{ $this->form }}
        </div>

        @if ($requiresTenant && ! $tenant)
            <x-filament::empty-state
                heading="{{ __('filament-jibble::resources.widgets.timesheet_heatmap.no_branch.heading') }}"
                description="{{ __('filament-jibble::resources.widgets.timesheet_heatmap.no_branch.body') }}"
                icon="heroicon-o-building-office"
                class="mt-6"
            />
        @elseif (! $hasAnyPeople)
            <x-filament::empty-state
                heading="{{ __('filament-jibble::resources.widgets.timesheet_heatmap.empty.heading') }}"
                description="{{ __('filament-jibble::resources.widgets.timesheet_heatmap.empty.body') }}"
                icon="heroicon-o-chart-bar-square"
                class="mt-6"
            />
        @elseif (empty($people))
            <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                <x-filament::icon icon="heroicon-o-face-frown" class="h-6 w-6 text-gray-300 dark:text-gray-600" />
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('filament-jibble::resources.widgets.timesheet_heatmap.search_empty') }}
                </span>
            </div>
        @else
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-[56rem] w-full border-separate whitespace-nowrap text-sm" style="border-spacing: 0 12px;">
                    <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="min-w-[220px] whitespace-nowrap py-2 pr-4 text-left font-medium">
                            {{ __('filament-jibble::resources.widgets.timesheet_heatmap.employee') }}
                        </th>
                        @foreach ($days as $day)
                            <th class="w-10 px-1 text-center font-medium">
                                <div class="text-[11px] text-gray-400 dark:text-gray-500">
                                    {{ $day['day'] ?? '?' }}
                                </div>
                                <div class="mt-1 text-sm {{ ($day['is_today'] ?? false) ? 'font-semibold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-300' }}">
                                    {{ $day['label'] ?? '?' }}
                                </div>
                            </th>
                        @endforeach
                        <th class="min-w-[2rem] whitespace-nowrap py-2 pl-4 text-right font-medium">
                            {{ __('filament-jibble::resources.widgets.timesheet_heatmap.total') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($people as $person)
                        <tr class="align-middle" wire:key="timesheet-person-{{ $person['id'] ?? uniqid() }}">
                            <td class="pr-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                            {{ $person['initials'] ?? '??' }}
                                        </div>
                                        <span
                                            style="bottom: -2px; right: -2px;"
                                            title="{{ $person['is_online'] ?? false ? __('filament-jibble::resources.widgets.timesheet_heatmap.online') : __('filament-jibble::resources.widgets.timesheet_heatmap.offline') }}"
                                            @class([
                                                'absolute h-2.5 w-2.5 rounded-full border-2 border-white dark:border-slate-900',
                                                'bg-emerald-400 shadow-sm' => $person['is_online'] ?? false,
                                                'bg-gray-400 dark:bg-gray-500' => ! ($person['is_online'] ?? false),
                                            ])
                                        ></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $person['name'] ?? 'Unknown' }}
                                        </div>
                                        @if (! empty($person['email']))
                                            <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                                {{ $person['email'] }}
                                            </div>
                                        @endif
                                        @if (! empty($person['connection']))
                                            <div class="truncate text-xs text-gray-400 dark:text-gray-500">
                                                {{ $person['connection'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                                @foreach ($person['slots'] ?? [] as $slot)
                                    <td class="px-1 py-2 text-center">
                                        <span
                                            class="{{ $slot['classes'] ?? 'timesheet-slot' }}"
                                            title="{{ $slot['tooltip'] ?? 'No tooltip' }}"
                                        >
                                            @if (! empty($slot['icon']))
                                                <span class="{{ $slot['icon_classes'] ?? '' }}">{{ $slot['icon'] }}</span>
                                            @endif
                                        </span>
                                    </td>
                                @endforeach
                            <td class="whitespace-nowrap pl-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $person['total_formatted'] ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($days) + 2 }}" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('filament-jibble::resources.widgets.timesheet_heatmap.search_empty') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if (! empty($this->legend))
                <div class="mt-6 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-600 dark:text-gray-300">
                        {{ __('filament-jibble::resources.widgets.timesheet_heatmap.legend_title') }}:
                    </span>
                    @foreach ($this->legend as $legendItem)
                        <span class="inline-flex items-center gap-2">
                            <span class="{{ $legendItem['classes'] }}">
                                @if (! empty($legendItem['icon']))
                                    <span class="{{ $legendItem['icon_classes'] ?? '' }}">{{ $legendItem['icon'] }}</span>
                                @endif
                            </span>
                            {{ $legendItem['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        @endif
    </x-filament::card>

</x-filament::widget>
