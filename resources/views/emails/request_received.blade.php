<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud Recibida - YamyCorp</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .status-badge { background-color: #ff9800; color: white; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎉 YamyCorp</h1>
        <p>Tu solicitud ha sido recibida</p>
    </div>

    <div class="content">
        <h2>¡Hola!</h2>

        <p>Hemos recibido tu solicitud de acceso para <strong>{{ $email }}</strong>.</p>

        @if($type === 'register')
            <p>📝 <strong>Tipo de solicitud:</strong> Registro manual</p>
        @else
            <p>🔐 <strong>Tipo de solicitud:</strong> Acceso con Google/Microsoft</p>
        @endif

        <p><span class="status-badge">⏳ Estado: Pendiente de aprobación</span></p>

        <h3>📋 ¿Qué sigue?</h3>
        <ul>
            <li>✅ Tu solicitud fue enviada al administrador</li>
            <li>📧 Recibirás un email cuando sea aprobada</li>
            <li>⏱️ Esto puede tomar entre minutos a 24 horas</li>
            <li>🚀 Una vez aprobada, podrás acceder al sistema</li>
        </ul>

        <h3>💡 Importante:</h3>
        <p>
            <strong>No necesitas hacer nada más.</strong> Te notificaremos por email
            tan pronto como tu cuenta sea activada.
        </p>

        @if($type === 'oauth')
            <p>
                <em>La próxima vez que intentes acceder con Google/Microsoft,
                    podrás ingresar directamente si ya fuiste aprobado.</em>
            </p>
        @endif
    </div>

    <div class="footer">
        <p>Este es un email automático, no responder.</p>
        <p>© {{ date('Y') }} YamyCorp. Todos los derechos reservados.</p>
    </div>
</div>
</body>
</html>
