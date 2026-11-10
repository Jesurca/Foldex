<?php

use App\Enums\Role;
use App\Models\User;

function datosRegistro(array $extra = []): array
{
    return array_merge([
        'name' => 'Ana Pérez',
        'email' => 'ana.perez@unab.edu.co',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
        'programa' => 'Ingeniería de Sistemas',
        'semestre' => 6,
    ], $extra);
}

describe('registro', function (): void {
    it('registra un estudiante con correo institucional y devuelve token', function (): void {
        $this->postJson('/api/v1/register', datosRegistro())
            ->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'programa', 'semestre', 'creado_en']])
            ->assertJsonPath('user.role', 'estudiante')
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'ana.perez@unab.edu.co', 'role' => 'estudiante']);
    });

    it('normaliza el correo a minúsculas y sin espacios', function (): void {
        $this->postJson('/api/v1/register', datosRegistro(['email' => '  Ana.Perez@UNAB.edu.co ']))
            ->assertCreated()
            ->assertJsonPath('user.email', 'ana.perez@unab.edu.co');
    });

    it('rechaza correos de otros dominios', function (string $email): void {
        $this->postJson('/api/v1/register', datosRegistro(['email' => $email]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Solo se permiten correos institucionales @unab.edu.co']);

        expect(User::count())->toBe(0);
    })->with([
        'gmail' => 'ana@gmail.com',
        'subdominio falso' => 'ana@unab.edu.co.fake.com',
        'sin arroba' => 'anaunab.edu.co',
        'dominio parecido' => 'ana@falsounab.edu.co',
    ]);

    it('ignora el intento de registrarse como admin', function (): void {
        $this->postJson('/api/v1/register', datosRegistro(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('user.role', 'estudiante');

        expect(User::sole()->role)->toBe(Role::Estudiante);
    });

    it('exige correo único y confirmación de contraseña', function (): void {
        User::factory()->create(['email' => 'ana.perez@unab.edu.co']);

        $this->postJson('/api/v1/register', datosRegistro(['password_confirmation' => 'otra123x']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('responde validaciones en español aunque falte el header Accept', function (): void {
        $this->post('/api/v1/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', fn (string $m) => str_contains($m, 'obligatorio'));
    });
});

describe('login', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create(['email' => 'juan@unab.edu.co', 'password' => 'secreto123']);
    });

    it('inicia sesión con correo institucional en cualquier capitalización', function (): void {
        $this->postJson('/api/v1/login', ['email' => ' Juan@UNAB.edu.co', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']])
            ->assertJsonPath('user.email', 'juan@unab.edu.co');
    });

    it('rechaza contraseña incorrecta con 422 y mensaje genérico', function (): void {
        $this->postJson('/api/v1/login', ['email' => 'juan@unab.edu.co', 'password' => 'mala'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Estas credenciales no coinciden con nuestros registros.']);
    });

    it('rechaza correos de otro dominio aunque existan en la BD', function (): void {
        User::factory()->create(['email' => 'colado@gmail.com', 'password' => 'secreto123']);

        $this->postJson('/api/v1/login', ['email' => 'colado@gmail.com', 'password' => 'secreto123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Solo se permiten correos institucionales @unab.edu.co']);
    });

    it('limita los intentos de login (throttle)', function (): void {
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/v1/login', ['email' => 'juan@unab.edu.co', 'password' => 'mala']);
        }

        $this->postJson('/api/v1/login', ['email' => 'juan@unab.edu.co', 'password' => 'mala'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Demasiadas solicitudes. Intenta de nuevo más tarde.');
    });
});

describe('sesión con token', function (): void {
    it('devuelve el usuario autenticado en /me', function (): void {
        $token = $this->postJson('/api/v1/register', datosRegistro())->json('token');

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'ana.perez@unab.edu.co');
    });

    it('responde 401 en español sin token', function (): void {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado. Inicia sesión y envía el token Bearer.');
    });

    it('revoca el token actual en logout', function (): void {
        $token = $this->postJson('/api/v1/register', datosRegistro())->json('token');

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        expect(User::sole()->tokens()->count())->toBe(0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    });

    it('crea, lista y revoca tokens personales', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('login')->plainTextToken;

        $nuevo = $this->withToken($token)->postJson('/api/v1/me/tokens', ['nombre' => 'MCP Claude'])
            ->assertCreated()
            ->assertJsonStructure(['token', 'aviso']);

        $this->withToken($token)->getJson('/api/v1/me/tokens')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.token');

        $idNuevo = (int) explode('|', $nuevo->json('token'))[0];
        $this->withToken($token)->deleteJson("/api/v1/me/tokens/{$idNuevo}")->assertNoContent();
        expect($user->tokens()->count())->toBe(1);
    });

    it('no permite revocar tokens de otro usuario', function (): void {
        $otro = User::factory()->create();
        $ajeno = $otro->createToken('x')->accessToken;
        $token = User::factory()->create()->createToken('login')->plainTextToken;

        $this->withToken($token)->deleteJson("/api/v1/me/tokens/{$ajeno->id}")->assertNotFound();
        expect($otro->tokens()->count())->toBe(1);
    });
});
