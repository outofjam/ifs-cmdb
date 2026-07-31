<x-auth-card title="Microsoft sign-in not available">
    <h1 class="text-lg font-semibold">Microsoft sign-in not available</h1>
    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
        This organization hasn't set up Microsoft sign-in yet — use your password instead.
    </p>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
            &larr; Back to sign in
        </a>
    </p>
</x-auth-card>
