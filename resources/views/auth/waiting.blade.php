<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acceso Pendiente</title>
    <style>
        body {
            display: flex; justify-content: center; align-items: center;
            height: 100vh; margin: 0;
            background: linear-gradient(135deg, #FF7E5F, #FEB47B);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .card {
            background: #fff; padding: 2rem; border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 360px; text-align: center;
        }
        .card h1 {
            margin-bottom: .5rem; font-size: 1.8rem; color: #FF7E5F;
        }
        .card p {
            margin: 1rem 0; font-size: 1rem;
        }
        .card .email {
            font-weight: bold; color: #444;
        }
        .card small {
            display: block; margin-top: 1.5rem; color: #888;
        }
        .btn {
            display: inline-block; margin-top: 1.5rem;
            padding: .6rem 1.2rem; background-color: #FF7E5F;
            color: #fff; text-decoration: none; border-radius: 8px;
            font-weight: bold; transition: background-color .2s;
        }
        .btn:hover {
            background-color: #e96b53;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>¡Gracias!</h1>
    <p>{{ $message }}</p>
    <p>Se ha enviado un correo a:</p>
    <p class="email">{{ $email }}</p>
    <small>Te notificaremos tan pronto el administrador apruebe tu cuenta.</small>
    <br>
    <a href="{{ env('FRONTEND_URL') }}/login" class="btn">
        Ir al inicio de sesión
    </a>
</div>
</body>
</html>
