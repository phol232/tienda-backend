<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Enlace Expirado - YamyCorp</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #dc3545; margin-bottom: 30px; }
        .content { text-align: center; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        .message { font-size: 18px; margin-bottom: 20px; }
        .note { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">⏰</div>
            <h1>Enlace Expirado</h1>
        </div>
        
        <div class="content">
            <p class="message">{{ $message ?? 'El enlace de aprobación ha expirado.' }}</p>
            
            <p class="note">
                <strong>¿Qué puedes hacer?</strong><br>
                Contacta al administrador para solicitar un nuevo enlace de aprobación.
            </p>
            
            <p class="note">
                <em>Este enlace era válido por 30 días desde su creación.</em>
            </p>
        </div>
    </div>
</body>
</html>
