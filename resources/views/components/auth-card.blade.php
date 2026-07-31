@props(['title'])

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 font-sans text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="w-full max-w-sm">
        <p class="mb-6 text-center text-sm font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500">
            {{ config('app.name') }}
        </p>

        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
