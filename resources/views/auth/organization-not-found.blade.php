<x-auth-card title="Organization not found">
    <h1 class="text-lg font-semibold">Organization not found</h1>
    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
        We couldn't find an organization with that ID. Double-check it with your admin and try again.
    </p>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Just getting started?
        <a href="{{ route('filament.admin.auth.register') }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">Create a new organization</a>.
    </p>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('auth.microsoft.start') }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
            &larr; Try again
        </a>
    </p>
</x-auth-card>
