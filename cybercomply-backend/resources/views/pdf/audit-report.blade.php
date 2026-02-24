<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Relatorio de Auditoria</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .header { border-bottom: 2px solid #111; margin-bottom: 12px; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 16px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .critical { color: #b91c1c; font-weight: bold; }
        .integrity { margin-top: 16px; padding: 8px; border: 1px solid #ddd; background: #f9fafb; font-family: monospace; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatorio de Auditoria CyberComply</h1>
    </div>

    <div class="meta">
        <div>Gerado por: {{ $generated_by }}</div>
        <div>Gerado em: {{ $generated_at }}</div>
        <div>Total de registos: {{ $logs->count() }}</div>
        <div>Periodo:
            {{ $logs->count() ? $logs->last()->created_at : '-' }}
            ate
            {{ $logs->count() ? $logs->first()->created_at : '-' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Source</th>
                <th>Acao</th>
                <th>Entidade</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                @php
                    $critical = in_array($log->action, ['PERMISSION_DENIED', 'ROLE_CHANGED', 'CLIENT_DEACTIVATED', 'USER_DEACTIVATED', 'EXPORT_GENERATED'], true);
                @endphp
                <tr class="{{ $critical ? 'critical' : '' }}">
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->user_id }}</td>
                    <td>{{ $log->source }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->entity_type }}:{{ $log->entity_id }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="integrity">
        <strong>Hash de integridade (SHA-256):</strong><br>
        {{ $integrity_hash }}
    </div>
</body>
</html>

