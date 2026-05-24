<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Wifi Maps</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/img/jonusa.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 p-6 text-slate-900">
    <form method="post" action="{{ route('login.store') }}" class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
        @csrf
        <h1 class="text-2xl font-bold">Wifi Maps</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk kelola mapping dan report.</p>
        @if ($errors->any())
            <div class="mt-4 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <label class="mt-5 block text-sm font-semibold">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required autofocus>
        <label class="mt-4 block text-sm font-semibold">Password</label>
        <input name="password" type="password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Login</button>
    </form>
</body>
</html>
