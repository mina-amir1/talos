{{ $ruleName }} — {{ strtoupper(str_replace('entry.', '', $event)) }}
{{ str_repeat('=', strlen($ruleName) + strlen($event) + 5) }}

Content type: {{ $uid }}

@foreach($fields as $key => $value)
{{ $key }}: {{ is_array($value) ? json_encode($value) : ($value ?? 'null') }}
@endforeach

---
Sent by Talos CMS — {{ now()->format('Y-m-d H:i') }} UTC
