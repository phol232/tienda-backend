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
     * Redirige al login de Google.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Maneja el callback de Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Busca o crea usuario
            $usuario = Usuarios::firstOrCreate(
                ['usr_email' => $googleUser->getEmail()],
                [
                    'usr_id'       => Usuarios::max('usr_id')
                        ? str_pad(Usuarios::max('usr_id')+1, strlen(Usuarios::max('usr_id')), '0', STR_PAD_LEFT)
                        : '1',
                    'usr_user'     => explode('@', $googleUser->getEmail())[0],
                    'usr_password' => Hash::make(Str::random(16)),
                    'usr_estado'   => 'Activo',
                ]
            );

            // Asegura que el perfil SIEMPRE se cree o se actualice con el usr_id
            $perfil = UsuariosPerfil::firstOrNew(['usrp_id' => $usuario->usr_id]);
            $nameParts = explode(' ', $googleUser->getName() ?? '');
            $perfil->usrp_id       = $usuario->usr_id;
            $perfil->usrp_nombre   = $nameParts[0] ?? '';
            $perfil->usrp_apellido = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
            $perfil->usrp_imagen   = $googleUser->getAvatar();
            $perfil->save();

            $token = $usuario->createToken('auth_token')->plainTextToken;
            $frontend = env('FRONTEND_URL', 'http://localhost:5000');

            // Incluye usr_id y perfil completo como query params para el frontend (más seguro con JSON, pero así es rápido)
            return Redirect::away("{$frontend}/auth/google/callback?token={$token}");

        } catch (\Exception $e) {
            return response()->json(['status'=>false, 'message'=>'Error Google OAuth: '.$e->getMessage()], 500);
        }
    }

    /**
     * Redirige al login de Microsoft.
     */
    public function redirectToMicrosoft()
    {
        // Asegúrate de pedir el scope User.Read para poder leer la foto de perfil
        return Socialite::driver('microsoft')
            ->stateless()
            ->scopes(['User.Read'])
            ->redirect();
    }

    /**
     * Maneja el callback de Microsoft e intenta traer la foto de perfil.
     */
    public function handleMicrosoftCallback()
    {
        try {
            $msUser = Socialite::driver('microsoft')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Microsoft OAuth: '.$e->getMessage()
            ], 500);
        }

        // --- Obtener la foto de perfil de Microsoft, si existe
        $avatar = null;
        $accessToken = $msUser->token;
        $graphUrl = 'https://graph.microsoft.com/v1.0/me/photo/$value';

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($graphUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'image/jpg'
                ],
                'http_errors' => false // No lanzar excepción si no hay foto
            ]);

            if ($response->getStatusCode() === 200) {
                // La foto viene como binario, conviértelo a base64 para guardar o mostrar
                $avatar = 'data:image/jpeg;base64,' . base64_encode($response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            $avatar = null;
        }

        // Busca o crea usuario
        $usuario = Usuarios::firstOrCreate(
            ['usr_email' => $msUser->getEmail()],
            [
                'usr_id'       => Usuarios::max('usr_id')
                                    ? str_pad(Usuarios::max('usr_id')+1, strlen(Usuarios::max('usr_id')), '0', STR_PAD_LEFT)
                                    : '1',
                'usr_user'     => explode('@', $msUser->getEmail())[0],
                'usr_password' => Hash::make(Str::random(16)),
                'usr_estado'   => 'Activo',
            ]
        );

        // Actualiza o crea perfil
        $perfil = UsuariosPerfil::firstOrNew(['usrp_id' => $usuario->usr_id]);
        $nameParts = explode(' ', $msUser->getName() ?? '');
        $perfil->usrp_id       = $usuario->usr_id;
        $perfil->usrp_nombre   = $nameParts[0] ?? '';
        $perfil->usrp_apellido = count($nameParts) > 1
                                 ? implode(' ', array_slice($nameParts, 1))
                                 : '';
        $perfil->usrp_imagen   = $avatar; // <- Guarda la foto si existe
        $perfil->save();

        // Genera token e redirige al frontend
        $token    = $usuario->createToken('auth_token')->plainTextToken;
        $frontend = env('FRONTEND_URL', 'http://localhost:5000');

        return Redirect::away("{$frontend}/auth/microsoft/callback?token={$token}");
    }

    // Cuando recibes el token, usa el endpoint /api/user para obtener usuario+perfil con usr_id
    public function getUserInfo(Request $request)
    {
        $usuario = $request->user();
        $perfil  = UsuariosPerfil::where('usrp_id', $usuario->usr_id)->first();

        return response()->json([
            'status'  => true,
            'usuario' => [
                'usr_id'   => $usuario->usr_id,
                'usr_user' => $usuario->usr_user,
                'usr_email'=> $usuario->usr_email,
                'perfil'   => $perfil
            ]
        ]);
    }

    /**
     * Cierra la sesión (revoca token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([ 'status'=>true, 'message'=>'Sesión cerrada correctamente' ]);
    }
}
