<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>MFA Setup</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .wrap { max-width: 700px; margin: 0 auto; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 14px; }
        .small { color: #6b7280; font-size: 11px; }
        .grid { display: table; width: 100%; }
        .cell { display: table-cell; vertical-align: top; }
        .qr { width: 220px; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; margin-top: 12px; }
        .codes { font-family: DejaVu Sans Mono, monospace; font-size: 11px; }
        .code { display: inline-block; width: 31%; margin: 0 1% 6px 0; padding: 6px; border: 1px solid #d1d5db; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h2 style="margin:0;">Configuração MFA - CyberComply</h2>
        <div class="small">Gerado em {{ $generatedAt }}</div>
    </div>

    <div class="box">
        <strong>Utilizador:</strong> {{ $user->display_name ?: $user->email }}<br>
        <strong>Email:</strong> {{ $user->email }}<br>
        <strong>User ID:</strong> {{ $user->id }}
    </div>

    <div class="grid" style="margin-top:14px;">
        <div class="cell qr">
            <img src="{{ $qrCodeUrl }}" alt="QR Code" style="width:200px;height:200px;">
        </div>
        <div class="cell">
            <div class="box">
                <strong>Secret (TOTP):</strong><br>
                <span class="codes">{{ $secret }}</span>
            </div>
            <div class="box">
                <strong>URI OTPAuth:</strong><br>
                <span class="codes" style="word-break: break-all;">{{ $otpauthUri }}</span>
            </div>
        </div>
    </div>

    @if(!empty($backupCodes))
        <div class="box">
            <strong>Backup codes (guardar em local seguro):</strong>
            <div class="codes" style="margin-top:8px;">
                @foreach($backupCodes as $code)
                    <span class="code">{{ $code }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
</body>
</html>
