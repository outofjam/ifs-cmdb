<x-auth-card title="Sign-in failed">
    <h1 class="text-lg font-semibold">We couldn't verify your account</h1>
    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
        Something didn't match while confirming your Microsoft account for this organization. Contact your admin if you think this is a mistake.
    </p>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('auth.microsoft.start') }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
            &larr; Try again
        </a>
    </p>
</x-auth-card>
