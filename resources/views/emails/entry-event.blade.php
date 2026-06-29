@extends('emails.layouts.base')

@php
$eventLabel = match($event) {
    'entry.create'    => 'New Entry',
    'entry.update'    => 'Updated',
    'entry.publish'   => 'Published',
    'entry.unpublish' => 'Unpublished',
    'entry.delete'    => 'Deleted',
    default           => $event,
};

$badgeClass = match($event) {
    'entry.create'    => 'badge-create',
    'entry.update'    => 'badge-update',
    'entry.publish'   => 'badge-publish',
    'entry.unpublish' => 'badge-unpublish',
    'entry.delete'    => 'badge-delete',
    default           => 'badge-update',
};

$iconColor = match($event) {
    'entry.create'    => '#059669',
    'entry.update'    => '#2563eb',
    'entry.publish'   => '#7c3aed',
    'entry.unpublish' => '#64748b',
    'entry.delete'    => '#dc2626',
    default           => '#2563eb',
};

$iconBg = match($event) {
    'entry.create'    => '#d1fae5',
    'entry.update'    => '#dbeafe',
    'entry.publish'   => '#ede9fe',
    'entry.unpublish' => '#f1f5f9',
    'entry.delete'    => '#fee2e2',
    default           => '#dbeafe',
};
@endphp

@section('title', $ruleName . ' — Talos CMS')

@section('body')
<div class="text-center">
    <div class="icon-circle" style="display:flex;background:{{ $iconBg }}">
        @if($event === 'entry.create')
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        @elseif($event === 'entry.update')
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        @elseif($event === 'entry.publish')
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        @elseif($event === 'entry.unpublish')
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        @elseif($event === 'entry.delete')
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        @else
        <svg width="24" height="24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        @endif
    </div>
    <h1>{{ $ruleName }}</h1>
    <p style="margin-top:8px">
        <span class="badge {{ $badgeClass }}">{{ $eventLabel }}</span>
    </p>
    <p style="margin-top:8px;color:#94a3b8;font-size:12px">
        Content type: <strong style="color:#475569;font-family:'SF Mono','Fira Code','Courier New',monospace">{{ $uid }}</strong>
    </p>
</div>

<div class="divider"></div>

@if(count($fields) > 0)
<table class="meta-table">
    @foreach($fields as $key => $value)
    <tr>
        <td>{{ $key }}</td>
        <td>
            @if(is_array($value))
                {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}
            @elseif(is_null($value))
                <em style="color:#94a3b8">null</em>
            @else
                {{ $value }}
            @endif
        </td>
    </tr>
    @endforeach
</table>
@else
<p class="text-muted text-center">No fields were selected for this notification rule.</p>
@endif
@endsection

@section('footer')
This notification was triggered by a content event in Talos CMS.
@endsection
