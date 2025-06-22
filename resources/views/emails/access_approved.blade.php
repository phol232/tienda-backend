<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cuenta Aprobada</title>
    <style>
        body { font-family: sans-serif; line-height:1.5; }
        .btn {
            display:inline-block;
            margin-top:1rem;
            padding:0.5em 1em;
            background:#28a745;
            color:#fff;
            text-decoration:none;
            border-radius:4px;
        }
    </style>
</head>
<body>
<h1>¡Felicidades!</h1>
<p>Tu cuenta <strong>{{ $email }}</strong> ha sido aprobada.</p>
<p>Puedes iniciar sesión ya mismo desde el siguiente enlace:</p>
<p>
    <a href="{{ env('FRONTEND_URL') }}/login" class="btn">
        Ir al inicio de sesión
    </a>
</p>
<p>¡Bienvenido(a) a la plataforma!</p>
</body>
</html>
