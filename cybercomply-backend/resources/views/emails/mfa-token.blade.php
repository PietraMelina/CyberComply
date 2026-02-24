<!DOCTYPE html>
<html lang="pt">
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937;">
    <div style="max-width: 560px; margin: 0 auto; padding: 20px;">
        <h2 style="margin-bottom: 12px;">Verificação de dois fatores</h2>
        <p>Recebemos uma tentativa de login na sua conta.</p>
        <p>Use o código abaixo para concluir o acesso:</p>

        <div style="margin: 18px 0; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; text-align: center;">
            <span style="font-size: 30px; letter-spacing: 6px; font-weight: 700;">{{ $token }}</span>
        </div>

        <p>Este código expira em <strong>{{ $minutes }} minutos</strong>.</p>
        <p style="font-size: 12px; color: #6b7280;">Se não foi você, ignore este email e troque sua senha.</p>
    </div>
</body>
</html>

