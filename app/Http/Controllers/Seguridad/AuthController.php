<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

use App\Models\Seguridad\Usuarios;
use App\Models\Seguridad\UsuariosPerfil;
use App\Models\Seguridad\AccessRequest;
use App\Models\Seguridad\RegistrationRequest;
use App\Mail\NewAccessRequest;
use App\Mail\AccessApproved;
use App\Mail\RequestReceived;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'usrp_nombre'   => 'required|string|max:50',
            'usrp_apellido' => 'required|string|max:50',
            'usr_email'     => 'required|email|max:100|unique:Usuarios,usr_email',
            'usr_user'      => 'required|string|max:30|unique:Usuarios,usr_user',
            'password'      => [
                'required','string','confirmed','min:8',
                'regex:/[A-Z]/','regex:/[0-9]/','regex:/[^A-Za-z0-9]/'
            ],
        ], [
            'password.regex' => 'La contraseña requiere mayúscula, número y carácter especial.'
        ]);

        $email    = $request->usr_email;
        $username = $request->usr_user;

        // 1) Si ya existe usuario activo
        if (Usuarios::where('usr_email', $email)->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Ya existe una cuenta con ese correo.',
            ], 409);
        }

        // 2) Si ya hay solicitud pendiente
        if (RegistrationRequest::where('email', $email)->where('approved', false)->exists()) {
            return response()->json([
                'status'  => true,
                'message' => 'Ya hemos recibido tu solicitud. Te notificaremos cuando sea aprobada.',
            ], 202);
        }

        // 3) Crear o actualizar la solicitud de registro
        $rr = RegistrationRequest::updateOrCreate(
            ['email' => $email],
            [
                'username' => $username,
                'message'  => "{$request->usrp_nombre} {$request->usrp_apellido}",
                'password' => Hash::make($request->password),
                'approved' => false,
            ]
        );

        // 4) Generar enlace firmado de aprobación (30 días)
        $approvalUrl = URL::temporarySignedRoute(
            'auth.approveRegister',
            now()->addDays(30),
            ['id' => $rr->id]
        );

        try {
            // 5) Notificar al administrador
            Mail::send(new NewAccessRequest(
                $rr->email,
                $rr->username,
                $rr->message,
                $approvalUrl
            ));

            // 6) Confirmación inmediata al usuario
            Mail::send(new RequestReceived($email, 'register'));

        } catch (\Exception $e) {
            \Log::error('Error enviando email de registro: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Error al enviar notificación por email. Contacta al administrador.',
                'error'   => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Solicitud registrada. Revisa tu correo para más detalles.',
        ], 202);
    }

    public function approveRegister(Request $request, $id)
    {
        // Verificar firma de URL
        if (! $request->hasValidSignature()) {
            \Log::error('❌ FIRMA INVÁLIDA para approveRegister ID: ' . $id);
            
            return view('auth.link_expired', [
                'message' => 'El enlace de aprobación ha expirado o es inválido.'
            ]);
        }

        $rr = RegistrationRequest::find($id);

        if (!$rr) {
            return view('auth.not_found', [
                'message' => 'Solicitud no encontrada.'
            ]);
        }

        if ($rr->approved) {
            return view('auth.access_already_approved', ['email' => $rr->email]);
        }

        $user = null;

        try {
            DB::transaction(function() use ($rr, &$user) {
                // 1) Marcar aprobado
                $rr->approved = true;
                $rr->save();

                // 2) Crear el usuario en la tabla Usuarios
                $last = Usuarios::max('usr_id');
                $num  = $last ? ((int)$last + 1) : 1;
                $len  = $last ? strlen($last) : 8;
                $uid  = str_pad($num, $len, '0', STR_PAD_LEFT);

                $user = Usuarios::create([
                    'usr_id'       => $uid,
                    'usr_email'    => $rr->email,
                    'usr_user'     => $rr->username,
                    'usr_password' => $rr->password,
                    'usr_estado'   => 'Activo',
                ]);

                UsuariosPerfil::create([
                    'usrp_id'       => $uid,
                    'usr_id'        => $uid,
                    'usrp_nombre'   => Str::before($rr->message ?? '', ' ') ?: $rr->username,
                    'usrp_apellido' => Str::after($rr->message ?? '', ' '),
                ]);
            });

            \Log::info('✅ Usuario creado exitosamente: ' . $rr->email);

            // Notificar al usuario aprobado
            Mail::send(new AccessApproved($rr->email));

        } catch (\Exception $e) {
            \Log::error('Error aprobando registro: ' . $e->getMessage());

            return view('auth.approval_error', [
                'message' => 'Error al aprobar la cuenta. Contacta al administrador.',
                'error' => $e->getMessage()
            ]);
        }

        return view('auth.access_granted', ['email' => $rr->email]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'usr_user' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = Usuarios::where('usr_user', $request->usr_user)->first();

        if (! $user || ! Hash::check($request->password, $user->usr_password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Usuario o contraseña incorrectos.',
            ], 401);
        }

        if ($user->usr_estado !== 'Activo') {
            return response()->json([
                'status'  => false,
                'message' => 'Usuario inactivo. Contacta al administrador.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Inicio de sesión exitoso.',
            'usuario' => [
                'usr_id'   => $user->usr_id,
                'usr_user' => $user->usr_user,
            ],
            'token'   => $token,
        ], 200);
    }

    /**
     * Inicia Google OAuth.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Maneja el callback de Google OAuth.
     */
    public function handleGoogleCallback()
    {
        try {
            $gUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Google OAuth: ' . $e->getMessage(),
            ], 500);
        }

        $email = $gUser->getEmail();
        return $this->handleOAuthCallback($email, 'google');
    }

    /**
     * Inicia Microsoft OAuth.
     */
    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')
            ->stateless()
            ->scopes(['User.Read'])
            ->redirect();
    }

    /**
     * Maneja el callback de Microsoft OAuth.
     */
    public function handleMicrosoftCallback()
    {
        try {
            $msUser = Socialite::driver('microsoft')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Microsoft OAuth: ' . $e->getMessage(),
            ], 500);
        }

        $email = $msUser->getEmail();
        return $this->handleOAuthCallback($email, 'microsoft');
    }

    /**
     * Maneja el callback común para OAuth (Google y Microsoft).
     */
    private function handleOAuthCallback($email, $provider)
    {
        // 1) Si ya existe en Usuarios → login inmediato
        if ($user = Usuarios::where('usr_email', $email)->first()) {
            if ($user->usr_estado !== 'Activo') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Usuario inactivo. Contacta al administrador.',
                ], 403);
            }
            Auth::login($user);
            $token    = $user->createToken('auth_token')->plainTextToken;
            $frontend = env('FRONTEND_URL');
            return Redirect::away("{$frontend}/auth/{$provider}/callback?token={$token}");
        }

        // 2) Si no existe → creamos o dejamos pendiente la AccessRequest
        $ar = AccessRequest::firstOrCreate(
            ['email' => $email],
            ['username' => explode('@', $email)[0], 'approved' => false]
        );

        // Notificar al admin y usuario si es nueva
        if ($ar->wasRecentlyCreated) {
            try {
                $approvalUrl = URL::temporarySignedRoute(
                    'auth.approve',
                    now()->addDays(30),
                    ['id' => $ar->id]
                );

                // Email al admin
                Mail::send(new NewAccessRequest(
                    $email,
                    $ar->username,
                    null,
                    $approvalUrl
                ));

                // Email al usuario
                Mail::send(new RequestReceived($email, 'oauth'));

            } catch (\Exception $e) {
                \Log::error("Error enviando email {$provider}: " . $e->getMessage());
            }
        }

        // Si no aprobado → mostrar vista de espera
        if (! $ar->approved) {
            return view('auth.waiting', [
                'email'   => $email,
                'message' => 'Tu cuenta está pendiente de aprobación. Revisa tu correo para más detalles.',
            ]);
        }

        // 3) Si ya aprobado → login
        $user = Usuarios::where('usr_email', $email)->firstOrFail();
        Auth::login($user);
        $token    = $user->createToken('auth_token')->plainTextToken;
        $frontend = env('FRONTEND_URL');
        return Redirect::away("{$frontend}/auth/{$provider}/callback?token={$token}");
    }

    /**
     * Aprueba una solicitud OAuth: crea el usuario y notifica al candidato.
     */
    public function approve(Request $request, $id)
    {
        // Verificar firma de URL
        if (! $request->hasValidSignature()) {
            \Log::error('❌ FIRMA INVÁLIDA para OAuth approve ID: ' . $id);
            
            return view('auth.link_expired', [
                'message' => 'El enlace de aprobación ha expirado o es inválido.'
            ]);
        }

        $ar = AccessRequest::find($id);

        if (!$ar) {
            return view('auth.not_found', [
                'message' => 'Solicitud no encontrada.'
            ]);
        }

        if ($ar->approved) {
            return view('auth.access_already_approved', ['email' => $ar->email]);
        }

        $user = null;

        try {
            DB::transaction(function() use ($ar, &$user) {
                // Marcar aprobado
                $ar->approved = true;
                $ar->save();

                // Generar nuevo usr_id
                $last = Usuarios::max('usr_id');
                $num  = $last ? ((int)$last + 1) : 1;
                $len  = $last ? strlen($last) : 8;
                $uid  = str_pad($num, $len, '0', STR_PAD_LEFT);

                // Crear el usuario definitivo
                $user = Usuarios::create([
                    'usr_id'       => $uid,
                    'usr_email'    => $ar->email,
                    'usr_user'     => $ar->username,
                    'usr_password' => Hash::make(Str::random(16)),
                    'usr_estado'   => 'Activo',
                ]);

                UsuariosPerfil::create([
                    'usrp_id'       => $uid,
                    'usr_id'        => $uid,
                    'usrp_nombre'   => Str::before($ar->message ?? '', ' ') ?: $ar->username,
                    'usrp_apellido' => Str::after($ar->message ?? '', ' '),
                ]);
            });

            \Log::info('✅ Usuario OAuth creado exitosamente: ' . $ar->email);

            // Notificar al usuario aprobado
            Mail::send(new AccessApproved($ar->email));

        } catch (\Exception $e) {
            \Log::error('Error aprobando OAuth: ' . $e->getMessage());

            return view('auth.approval_error', [
                'message' => 'Error al aprobar la cuenta. Contacta al administrador.',
                'error' => $e->getMessage()
            ]);
        }

        return view('auth.access_granted', ['email' => $ar->email]);
    }

    /**
     * Devuelve info del usuario autenticado.
     */
    public function getUserInfo(Request $request)
    {
        $user   = $request->user();
        $perfil = UsuariosPerfil::where('usrp_id', $user->usr_id)->first();

        return response()->json([
            'status'  => true,
            'usuario' => [
                'usr_id'    => $user->usr_id,
                'usr_user'  => $user->usr_user,
                'usr_email' => $user->usr_email,
                'perfil'    => $perfil,
            ],
        ]);
    }

    /**
     * Cierra sesión revocando el token actual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }
}
