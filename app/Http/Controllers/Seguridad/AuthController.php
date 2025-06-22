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

        // 4) Generar enlace firmado de aprobación
        $approvalUrl = URL::temporarySignedRoute(
            'auth.approveRegister',
            now()->addDays(7),
            ['id' => $rr->id]
        );

        // 5) Notificar al administrador
        Mail::to(env('ADMIN_EMAIL'))
            ->send(new NewAccessRequest(
                $rr->email,
                $rr->username,
                $rr->message,
                $approvalUrl
            ));

        // 6) Confirmación al usuario
        Mail::raw(
            "Hola {$request->usrp_nombre},\n\n" .
            "Hemos recibido tu solicitud de registro. En breve te notificaremos cuando sea aprobada.",
            fn($msg) => $msg->to($email)
                ->subject('Solicitud de registro recibida')
        );

        return response()->json([
            'status'  => true,
            'message' => 'Solicitud registrada. Revisa tu correo para más detalles.',
        ], 202);
    }

    public function approveRegister(Request $request, $id)
    {
        $rr = RegistrationRequest::findOrFail($id);

        if ($rr->approved) {
            return view('auth.access_already_approved', ['email' => $rr->email]);
        }

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
                'usr_password' => $rr->password,   // reutilizamos el hash
                'usr_estado'   => 'Activo',
            ]);

            UsuariosPerfil::create([
                'usrp_id'       => $uid,
                'usr_id'        => $uid,
                'usrp_nombre'   => Str::before($rr->message ?? '', ' ') ?: $rr->username,
                'usrp_apellido' => Str::after($rr->message ?? '', ' '),
            ]);
        });

        Mail::to($rr->email)
            ->send(new AccessApproved($rr->email));

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
            return Redirect::away("{$frontend}/auth/google/callback?token={$token}");
        }

        // 2) Si no existe → creamos o dejamos pendiente la AccessRequest
        $ar = AccessRequest::firstOrCreate(
            ['email' => $email],
            ['username' => explode('@', $email)[0], 'approved' => false]
        );

        // Notificar al admin si es nueva
        if ($ar->wasRecentlyCreated) {
            $approvalUrl = URL::temporarySignedRoute(
                'auth.approve',
                now()->addDays(7),
                ['id' => $ar->id]
            );
            Mail::to(env('ADMIN_EMAIL'))
                ->send(new NewAccessRequest($email, $ar->username, null, $approvalUrl));
        }

        // Si no aprobado → mostrar vista de espera
        if (! $ar->approved) {
            return view('auth.waiting', [
                'email'   => $email,
                'message' => 'Tu cuenta está pendiente de aprobación. Revisa tu correo.',
            ]);
        }

        // 3) Si ya aprobado, el usuario fue creado al aprobar → login
        $user = Usuarios::where('usr_email', $email)->firstOrFail();
        Auth::login($user);
        $token    = $user->createToken('auth_token')->plainTextToken;
        $frontend = env('FRONTEND_URL');
        return Redirect::away("{$frontend}/auth/google/callback?token={$token}");
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
        // Reutiliza la lógica de Google
        return $this->handleGoogleCallback();
    }

    /**
     * Aprueba una solicitud: crea el usuario y notifica al candidato.
     */
    public function approve(Request $request, $id)
    {
        $ar = AccessRequest::findOrFail($id);

        if ($ar->approved) {
            return view('auth.access_already_approved', ['email' => $ar->email]);
        }

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

        // Notificar al usuario aprobado
        Mail::to($ar->email)
            ->send(new AccessApproved($ar->email));

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
