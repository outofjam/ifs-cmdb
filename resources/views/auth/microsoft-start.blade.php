<x-auth-card title="Sign in with Microsoft">
    <h1 class="text-lg font-semibold">Sign in with Microsoft</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Enter your organization's ID to continue. Your admin can find it on the Entra settings page.
    </p>

    <form method="POST" action="{{ route('auth.microsoft.resolve') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization ID</label>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    required
                    autofocus
                    value="{{ old('slug') }}"
                    placeholder="acme-hvac"
                    class="block w-full rounded-lg border border-gray-300 py-2 pl-10 pr-3 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                >
            </div>
            @error('slug')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
        >
            Continue
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('filament.admin.auth.login') }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
            &larr; Back to sign in
        </a>
    </p>
</x-auth-card>
