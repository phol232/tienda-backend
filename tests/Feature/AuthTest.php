<?php
// tests/Feature/AuthTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Seguridad\Usuarios;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_con_credenciales_válidas_devuelve_token_y_datos_usuario()
    {
        $usuario = Usuarios::factory()->create([
            'usr_email'    => 'usuario@test.com',
            'usr_password' => Hash::make('secreto'),
            'usr_estado'   => 'Activo',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'usuario@test.com',
            'password' => 'secreto',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'usuario' => ['usr_id','usr_user','usr_email'],
                'token',
            ])
            ->assertJson(['status' => true]);
    }

    /** @test */
    public function login_con_credenciales_invalidas_devuelve_401()
    {
        Usuarios::factory()->create([
            'usr_email'    => 'user@test.com',
            'usr_password' => Hash::make('correcto'),
            'usr_estado'   => 'Activo',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'user@test.com',
            'password' => 'equivocado',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => false]);
    }

    /** @test */
    public function login_con_usuario_inactivo_devuelve_403()
    {
        Usuarios::factory()->create([
            'usr_email'    => 'inactivo@test.com',
            'usr_password' => Hash::make('secreto'),
            'usr_estado'   => 'Inactivo',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'inactivo@test.com',
            'password' => 'secreto',
        ]);

        $response->assertStatus(403)
            ->assertJson(['status' => false]);
    }

    /** @test */
    public function login_con_campos_faltantes_devuelve_422()
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email','password']);
    }
}
