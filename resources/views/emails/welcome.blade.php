@extends('emails.layouts.base')

@section('title', 'Welcome to Talos CMS')

@section('body')
<div class="text-center">
    <div class="icon-circle welcome" style="display:flex">
        <svg width="26" height="26" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h1>Welcome aboard</h1>
    <p style="margin-top:8px">Hi {{ $name }}, your Talos CMS account is ready. Here are your login credentials.</p>
</div>

<div class="divider"></div>

<div class="creds-box">
    <p>Email &nbsp;&nbsp;&nbsp; <strong>{{ $email }}</strong></p>
    <p>Password <strong>{{ $temporaryPassword }}</strong></p>
</div>

<p class="text-muted" style="text-align:center;margin-top:4px">
    Please change your password immediately after signing in.
</p>

<div class="divider"></div>

<div class="text-center">
    <a href="{{ $loginUrl }}" class="btn">Sign in to Talos</a>
</div>
@endsection

@section('footer')
You received this because an admin account was created for you on Talos CMS.
@endsection
