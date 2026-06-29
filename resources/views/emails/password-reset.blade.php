@extends('emails.layouts.base')

@section('title', 'Reset your password — Talos CMS')

@section('body')
<div class="text-center">
    <div class="icon-circle lock" style="display:flex">
        <svg width="24" height="24" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h1>Reset your password</h1>
    <p style="margin-top:8px">Hi {{ $name }}, we received a request to reset your Talos CMS password. Click the button below to choose a new one.</p>
</div>

<div class="divider"></div>

<div class="text-center" style="padding:8px 0">
    <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
</div>

<div class="divider"></div>

<p class="text-muted text-center">
    This link expires in <strong style="color:#475569">24 hours</strong>.
    If you didn&rsquo;t request a password reset, you can safely ignore this email &mdash; your account is secure.
</p>

<p class="text-muted text-center" style="margin-top:12px;word-break:break-all">
    Or copy this URL: <a href="{{ $resetUrl }}" style="color:#2563eb">{{ $resetUrl }}</a>
</p>
@endsection

@section('footer')
You received this because a password reset was requested for your Talos CMS account.
@endsection
