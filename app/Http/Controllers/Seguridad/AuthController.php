<?php

namespace App\Http\Controllers\Seguridad;

use Illuminate\Http\Request;
use App\Models\Seguridad\Usuarios;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validar los campos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Buscar usuario por email
        $usuario = Usuarios::where('usr_email', $request->email)->first();

        // Validar usuario y contraseña
        if (!$usuario || !Hash::check($request->password, $usuario->usr_password)) {
            return response()->json([
                'status' => false,
                'message' => 'Correo o contraseña incorrectos'
            ], 401);
        }

        // Verificar estado activo
        if ($usuario->usr_estado !== 'Activo') {
            return response()->json([
                'status' => false,
                'message' => 'Usuario inactivo, contacta al administrador'
            ], 403);
        }

        // Crear token con Sanctum
        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Inicio de sesión exitoso',
            'usuario' => [
                'usr_id' => $usuario->usr_id,
                'usr_user' => $usuario->usr_user,
                'usr_email' => $usuario->usr_email,
            ],
            'token' => $token
        ]);
    }
}
