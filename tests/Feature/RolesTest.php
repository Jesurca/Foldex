<?php

use App\Enums\Role;
use App\Models\Comentario;
use App\Models\Proyecto;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('middleware role:admin', function (): void {
    it('rechaza a visitantes con 401', function (): void {
        $this->getJson('/api/v1/admin/users')->assertUnauthorized();
    });

    it('rechaza a estudiantes con 403', function (): void {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para realizar esta acción.');
    });

    it('permite a un admin listar usuarios paginados', function (): void {
        Sanctum::actingAs(User::factory()->admin()->create());
        User::factory()->count(3)->create();

        $this->getJson('/api/v1/admin/users?role=estudiante')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    });
});

describe('gestión de admins', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
    });

    it('un admin crea otro admin con correo institucional', function (): void {
        $this->postJson('/api/v1/admin/users', [
            'name' => 'Nuevo Admin',
            'email' => 'Nuevo.Admin@UNAB.edu.co',
            'password' => 'Segura123*x',
        ])->assertCreated()->assertJsonPath('data.role', 'admin')->assertJsonPath('data.email', 'nuevo.admin@unab.edu.co');

        expect(User::where('email', 'nuevo.admin@unab.edu.co')->sole()->role)->toBe(Role::Admin);
    });

    it('no crea admins con correo externo', function (): void {
        $this->postJson('/api/v1/admin/users', [
            'name' => 'Externo',
            'email' => 'externo@hotmail.com',
            'password' => 'Segura123*x',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email' => 'Solo se permiten correos institucionales @unab.edu.co']);
    });

    it('elimina la cuenta de un estudiante con sus tokens y proyectos', function (): void {
        $estudiante = User::factory()->create();
        $estudiante->createToken('x');
        Proyecto::factory()->for($estudiante)->create();

        $this->deleteJson("/api/v1/admin/users/{$estudiante->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $estudiante->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('proyectos', 0);
    });

    it('no permite que un admin se elimine a sí mismo', function (): void {
        $this->deleteJson("/api/v1/admin/users/{$this->admin->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Un administrador no puede eliminar su propia cuenta.');
    });

    it('responde 404 en español para un usuario inexistente', function (): void {
        $this->deleteJson('/api/v1/admin/users/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'El recurso solicitado no existe.');
    });
});

describe('policies', function (): void {
    it('solo el dueño o un admin pueden editar/borrar un proyecto', function (): void {
        $dueno = User::factory()->create();
        $otro = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $proyecto = Proyecto::factory()->for($dueno)->create();

        expect($dueno->can('update', $proyecto))->toBeTrue()
            ->and($dueno->can('delete', $proyecto))->toBeTrue()
            ->and($otro->can('update', $proyecto))->toBeFalse()
            ->and($otro->can('delete', $proyecto))->toBeFalse()
            ->and($admin->can('delete', $proyecto))->toBeTrue();
    });

    it('solo el autor o un admin pueden borrar un comentario', function (): void {
        $comentario = Comentario::factory()->create();
        $otro = User::factory()->create();
        $admin = User::factory()->admin()->create();

        expect($comentario->user->can('delete', $comentario))->toBeTrue()
            ->and($otro->can('delete', $comentario))->toBeFalse()
            ->and($admin->can('delete', $comentario))->toBeTrue();
    });
});
