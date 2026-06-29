<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>@yield('title', 'Talos CMS')</title>
<style>
    body,html{margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;color:#1e293b}
    *{box-sizing:border-box}
    .email-wrapper{width:100%;background:#f1f5f9;padding:40px 16px}
    .email-card{max-width:580px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07),0 1px 4px rgba(0,0,0,0.04);border:1px solid #e2e8f0}
    .email-header{background:#0f172a;padding:32px 40px;text-align:center}
    .email-header img{height:48px;width:auto;object-fit:contain}
    .email-header .tagline{color:#94a3b8;font-size:12px;margin:10px 0 0;letter-spacing:0.03em}
    .email-body{padding:40px}
    .email-footer{background:#f8fafc;border-top:1px solid #f1f5f9;padding:20px 40px;text-align:center}
    .email-footer p{color:#94a3b8;font-size:12px;margin:0;line-height:1.6}
    h1{font-size:22px;font-weight:700;color:#0f172a;margin:0 0 8px;line-height:1.3}
    p{font-size:14px;color:#475569;line-height:1.7;margin:0 0 16px}
    p:last-child{margin-bottom:0}
    a{color:#2563eb}
    .btn{display:inline-block;background:#2563eb;color:#ffffff !important;text-decoration:none;font-size:14px;font-weight:600;padding:13px 28px;border-radius:8px;letter-spacing:0.01em}
    .btn:hover{background:#1d4ed8}
    .divider{height:1px;background:#f1f5f9;margin:28px 0}
    .badge{display:inline-block;font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;padding:3px 10px;border-radius:100px}
    .badge-create{background:#d1fae5;color:#065f46}
    .badge-update{background:#dbeafe;color:#1e40af}
    .badge-publish{background:#ede9fe;color:#5b21b6}
    .badge-unpublish{background:#f1f5f9;color:#475569}
    .badge-delete{background:#fee2e2;color:#991b1b}
    .meta-table{width:100%;border-collapse:collapse;margin-top:4px}
    .meta-table tr{border-bottom:1px solid #f1f5f9}
    .meta-table tr:last-child{border-bottom:none}
    .meta-table td{padding:10px 0;font-size:13px;vertical-align:top}
    .meta-table td:first-child{color:#94a3b8;font-weight:500;width:32%;padding-right:16px;white-space:nowrap}
    .meta-table td:last-child{color:#1e293b;word-break:break-word;font-family:'SF Mono','Fira Code','Courier New',monospace;font-size:12px}
    .creds-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin:20px 0}
    .creds-box p{margin:0 0 8px;font-size:13px;color:#475569}
    .creds-box p:last-child{margin:0}
    .creds-box strong{color:#0f172a;font-family:'SF Mono','Fira Code','Courier New',monospace;font-size:12px;background:#e2e8f0;padding:2px 6px;border-radius:4px}
    .icon-circle{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
    .icon-circle.success{background:#d1fae5}
    .icon-circle.lock{background:#dbeafe}
    .icon-circle.welcome{background:#ede9fe}
    .text-center{text-align:center}
    .text-muted{color:#94a3b8;font-size:12px}
    @media only screen and (max-width:600px){
        .email-body{padding:28px 24px}
        .email-header{padding:24px}
        .email-footer{padding:16px 24px}
    }
</style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-card">

        {{-- Header --}}
        <div class="email-header">
            <img src="{{ url('/logo.png') }}" alt="{{ config('talos.admin_title', 'Talos CMS') }}">
            <p class="tagline">{{ config('talos.admin_title', 'Talos CMS') }}</p>
        </div>

        {{-- Body --}}
        <div class="email-body">
            @yield('body')
        </div>

        {{-- Footer --}}
        <div class="email-footer">
            <p>@yield('footer', 'This is an automated message from Talos CMS. Please do not reply.')</p>
            <p style="margin-top:6px">{{ now()->format('Y') }} &mdash; Talos CMS</p>
        </div>

    </div>
</div>
</body>
</html>
