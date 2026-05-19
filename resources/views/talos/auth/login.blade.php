<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in — {{ config('talos.admin_title', 'Talos CMS') }}</title>
    <link rel="icon" type="image/png" sizes="any" href="/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: #f1f5f9;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04);
            border-radius: 1rem;
        }

        .input-field {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            color: #1e293b;
            font-size: 0.875rem;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .input-field:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            background: #ffffff;
        }

        .input-field::placeholder { color: #94a3b8; }

        .btn-primary {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.2);
        }

        .btn-primary:hover:not(:disabled) {
            background: #1d4ed8;
            box-shadow: 0 4px 14px rgba(37,99,235,0.3);
        }

        .btn-primary:active:not(:disabled) { transform: translateY(1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body class="h-full flex items-center justify-center px-4">

    <div class="w-full max-w-[400px]">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <img src="{{ asset('/storage/logo.png') }}"
                 class="mx-auto"
                 style="width:200px; height:120px; object-fit:contain;" />
            <p class="text-sm text-slate-500 mt-3">Sign in to the admin panel</p>
        </div>

        {{-- Card --}}
        <div class="login-card px-8 py-8">

            {{-- Error --}}
            @if($errors->any())
                <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('talos.login.post') }}" method="POST"
                  class="space-y-5"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide" for="email">
                        Email address
                    </label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email') }}" required autofocus
                           placeholder="you@example.com"
                           class="input-field">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide" for="password">
                        Password
                    </label>
                    <input id="password" name="password" type="password"
                           required placeholder="••••••••"
                           class="input-field">
                </div>

                <button type="submit" class="btn-primary" :disabled="loading">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-xs mt-6">
            Talos CMS &mdash; Built by UpStrike
        </p>
    </div>

</body>
</html>
