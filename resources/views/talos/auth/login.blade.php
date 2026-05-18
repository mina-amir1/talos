<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in — {{ config('talos.admin_title', 'Talos CMS') }}</title>
    <link rel="icon" type="image/png" sizes="any" href="/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex items-center justify-center">
    <div class="w-full max-w-sm px-6">
        {{-- Logo --}}
        <div class="text-center mb-8">
{{--            <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl mb-4">--}}
{{--                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"--}}
{{--                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />--}}
{{--                </svg>--}}
{{--            </div>--}}
{{--            <h1 class="text-2xl font-bold text-white">Talos CMS</h1>--}}
            <img src="{{ asset('/storage/logo.png') }}" style=" width: 320px;height: 200px;object-fit: cover;"/>
            <p class="text-gray-500 text-sm mt-1">Sign in to the admin panel</p>
        </div>

        {{-- Error --}}
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-900/40 border border-red-700 rounded-lg text-red-300 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('talos.login.post') }}" method="POST" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500
                              focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5" for="password">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500
                              focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm">
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-semibold text-sm transition-colors flex items-center justify-center gap-2"
                    :disabled="loading">
                <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
            </button>
        </form>

        <p class="text-center text-gray-600 text-xs mt-8">
            Talos CMS &mdash; Built By UpStrike
        </p>
    </div>
</body>
</html>
