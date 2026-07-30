<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sign in with Microsoft</title>
</head>
<body>
    <form method="POST" action="{{ route('auth.microsoft.resolve') }}">
        @csrf
        <label for="email">Work email</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Continue</button>
    </form>
</body>
</html>
