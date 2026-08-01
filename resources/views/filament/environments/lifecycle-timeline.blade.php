@php
    $events = $record->lifecycleTimeline();
@endphp

<div>
    @forelse ($events as $event)
        <div class="flex gap-3">
            <div class="flex flex-col items-center">
                <span @class([
                    'h-3 w-3 shrink-0 rounded-full',
                    'bg-success-500' => $event['type'] === 'created',
                    'bg-primary-500' => $event['type'] === 'upgraded',
                    'bg-gray-400 dark:bg-gray-600' => $event['type'] === 'configuration_changed',
                ])></span>
                @unless ($loop->last)
                    <span class="w-px flex-1 bg-gray-200 dark:bg-white/10"></span>
                @endunless
            </div>
            <div class="pb-6">
                <div class="flex flex-wrap items-center gap-x-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $event['label'] }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $event['occurred_at']?->diffForHumans() }}
                    </span>
                </div>
                @if ($event['detail'])
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $event['detail'] }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        <x-filament::empty-state
            icon="heroicon-o-clock"
            heading="No history yet"
            description="Changes to this environment will appear here."
        />
    @endforelse
</div>
