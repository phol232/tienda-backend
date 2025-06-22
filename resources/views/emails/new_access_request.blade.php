<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud de Acceso</title>
</head>
<body style="font-family: sans-serif; line-height:1.5">
<p>Hola Admin,</p>

<p>Se ha generado una nueva solicitud de acceso:</p>
<ul>
    <li><strong>Email:</strong> {{ $email }}</li>
    @if($username)
        <li><strong>Usuario:</strong> {{ $username }}</li>
    @endif
    @if($messageText)
        <li><strong>Mensaje:</strong> {{ $messageText }}</li>
    @endif
</ul>

<p>
    <a href="{{ $approvalUrl }}"
       style="display:inline-block;padding:0.5em 1em;background:#28a745;color:#fff;text-decoration:none;border-radius:4px;">
        ✅ Hacer clic para aprobar
    </a>
</p>

<p style="font-size:.9em;color:#666;">
    Nota: este enlace expirará en 7 días.
</p>
</body>
</html>
