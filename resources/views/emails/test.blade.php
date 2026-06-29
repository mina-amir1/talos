@extends('emails.layouts.base')

@section('title', 'SMTP Verified — Talos CMS')

@section('body')
<div class="text-center">
    <div class="icon-circle success" style="display:flex">
        <svg width="26" height="26" fill="none" stroke="#059669" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h1>SMTP is working</h1>
    <p style="margin-top:8px">
        Your email configuration is set up correctly.<br>
        Talos CMS can deliver transactional emails through your SMTP provider.
    </p>
</div>

<div class="divider"></div>

<table class="meta-table">
    <tr>
        <td>Sent at</td>
        <td>{{ now()->format('D, d M Y H:i:s') }} UTC</td>
    </tr>
    <tr>
        <td>Delivered by</td>
        <td>Talos CMS</td>
    </tr>
</table>
@endsection

@section('footer')
This is an automated test message. No action is required.
@endsection
