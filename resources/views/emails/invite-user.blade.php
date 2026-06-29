@extends('emails.layouts.base')

@section('title', "You've been invited — Talos CMS")

@section('body')
<div class="text-center">
    <div class="icon-circle" style="display:flex;background:#eff6ff;margin:0 auto 16px">
        <svg width="24" height="24" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
    </div>
    <h1>You've been invited</h1>
    <p style="margin-top:8px">Hi {{ $name }}, you have been invited to access Talos CMS. Click the button below to set up your password and get started.</p>
</div>

<div class="divider"></div>

<div class="text-center" style="padding:8px 0">
    <a href="{{ $inviteUrl }}" class="btn">Set Up Password</a>
</div>

<div class="divider"></div>

<p class="text-muted text-center">
    This invite link expires in <strong style="color:#475569">24 hours</strong>.
    If you weren&rsquo;t expecting this invitation, you can safely ignore this email.
</p>

<p class="text-muted text-center" style="margin-top:12px;word-break:break-all">
    Or copy this URL: <a href="{{ $inviteUrl }}" style="color:#2563eb">{{ $inviteUrl }}</a>
</p>
@endsection

@section('footer')
You received this because you were invited to join Talos CMS.
@endsection
