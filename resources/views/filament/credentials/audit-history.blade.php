<div class="space-y-4">
    @forelse ($audits as $audit)
        <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <x-filament::badge :color="match ($audit->event) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }">
                        {{ ucfirst($audit->event) }}
                    </x-filament::badge>
                    <span class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $audit->user?->name ?? 'Unknown user' }}
                    </span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $audit->created_at?->diffForHumans() }}
                </span>
            </div>

            @if ($audit->event === 'updated' && filled($audit->presentableFieldChanges))
                <dl class="mt-3 space-y-1 text-sm">
                    @foreach ($audit->presentableFieldChanges as $change)
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <dt class="font-medium text-gray-700 dark:text-gray-300">{{ $change['label'] }}:</dt>
                            <dd class="text-gray-500 line-through dark:text-gray-400">{{ $change['old'] ?? 'Not set' }}</dd>
                            <dd class="text-gray-950 dark:text-white">{{ $change['new'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @empty
        <x-filament::empty-state
            icon="heroicon-o-clock"
            heading="No audit history yet"
            description="Changes to this credential will appear here."
        />
    @endforelse
</div>
