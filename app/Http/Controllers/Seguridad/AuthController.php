<?php

namespace App\Http\Controllers\Seguridad;

use App\Models\Seguridad\Usuarios;
use App\Models\Seguridad\UsuariosPerfil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Registra un nuevo usuario y su perfil.
     */
    public function register(Request $request)
    {
        $request->validate([
            'usrp_nombre'   => 'required|string|max:50',
            'usrp_apellido' => 'required|string|max:50',
            'usr_email'     => 'required|email|unique:Usuarios,usr_email|max:100',
            'usr_user'      => 'required|string|unique:Usuarios,usr_user|max:30',
            'password'      => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/'
            ],
        ], [
            'password.regex' => 'La contraseña requiere mayúscula, número y carácter especial.'
        ]);

        DB::beginTransaction();

        try {
            $last = Usuarios::max('usr_id');
            $num  = $last ? ((int)$last + 1) : 1;
            $len  = $last ? strlen($last) : 8;
            $uid  = str_pad($num, $len, '0', STR_PAD_LEFT);

            $usuario = Usuarios::create([
                'usr_id'       => $uid,
                'usr_email'    => $request->usr_email,
                'usr_user'     => $request->usr_user,
                'usr_password' => Hash::make($request->password),
                'usr_estado'   => 'Activo',
            ]);

            UsuariosPerfil::create([
                'usrp_id'       => $uid,
                'usr_id'        => $uid,
                'usrp_nombre'   => $request->usrp_nombre,
                'usrp_apellido' => $request->usrp_apellido,
            ]);

            DB::commit();

            $token = $usuario->createToken('api-token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Usuario registrado exitosamente',
                'data'    => [
                    'usuario' => [
                        'usr_id'    => $usuario->usr_id,
                        'usr_user'  => $usuario->usr_user,
                        'usr_email' => $usuario->usr_email,
                        'perfil'    => [
                            'nombre'   => $request->usrp_nombre,
                            'apellido' => $request->usrp_apellido,
                        ],
                    ],
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Error al registrar el usuario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Autentica usando usr_user y devuelve token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'usr_user' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = Usuarios::where('usr_user', $request->usr_user)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->usr_password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }

        if ($usuario->usr_estado !== 'Activo') {
            return response()->json([
                'status'  => false,
                'message' => 'Usuario inactivo, contacte al administrador',
            ], 403);
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Inicio de sesión exitoso',
            'usuario' => [
                'usr_id'   => $usuario->usr_id,
                'usr_user' => $usuario->usr_user,
            ],
            'token'   => $token,
        ], 200);
    }

    /**
     * Revoca el token actual (logout).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Sesión cerrada correctamente',
        ], 200);
    }

    /**
     * Redirige al login de Google.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Maneja el callback de Google, crea/recupera usuario y redirige al frontend con el token.
     */
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        DB::beginTransaction();

        $usuario = Usuarios::where('usr_email', $googleUser->getEmail())->first();
        if (! $usuario) {
            $last = Usuarios::max('usr_id');
            $num  = $last ? ((int)$last + 1) : 1;
            $len  = $last ? strlen($last) : 8;
            $uid  = str_pad($num, $len, '0', STR_PAD_LEFT);

            $usuario = Usuarios::create([
                'usr_id'       => $uid,
                'usr_email'    => $googleUser->getEmail(),
                'usr_user'     => $googleUser->getNickname() ?? 'user'.$uid,
                'usr_password' => Hash::make(Str::random(16)),
                'usr_estado'   => 'Activo',
            ]);

            UsuariosPerfil::create([
                'usrp_id'       => $uid,
                'usr_id'        => $uid,
                'usrp_nombre'   => $googleUser->getName(),
                'usrp_apellido' => '',
            ]);
        }

        DB::commit();

        $token = $usuario->createToken('google-token')->plainTextToken;

        $frontend = env('FRONTEND_URL', 'http://localhost:5000');
        return Redirect::away("{$frontend}/auth/google/callback?token={$token}");
    }
}
